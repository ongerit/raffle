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

$hosts = array();

try {
	$result = $creds->makeOAuthRequest(
			'http://api.meetup.com/2/event/' . urlencode($event_id) . '?fields=event_hosts', 'GET'
	);

	if ($result['code'] == 200) {
		$data = json_decode(utf8_encode($result['body']), true);

		$group_name = $data['group']['name'];
		$event_name = $data['name'];
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

				if (rand(0, 100) > 90) {
					$checkins[$result['member']['member_id']] = true;
				}
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

$_TITLE = $event_name . ' on ' . date('M j, Y', $event_time);
$_CURRENT_PAGE = 'raffle';

require_once($project_env['ROOT_FILESYSTEM_PATH'] . '/header.php');
?>
<style>
	#random {
		margin-right: 1em;
	}

	.progress {
		margin: 0.3em 0;
	}

	#winner_section {
		display: none;
	}
</style>

<h2>
	<a target="_blank" href="<?php echo $event_url ?>"><?php echo $event_name ?></a> on <?php echo UserTools::escape(date('M j, Y', $event_time)) ?>
</h2>

<div class="well" id="controls">
	<button id="random" class="pull-left btn btn-primary">Pick a Random Winner!</button>

	<div class="progress progress-striped">
		<div class="bar" style="width: 0%;"></div>
	</div>

	<div class="clb"></div>

	<script>
		$().ready(function(e) {
			var progress_html = $('.progress').html();

			$('#allrsvps').click(function(e) {
				setTimeout(function(e) {
					var shown_num = $('#all_rsvps .rsvp').show().length;

					if (shown_num > 0) {
						$('#controls').show();
					} else {
						$('#controls').hide();
					}
				}, 0);
			});
			$('#allrsvps').click();

			$('#random').click(function(e) {
				var all = $('#all_rsvps .rsvp'),
				picked_index,
				picked,
				fake,
				tries = 30;

				var animator = function(number) {
					$('#random').attr('disabled', 'disabled').removeClass('btn-primary');

					if (picked) {
						picked.removeClass('picking');
					}

					if (fake) {
						fake.remove();
					}

					picked_index = Math.round(Math.random() * (all.length - 1));
					picked = $(all[picked_index]);
					fake = picked.clone(true);
					fake.appendTo($('#winners'));

					picked.addClass('picking');

					var progress = (tries - number) * 100 / tries;

					$('.progress').addClass('progress-striped');
					$('.progress .bar').width(progress + '%');

					if (number > 0) {
						window.setTimeout(function() { animator(number - 1); }, 700 / number);
					} else {
						window.setTimeout(function() {
							if (picked) {
								picked.removeClass('picking');
							}

							if (fake) {
								fake.remove();
							}

							// actually picking
							picked.remove().appendTo($('#winners'));

							$('#random').removeAttr('disabled').addClass('btn-primary');

							setTimeout(function() {
								$('.progress').removeClass('progress-striped');
								$('.progress .bar').width('100%').addClass('bar-success');
							}, 100);
						}, 1);
					}
				}

				$('.progress').html(progress_html);

				$('#winner_section').show();

				// random animation
				if (all.length > 1) {
					animator(tries);
				} else {
					$('#controls').hide();
					animator(0);
				}
			});
		});
	</script>
</div>

<section id="winner_section">
	<h2>Winners!</h2>
	<div class="well well-small">
		<div class="rsvps" id="winners">
		</div>
	</div>
</section>

<h2><?php echo count($rsvps) ?> RSVPs</h2>
<div class="rsvps" id="all_rsvps">
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

<div id="stash" style="display: none"></div>
<?php
require_once($project_env['ROOT_FILESYSTEM_PATH'] . '/footer.php');
