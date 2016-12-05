<?php
require_once(dirname(__DIR__) . '/global.php');

// get user if logged in or require user to login
$user = User::require_login();

$fetched_groups_organizer = array();

// You can work with users, but it's recommended to tie your data to accounts, not users
$current_account = Account::getCurrentAccount($user);

$creds = $user->getUserCredentials('meetup');
$meetup_info = $creds->getUserInfo();
$meetup_id = $meetup_info['id'];

$page = 0; // requesting first page
$keep_going = true;

try {
	while ($keep_going) {
		$result = $creds->makeOAuthRequest(
				'http://api.meetup.com/2/groups?order=name&member_id=self', 'GET'
		);
		if ($result['code'] == 200) {
			$group_data = json_decode(utf8_encode($result['body']), true);

			foreach ($group_data['results'] as $group) {
				$group_info = array(
					'name' => $group['name'],
					'link' => $group['link'],
					'id' => $group['id'],
					'logo' => isset($group['group_photo']) ? $group['group_photo']['thumb_link'] : null,
					'members' => $group['members']
				);

				if ($group['organizer']['member_id'] == $meetup_id) {
					$fetched_groups_organizer[] = $group_info;

					//var_export($group_data);
				}
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
} catch (OAuthException2 $ex) {
	// silently ignoring all API call problems
}

usort($fetched_groups_organizer, function($a, $b) {
	return $a['members'] > $b['members'] ? -1 : 1;
});

$template_info = StartupAPI::getTemplateInfo();

$template_info['fetched_groups_organizer'] = $fetched_groups_organizer;

// add more data for your page
StartupAPI::$template->display('@raffle/index.html.twig', $template_info);
