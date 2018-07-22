<?php
function connect_db() {
	$server = 'localhost'; // this may be an ip address instead
	$user = 'admin';
	$pass = 'WaMY40rA2TZ5';
	$database = 'assistanceLogger';
	$connection = new mysqli($server, $user, $pass, $database);

	return $connection;
}

/*
SQL for retrieving diff since last logged event
select elPrev.id as prevId, TIMEDIFF(elPrev.eventStoredAt, el1.eventStoredAt) as timeSinceLastLog, el1.*
from eventLog el1
inner join eventLog elPrev on elPrev.id = (el1.id-1)
where el1.eventType_id = 4
*/

?>
