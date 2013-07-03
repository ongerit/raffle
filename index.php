<?php
require_once(__DIR__.'/global.php');

// get user if logged in or require user to login
$user = User::get();
#$user = User::require_login();

$fetched_groups_organizer = array();

if (!is_null($user)) {
	// You can work with users, but it's recommended to tie your data to accounts, not users
	$current_account = Account::getCurrentAccount($user);

	$creds = $user->getUserCredentials('meetup');
	$meetup_info = $creds->getUserInfo();
	$meetup_id = $meetup_info['id'];

	$page = 0; // requesting first page
	$keep_going = true;

	try {
		while($keep_going) {
			$result = $creds->makeOAuthRequest(
				'http://api.meetup.com/2/groups?order=name&member_id=self',
				'GET'
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
}

$_CURRENT_PAGE = 'raffle';

require_once($project_env['ROOT_FILESYSTEM_PATH'] . '/header.php');

if (!is_null($user)) {
?>
<h1>Welcome, <?php echo $user->getName() ?>!</h1>

<?php

	usort($fetched_groups_organizer, function($a, $b) {
		return $a['members'] > $b['members'] ? -1 : 1;
	});

	if (count($fetched_groups_organizer)) {
		if (count($fetched_groups_organizer)) {
?>
<h3>You organize:</h3>
<ul class="groups">
<?php
			foreach ($fetched_groups_organizer as $group) {
				?><li>
					<div class="logo">
					<?php if ($group['logo'] != '') { ?>
						<img src="<?php echo $group['logo'] ?>" />
					<?php } ?>
					</div>
					<a href="events.php?group_id=<?php echo $group['id'] ?>"><?php echo $group['name'] ?></a><br/>
					<?php echo $group['members'] ?> members
					<div class="clb"/>
				</li><?php
			}
?>
</ul>
<?php
		}

	} else { ?>
		<p>You still do not organize any groups?!</p>
		<p><a target="_blank" href="http://www.meetup.com/create/">Time to start organizing!</a></p>
	<?php
	}
}
else
{
?>
<h1><?php echo $appName ?></h1>

<?php
	$meetup_module = AuthenticationModule::get('meetup');
	?><p><?php $meetup_module->renderRegistrationForm(); ?></p><?php
}

require_once($project_env['ROOT_FILESYSTEM_PATH'] . '/footer.php');