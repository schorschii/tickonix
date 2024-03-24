<?php

session_start();

if(!isset($_SESSION['tickonix_login'])) {
	redirectToLogin();
}
if(!isset($_SESSION['tickonix_installation']) || $_SESSION['tickonix_installation'] != dirname(__FILE__)) {
	error_log('auth error: mp installation not matching '.dirname(__FILE__));
	redirectToLogin();
}

function redirectToLogin() {
	header('Location: login.php');
	die();
}
