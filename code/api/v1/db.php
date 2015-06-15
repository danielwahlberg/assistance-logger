<?php
function connect_db() {
	$server = 'localhost'; // this may be an ip address instead
	$user = 'admin';
	$pass = 'SlR1MAPiiEKb';
	$database = 'assistanceLogger';
	$connection = new mysqli($server, $user, $pass, $database);

	return $connection;
}


?>