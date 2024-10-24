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
	&& !empty($_POST['id'])) {
		$db->updateEvent($_POST['id'], $_POST['title'], $_POST['max'], $_POST['start_date'].' '.$_POST['start_time'], $_POST['end_date'].' '.$_POST['end_time'], $_POST['location'], $_POST['voucher_only'], $_POST['tickets_per_email']);
		$info = 'Veranstaltung wurde bearbeitet.';
		$infoClass = 'green';
	}

	// create event if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'event_create') {
		if(empty($_POST['id']) || empty(trim($_POST['id']))) throw new Exception('ID darf nicht leer sein');
		$db->insertEvent($_POST['id'], $_POST['title'], $_POST['max'], $_POST['start_date'].' '.$_POST['start_time'], $_POST['end_date'].' '.$_POST['end_time'], $_POST['location'], $_POST['voucher_only'], $_POST['tickets_per_email']);
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
		$db->updateVoucher($_POST['code'], empty($_POST['event_id']) ? null : $_POST['event_id'], $_POST['valid_amount']);
		$info = 'Voucher wurde bearbeitet.';
		$infoClass = 'green';
	}

	// create voucher if requested
	if(!empty($_POST['action']) && $_POST['action'] == 'voucher_create') {
		if(empty($_POST['code']) || empty(trim($_POST['code']))) throw new Exception('Code darf nicht leer sein');
		$db->insertVoucher($_POST['code'], empty($_POST['event_id']) ? null : $_POST['event_id'], $_POST['valid_amount']);
		$info = 'Voucher wurde angelegt.';
		$infoClass = 'green';
	}

	// generate and output QR image
	if(!empty($_POST['action']) && $_POST['action'] == 'voucher_qr') {
		generateVoucherQrImage(
			$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']),
			$_POST['code']??'',
		);
	}
} catch(Exception $e) {
	$info = $e->getMessage();
	$infoClass = 'red';
}

function generateVoucherQrImage($url, $code) {
	$urlWithCode = $url.'?voucher_code='.urlencode($code);
	$qrGenerator = new QRCode($urlWithCode);
	$qrImage = $qrGenerator->render_image();

	$origWidth = imagesx($qrImage);
	$width  = imagesx($qrImage) * 1.2;
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
	$fontSize = 12;
	$fontFile = 'font/arialbd.ttf';
	$codeText = 'Voucher-Code:'."\n".$code;
	list($left, $bottom, $right, , , $top) = imageftbbox($fontSize, 0, $fontFile, $codeText);
	$left_offset = ($right - $left) / 2;
	$top_offset = ($bottom - $top) / 2;
	$x = $width/2 - $left_offset;
	$y = $height/8*6.5 + $top_offset;
	imagefttext($finalImage, $fontSize, 0, $x, $y, $black, $fontFile, $codeText);

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

				<?php if($info) { ?>
					<div class='infobox <?php echo $infoClass; ?>'><?php echo htmlspecialchars($info); ?></div>
				<?php } ?>

				<div class='actionbar'>
					<a class='button' href='?view=general'>Texte</a>
					<a class='button' href='?view=events'>Veranstaltungen</a>
					<a class='button' href='?view=voucher'>Voucher</a>
					<a class='button' href='check.php'>Checkin/Checkout</a>
				</div>
				<br>

				<?php if(($_GET['view']??'') == 'general') { ?>
					<form method='POST'>
					<input type='hidden' name='action' value='text_update'>
					<h3>Website-Titel</h3>
					<input class='fullwidth' 'type='text' name='web-title' value='<?php echo htmlspecialchars($db->getSetting('web-title'), ENT_QUOTES); ?>'>
					<h3>Website-Text <small>(kann HTML beinhalten)</small></h3>
					<textarea class='fullwidth' rows='5' name='web-description'><?php echo htmlspecialchars($db->getSetting('web-description')); ?></textarea>
					<hr/>
					<h3>Einladungsmail-Betreff</h3>
					<input class='fullwidth' 'type='text' name='invitation-mail-subject' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-subject'), ENT_QUOTES); ?>'>
					<h3>Einladungsmail-Sendername</h3>
					<input class='fullwidth' 'type='text' name='invitation-mail-sender-name' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-sender-name'), ENT_QUOTES); ?>'>
					<h3>Einladungsmail-Senderadresse</h3>
					<input class='fullwidth' 'type='text' name='invitation-mail-sender-address' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-sender-address'), ENT_QUOTES); ?>'>
					<h3>Einladungsmail-Reply-To-Adresse</h3>
					<input class='fullwidth' 'type='text' name='invitation-mail-reply-to' value='<?php echo htmlspecialchars($db->getSetting('invitation-mail-reply-to'), ENT_QUOTES); ?>'>
					<h3>Einladungsmail-Text</h3>
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
						if(!empty($_POST['id']) && !empty($_POST['action']) && $_POST['action'] == 'event_show') {
							$selectedEvent = $events[$_POST['id']];
						} ?>
						<table id='tblInput'>
							<tr>
								<th>ID:</th>
								<td><input type='text' name='id' value='<?php echo htmlspecialchars($selectedEvent ? $selectedEvent['id'] : ''); ?>' <?php if($selectedEvent) echo 'readonly'; ?>></td>
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
								<td colspan='3'></td>
								<td>
									<?php if($selectedEvent) { ?>
										<button name='action' value='event_edit' class='primary'>Bearbeiten</button>
									<?php } else { ?>
										<button name='action' value='event_create' class='primary'>Erstellen</button>
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
								<th>Max.</th>
								<th>Voucher</th>
								<th>Tickets/E-Mail</th>
								<th>Aktion</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($events as $event) { ?>
							<tr>
								<td>
									<div><?php echo htmlspecialchars($event['title']); ?><span class='hint'>&nbsp;<?php echo htmlspecialchars($event['id']); ?></span></div>
									<div class='hint'>Beginn: <?php echo htmlspecialchars($event['start']); ?></div>
									<div class='hint'>Ende: <?php echo htmlspecialchars($event['end']); ?></div>
								</td>
								<td><?php echo htmlspecialchars($event['max']); ?></td>
								<td><?php echo htmlspecialchars($event['voucher_only'] ? 'JA' : 'NEIN'); ?></td>
								<td><?php echo htmlspecialchars($event['tickets_per_email']); ?></td>
								<td class='actions'>
									<form method='POST'>
										<input type='hidden' name='id' value='<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>'>
										<button name='action' value='event_show'><img src='img/edit.svg'></button>
										<button name='action' value='event_delete' onclick='return confirm("Durch das Löschen der Veranstaltung werden auch die zugehörigen Tickets gelöscht. Sind Sie sicher?")'><img src='img/delete.svg'></button>
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
						if(!empty($_POST['code']) && !empty($_POST['action']) && $_POST['action'] == 'voucher_show') {
							$selectedVoucher = $vouchers[$_POST['code']];
						} ?>
						<table id='tblInput'>
							<tr>
								<th>Code:</th>
								<td><input type='text' name='code' value='<?php echo htmlspecialchars($selectedVoucher ? $selectedVoucher['code'] : ''); ?>' <?php if($selectedVoucher) echo 'readonly'; ?>></td>
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
							</tr>
							<tr>
								<td colspan='3'></td>
								<td>
									<?php if($selectedVoucher) { ?>
										<button name='action' value='voucher_edit' class='primary'>Bearbeiten</button>
									<?php } else { ?>
										<button name='action' value='voucher_create' class='primary'>Erstellen</button>
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
								<th>Aktion</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($vouchers as $voucher) { ?>
							<tr>
								<td><?php echo htmlspecialchars($voucher['code']); ?></td>
								<td><?php echo htmlspecialchars($voucher['valid_amount']); ?></td>
								<td><?php echo htmlspecialchars($voucher['event_id'] ? $events[$voucher['event_id']]['title'] : ''); ?></td>
								<td class='actions'>
									<form method='POST'>
										<input type='hidden' name='code' value='<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>'>
										<button name='action' value='voucher_qr'><img src='img/qr.svg'></button>
										<button name='action' value='voucher_show'><img src='img/edit.svg'></button>
										<button name='action' value='voucher_delete' onclick='return confirm("Sind Sie sicher?")'><img src='img/delete.svg'></button>
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
