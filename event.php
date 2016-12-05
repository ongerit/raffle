<?php
require_once(dirname(__DIR__) . '/global.php');

$user = User::require_login();

if (!isset($_GET['event_id'])) {
	header('Location: ' . $project_env['ROOT_ABSOLUTE_URL_PATH'] . '/events.php?group_id=' . $group_id);
	exit;
}

$event_id = $_GET['event_id'];

$creds = $user->getUserCredentials('meetup');
$meetup_info = $creds->getUserInfo();
$meetup_id = $meetup_info['id'];

/*
 *  Getting event info
 */
$group_name = null;
$event_name = null;
$event_url = null;

$hosts = array();

try {
	$result = $creds->makeOAuthRequest(
			'http://api.meetup.com/2/event/' . urlencode($event_id) . '?fields=event_hosts', 'GET'
	);

	if ($result['code'] == 200) {
		$data = json_decode(utf8_encode($result['body']), true);

		$group_name = $data['group']['name'];
		$event_name = $data['name'];
		$event_url = $data['event_url'];
		$event_time = $data['time'] / 1000;

		foreach ($data['event_hosts'] as $host) {
			$hosts[] = $host['member_id'];
		}
	} else {
		header('HTTP/1.1 404 Event not found');
		exit;
	}
} catch (OAuthException2 $ex) {
	// silently ignoring all API call problems
}

/*
 *  Getting RSVPs
 */
$page = 0; // requesting first page
$keep_going = true;

$rsvps = array();

try {
	while ($keep_going) {
		$result = $creds->makeOAuthRequest(
				'http://api.meetup.com/2/rsvps?rsvp=yes&order=social&event_id=' . $event_id, 'GET'
		);
		if ($result['code'] == 200) {
			$data = json_decode(utf8_encode($result['body']), true);

			foreach ($data['results'] as $result) {
				// var_export($result);

				if (in_array($result['member']['member_id'], $hosts)) {
					continue;
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

$template_info = StartupAPI::getTemplateInfo();

$template_info['group_name'] = $group_name;

$template_info['event_name'] = $event_name;
$template_info['event_time'] = $event_time;
$template_info['event_url'] = $event_url;
$template_info['rsvps'] = $rsvps;

// add more data for your page
StartupAPI::$template->display('@raffle/event.html.twig', $template_info);
