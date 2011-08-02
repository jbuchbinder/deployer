<?php

// Redirect
Header('Location: dashboard.php');

?>
<html>
<head>
	<title>Deployer</title>
	<link rel="stylesheet" type="text/css" href="css/deployer.css"></link>
</head>
<body>

<h1>Deployer</h1>

<ul class="buttons">
	<li><a href="repository_sync.php">Update software from shrek</a></li>
	<li><a href="dashboard.php">Dashboard</a></li>
	<li><a href="edit_config.php">Edit Domain Config</a></li>
</ul>

</body>
</html>
