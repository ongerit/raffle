<?php
require_once(__DIR__ . '/global.php');

if (!isset($CURRENT_PAGE)) {
	$CURRENT_PAGE = 'home';
}

$menus = array(
	'home' => array(
		'title' => 'Home',
		'url' => $project_env['ROOT_ABSOLUTE_URL_PATH'] . '/'
	),
	'about' => array(
		'title' => 'About',
		'url' => $project_env['ROOT_ABSOLUTE_URL_PATH'] . 'about.php'
	)
);

$page_title = UserConfig::$appName;
if (isset($TITLE)) {
	$page_title = "$page_title - $TITLE";
}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo UserTools::escape($page_title) ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php StartupAPI::head(); ?>
	</head>
	<body>
		<div class="navbar">
			<div class="navbar-inner">
				<span class="brand">
					<a href="<?php echo $project_env['ROOT_ABSOLUTE_URL_PATH'] ?>/"><?php echo UserTools::escape($appName) ?></a>
				</span>

				<ul class="nav">
					<?php foreach ($menus as $slug => $menu) { ?>
						<li<?php if ($CURRENT_PAGE == $slug) { ?> class="active"<?php } ?>>
							<a href="<?php echo $menu['url'] ?>"><?php echo $menu['title'] ?></a>
						</li>
					<?php } ?>
				</ul>
				<?php StartupAPI::power_strip(false, false); ?>
			</div>
		</div>
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span12">
