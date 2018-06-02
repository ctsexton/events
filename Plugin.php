<?php namespace CamSexton\Events;

use System\Classes\PluginBase;
use Db;
use Google_Client;
use Google_Service_Drive;

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
		$calendar = $calArray[0];

		// get API key from environment
		$API_KEY = config("api.GCAL_API_KEY");

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
			$this->downloadImages($json);
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
			$this->downloadImages($json);
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

	// If an array has a field, return the contents of that field, otherwise return an alternate value (defaults to "")
	protected function checkField($array, $field, $emptyVal = "") {
		if (isset($array[$field])) {
			return $array[$field];
		}
		else {
			return $emptyVal;
		}
	 }

	// copy events in json into database
	protected function loadEvents($json) {
		// iterate through json, inserting or replacing entries

		$timezone_default = $this->checkField($json, 'timeZone', 'UTC');
		$items = $this->checkField($json, 'items');
		if ($items == "") {
			return;
		}

		foreach ($items as $number => $item) {

			if ($item['status'] === 'cancelled') {
				Db::delete('DELETE FROM camsexton_events_entries WHERE event_id = :event_id', ['event_id' => $item['id']]);
				file_put_contents('php://stderr', print_r("DELETED: " . $item['id'] . "\n", TRUE));
			} elseif ($item['status'] === 'confirmed') {
				$event_id = $this->checkField($item, 'id');
				$title = $this->checkField($item, 'summary');
				file_put_contents('php://stderr', print_r("TITLE: " . $title . "\n", TRUE));
				$start = $this->checkField($item, 'start');
				$date_time = $this->checkField($start, 'dateTime');
				$event_timezone = $this->checkField($start, 'timeZone', $timezone_default);
				$venue = $this->checkField($item, 'location');
				$description = htmlspecialchars(nl2br($this->checkField($item, 'description')));
				$attachments = $this->checkField($item, 'attachments');
				if ($attachments != "") {
					$file_id = $attachments[0]['fileId'];
					file_put_contents('php://stderr', print_r("File ID: " . $file_id ."\n", TRUE));
				} else {
					$file_id = "";
				}
				$created_at = $this->checkField($item, 'created');
				$updated_at = $this->checkField($item, 'updated');
				
				Db::statement('INSERT OR REPLACE INTO camsexton_events_entries (id, event_id, title, date_time, event_timezone, venue, description, file_id, created_at, updated_at) VALUES (
					(SELECT id FROM camsexton_events_entries WHERE event_id = :event_id),
					:event_id,
					:title,
					:date_time,
					:event_timezone,
					:venue,
					:description,
					:file_id,
					:created_at,
					:updated_at)', [
						'event_id' => $event_id,
						'title' => $title,
						'date_time' => $date_time,
						'event_timezone' => $event_timezone,
						'venue' => $venue,
						'description' => $description,
						'file_id' => $file_id,
						'created_at' => $created_at,
						'updated_at' => $updated_at
					]);
			}
		}
	}

	// for all image attachment links in json, download from google drive
	protected function downloadImages($json) {

		// Check that key file exists
		if (!file_exists('storage/client_secret.json')) {
			file_put_contents('php://stderr', print_r("NO CLIENT_SECRET FILE\n", TRUE));
			return;
		}

		// Check that there are items in the array
		// TO DO: check attachments at this point as well before getClient
		$items = $this->checkField($json, 'items');
		if ($items == "") {
			return;
		}

		// Google Authentication
		$client = $this->getClient();

		// Start google drive service
		$service = new Google_Service_Drive($client);

		// iterate through items and download files, write files.
		foreach ($items as $number => $item) {
			$attachments = $this->checkField($item, 'attachments');
			if ($attachments != "") {
				$file_id = $attachments[0]['fileId'];

				// GET REQUEST
				try {
					$content = $service->files->get($file_id, array("alt" => "media"));
				} catch (\Google_Service_Exception $e) {
					file_put_contents('php://stderr', print_r("ERROR FOR FILE ID: " . $file_id . "\n", TRUE));

					$msg = $e->getMessage();
					file_put_contents('php://stderr', print_r("ERROR MESSAGE:\n\n" . $msg . "\n", TRUE));
					continue;
				}

				// Open file handle for output.
				$outHandle = fopen("storage/app/media/" . $file_id . ".jpeg", "w+");

				// Until we have reached the EOF, read 1024 bytes at a time and write to the output file handle.
				while (!$content->getBody()->eof()) {
						fwrite($outHandle, $content->getBody()->read(1024));
				}

				// Close output file handle.
				fclose($outHandle);

				file_put_contents('php://stderr', print_r("FILE DOWNLOADED: storage/app/media/" . $file_id . ".jpeg\n", TRUE));
			}
		}
	}

	// create new authenticated google drive client and return it
	protected function getClient() {

		// Check that key file exists
		if (!file_exists('storage/client_secret.json')) {
			file_put_contents('php://stderr', print_r("NO CLIENT_SECRET FILE\n", TRUE));
			return;
		}

		$client = new Google_Client();
		$client->setAuthConfig('storage/client_secret.json');
		$client->setAccessType("offline");        // offline access
		$client->setIncludeGrantedScopes(true);   // incremental auth
		$client->addScope(Google_Service_Drive::DRIVE_READONLY);
		$authUrl = $client->createAuthUrl();

		$credentialsPath = 'storage/credentials.json';
		if (file_exists($credentialsPath)) {
			$accessToken = json_decode(file_get_contents($credentialsPath), true);
		} else {
			// Request authorization from the user.
			$authUrl = $client->createAuthUrl();
			printf("Open the following link in your browser:\n%s\n", $authUrl);
			print 'Enter verification code: ';
			$authCode = trim(fgets(STDIN));
			// Exchange authorization code for an access token.
			$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

			// Store the credentials to disk.
			if (!file_exists(dirname($credentialsPath))) {
				mkdir(dirname($credentialsPath), 0700, true);
			}
			file_put_contents($credentialsPath, json_encode($accessToken));
			printf("Credentials saved to %s\n", $credentialsPath);
		}
		$client->setAccessToken($accessToken);

		// Refresh the token if it's expired.
		if ($client->isAccessTokenExpired()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
		}
		return $client;
	}
}
