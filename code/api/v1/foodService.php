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

       return $data;
   }

   /** Retrieves the sum of stored feeding per food type the latest month
    * @return array($feedingDate => array($feedingTypeName => $amountOfFeedingTypeThisDay))
    */
   public function getFeedingStatistics() {
     $arrFoodTypes = array('Food'=>1,'GTube'=>2, 'Liquid'=>3); // Can be retrieved from DB instead if food types would become dynamic
     $db = connect_db();
     $data = array(); // Will be populated with feeding date as keys, and array as value. Each date's array contain $feedingTypeName => $amountForFeedingType

     foreach($arrFoodTypes as $typeName => $typeId) {
       $sql =
         "SELECT sum(amount) as amountOfFeedingTypeThisDay, ft.name, DATE(feedingStoredAt) as feedingDate
          from feedingLog log
          inner join foodType ft ON ft.id = log.foodType_id
          where feedingStoredAt > DATE_ADD(NOW(), INTERVAL -1 MONTH)
            AND foodType_id = $typeId
          group by foodType_id, feedingDate
          order by feedingDate, foodType_id";

        $result = $db->query($sql);

        while($row = $result->fetch_array(MYSQLI_ASSOC)){

          if(!isset($data[$row['feedingDate']]))
            $data[$row['feedingDate']] = array(); // Initiate this date's array

          $data[$row['feedingDate']][$typeName] = $row['amountOfFeedingTypeThisDay'];
        }
    }

    // Loop through resulting array to add sums
    foreach($data as $feedingDate => $arrFeedingTypes) {
      $data[$feedingDate]['sumAmount'] = array_sum($arrFeedingTypes);
      $data[$feedingDate]['feedingDate'] = $feedingDate;
    }

      return $data;
   }

   public function storeFeeding($arrInput) {
       $sql = "INSERT INTO feedingLog
         (amount, foodType_id, assistant_id, feedingStoredAt)
         VALUES({$arrInput['amount']}, {$arrInput['foodType']['id']}, {$arrInput['assistant']['id']}, NOW())";
       $db = connect_db();
       $db->query($sql);
      return $db->insert_id;
   }

   public function deleteFeeding($id) {
         $sql = "DELETE FROM feedingLog where id = $id";
         $db = connect_db();
         $db->query($sql);
   }
 }
?>
