<?php namespace CamSexton\Events;

use System\Classes\PluginBase;
use Db;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
		return [
			'CamSexton\Events\Components\UpcomingEvents' => 'upcomingevents',
			'CamSexton\Events\Components\PastEvents' => 'pastevents',
		];
    }

    public function registerSettings()
    {
    }

	public function registerSchedule($schedule)
	{
		$schedule->call(function() { 
			return $this->refresh(); 
		})->everyMinute();
	}

	protected function refresh() {
		// get calendar id & sync token from DB
		$calArray = Db::select('SELECT calendar_id, next_sync_token FROM camsexton_events_calendar LIMIT 1');

		if ($calArray == FALSE) {
			file_put_contents('php://stderr', print_r("NO CALENDAR GIVEN\n", TRUE));
			return;
		}

		// get API key from environment
		$API_KEY = config("api.GCAL_API_KEY");
		$calendar = $calArray[0];

		// if calendar id is falsey then do NOT proceed
		if ($calendar->calendar_id == FALSE) {
			file_put_contents('php://stderr', print_r("NO CALENDAR ID GIVEN\n", TRUE));
			return;
		}

		// if sync token exists, make normal update request
		if ($calendar->next_sync_token != FALSE) {
			// request new records from google or cancel refresh on fail
			$json = $this->requestNew($calendar->calendar_id, $calendar->next_sync_token, $API_KEY);
			if ($json == NULL) {
				return;
			}

			// update calendar table
			$this->updateCalTable($calendar->calendar_id, $json);

			// add/delete events from entries table
			$this->loadEvents($json);
		} else {
			// if sync token is null, then delete all records, full refresh
			// request all records from google or cancel refresh on fail
			$json = $this->requestAll($calendar->calendar_id, $API_KEY);
			if ($json == NULL) {
				return;
			}

			// update calendar table
			$this->updateCalTable($calendar->calendar_id, $json);

			// Delete events from entries table
			Db::delete('DELETE FROM camsexton_events_entries');

			// Fill entries table with events
			file_put_contents('php://stderr', print_r("ABOUT TO LOAD ALL NEW EVENTS\n", TRUE));
			$this->loadEvents($json);
		}

	}

	protected function requestAll($calendar_id, $API_KEY) {
		//
		$URL = 'https://www.googleapis.com/calendar/v3/calendars/' 
			. urlencode($calendar_id)
			. '/events?key='
			. $API_KEY;

		return $this->makeRequest($URL);
	}

	protected function requestNew($calendar_id, $next_sync_token, $API_KEY) {
		//
		$URL = 'https://www.googleapis.com/calendar/v3/calendars/' 
			. urlencode($calendar_id)
			. '/events?syncToken='
			. urlencode($next_sync_token)
			. '&key='
			. $API_KEY;

		return $this->makeRequest($URL);
	}

	// Make the GET request and return json or 1
	protected function makeRequest($URL) {
		$data = @file_get_contents($URL);
		if ($data === false) {
			file_put_contents('php://stderr', print_r("JSON DATA FALSE\n", TRUE));
			return NULL;
		}
		$json = json_decode($data, true);

		// json is an associative array

		if (isset($json['error'])) {
			file_put_contents('php://stderr', print_r("JSON error\n", TRUE));
			return NULL;
		}
		else {
			file_put_contents('php://stderr', print_r("Successfully got JSON\n", TRUE));
			return $json;
		}
	}

	// Update calendar table
	protected function updateCalTable ($calendar_id, $json) {
			Db::update('UPDATE camsexton_events_calendar SET 
				calendar_name = :calendar_name,
				timezone = :timezone,
				last_updated = :last_updated,
				next_sync_token = :next_sync_token
				WHERE calendar_id = :calendar_id', [
					'calendar_name' => $json['summary'],
					'timezone' => $json['timeZone'],
					'last_updated' => $json['updated'],
					'next_sync_token' => $json['nextSyncToken'],
					'calendar_id' => $calendar_id
				]);
	}

	protected function loadEvents($json) {
		// iterate through json, inserting or replacing entries

		$timezone_default = $this->checkField($json, 'timeZone', 'UTC');
		$items = $this->checkField($json, 'items');

		foreach ($items as $number => $item) {

			if ($item['status'] === 'cancelled') {
				Db::delete('DELETE FROM camsexton_events_entries WHERE event_id = :event_id', ['event_id' => $item['id']]);
				file_put_contents('php://stderr', print_r("DELETED: " . $item['id'] . "\n", TRUE));
			} elseif ($item['status'] === 'confirmed') {
				$event_id = $this->checkField($item, 'id');
				$title = $this->checkField($item, 'summary');
				$start = $this->checkField($item, 'start');
				$date_time = $this->checkField($start, 'dateTime');
				$event_timezone = $this->checkField($start, 'timeZone', $timezone_default);
				$venue = $this->checkField($item, 'location');
				$description = $this->checkField($item, 'description');
				$created_at = $this->checkField($item, 'created');
				$updated_at = $this->checkField($item, 'updated');
				
				file_put_contents('php://stderr', print_r("TITLE: " . $title . "\n", TRUE));
				Db::statement('INSERT OR REPLACE INTO camsexton_events_entries (id, event_id, title, date_time, event_timezone, venue, description, created_at, updated_at) VALUES (
					(SELECT id FROM camsexton_events_entries WHERE event_id = :event_id),
					:event_id,
					:title,
					:date_time,
					:event_timezone,
					:venue,
					:description,
					:created_at,
					:updated_at)', [
						'event_id' => $event_id,
						'title' => $title,
						'date_time' => $date_time,
						'event_timezone' => $event_timezone,
						'venue' => $venue,
						'description' => $description,
						'created_at' => $created_at,
						'updated_at' => $updated_at
					]);
			}
		}
	}

	protected function checkField($array, $field, $emptyVal = "") {
		if (isset($array[$field])) {
			return $array[$field];
		}
		else {
			return $emptyVal;
		}
	 }
}
