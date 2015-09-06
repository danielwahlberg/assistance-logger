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
