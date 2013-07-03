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

// default value in case we didn't get the result
$group_name = "Group: $group_id";

try {
	while ($keep_going) {
		$result = $creds->makeOAuthRequest(
				'http://api.meetup.com/2/events?status=upcoming&order=time&group_id=' . $group_id, 'GET'
		);
		if ($result['code'] == 200) {
			$data = json_decode(utf8_encode($result['body']), true);

			foreach ($data['results'] as $result) {
				$group_name = $result['group']['name'];

				$events[] = array(
					'name' => $result['name'],
					'id' => $result['id'],
					'yes_rsvp_count' => $result['yes_rsvp_count'],
					'time' => $result['time'] / 1000
				);
			}

			// keep going while next meta parameter is set
			$keep_going = $data['meta']['next'] !== '';

			if ($keep_going) {
				$page++;
			}
		} else {
			$keep_going = false;
		}
	}
} catch (OAuthException2 $ex) {
	// silently ignoring all API call problems
}

$_TITLE = $group_name;
$_CURRENT_PAGE = 'raffle';

require_once($project_env['ROOT_FILESYSTEM_PATH'] . '/header.php');
?>
<h1><?php echo $group_name ?></h1>

<h3>Events:</h3>
<ul class="events">
	<?php
	foreach ($events as $event) {
		?><li>
			<a href="event.php?event_id=<?php echo UserTools::escape($event['id'])
		?>&group_id=<?php echo UserTools::escape($group_id)
		?>&group_name=<?php echo UserTools::escape($group_name)
		?>"><?php echo UserTools::escape($event['name']) ?></a>
			on <?php echo UserTools::escape(date('M j, Y', $event['time'])) ?> (<?php echo UserTools::escape($event['yes_rsvp_count']) ?> RSVPs)
		</li><?php
	   }
	?>
</ul>
<?php
require_once($project_env['ROOT_FILESYSTEM_PATH'] . '/footer.php');