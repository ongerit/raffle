<?php
require_once(__DIR__ . '/global.php');

$user = User::require_login();

if (!isset($_GET['group_id'])) {
	header('Location: ' . $raffle_env['ROOT_ABSOLUTE_URL_PATH']);
	exit;
}

$group_id = $_GET['group_id'];

$creds = $user->getUserCredentials('meetup');
$meetup_info = $creds->getUserInfo();
$meetup_id = $meetup_info['id'];

$page = 0; // requesting first page
$keep_going = true;

$events = array();
while($keep_going) {
	$result = $creds->makeOAuthRequest(
		'http://api.meetup.com/2/events?status=upcoming&order=time&group_id='.$group_id,
		'GET'
	);
	if ($result['code'] == 200) {
		$group_data = json_decode(utf8_encode($result['body']), true);

		foreach ($group_data['results'] as $group) {
			$events[] = array(
				'name' => $group['name'],
				'id' => $group['id'],
				'yes_rsvp_count' => $group['yes_rsvp_count']
			);
		}

		// keep going while next meta parameter is set
		$keep_going = $group_data['meta']['next'] !== '';

		if ($keep_going) {	
			$page++;
		}
	} else {
		$keep_going = false;
	}
}
?>
<html>
<head>
	<title><?php echo $appName ?></title>
	<?php StartupAPI::head(); ?>
	<link rel="stylesheet" type="text/css" href="meetup.css"/>
</head>
<body>
<div style="float: right"><?php StartupAPI::power_strip(); ?></div>
<h1>Group: <?php echo $group_id ?></h1>

<h3>Events:</h3>
<ul class="events">
<?php
foreach ($events as $event) {
	?><li>
		<a href="event.php?event_id=<?php echo $event['id'] ?>&group_id=<?php echo $group_id ?>"><?php echo $event['name'] ?></a> (<?php echo $event['yes_rsvp_count'] ?> RSVPs)<br/>
	</li><?php
}
?>
</ul>

</body>
</html>
