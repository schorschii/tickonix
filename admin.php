<?php
require_once('loader.inc.php');
require_once('session.inc.php');

$info = null;
$infoClass = null;
$sound = null;
$tickets = null;

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
			$found = false;
			$codeToCheck = strtoupper($_POST['check']);
			foreach($tickets as $ticket) {
				if($ticket['code'] === $codeToCheck) {
					$found = true;
					if($ticket['checked_in']) {
						$info = 'Der Code '.$codeToCheck.' wurde bereits eingelöst!';
						$infoClass = 'yellow';
						$sound = 'img/gong.mp3';
					} else {
						$info = 'Der Code '.$codeToCheck.' ist gültig. Der Eintritt wurde vermerkt.';
						$infoClass = 'green';
						$db->setCheckIn($ticket['id'], 1);
						$sound = 'img/success.mp3';
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
		<title><?php echo TITLE; ?> | Tickets</title>
		<script src='js/admin.js'></script>
		<style>
			form.flex {
				display: flex;
				justify-content: space-between;
				align-items: center;
				gap: 10px;
			}
			form.flex input, form.flex select {
				flex-grow: 1;
			}
			#tblTickets {
				width: 100%;
			}
			#tblTickets tbody tr:hover th,
			#tblTickets tbody tr:hover td {
				background-color: rgba(255,225,0,0.4);
			}
			#tblTickets tr th:first-child,
			#tblTickets tr td:first-child {
				text-align: center;
				width: 1%;
			}
		</style>
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

				<h1><?php echo TITLE; ?></h1>

				<img class='contentbox-embleme' src='img/ticket.svg'>

				<?php if($info) { ?>
					<div class='infobox <?php echo $infoClass; ?>'><?php echo htmlspecialchars($info); ?></div>
				<?php } ?>

				<form method='GET' class='flex' style='clear:both'>
					<select name='event'>
						<option selected disabled value=''>=== Bitte auswählen ===</option>
						<?php foreach(EVENTS as $key => $event) { ?>
							<option value='<?php echo htmlspecialchars($key, ENT_QUOTES); ?>' <?php if($key == ($_GET['event']??'')) echo 'selected'; ?>>
								<?php $soldOut = count($db->getTickets($key)) >= $event['max']; ?>
								<?php echo htmlspecialchars($event['title']??'???').($soldOut ? ' AUSVERKAUFT!' : ''); ?>
							</option>
						<?php } ?>
					</select>
					<button>Anzeigen</button>
				</form>

				<?php if($tickets !== null) {
					$checkedIn = 0;
					foreach($tickets as $ticket) {
						if($ticket['checked_in']) $checkedIn ++;
					}
				?>
					<form method='POST' class='flex'>
						<input type='hidden' name='event' value='<?php echo htmlspecialchars($_GET['event']); ?>' />
						<input type='text' name='check' placeholder='QR-Code scannen oder Code eingeben' autofocus='true' required='true' />
						<button>Prüfen</button>
					</form>
					<br>
					<table>
						<tr><td>Kontingent:</td><th><?php echo EVENTS[$_GET['event']]['max']; ?></th></tr>
						<tr><td>Reservierungen:</td><th><?php echo count($tickets??[]); ?></th></tr>
						<tr><td>Einlass gewährt:</td><th><?php echo $checkedIn; ?></th></tr>
					</table>
					<br>
					<form method='POST' onsubmit='return confirm("Sind Sie sicher?")'>
					<table id='tblTickets'>
						<thead>
							<tr>
								<th><input type='checkbox' onclick='toggleCheckboxesInContainer(tblTickets, this.checked)'></th>
								<th>Code</th>
								<th>E-Mail</th>
								<th>Einlass</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($tickets ?? [] as $ticket) { ?>
							<tr>
								<td><input type='checkbox' name='id[]' value='<?php echo htmlspecialchars($ticket['id']); ?>'></td>
								<td class='monospace'><?php echo htmlspecialchars($ticket['code']); ?></td>
								<td><?php echo htmlspecialchars($ticket['email']); ?></td>
								<td><?php if($ticket['checked_in']) { ?><img src='img/login.svg'><?php } ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<div>
						<button name='delete' value='1'>Markierte löschen</button>
					</div>
					</form>
				<?php } ?>

			</div>
		</div>
		<?php require('foot.inc.php'); ?>
	</body>
</html>
