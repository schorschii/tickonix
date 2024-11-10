<?php
require_once('loader.inc.php');
require_once('session.inc.php');

$info = null;
$infoClass = null;

try {
	// update texts if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'text_update') {
		$db->updateSetting('web-title', $_POST['web-title']);
		$db->updateSetting('web-description', $_POST['web-description']);
		$db->updateSetting('invitation-mail-reply-to', $_POST['invitation-mail-reply-to']);
		$db->updateSetting('invitation-mail-sender-address', $_POST['invitation-mail-sender-address']);
		$db->updateSetting('invitation-mail-sender-name', $_POST['invitation-mail-sender-name']);
		$db->updateSetting('invitation-mail-subject', $_POST['invitation-mail-subject']);
		$db->updateSetting('invitation-mail-body', $_POST['invitation-mail-body']);
		$info = 'Texte wurden gespeichert.';
		$infoClass = 'green';
	}

	// delete event if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'event_delete'
	&& !empty($_POST['id'])) {
		$db->deleteEvent($_POST['id']);
		$info = 'Veranstaltung wurde gelöscht.';
		$infoClass = 'green';
	}

	// edit event if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'event_edit'
	&& !empty($_POST['id_old'])) {
		if(empty($_POST['id']) || empty(trim($_POST['id'])))
			$_POST['id'] = randomString(6, 'abcdefghijklmnopqrstuvwxyz');
		$reservation_start = $_POST['reservation_start_date'].' '.$_POST['reservation_start_time'];
		$reservation_end = $_POST['reservation_end_date'].' '.$_POST['reservation_end_time'];
		$db->updateEvent(
			$_POST['id'], $_POST['title'], $_POST['max'],
			$_POST['start_date'].' '.$_POST['start_time'], $_POST['end_date'].' '.$_POST['end_time'],
			$_POST['location'], $_POST['voucher_only'], $_POST['tickets_per_email'],
			empty(trim($reservation_start)) ? null : $reservation_start,
			empty(trim($reservation_end)) ? null : $reservation_end,
			$_POST['id_old'],
		);
		$info = 'Veranstaltung wurde bearbeitet.';
		$infoClass = 'green';
	}

	// create event if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'event_create') {
		if(empty($_POST['id']) || empty(trim($_POST['id'])))
			$_POST['id'] = randomString(6, 'abcdefghijklmnopqrstuvwxyz');
		$reservation_start = $_POST['reservation_start_date'].' '.$_POST['reservation_start_time'];
		$reservation_end = $_POST['reservation_end_date'].' '.$_POST['reservation_end_time'];
		$db->insertEvent(
			$_POST['id'], $_POST['title'], $_POST['max'],
			$_POST['start_date'].' '.$_POST['start_time'], $_POST['end_date'].' '.$_POST['end_time'],
			$_POST['location'], $_POST['voucher_only'], $_POST['tickets_per_email'],
			empty(trim($reservation_start)) ? null : $reservation_start,
			empty(trim($reservation_end)) ? null : $reservation_end,
		);
		$info = 'Veranstaltung wurde angelegt.';
		$infoClass = 'green';
	}

	// delete voucher if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'voucher_delete') {
		$db->deleteVoucher($_POST['code']);
		$info = 'Voucher wurde gelöscht.';
		$infoClass = 'green';
	}

	// edit voucher if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'voucher_edit') {
		if(empty($_POST['code']) || empty(trim($_POST['code']))) {
			$_POST['code'] = randomString(5);
		}
		$db->updateVoucher($_POST['code'], empty($_POST['event_id']) ? null : $_POST['event_id'], $_POST['valid_amount'], $_POST['notes'], $_POST['code_old']);
		$info = 'Voucher wurde bearbeitet.';
		$infoClass = 'green';
	}

	// create voucher if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'voucher_create') {
		$amount = $_POST['voucher_amount'] ?? 1;
		for($i=0; $i<$amount; $i++) {
			$currentCode = $_POST['code'] ?? '';
			if(empty($_POST['code']) || empty(trim($_POST['code']))) {
				$currentCode = randomString(6);
			} elseif($amount > 1) {
				$currentCode .= randomString(4);
			}
			$db->insertVoucher($currentCode, empty($_POST['event_id']) ? null : $_POST['event_id'], $_POST['valid_amount'], $_POST['notes']);
			$info = 'Voucher wurde angelegt.';
			$infoClass = 'green';
		}
	}

	// generate and output QR image
	if(!empty($_GET['action']) && $_GET['action'] == 'voucher_qr') {
		generateVoucherQrImage(
			$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']),
			$_GET['code']??'',
		);
	}
} catch(Exception $e) {
	$info = $e->getMessage();
	$infoClass = 'red';
}

function generateVoucherQrImage($url, $code) {
	if(empty($code)) $urlWithCode = $url;
	else $urlWithCode = $url.'?voucher_code='.urlencode($code);

	$qrGenerator = new QRCode($urlWithCode);
	$qrImage = $qrGenerator->render_image();

	$origWidth = imagesx($qrImage);
	$width  = imagesx($qrImage) * 1.3;
	$origHeight = imagesy($qrImage);
	$height = $origHeight * 1.5;

	$finalImage = imagecreate($width, $height);
	$white = imagecolorallocate($finalImage, 255, 255, 255);
	$black = imagecolorallocate($finalImage, 0, 0, 0);
	imagefilledrectangle($finalImage, 0, 0, $width, $height, $white);

	imagecopy($finalImage, $qrImage, ($width/2)-($origWidth/2), ($height/2)-($origHeight/2), 0, 0, $origWidth, $origHeight);

	// top text
	$fontSize = 10;
	$fontFile = 'font/arial.ttf';
	list($left, $bottom, $right, , , $top) = imageftbbox($fontSize, 0, $fontFile, $url);
	$left_offset = ($right - $left) / 2;
	$top_offset = ($bottom - $top) / 2;
	$x = $width/2 - $left_offset;
	$y = $height/8 + $top_offset;
	imagefttext($finalImage, $fontSize, 0, $x, $y, $black, $fontFile, $url);
	// bottom text
	if(!empty($code)) {
		$fontSize = 12;
		$fontFile = 'font/arialbd.ttf';
		$codeText = 'Voucher-Code:'."\n".$code;
		list($left, $bottom, $right, , , $top) = imageftbbox($fontSize, 0, $fontFile, $codeText);
		$left_offset = ($right - $left) / 2;
		$top_offset = ($bottom - $top) / 2;
		$x = $width/2 - $left_offset;
		$y = $height/8*6.5 + $top_offset;
		imagefttext($finalImage, $fontSize, 0, $x, $y, $black, $fontFile, $codeText);
	}

	header('Content-type: image/png');
	imagepng($finalImage);
	die();
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php require_once('head.inc.php'); ?>
		<title>Administration | Tickonix</title>
		<script src='js/admin.js'></script>
		<link rel='stylesheet' href='css/admin.css'></link>
	</head>
	<body>
		<div id='container'>
			<div id='splash' class='contentbox'>

				<?php foreach(['img/logo-custom.png','img/logo-custom.jpg'] as $file) if(file_exists($file)) { ?>
					<img id='logo' src='<?php echo $file; ?>'>
				<?php } ?>

				<h1>Administration</h1>

				<img class='contentbox-embleme' src='img/ticket.svg'>

				<div class='toggler'>
					<a class='<?php if(($_GET['view']??'')=='general') echo 'active'; ?>' href='?view=general'>Texte</a>
					<a class='<?php if(($_GET['view']??'')=='events') echo 'active'; ?>' href='?view=events'>Veranstaltungen</a>
					<a class='<?php if(($_GET['view']??'')=='voucher') echo 'active'; ?>' href='?view=voucher'>Voucher</a>
					<a class='' href='check.php'>Reservierungen</a>
				</div>

				<?php if($info) { ?>
					<div class='infobox <?php echo $infoClass; ?>'><?php echo htmlspecialchars($info); ?></div>
				<?php } ?>

				<?php if(($_GET['view']??'') == 'general') { ?>
					<form method='POST'>
					<input type='hidden' name='action' value='text_update'>
					<h2>Website</h2>
					<h3>Titel</h3>
					<input class='fullwidth' type='text' name='web-title' value='<?php echo htmlspecialchars($db->getSetting('web-title'), ENT_QUOTES); ?>'>
					<h3>Text <small>(kann HTML beinhalten)</small></h3>
					<textarea class='fullwidth' rows='5' name='web-description'><?php echo htmlspecialchars($db->getSetting('web-description')); ?></textarea>
					<hr/>
					<h2>Einladungsmail</h2>
					<h3>Betreff</h3>
					<input class='fullwidth' type='text' name='invitation-mail-subject' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-subject'), ENT_QUOTES); ?>'>
					<h3>Sendername</h3>
					<input class='fullwidth' type='text' name='invitation-mail-sender-name' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-sender-name'), ENT_QUOTES); ?>'>
					<h3>Senderadresse</h3>
					<input class='fullwidth' type='email' name='invitation-mail-sender-address' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-sender-address'), ENT_QUOTES); ?>'>
					<h3>Reply-To-Adresse</h3>
					<input class='fullwidth' type='email' name='invitation-mail-reply-to' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-reply-to'), ENT_QUOTES); ?>'>
					<h3>Text <small>(kann HTML beinhalten)</small></h3>
					<textarea class='fullwidth' rows='5' name='invitation-mail-body'><?php echo htmlspecialchars($db->getSetting('invitation-mail-body')); ?></textarea>
					<small><table>
					<tr><td>$$TITLE$$</td><td>--&gt; Website-Titel</td></tr>
					<tr><td>$$EVENT$$</td><td>--&gt; Veranstaltungs-Titel</td></tr>
					<tr><td>$$START$$</td><td>--&gt; Startdatum und -Zeit</td></tr>
					<tr><td>$$END$$</td><td>--&gt; Enddatum und -Zeit</td></tr>
					<tr><td>$$LOCATION$$</td><td>--&gt; Veranstaltunsgort</td></tr>
					<tr><td>$$CODE$$</td><td>--&gt; zufallsgenerierter Code (QR-Code-Inhalt, für manuelle Eingabe)</td></tr>
					<tr><td>$$QRCODE$$</td><td>--&gt; HTML &lt;img&gt;-Element mit dem QR-Code</td></tr>
					<tr><td>$$REVOKELINK$$</td><td>--&gt; Link zur Ticketstornierung</td></tr>
					</table></small>
					<br><br>
					<button class='primary fullwidth'>Speichern</button>
					</form>
				<?php } ?>

				<?php if(($_GET['view']??'') == 'events') {
					$events = $db->getEvents();
				?>
					<form method='POST'>
						<?php
						$selectedEvent = null;
						if(!empty($_GET['id'])) {
							$selectedEvent = $events[$_GET['id']] ?? null;
						} ?>
						<table id='tblInput'>
							<tr>
								<th>Interne ID:</th>
								<td><input type='text' name='id' title='Leer lassen, um eine ID automatisch zu generieren' placeholder='(optional)' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['id'] : ''); ?>'></td>
								<th>Titel:</th>
								<td><input type='text' name='title' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['title'] : ''); ?>'></td>
							</tr>
							<tr>
								<th>Beginn:</th>
								<td class='multiinput'>
									<input type='date' name='start_date' value='<?php echo htmlspecialchars($selectedEvent ? explode(' ',$selectedEvent['start'])[0] : ''); ?>'>
									<input type='time' name='start_time' value='<?php echo htmlspecialchars($selectedEvent ? explode(' ',$selectedEvent['start'])[1] : ''); ?>'>
								</td>
								<th>Ende:</th>
								<td class='multiinput'>
									<input type='date' name='end_date' value='<?php echo htmlspecialchars($selectedEvent ? explode(' ',$selectedEvent['end'])[0] : ''); ?>'>
									<input type='time' name='end_time' value='<?php echo htmlspecialchars($selectedEvent ? explode(' ',$selectedEvent['end'])[1] : ''); ?>'>
								</td>
							</tr>
							<tr>
								<th>Ort:</th>
								<td><input type='text' name='location' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['location'] : ''); ?>'></td>
								<th>Tickets/E-Mail:</th>
								<td><input type='number' name='tickets_per_email' min='1' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['tickets_per_email'] : '1'); ?>'></td>
							</tr>
							<tr>
								<th>Max:</th>
								<td><input type='number' name='max' min='1' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['max'] : '1'); ?>'></td>
								<th></th>
								<td>
									<input type='hidden' name='voucher_only' value='0'>
									<label><input type='checkbox' name='voucher_only' value='1' <?php if($selectedEvent && $selectedEvent['voucher_only']) echo 'checked'; ?>>Nur mit Voucher</label>
								</td>
							</tr>
							<tr>
								<th>Reserv.-Beginn:</th>
								<td class='multiinput'>
									<input type='date' name='reservation_start_date' value='<?php echo htmlspecialchars($selectedEvent ? (explode(' ',$selectedEvent['reservation_start'])[0]??'') : ''); ?>'>
									<input type='time' name='reservation_start_time' value='<?php echo htmlspecialchars($selectedEvent ? (explode(' ',$selectedEvent['reservation_start'])[1]??'') : ''); ?>'>
								</td>
								<th>Reserv.-Ende:</th>
								<td class='multiinput'>
									<input type='date' name='reservation_end_date' value='<?php echo htmlspecialchars($selectedEvent ? (explode(' ',$selectedEvent['reservation_end'])[0]??'') : ''); ?>'>
									<input type='time' name='reservation_end_time' value='<?php echo htmlspecialchars($selectedEvent ? (explode(' ',$selectedEvent['reservation_end'])[1]??'') : ''); ?>'>
								</td>
							</tr>
							<tr>
								<td colspan='3'>
									<button type='button' style='width:auto;padding:6px' onclick='window.open("admin.php?action=voucher_qr", "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");'><img src='img/qr.svg'>&nbsp;QR-Code zur Reservierungsseite</button>
								</td>
								<td>
									<?php if($selectedEvent) { ?>
										<input type='hidden' name='id_old' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['id'] : ''); ?>'>
										<button name='action' value='event_edit' class='primary'>Änderungen speichern</button>
									<?php } else { ?>
										<button name='action' value='event_create' class='primary'>Veranstaltung erstellen</button>
									<?php } ?>
								</td>
							</tr>
						</table>
					</form>
					<hr/>
					<table id='tblEvents'>
						<thead>
							<tr>
								<th>Titel</th>
								<th>Reservierungen</th>
								<th>Tickets/E-Mail</th>
								<th class='actions'>Aktion</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($events as $event) { ?>
							<tr>
								<td>
									<div><?php echo htmlspecialchars($event['title']); ?></div>
									<div class='voucheronly'><?php echo $event['voucher_only'] ? 'nur mit Voucher' : ''; ?></div>
									<div class='hint'>Beginn: <?php echo htmlspecialchars($event['start']); ?></div>
									<div class='hint'>Ende: <?php echo htmlspecialchars($event['end']); ?></div>
									<div class='hint'>Reserv.-Beginn: <?php echo htmlspecialchars($event['reservation_start']??'-'); ?></div>
									<div class='hint'>Reserv.-Ende: <?php echo htmlspecialchars($event['reservation_end']??'-'); ?></div>
								</td>
								<td><a href='check.php?event=<?php echo urlencode($event['id']); ?>'><?php echo htmlspecialchars(count($db->getValidTickets($event['id'])).'/'.$event['max']); ?></a></td>
								<td><?php echo htmlspecialchars($event['tickets_per_email']); ?></td>
								<td class='actions'>
									<form method='GET'>
										<input type='hidden' name='id' value='<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>'>
										<button name='view' value='events'><img src='img/edit.svg'></button>
									</form>
									<form method='POST'>
										<input type='hidden' name='id' value='<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>'>
										<button name='action' value='event_delete' onclick='return confirm("Durch das Löschen der Veranstaltung werden auch die zugehörigen Tickets gelöscht. Sind Sie sicher, dass Sie die Veranstaltung löschen möchten?")'><img src='img/delete.svg'></button>
									</form>
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

				<?php if(($_GET['view']??'') == 'voucher') {
					$events = $db->getEvents();
					$vouchers = $db->getVouchers();
				?>
					<form method='POST'>
						<?php
						$selectedVoucher = null;
						if(!empty($_GET['code'])) {
							$selectedVoucher = $vouchers[$_GET['code']] ?? null;
						} ?>
						<table id='tblInput'>
							<tr>
								<th>Code:</th>
								<td><input type='text' name='code' title='Leer lassen, um zufälligen Code zu generieren' placeholder='(optional)' value='<?php echo htmlspecialchars($selectedVoucher ? $selectedVoucher['code'] : ''); ?>'></td>
								<th>Anzahl Einlösungen:</th>
								<td><input type='number' name='valid_amount' min='1' value='<?php echo htmlspecialchars($selectedVoucher ? $selectedVoucher['valid_amount'] : '1'); ?>'></td>
							</tr>
							<tr>
								<th>Veranstaltung:</th>
								<td>
									<select name='event_id'>
										<option value=''>GÜLTIG FÜR ALLE</option>
										<?php foreach($events as $event) { ?>
											<option value='<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>' <?php if($selectedVoucher && $selectedVoucher['event_id']===$event['id']) echo 'selected'; ?>><?php echo htmlspecialchars($event['title']); ?></option>
										<?php } ?>
									</select>
								</td>
								<th>Notiz:</th>
								<td><input type='text' name='notes' value='<?php echo htmlspecialchars($selectedVoucher ? $selectedVoucher['notes'] : ''); ?>'></td>
							</tr>
							<tr>
								<?php if($selectedVoucher) { ?>
									<td colspan='3'></td>
								<?php } else { ?>
									<th>Anzahl Voucher:</th>
									<td><input type='number' name='voucher_amount' min='1' value='1'></td>
									<td></td>
								<?php } ?>
								<td>
									<?php if($selectedVoucher) { ?>
										<input type='hidden' name='code_old' value='<?php echo htmlspecialchars($selectedVoucher ? $selectedVoucher['code'] : ''); ?>'>
										<button name='action' value='voucher_edit' class='primary'>Änderungen speichern</button>
									<?php } else { ?>
										<button name='action' value='voucher_create' class='primary'>Voucher erstellen</button>
									<?php } ?>
								</td>
							</tr>
						</table>
					</form>
					<hr/>
					<table id='tblEvents'>
						<thead>
							<tr>
								<th>Code</th>
								<th>Anzahl</th>
								<th>Veranstaltung</th>
								<th class='actions'>Aktion</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($vouchers as $voucher) { ?>
							<tr>
								<td>
									<div class='monospace'><?php echo htmlspecialchars($voucher['code']); ?></div>
									<div class='hint'><?php echo htmlspecialchars($voucher['notes']); ?></div>
								</td>
								<td><?php echo htmlspecialchars(count($db->getTicketsByVoucherCode($voucher['code'])).'/'.$voucher['valid_amount']); ?></td>
								<td><?php echo htmlspecialchars($voucher['event_id'] ? $events[$voucher['event_id']]['title'] : '(alle)'); ?></td>
								<td class='actions'>
									<form method='GET'>
										<input type='hidden' name='code' value='<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>'>
										<button name='view' value='voucher'><img src='img/edit.svg'></button>
									</form>
									<button onclick='window.open("admin.php?action=voucher_qr&code=<?php echo urlencode($voucher['code']); ?>", "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");'><img src='img/qr.svg'></button>
									<form method='POST'>
										<input type='hidden' name='code' value='<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>'>
										<button name='action' value='voucher_delete' onclick='return confirm("Sind Sie sicher, dass Sie den Voucher löschen möchten?")'><img src='img/delete.svg'></button>
									</form>
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

			</div>
		</div>
		<?php require('foot.inc.php'); ?>
	</body>
</html>
