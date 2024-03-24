<?php
function randomString($length = 30) {
	$characters = '23456789ABCDEFGHKMNPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
