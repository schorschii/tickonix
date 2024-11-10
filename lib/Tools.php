<?php

function randomString($length=30, $alphabet='23456789ABCDEFGHKMNPQRSTUVWX') {
	// 1, l, 0, O excluded by default because of possible confusion
	// Y and Z excluded by default to avoid problems with EN/DE keyboard layout
	$charactersLength = strlen($alphabet);
	$randomString = '';
	for($i = 0; $i < $length; $i++) {
		$randomString .= $alphabet[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
