<?php

class ICalendar {

	/* generates ICalendar (.ics) file contents */

	static function generate(
		string $title,
		int $start, int $end,
		string $organizerName=null, string $organizerMail=null,
		string $location=null,
		int $alarm=null
	) {
		return 'BEGIN:VCALENDAR' ."\n"
		.'VERSION:2.0' ."\n"
		.'PRODID:-//sieber.systems/Tickonix//EN' ."\n"
		.'METHOD:REQUEST' ."\n"
		.'BEGIN:VEVENT' ."\n"
		.($organizerName ? ('ORGANIZER;CN="'.$organizerName.'"'.($organizerMail ? ':MAILTO:'.$organizerMail : '')) : '') ."\n"
		.($location ? 'LOCATION:'.$location : '') ."\n"
		.'SUMMARY:'.$title ."\n"
		.'CLASS:PUBLIC' ."\n"
		.'DTSTART;TZID=Europe/Berlin:'.date('Ymd\THis', $start) ."\n"
		.'DTEND;TZID=Europe/Berlin:'.date('Ymd\THis', $end) ."\n"
		.'BEGIN:VALARM' ."\n"
		.'TRIGGER:-PT'.$alarm.'M' ."\n"
		.'ACTION:DISPLAY' ."\n"
		.'DESCRIPTION:Reminder' ."\n"
		.'END:VALARM' ."\n"
		.'END:VEVENT' ."\n"
		.'END:VCALENDAR' ."\n";
	}

}
