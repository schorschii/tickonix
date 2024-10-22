<?php
require_once('loader.inc.php');
session_start();

$info = null;
$infoclass = null;

// execute login if requested
if(isset($_POST['username']) && isset($_POST['password'])) {
	if(!empty(ADMIN_USERNAME) && !empty(ADMIN_PASSWORD)
	&& $_POST['username'] === ADMIN_USERNAME
	&& $_POST['password'] === ADMIN_PASSWORD) {
		$_SESSION['tickonix_login'] = ADMIN_USERNAME;
		$_SESSION['tickonix_installation'] = dirname(__FILE__);
		header('Location: admin.php');
		die();
	} else {
		sleep(2);
		$info = 'Anmeldung fehlgeschlagen';
		$infoclass = 'red';
	}
}
// logout if requested
elseif(isset($_GET['logout'])) {
	if(isset($_SESSION['tickonix_login'])) {
		session_destroy();
		$info = 'Abmeldung erfolgreich';
		$infoclass = 'green';
	}
}
// check if already logged in
elseif(isset($_SESSION['tickonix_login'])) {
	header('Location: admin.php');
	die();
}
?>

<!DOCTYPE html>
<html>
<head>
	<?php require_once('head.inc.php'); ?>
	<title>Anmeldung | Tickonix</title>
</head>
<body>
	<div id='container'>
		<div id='splash'>
			<?php if($info != null) { ?>
				<div class='infobox <?php echo $infoclass; ?>'><?php echo $info; ?></div>
			<?php } ?>

			<p>
				Sie sind im Begriff sich am Ticket-Adminbereich anzumelden. Diese Seite ist nur für Mitglieder des OrgTeams vorgesehen.
			</p>

			<form method='POST' class='login-flex'>
				<input type='text' id='txtUsername' name='username' placeholder='Benutzername' class='flex-fill' required='true' autofocus='true'>
				<input type='password' id='txtPassword' name='password' placeholder='Passwort' class='flex-fill' required='true'>
				<button id='btnSubmit'>Anmelden</button>
			</form>
		</div>
	</div>
</body>
</html>
