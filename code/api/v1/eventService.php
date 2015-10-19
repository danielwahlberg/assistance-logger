<?php
/**
 * Event service:
 * Handles registration and listing of events
 */

 class EventService {

   public function getEventsFor($givenDate) {
     $sql =
     "SELECT log.id, a.id, a.firstName, log.duration, log.description, type.name, type.id as typeId
     FROM eventLog log
     INNER JOIN assistant a ON a.id = log.assistant_id
     INNER JOIN eventType type ON type.id = log.eventType_id
     WHERE DATE(log.eventStoredAt) = ?";

     $db = connect_db();
     $toReturn = array();
     if($stmt = $db->prepare($sql)) {
       $stmt->bind_param('s', $givenDate);

       $stmt->bind_result($logId, $assistantId, $assistantFirstName, $duration, $description, $eventTypeName, $eventTypeId);
       $stmt->execute();

       while($stmt->fetch()) {
         $toReturn[] = array('logId'=>$logId, 'assistantId'=>$assistantId, 'assistantFirstName'=>$assistantFirstName,
          'duration'=> $duration, 'description'=>$description, 'eventTypeName'=>$eventTypeName, 'eventTypeId'=>$eventTypeId);
       }
       $stmt->close();
     }
     return $toReturn;
   }

   public function getEventTypes() {
     $sql =
     "SELECT id, name, description FROM eventType";

     $db = connect_db();
     $result = $db->query($sql);
     while($row = $result->fetch_array(MYSQLI_ASSOC)){
       $data[] = $row;
     }
     return $data;
   }

   public function storeEvent($arrInput) {
     $sql =
     "INSERT INTO eventLog (eventType_id, assistant_id, duration, description, eventStoredAt)
     VALUES(?,?,?,?,NOW())";

     $db = connect_db();
     $stmt = $db->prepare($sql);
     $stmt->bind_param('iiss', $arrInput['eventType']['id'], $arrInput['assistant']['id'], $arrInput['duration'], $arrInput['description']);
     $stmt->execute();
     return $db->insert_id;
   }
 }
?>
