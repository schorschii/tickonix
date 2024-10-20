<?php
require_once('loader.inc.php');
session_start();

$info = null;
$infoClass = null;
$showForm = true;

if(!empty($_POST['captcha'])
&& !empty($_POST['event'])
&& !empty($_POST['email'])) {
	try {
		// check if requested event exists - never trust user input!
		if(!array_key_exists($_POST['event'], EVENTS)) {
			throw new Exception('Die angeforderte Veranstaltung existiert nicht.');
		}

		// check captcha
		if(!isset($_SESSION['captcha_text']) || $_POST['captcha'] !== $_SESSION['captcha_text']) {
			throw new Exception('Das eingegebene Captcha ist nicht korrekt. Bitte erneut versuchen.');
		}
		// invalidate captcha - can only be used once
		unset($_SESSION['captcha_text']);

		// check if there are still places free
		$tickets = $db->getTickets($_POST['event']);
		if(count($tickets) >= EVENTS[$_POST['event']]['max']) {
			throw new Exception('Die angeforderte Veranstaltung ist leider bereits ausgebucht.');
		}

		// check email syntax
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Ihre eingegebene E-Mailadresse ist ungültig.');
		}
		// if duplicate email addresses are not allowed, check if email address is already registered
		if(!ALLOW_DUPLICATE_EMAILS && $db->getTicketByEmailAndEvent($_POST['email'], $_POST['event'])) {
			throw new Exception('Die eingegebene E-Mailadresse wurde bereits verwendet.');
		}

		// generate an unique token
		do {
			$token = randomString(8);
		} while($db->getTicketByCode($token));

		// send mail to requester
		$mailer = new InvitationMailer();
		$mailer->send(
			INVITATION_MAIL_SUBJECT, INVITATION_MAIL_TEMPLATE,
			TITLE, EVENTS[$_POST['event']], $token,
			$_POST['email'], INVITATION_MAIL_SENDER_NAME, INVITATION_MAIL_SENDER_MAIL, INVITATION_MAIL_REPLY_TO
		);

		// save reservation into db
		$db->insertTicket($_POST['event'], $_POST['email'], $token);

		$showForm = false;
		$info = 'Reservierung erfolgreich. Die E-Mail mit dem Eintritts-Code ist unterwegs!';
		$infoClass = 'green';
	} catch(Exception $e) {
		$info = $e->getMessage();
		$infoClass = 'red';
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php require_once('head.inc.php'); ?>
		<title><?php echo TITLE; ?> | Tickets</title>
	</head>
	<body>
		<div id='container'>
			<div id='splash' class='contentbox'>

				<?php foreach(['img/logo-custom.png','img/logo-custom.jpg'] as $file) if(file_exists($file)) { ?>
					<img id='logo' src='<?php echo $file; ?>'>
				<?php } ?>

				<h1><?php echo TITLE; ?></h1>
				<?php echo DESCRIPTION; ?>

				<img class='contentbox-embleme' src='img/ticket.svg'>

				<?php if($info) { ?>
					<div class='infobox <?php echo $infoClass; ?>'><?php echo htmlspecialchars($info); ?></div>
				<?php } ?>

				<?php if($showForm) { ?>
				<form method='POST' class='reservation'>
					<table>
						<tr>
							<td><label>Veranstaltung:</label></td>
							<td>
								<select name='event' required='true' autofocus='true'>
									<option selected disabled value=''>=== Bitte auswählen ===</option>
									<?php foreach(EVENTS as $key => $event) { ?>
										<?php
											$addText = ''; $soldOut = false;
											$reservedCount = count($db->getTickets($key));
											if($reservedCount >= $event['max']) {
												$addText = 'AUSVERKAUFT!';
												$soldOut = true;
											} else {
												$addText = '('.($event['max']-$reservedCount).' Plätze verfügbar)';
											}
										?>
										<option value='<?php echo htmlspecialchars($key, ENT_QUOTES); ?>' <?php if($soldOut) echo 'disabled'; ?>>
											<?php echo htmlspecialchars($event['title']??'???').' '.$addText; ?>
										</option>
									<?php } ?>
								</select>
							</td>
						</tr>
						<tr>
							<td><label>E-Mail-Adresse:</label></td>
							<td><input type='email' name='email' required='true' autofocus='true' value='<?php echo htmlspecialchars($_POST['email']??'', ENT_QUOTES); ?>'></td>
						</tr>
						<tr>
							<td><label>Captcha:</label></td>
							<td>
								<div style='display:flex; justify-content:space-between; flex-wrap:wrap'>
									<input type='text' name='captcha' required='true'>
									<img src='captcha.php'>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								<label class='flex'>
									<input type='checkbox' name='agree' value='1' required='true'>
									<div>Ich stimme zu, dass diese Daten bis zum Zeitraum der Veranstaltung gespeichert werden.</div>
								</label>
							</td>
						</tr>
					</table>
					<button>Verbindlich reservieren</button>
				</form>
				<?php } else { ?>
					<form method='GET'>
						<button>Neue Reservierung</button>
					</form>
				<?php } ?>

			</div>
		</div>
		<?php require('foot.inc.php'); ?>
	</body>
</html>
