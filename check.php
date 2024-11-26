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
		$request = json_decode(file_get_contents('php://input'), true);
		if(!empty($request['check'])) {
			$isCheckin = ($request['mode'] !== 'checkout');
			$found = false;
			$codeToCheck = strtoupper($request['check']);
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
			// return JSON for AJAX request
			$table = getTicketsTableHtml($db->getTickets($_GET['event']));
			header('Content-Type: application/json');
			die(
				json_encode([
					'info'=>$info, 'infoClass'=>$infoClass, 'sound'=>$sound,
					'rows'=>$table['rows'], 'count'=>$table['count'], 'max'=>$events[$_GET['event']]['max'],
					'checked_in'=>$table['checked_in'], 'checked_out'=>$table['checked_out'], 'revoked'=>$table['revoked']
				])
			);
		}
	}
} catch(Exception $e) {
	$info = $e->getMessage();
	$infoClass = 'red';
}

function getTicketsTableHtml($tickets) {
	$count = 0; $checkedIn = 0; $checkedOut = 0; $revoked = 0;
	$rowsHtml = '';
	foreach($tickets as $ticket) {
		$tooltip = 'Reserviert: '.$ticket['created']."\n"
			.'Storniert: '.$ticket['revoked']."\n"
			.'Eingecheckt: '.$ticket['checked_in']."\n"
			.'Ausgecheckt: '.$ticket['checked_out'];
		$class = '';
		if($ticket['checked_in']) {$class = 'checkedin'; $checkedIn ++;}
		if($ticket['checked_out']) {$class = 'checkedout'; $checkedOut ++;}
		if($ticket['revoked']) {$class = 'revoked'; $revoked ++;} else {$count ++;}
		$rowsHtml .= '<tr class="'.$class.'" title="'.htmlspecialchars($tooltip, ENT_QUOTES).'">'
			. '	<td><input type="checkbox" name="id[]" value="'.htmlspecialchars($ticket['code'], ENT_QUOTES).'"></td>'
			. '	<td class="monospace">'.htmlspecialchars($ticket['code']).'</td>'
			. '	<td>'.htmlspecialchars($ticket['email']).'</td>'
			. '	<td>'.htmlspecialchars($ticket['voucher_code']??'').'</td>'
			. '</tr>';
	}
	return [
		'rows'=>$rowsHtml, 'count'=>$count,
		'checked_in'=>$checkedIn, 'checked_out'=>$checkedOut, 'revoked'=>$revoked,
	];
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php require_once('head.inc.php'); ?>
		<title>Checkin/Checkout</title>
		<script src='js/admin.js'></script>
		<link rel='stylesheet' href='css/admin.css'></link>
		<script src='js/html5-qrcode.min.js'></script>
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
					<select id='sltEvent' name='event'>
						<option selected disabled value=''>=== Bitte auswählen ===</option>
						<?php foreach($events as $key => $event) { ?>
							<option value='<?php echo htmlspecialchars($key, ENT_QUOTES); ?>' <?php if($key == ($_GET['event']??'')) echo 'selected'; ?>>
								<?php echo htmlspecialchars($event['title']??'???'); ?>
							</option>
						<?php } ?>
					</select>
					<button class='checkin'>Anzeigen</button>
				</form>

				<?php if($tickets !== null) { $table = getTicketsTableHtml($tickets); ?>
					<div class='flex'>
						<div class='inputwithbutton'>
							<input type='text' id='txtCheckCode' placeholder='QR-Code scannen oder Code eingeben' autofocus='true' required='true' />
							<button type='button' title='Kamera zum Scannen benutzen' onclick='startScanner()'><img src='img/qr-scanner.svg'></button>
						</div>
						<label><input type='radio' id='rdoCheckin' name='mode' value='checkin' checked='true'>Checkin</label>
						<label><input type='radio' id='rdoCheckout' name='mode' value='checkout'>Checkout</label>
						<button type='button' id='btnCheckCode' class='checkin' onclick='checkCode(sltEvent.value, txtCheckCode.value, rdoCheckout.checked?"checkout":"checkin")'>Prüfen</button>
					</div>
					<br>
					<div>
						<?php $max = $events[$_GET['event']]['max'];
						echo progressBar($table['count']*100/$max, 'prgReservations', null, 'fullwidth', '', $table['count'].'/'.$max.' Reservierungen');
						?>
					</div>
					<div class='legend'>
						<span class='checkedin'><b id='spnCheckedIn'><?php echo $table['checked_in']; ?></b> eingecheckt</span>
						<span class='checkedout'><b id='spnCheckedOut'><?php echo $table['checked_out']; ?></b> ausgecheckt</span>
						<span><b id='spnRevoked'><?php echo $table['revoked']; ?></b> <span class='revoked'>storniert</span></span>
					</div>
					<br>
					<form method='POST' onsubmit='return confirm("Sind Sie sicher, dass Sie die ausgewählten Reservierungen löschen möchten?")'>
					<div class='scroll-h'>
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
								<?php echo $table['rows']; ?>
							</tbody>
						</table>
					</div>
					<div class='actionbar'>
						<button name='delete' value='1'>Ausgewählte löschen</button>
					</div>
					</form>
				<?php } ?>

			</div>
			<?php require('foot.inc.php'); ?>
		</div>

		<div id='qrContainer'>
			<div id='qrScanner'></div>
			<button id='btnStopScan' onclick='stopScanner()'>Scanmodus beenden</button>
		</div>
		<div id='checkResult'></div>
	</body>
</html>
