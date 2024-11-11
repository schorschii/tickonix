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

function shortenDateRange($start, $end) {
	$startTime = empty($start) ? null : strtotime($start);
	$endTime = empty($end) ? null : strtotime($end);
	if($startTime && $endTime
	&& date('Y', $startTime) === date('Y', $endTime)
	&& date('m', $startTime) === date('m', $endTime)
	&& date('d', $startTime) === date('d', $endTime)) {
		return date('Y-m-d H:i', $startTime).' - '.date('H:i', $endTime);
	} else {
		return date('Y-m-d H:i', $startTime).' - '.date('Y-m-d H:i', $endTime);
	}
}

function progressBar($percent, $cid=null, $tid=null, $class=''/*hidden big stretch animated*/, $style='', $text=null) {
	$percent = intval($percent);
	return
		'<span class="progressbar-container '.$class.'" style="--progress:'.$percent.'%; '.$style.'" '.($cid==null ? '' : 'id="'.htmlspecialchars($cid).'"').'>'
			.'<span class="progressbar"><span class="progress"></span></span>'
			.'<span class="progresstext" '.($tid==null ? '' : 'id="'.htmlspecialchars($tid).'"').'>'.(
				$text ? htmlspecialchars($text) : (strpos($class,'animated')!==false ? LANG('in_progress') : $percent.'%')
			).'</span>'
		.'</span>';
}
