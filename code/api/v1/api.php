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

  $app->get('/medicines/whenNeededMedicines/:forDate', function($forDate){
    $db = connect_db();
    $forDateEscaped = $db->real_escape_string($forDate);
    $sql = 
        "SELECT
          d.id as id, m.name as medicineName, d.dose as dose, d.preferredTime as time
        FROM
          medicine m
        INNER JOIN medicineDose d ON d.medicine_id = m.id
        WHERE d.startDate <= '$forDateEscaped'
          AND (d.endDate IS NULL OR d.endDate >= '$forDateEscaped')
          AND d.giveWhenNeeded = 1
        ORDER BY d.preferredTime
          ";

    $result = $db->query( $sql );
    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
      $data[] = $row;
    }

    echo json_encode($data);
  });


  /**
   * Retrieve logged medication given because it was needed (not given regularly) 
   */
  $app->get('/medicines/whenNeededLog/:forDate', function($forDate){
    $db = connect_db();
    $forDateEscaped = $db->real_escape_string($forDate);
    $sql = 
        "SELECT m.name AS medicineName, d.dose, a.firstName as givenBy, log.medicineGiven as givenTime  
      FROM medicineUsageLog log
      INNER JOIN assistant a ON a.id = log.assistant_id
      INNER JOIN medicineDose d ON log.medicineDose_id = d.id
      INNER JOIN medicine m ON d.medicine_id = m.id
      WHERE d.giveWhenNeeded = 1
        AND DATE(log.medicineGiven) = '$forDateEscaped'
          ";

    $result = $db->query( $sql );
    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
      $data[] = $row;
    }

    echo json_encode($data);
  });


  $app->get('/medicines/:forDate', function($forDate){
    $db = connect_db();
    $forDateEscaped = $db->real_escape_string($forDate);

    // First SQL: Retrieve medcines and their doeses
    $sql = 
    "SELECT
      d.id as id, m.name as medicineName, d.dose as dose, d.preferredTime as time
    FROM
      medicine m
    INNER JOIN medicineDose d ON d.medicine_id = m.id
    WHERE d.startDate <= '$forDateEscaped'
      AND (d.endDate IS NULL OR d.endDate >= '$forDateEscaped')
      AND d.giveWhenNeeded = 0
    ORDER BY d.preferredTime
      ";
    
    
    $result = $db->query( $sql );

    $arrDoseIds = array(); // dose id => $data array index
    $currentIdx = 0;

    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
      $arrDoseIds[$row['id']] = $currentIdx++;
      $data[] = $row;
    }
    $strDoseIds = implode(',', array_keys($arrDoseIds));

    // Second SQL: Complement with logs of how much medicine that has been given
    $sql = 
      "SELECT log.medicineDose_id as doseId, a.firstName as givenBy, log.medicineGiven as givenTime  
      FROM medicineUsageLog log
      INNER JOIN assistant a ON a.id = log.assistant_id
      WHERE medicineDose_id IN($strDoseIds)
        AND DATE(log.medicineGiven) = '$forDateEscaped'
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

    foreach ($arrInput as $currentInput) {
      $sql ="
        INSERT INTO medicineUsageLog (medicineDose_id, assistant_id, medicineGiven)
        VALUES(".$currentInput['id'].", {$currentInput['givenByAssistantId']}, NOW())
      ";
      $db = connect_db();
      $db->query($sql);
    }

  });

  $app->get('/food/foodTypes/', function() {
    $sql = "SELECT id, name FROM foodTypes";
    $db = connect_db();
    $result = $db->query($sql);
    while($row = $result->fetch_array(MYSQLI_ASSOC)){
      $data[] = $row;
    }
    echo json_encode($data);
  });

  $app->get('/food/feeding/', function() {
    $sql = 
      "SELECT food.name, log.id, log.amount, food.unit, log.feedingStoredAt, a.firstName as givenBy
      FROM feedingLog log
      INNER JOIN foodType food ON food.id = log.foodType_id
      INNER JOIN assistant a ON a.id = log.assistant_id
      WHERE DATE(feedingStoredAt) = DATE(NOW())
      ORDER BY feedingStoredAt
      ";

      $db = connect_db();
      $result = $db->query($sql);
      $data = array();
      while($row = $result->fetch_array(MYSQLI_ASSOC)){
        $data[] = $row;
      }
      echo json_encode($data);
  });

  $app->post('/food/feeding/', function() use ($app){
    $arrInput = $app->request()->getBody();    

    $sql = "INSERT INTO feedingLog 
      (amount, foodType_id, assistant_id, feedingStoredAt)
      VALUES({$arrInput['amount']}, {$arrInput['foodType']['id']}, {$arrInput['assistant']['id']}, NOW())";
    $db = connect_db();
    $db->query($sql);
    echo json_encode($db->insert_id);
  });

  $app->delete('/food/feeding/:id', function($id){
    
    $sql = "DELETE FROM feedingLog where id = $id";
    $db = connect_db();
    $db->query($sql);

  });

  $log = $app->getLog();

  $app->run();
?>