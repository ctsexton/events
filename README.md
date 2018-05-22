# Events Component for OctoberCMS
## Author: Cam Sexton
##### A component for OctoberCMS written in PHP. Syncs the website database with a public Google Calendar.

See my code in ./Plugin.php and ./components/

To install:
1. Clone this folder to your October project under /plugins/camsexton/
2. In project root, run: 
```
php artisan migrate:up
```
3. Create the file /config/api.php in your project and paste the following:
```
<?php

return [
		'GCAL_API_KEY' => env('GCAL_API_KEY'),
];
```
4. Go to developer.google.com and request an API key for Google Calendar. In your project root folder, add it to your .env file as GCAL_API_KEY={YOUR_API_KEY}

5. Go to your backend and under CalendarSettings tab, input the Google Calendar ID into the Calendar ID field.
6. Make sure you have added the following command in your crontab.
```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```
7. If you don't want to wait for the cron job to execute, you can perform the initial sync immediately by running:
```
php artisan schedule:run
```

