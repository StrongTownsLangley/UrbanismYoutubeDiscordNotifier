<?php
// Notifier //
// Coded by James Hansen for Strong Towns Langley //
// To make run every 5 mins on Linux:
// Run on the terminal: crontab -e
// Add this line to crontab: */5 * * * * /usr/bin/php /path/to/your/notifier.php
// Save the file and exit the editor. For nano, press Ctrl+X, then Y, then Enter.

// Settings
global $reminder_webhook;
$reminder_webhook = "https://discord.com/api/webhooks/a/b"; // Reminder Bot Webhook for Discord
$youtube_webhook = "https://discord.com/api/webhooks/a/b"; // Youtube Bot Webhook for Discord
date_default_timezone_set('America/Los_Angeles');
$next_meeting_text_file = "next_meeting_date_and_time.txt";

// Discord
function sendDiscordMessage($webhookUrl,$message)
{
	// Create payload
	$data = json_encode([
		"content" => $message
	]);

	// Set headers
	$headers = [
		"Content-Type: application/json",
	];

	// Initialize cURL session
	$curl = curl_init($webhookUrl);

	// Set cURL options
	curl_setopt_array($curl, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false, // You may need to remove this line if you're on a production server
	]);

	// Execute cURL request
	$response = curl_exec($curl);

	// Check for errors
	if ($response === false) {
		die("Error sending message: " . curl_error($curl));
	}

	// Close cURL session
	curl_close($curl);

	// Output response
	echo "Message sent successfully!";
}

$urbanist_youtube_channels = array(
	array("prefix" => "c/", "channel" => "StrongtownsOrg", "name" => "Strong Towns", "hook" => $youtube_webhook),
	array("prefix" => "c/", "channel" => "notjustbikes", "name" => "Not Just Bikes", "hook" => $youtube_webhook),
	array("prefix" => "c/", "channel" => "AboutHere", "name" => "About Here", "hook" => $youtube_webhook),
	array("prefix" => "c/", "channel" => "OhTheUrbanity", "name" => "Oh The Urbanity!", "hook" => $youtube_webhook),
	array("prefix" => "c/", "channel" => "CityBeautiful", "name" => "City Beautiful", "hook" => $youtube_webhook),
	array("prefix" => "c/", "channel" => "CityNerd", "name" => "City Nerd", "hook" => $youtube_webhook),
	array("prefix" => "c/", "channel" => "RMTransit", "name" => "RM Transit", "hook" => $youtube_webhook),
	array("prefix" => "@", "channel" => "humanecities", "name" => "Humane Cities", "hook" => $youtube_webhook),	
	array("prefix" => "@", "channel" => "nicthedoor", "name" => "Nic Laporte", "hook" => $youtube_webhook),	
	array("prefix" => "@", "channel" => "TheGreaterDiscussions", "name" => "The Greater Discussions", "hook" => $youtube_webhook),	
	array("prefix" => "@", "channel" => "the_aesthetic_city", "name" => "The Aesthetic City", "hook" => $youtube_webhook),	
	array("prefix" => "playlist?list=", "channel" => "PLhycIWhOLttikNg2Z1aJvGPoqijpGs9qh", "name" => "The Armchair Urbanist (Alan Fisher)", "hook" => $youtube_webhook)
);

if(file_exists($next_meeting_text_file)) // DO NOT CONTINUE IF FILE DOES NOT EXIST
{
	// Next Meeting Notify

	// Function to send notification
	function sendNotification($message, $meetingDateFormatted, $link) {
		global $reminder_webhook;
		// Code to send notification (e.g., email, SMS, push notification)
		sendDiscordMessage($reminder_webhook, $message . " " . $link);
	}

	// Function to check and send notifications for meetings
	$meetingDateTime_str = file_get_contents($next_meeting_text_file);
	$today = new DateTime();
	$meetingDateTime = new DateTime($meetingDateTime_str);
	
	// Calculate the intervals for notification
	$interval1 = new DateInterval('P1W'); // 1 week before
	$interval2 = new DateInterval('P1D'); // 1 day before
	$interval3 = new DateInterval('PT0S'); // When it starts (no delay)

	// Calculate notification dates
	$notifyDate1 = clone $meetingDateTime;
	$notifyDate1->sub($interval1);

	$notifyDate2 = clone $meetingDateTime;
	$notifyDate2->sub($interval2);

	$notifyDate3 = clone $meetingDateTime;

	// Load notifications sent for this meeting
	$file = __DIR__ . '/notifications/' . $meetingDate . '.txt';
	$notificationsSent = file_exists($file) ? unserialize(file_get_contents($file)) : [];

	// Check if notifications need to be sent
	if ($today >= $notifyDate1 && !in_array('1W', $notificationsSent)) {
		sendNotification("Reminder: Meeting in 1 week.", $meetingDateFormatted , "https://meetinglink/");
		$notificationsSent[] = '1W';
	}
	if ($today >= $notifyDate2 && !in_array('1D', $notificationsSent)) {
		sendNotification("Reminder: Meeting tomorrow.", $meetingDateFormatted, "https://meetinglink/");
		$notificationsSent[] = '1D';
	}
	if ($today >= $notifyDate3 && !in_array('NOW', $notificationsSent)) {
		sendNotification("Reminder: Meeting starting now.", $meetingDateFormatted, "https://meetinglink/");
		$notificationsSent[] = 'NOW';
	}

	// Save notifications sent for this meeting
	file_put_contents($file, serialize($notificationsSent));
}

// Get Latest Video and Compare To Last
foreach($urbanist_youtube_channels as $chan)
{
	echo "Fetching Latest Video from '" . $chan["channel"] . "'...\n";
	$channel = "https://www.youtube.com/" . $chan["prefix"] . $chan["channel"];
	$command = __DIR__ ."/yt-dlp_linux --get-id --skip-download --playlist-end 1 " . $channel;
	$output=null;
	$retval=null;
	exec($command, $output, $retval);
	$video_code = $output[0];
	if (empty(trim($video_code))) {
		continue;
	}
	$last_value_file = __DIR__ . "/videos/" . $chan["channel"] . ".txt";
	$last_value = "";
	if(file_exists($last_value_file))
	{
		$last_value = file_get_contents($last_value_file);
	}
	if($last_value != $video_code)
	{
		$video_link = "https://www.youtube.com/watch?v=" . $video_code;
		echo "New Video From '" .  $chan["name"]. "': " . $video_link . "\n";	
		file_put_contents($last_value_file, $video_code);
		sendDiscordMessage($chan["hook"], "New Video From '" . $chan["name"]. "': " . $video_link);		
	}
}


?>
