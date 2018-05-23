<?php namespace CamSexton\Events\Components;

use Cms\Classes\ComponentBase;
use Db;
use DateTime;
use DateTimeZone;

class UpcomingEvents extends ComponentBase

{
	public function componentDetails() {
		return [
			'name' => 'Upcoming Events',
			'description' => 'Display upcoming events from database'
		];
	}

	public function onRun() {
		$this->queryEvents();
	}

	public $events;

	protected function queryEvents() {
		$this->events = Db::select("SELECT 
			title, 
			venue,
			date_time,
			event_timezone,
			description,
			file_id
			FROM camsexton_events_entries
			WHERE date_time > datetime('now')
			ORDER BY date_time ASC
			");

		if ($this->events == null) {
			$noEvents = array(
			"title" => "No Upcoming Events",
			"venue" => "",
			"format_date" => "",
			"description" => "Please check back later!");
			$this->events = [$noEvents];
			return;
		}

		foreach ($this->events as $item) {
			// Change date timezone from UTC to event timezone
			$date = $item->date_time;
			$format_date = new DateTime($date);
			$tz = new DateTimeZone($item->event_timezone);
			$format_date->setTimezone($tz);
			$item->format_date = $format_date->format('l j F Y \a\t g:ia');
			$item->description = htmlspecialchars_decode($item->description);
			if ($item->file_id != "") {
				$file_id = $item->file_id;
				$item->photo_url = 'https://drive.google.com/uc?id=' . $file_id;
			} else {
				$item->photo_url = "";
			}
		}
	}
}
