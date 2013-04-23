<?php
require_once(__DIR__ . '/global.php');

$user = User::require_login();

if (!isset($_GET['group_id'])) {
	header('Location: ' . $project_env['ROOT_ABSOLUTE_URL_PATH']);
	exit;
}
$group_id = $_GET['group_id'];

if (!isset($_GET['event_id'])) {
	header('Location: ' . $project_env['ROOT_ABSOLUTE_URL_PATH'] . '/events.php?group_id=' . $group_id);
	exit;
}

$event_id = $_GET['event_id'];

$creds = $user->getUserCredentials('meetup');
$meetup_info = $creds->getUserInfo();
$meetup_id = $meetup_info['id'];

$page = 0; // requesting first page
$keep_going = true;

$rsvps = array();

$group_name = array_key_exists('group_name', $_GET) ? $_GET['group_name'] : null;
$event_name = null;

try {
	while ($keep_going) {
		$result = $creds->makeOAuthRequest(
				'http://api.meetup.com/2/rsvps?rsvp=yes&order=social&event_id=' . $event_id, 'GET'
		);
		if ($result['code'] == 200) {
			$data = json_decode(utf8_encode($result['body']), true);

			foreach ($data['results'] as $result) {
				if (is_null($group_name) && isset($result['group'])
						&& is_array($result['group']) && isset($result['group']['urlname'])) {
					$group_name = $result['group']['urlname'];
				}

				if (is_null($event_name) && isset($result['event'])
						&& is_array($result['event']) && isset($result['event']['name'])) {
					$event_name = $result['event']['name'];
				}

				$rsvps[] = array(
					'name' => $result['member']['name'],
					'id' => $result['member']['member_id'],
					'photo_url' => isset($result['member_photo']) ? $result['member_photo']['thumb_link'] : 'http://img2.meetupstatic.com/2982428616572973604/img/noPhoto_80.gif'
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


if (is_null($group_name)) {
	$group_name = "Group: $group_id";
}

if (is_null($event_name)) {
	$event_name = "Event: $event_id";
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
		<h1><a href="events.php?group_id=<?php echo $group_id ?>"><?php echo $group_name ?></a></h1>
		<h2><?php echo $event_name ?></h2>

		<div class="rsvps">
			<?php
			foreach ($rsvps as $rsvp) {
				?>
				<div class="rsvp">
					<div class="thumb">
						<img src="<?php echo $rsvp['photo_url'] ?>"/>
					</div>
					<?php echo $rsvp['name'] ?>
					<div class="clb"></div>
				</div>
				<?php
			}
			?>
		</div>

	</body>
</html>
