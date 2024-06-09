<?php
function randomString($length=30) {
	// exclude 1, l, 0, O because of possible confusion
	// exclude Y and Z to avoid problems with EN/DE keyboard layout
	$characters = '23456789ABCDEFGHKMNPQRSTUVWX';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
