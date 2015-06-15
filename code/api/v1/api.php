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
  $app->add(new \Slim\Middleware\ContentTypes()); // Make sure JSON encoded post variables are parsed by Slim
  $app->response->headers->set('Content-Type', 'application/json');

  $app->get('/hello/:name', function ($name) {
      echo "Hello, $name";
  });

  $app->get('/medicines/:forDate', function($forDate){

     // TODO Add proper string escaping to avoid SQL injections!
    // TODO This SQL will miss all doses that have been given prior to $forDate; log.medicineGiven is neither null nor DATE(log.medicineGiven)=$forDate for such doses
    $sql = 
    "SELECT
      d.id as id, m.name as medicine, d.dose as dose, d.preferredTime as time
    FROM
      medicine m
    INNER JOIN medicineDose d ON d.medicine_id = m.id
    WHERE d.startDate <= '$forDate'
      AND (d.endDate IS NULL OR d.endDate >= '$forDate')
    ORDER BY d.preferredTime
      ";
    
    $db = connect_db();
    $result = $db->query( $sql );

    $arrDoseIds = array(); // dose id => $data array index
    $currentIdx = 0;

    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
      $arrDoseIds[$row['id']] = $currentIdx++;
      $data[] = $row;
    }
    $strDoseIds = implode(',', array_keys($arrDoseIds));

    $sql = 
      "SELECT log.medicineDose_id as doseId, a.firstName as givenBy, log.medicineGiven as givenTime  
      FROM medicineUsageLog log
      INNER JOIN assistant a ON a.id = log.assistant_id
      WHERE medicineDose_id IN($strDoseIds)
        AND DATE(log.medicineGiven) = '$forDate'
    ";
    $db = connect_db();
    $result = $db->query( $sql );
    
    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[$arrDoseIds[$row['doseId']]]['givenBy'] = $row['givenBy'];
      $data[$arrDoseIds[$row['doseId']]]['givenTime'] = $row['givenTime'];
      $data[$arrDoseIds[$row['doseId']]]['isGiven'] = true;
      $data[$arrDoseIds[$row['doseId']]]['givenMedicineStored'] = true;
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
    );  */
  });

  $app->post('/medication/', function() use ($app){
    $arrInput = $app->request()->getBody();

    $sql ="
      INSERT INTO medicineUsageLog (medicineDose_id, assistant_id, medicineGiven)
      VALUES(".$arrInput[0]['id'].", {$arrInput[0]['givenByAssistantId']}, NOW())
    ";
    $db = connect_db();
    $db->query($sql);
  });

  $log = $app->getLog();

  $app->run();
?>