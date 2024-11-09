<?php
require_once('loader.inc.php');
require_once('session.inc.php');

$info = null;
$infoClass = null;
$sound = null;
$tickets = null;
$events = $db->getEvents();

try {
	if(!empty($_GET['event'])) {
		// delete reservation if requested
		if(!empty($_POST['delete'])
		&& !empty($_POST['id']) && is_array($_POST['id'])) {
			foreach($_POST['id'] as $id) {
				if(!$db->deleteTicket($id)) {
					throw Exception('Ticket konnte nicht gelöscht werden!');
				}
			}
			$info = 'Ticket(s) wurde(n) gelöscht.';
			$infoClass = 'green';
		}

		// load ticket list from db
		$tickets = $db->getTickets($_GET['event']);

		// check given code
		if(!empty($_POST['check'])) {
			$isCheckin = ($_POST['mode'] !== 'checkout');
			$found = false;
			$codeToCheck = strtoupper($_POST['check']);
			foreach($tickets as $ticket) {
				if($ticket['code'] === $codeToCheck) {
					$found = true;
					if($ticket['revoked']) {
						$info = 'Der Code '.$codeToCheck.' wurde storniert.';
						$infoClass = 'red';
						$sound = 'img/fail.mp3';
					} elseif($isCheckin) {
						if($ticket['checked_in']) {
							$info = 'Der Code '.$codeToCheck.' wurde bereits eingelöst!';
							$infoClass = 'yellow';
							$sound = 'img/gong.mp3';
						} else {
							$info = 'Der Code '.$codeToCheck.' ist gültig. Der Eintritt wurde vermerkt.';
							$infoClass = 'green';
							$db->updateTicketCheckIn($ticket['code']);
							$sound = 'img/success.mp3';
						}
					} else {
						if($ticket['checked_out']) {
							$info = 'Der Code '.$codeToCheck.' wurde bereits ausgecheckt!';
							$infoClass = 'yellow';
							$sound = 'img/gong.mp3';
						} else {
							$info = 'Der Code '.$codeToCheck.' ist gültig. Der Austritt wurde vermerkt.';
							$infoClass = 'green';
							$db->updateTicketCheckOut($ticket['code']);
							$sound = 'img/success.mp3';
						}
					}
					break;
				}
			}
			if(!$found) {
				$info = 'Der Code '.$codeToCheck.' konnte nicht gefunden werden! Möglicherweise gehört er zu einer anderen Veranstaltung.';
				$infoClass = 'red';
				$sound = 'img/fail.mp3';
			}
			// reload ticket list with updated 'checked_in' columns
			$tickets = $db->getTickets($_GET['event']);
		}
	}
} catch(Exception $e) {
	$info = $e->getMessage();
	$infoClass = 'red';
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php require_once('head.inc.php'); ?>
		<title>Checkin/Checkout</title>
		<script src='js/admin.js'></script>
		<link rel='stylesheet' href='css/admin.css'></link>
	</head>
	<body>
		<div id='container'>
			<?php if($sound) { ?>
				<iframe src='<?php echo $sound; ?>' allow='autoplay' style='display:none'></iframe>
			<?php } ?>

			<div id='splash' class='contentbox'>

				<?php foreach(['img/logo-custom.png','img/logo-custom.jpg'] as $file) if(file_exists($file)) { ?>
					<img id='logo' src='<?php echo $file; ?>'>
				<?php } ?>

				<h1>Checkin/Checkout</h1>

				<img class='contentbox-embleme' src='img/ticket.svg'>

				<div class='toggler'>
					<a class='' href='admin.php?view=general'>Texte</a>
					<a class='' href='admin.php?view=events'>Veranstaltungen</a>
					<a class='' href='admin.php?view=voucher'>Voucher</a>
					<a class='active' href='check.php'>Reservierungen</a>
				</div>

				<?php if($info) { ?>
					<div class='infobox <?php echo $infoClass; ?>'><?php echo htmlspecialchars($info); ?></div>
				<?php } ?>

				<form method='GET' class='flex' style='clear:both'>
					<select name='event'>
						<option selected disabled value=''>=== Bitte auswählen ===</option>
						<?php foreach($events as $key => $event) { ?>
							<option value='<?php echo htmlspecialchars($key, ENT_QUOTES); ?>' <?php if($key == ($_GET['event']??'')) echo 'selected'; ?>>
								<?php $soldOut = count($db->getTickets($key)) >= $event['max']; ?>
								<?php echo htmlspecialchars($event['title']??'???').($soldOut ? ' AUSVERKAUFT!' : ''); ?>
							</option>
						<?php } ?>
					</select>
					<button class='checkin'>Anzeigen</button>
				</form>

				<?php if($tickets !== null) {
					$checkedIn = 0; $checkedOut = 0; $revoked = 0;
					foreach($tickets as $ticket) {
						if($ticket['checked_in']) $checkedIn ++;
						if($ticket['checked_out']) $checkedOut ++;
						if($ticket['revoked']) $revoked ++;
					}
				?>
					<form method='POST' class='flex'>
						<input type='hidden' name='event' value='<?php echo htmlspecialchars($_GET['event']); ?>' />
						<input type='text' name='check' placeholder='QR-Code scannen oder Code eingeben' autofocus='true' required='true' />
						<label><input type='radio' name='mode' value='checkin' <?php if(($_POST['mode']??'checkin')=='checkin') echo 'checked'; ?>>Checkin</label>
						<label><input type='radio' name='mode' value='checkout' <?php if(($_POST['mode']??'checkin')=='checkout') echo 'checked'; ?>>Checkout</label>
						<button class='checkin'>Prüfen</button>
					</form>
					<br>
					<div class='legend'>
						<span><b><?php echo $events[$_GET['event']]['max']; ?></b> Kontingent</span>
						<span><b><?php echo count($tickets??[]); ?></b> Reservierungen</span>
						<span></span>
					</div>
					<div class='legend'>
						<span class='checkedin'><b><?php echo $checkedIn; ?></b> eingecheckt</span>
						<span class='checkedout'><b><?php echo $checkedOut; ?></b> ausgecheckt</span>
						<span><b><?php echo $revoked; ?></b> <span class='revoked'>storniert</span></span>
					</div>
					<br>
					<form method='POST' onsubmit='return confirm("Sind Sie sicher, dass Sie die ausgewählten Reservierungen löschen möchten?")'>
					<table id='tblTickets'>
						<thead>
							<tr>
								<th><input type='checkbox' onclick='toggleCheckboxesInContainer(tblTickets, this.checked)'></th>
								<th>Code</th>
								<th>E-Mail</th>
								<th>Voucher</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($tickets ?? [] as $ticket) {
								$tooltip = 'Reserviert: '.$ticket['created']."\n"
									.'Storniert: '.$ticket['revoked']."\n"
									.'Eingecheckt: '.$ticket['checked_in']."\n"
									.'Ausgecheckt: '.$ticket['checked_out'];
								$class = '';
								if($ticket['checked_in']) $class = 'checkedin';
								if($ticket['checked_out']) $class = 'checkedout';
								if($ticket['revoked']) $class = 'revoked';
							?>
							<tr class='<?php echo $class; ?>' title='<?php echo htmlspecialchars($tooltip, ENT_QUOTES); ?>'>
								<td><input type='checkbox' name='id[]' value='<?php echo htmlspecialchars($ticket['code'], ENT_QUOTES); ?>'></td>
								<td class='monospace'><?php echo htmlspecialchars($ticket['code']); ?></td>
								<td><?php echo htmlspecialchars($ticket['email']); ?></td>
								<td><?php echo htmlspecialchars($ticket['voucher_code']??''); ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<div class='actionbar'>
						<button name='delete' value='1'>Ausgewählte löschen</button>
					</div>
					</form>
				<?php } ?>

			</div>
		</div>
		<?php require('foot.inc.php'); ?>
	</body>
</html>
