<?php
function connect_db() {
	$server = 'localhost'; // this may be an ip address instead
	$user = 'admin';
	$pass = '963K5ZUtsaMl';
	$database = 'assistanceLogger';
	$connection = new mysqli($server, $user, $pass, $database);

	return $connection;
}


?>
