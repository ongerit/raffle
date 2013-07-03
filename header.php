<?php
require_once(__DIR__ . '/global.php');

if (!isset($_CURRENT_PAGE)) {
	$_CURRENT_PAGE = 'home';
}

$menus = array(
	'raffle' => array(
		'icon' => 'icon-gift',
		'title' => 'Raffle',
		'url' => $project_env['ROOT_ABSOLUTE_URL_PATH'] . '/'
	),
	'about' => array(
		'icon' => 'icon-info-sign',
		'title' => 'About',
		'url' => $project_env['ROOT_ABSOLUTE_URL_PATH'] . '/about.html'
	)
);

$page_title = UserConfig::$appName;
if (isset($_TITLE)) {
	$page_title = $_TITLE . ' - ' . $page_title;
}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo UserTools::escape($page_title) ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php StartupAPI::head(); ?>
		<link rel="stylesheet" type="text/css" href="meetup.css"/>
	</head>
	<body>
		<div class="navbar">
			<div class="navbar-inner">
				<span class="brand">
					<a href="<?php echo $project_env['ROOT_ABSOLUTE_URL_PATH'] ?>/"><?php echo UserTools::escape($appName) ?></a>
				</span>

				<ul class="nav">
					<?php foreach ($menus as $slug => $menu) { ?>
						<li<?php if ($_CURRENT_PAGE == $slug) { ?> class="active"<?php } ?>>
							<a href="<?php echo $menu['url'] ?>">
								<?php
								if ($menu['icon']) {
									?><i class="<?php echo $menu['icon'] ?>"></i><?php
						}
								?>
								<?php echo $menu['title'] ?>
							</a>
						</li>
					<?php } ?>
				</ul>
				<?php StartupAPI::power_strip(false, false); ?>
			</div>
		</div>
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span12">
