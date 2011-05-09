<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<title><?php print $PAGE['title']; ?></title>
		<link rel="stylesheet" href="<?php Path::put('style.css', __FILE__); ?>" type="text/css" media="screen" charset="utf-8">
	</head>
	
	<body>
		<ul id="menu">
			<li>
				<a href="<?php Path::put('../.', __FILE__); ?>" title="Test application Homepage">Home</a>
			</li>
			<li>
				<a href="<?php Path::put('../database.php', __FILE__); ?>" title="Test Database">Database</a>
			</li>
			<li>
				<a href="<?php Path::put('../mail.php', __FILE__); ?>" title="Test Email">Mail</a>
			</li>
			<li>
				<a href="<?php Path::put('../routes/', __FILE__); ?>" title="Test Routes">Routes</a>
			</li>
			<li>
				<a href="<?php Path::put('../session.php', __FILE__); ?>" title="Test Routes">Session</a>
			</li>
			<div class="clear"></div>
		</ul>
		<div id="content">