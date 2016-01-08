<?php
/**
 * Food service:
 * Handles registration of feeding, food lists etc.
 */

 class FoodService {
   public function getFoodTypes() {
     $sql = "SELECT id, name FROM foodTypes";
     $db = connect_db();
     $result = $db->query($sql);
     while($row = $result->fetch_array(MYSQLI_ASSOC)){
       $data[] = $row;
     }
     return $data;
   }

   public function getLoggedFeeding() {
     $app = \Slim\Slim::getInstance();
     if(date('H') < 6) {
       // Time is before 06:00; get data starting 6:00 yesterday
       // (this is to include recent log entries in data returned)
       $dateStart = new DateTime('06:00');
       $dateStart = $dateStart->sub(new DateInterval('PT24H')); // Subtract 24 hours
     } else {
       // Time is after 06:00; get data starting 6:00 today
       $dateStart = new DateTime('06:00');
     }

     $dateEnd = clone $dateStart;
     $dateEnd = $dateEnd->add(new DateInterval('PT24H'));

     $sql =
       "SELECT food.name, log.id, log.amount, food.unit, log.feedingGivenAt, a.firstName as givenBy
       FROM feedingLog log
       INNER JOIN foodType food ON food.id = log.foodType_id
       INNER JOIN assistant a ON a.id = log.assistant_id
       WHERE feedingGivenAt BETWEEN '{$dateStart->format('Y-m-d h:i:s')}' AND '{$dateEnd->format('Y-m-d h:i:s')}'
        AND log.patient_id = {$app->currentUser->patientId}
       ORDER BY feedingGivenAt
       ";

       $db = connect_db();
       $result = $db->query($sql);
       $data = array();
       while($row = $result->fetch_array(MYSQLI_ASSOC)){
         $data[] = $row;
       }

       return $data;
   }

   /** Retrieves the sum of stored feeding per food type the latest month
    * @return array($feedingDate => array($feedingTypeName => $amountOfFeedingTypeThisDay))
    */
   public function getFeedingStatistics() {
     $app = \Slim\Slim::getInstance();

     $arrFoodTypes = array('Food'=>1,'GTube'=>2, 'Liquid'=>3); // Can be retrieved from DB instead if food types would become dynamic

     $db = connect_db();
     $data = array(); // Will be populated with feeding date as keys, and array as value. Each date's array contain $feedingTypeName => $amountForFeedingType

     foreach($arrFoodTypes as $typeName => $typeId) {
       $sql =
         "SELECT sum(amount) as amountOfFeedingTypeThisDay, ft.name, DATE(feedingGivenAt) as feedingDate, ft.kcalPerUnit
          from feedingLog log
          inner join foodType ft ON ft.id = log.foodType_id
          where feedingGivenAt > DATE_ADD(NOW(), INTERVAL -1 MONTH)
            AND foodType_id = $typeId
            AND log.patient_id = {$app->currentUser->patientId}
          group by foodType_id, feedingDate
          order by feedingDate, foodType_id";

        $result = $db->query($sql);

        while($row = $result->fetch_array(MYSQLI_ASSOC)){

          if(!isset($data[$row['feedingDate']]))
            $data[$row['feedingDate']] = array('sumNutrition'=>0, 'sumAmount'=>0); // Initiate this date's array

          $data[$row['feedingDate']][$typeName] = $row['amountOfFeedingTypeThisDay'];
          $data[$row['feedingDate']]['sumNutrition'] += ($row['amountOfFeedingTypeThisDay'] * $row['kcalPerUnit']);
          $data[$row['feedingDate']]['sumAmount'] += $row['amountOfFeedingTypeThisDay'];
        }
    }

    // Loop through resulting array to add sums
    foreach($data as $feedingDate => $arrFeedingTypes) {
      $data[$feedingDate]['feedingDate'] = $feedingDate;
    }

      return $data;
   }

   public function storeFeeding($arrInput) {
       $app = \Slim\Slim::getInstance();
       $db = connect_db();
       $sql = "INSERT INTO feedingLog
         (amount, foodType_id, assistant_id, feedingGivenAt, feedingStoredAt, patient_id)
         VALUES(?, ?, ?, ?, NOW(), ?)";
       $db = connect_db();
       $stmt = $db->prepare($sql);
       $stmt->bind_param('siisi', $arrInput['amount'], $arrInput['foodType']['id'], $arrInput['assistant']['id'], $arrInput['givenTime'], $app->currentUser->patientId);
       $stmt->execute();
      return $db->insert_id;
   }

   public function deleteFeeding($id) {
         $app = \Slim\Slim::getInstance();
         $sql = "DELETE FROM feedingLog where id = $id AND patient_id = {$app->currentUser->patientId}";
         $db = connect_db();
         $db->query($sql);
   }
 }
?>
