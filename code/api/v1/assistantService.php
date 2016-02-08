<?php
/**
 * Assistant service:
 * Handles listing of assistants
 */

class AssistantService{

  public function getActiveAssistants() {
    $app = \Slim\Slim::getInstance();
    $db = connect_db();
    $result = $db->query( "SELECT id, firstName AS name FROM assistant WHERE patient_id = {$app->currentUser->patientId} AND endDate IS NULL OR endDate > NOW()" );
    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
      $data[] = $row;
    }

      return $data;
  }
}
