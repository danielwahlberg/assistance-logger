<?php
	require '../../assets/Slim-2.6.2/Slim/Slim.php';
	require 'db.php';

	\Slim\Slim::registerAutoloader();

	$app = new \Slim\Slim(
		array(
			'mode' => 'development',
			'debug' => true,
			'log.level' => \Slim\Log::DEBUG
		)
	);
	$app->add(new \Slim\Middleware\ContentTypes());	// Make sure JSON encoded post variables are parsed by Slim
	$app->response->headers->set('Content-Type', 'application/json');

	$app->get('/hello/:name', function ($name) {
    	echo "Hello, $name";
	});

	$app->get('/medicines/:forDate', function($forDate){

		 // TODO Add poper string escaping to avoid SQL injections!
		$sql = 
		"SELECT
 			m.id as id, m.name as medicine, d.dose as dose, d.preferredTime as time, 0 as isGiven, null as givenBy
		FROM
 			medicine m
		INNER JOIN medicineDose d
			ON d.medicine_id = m.id
		WHERE d.startDate <= '$forDate'
			AND (d.endDate IS NULL OR d.endDate >= '$forDate')
		ORDER BY d.preferredTime
			";
		
		$db = connect_db();
		$result = $db->query( $sql );

		while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$row['isGiven'] = false; // $row contains the string "0", which is not properly handled by Angular; hard code to boolean false
			$row['stateStored'] = true;
			$data[] = $row;
		}
		
		echo json_encode($data);
	});


	$app->get('/assistants/', function(){
		$db = connect_db();
		$result = $db->query( 'SELECT id, firstName AS name FROM assistant' );
		while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$data[] = $row;
		}
		echo json_encode($data);
		/*
		$app->render('friends.php', array(
				'page_title' => "Your Friends",
				'data' => $data
			)
		);	*/
	});

	$app->post('/medication/', function() use ($app){
		$arrInput = $app->request()->getBody();
		print_r($arrInput, true);
	});

	$log = $app->getLog();

	$app->run();
?>