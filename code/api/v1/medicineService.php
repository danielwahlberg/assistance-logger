<?php
/**
 * Medication service:
 * Handles registration of taken medicine, medicine lists etc.
 */

class MedicineService{

	/**
	 * Get list of medicines and doses which are active at the given date
	 */
	public function getWhenNeededMedicationList($forDate) {
		 $app = \Slim\Slim::getInstance();
	   $db = connect_db();
	    $forDateEscaped = $db->real_escape_string($forDate);
	    $sql =
	        "SELECT
	          d.id as doseId, m.id as medicineId, m.name as medicineName, d.dose as dose, d.preferredTime as time
	        FROM
	          medicine m
	        INNER JOIN medicineDose d ON d.medicine_id = m.id
	        WHERE d.startDate <= '$forDateEscaped'
	          AND (d.endDate IS NULL OR d.endDate >= '$forDateEscaped')
	          AND d.giveWhenNeeded = 1
						AND d.patient_id = {$app->currentUser->patientId}
	        ORDER BY d.preferredTime
	          ";

	    $result = $db->query( $sql );
			$data = array();
	    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
	      $data[] = $row;
	    }
			return $data;
	}

	/**
     * Retrieve logged medication given because it was needed (not given regularly)
     */
	public function getLoggedWhenNeededMedication($forDate){
			$app = \Slim\Slim::getInstance();
	    $db = connect_db();
	    $forDateEscaped = $db->real_escape_string($forDate);
			$data = array();
	    $sql =
	        "SELECT m.name AS medicineName, d.dose, a.firstName as givenBy, log.medicineGiven as givenTime
	      FROM medicineUsageLog log
	      INNER JOIN assistant a ON a.id = log.assistant_id
	      INNER JOIN medicineDose d ON log.medicineDose_id = d.id
	      INNER JOIN medicine m ON d.medicine_id = m.id
	      WHERE d.giveWhenNeeded = 1
	        AND DATE(log.medicineGiven) = '$forDateEscaped'
					AND d.patient_id = {$app->currentUser->patientId}
	          ";

	    $result = $db->query( $sql );
	    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
	      $data[] = $row;
	    }
	    return $data;
	}

	public function getMedicineList($forDate) {
		$app = \Slim\Slim::getInstance();
		$db = connect_db();
    $forDateEscaped = $db->real_escape_string($forDate);

    // First SQL: Retrieve medcines and their doeses
    $sql =
    "SELECT
      d.id as doseId, m.name as medicineName, m.id as medicineId , d.dose as dose, d.preferredTime as time
    FROM
      medicine m
    INNER JOIN medicineDose d ON d.medicine_id = m.id
    WHERE d.startDate <= '$forDateEscaped'
      AND (d.endDate IS NULL OR d.endDate >= '$forDateEscaped')
      AND d.giveWhenNeeded = 0
			AND d.patient_id = {$app->currentUser->patientId}
    ORDER BY d.preferredTime
      ";


    $result = $db->query( $sql );

    $arrDoseIds = array(); // dose id => $data array index
    $currentIdx = 0;

    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
      $arrDoseIds[$row['doseId']] = $currentIdx++;
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

    while($row = $result->fetch_array(MYSQLI_ASSOC)) { // TODO Throws error when result is empty, e.g. for newly created patients
      $data[$arrDoseIds[$row['doseId']]]['givenBy'] = $row['givenBy'];
      $data[$arrDoseIds[$row['doseId']]]['givenTime'] = $row['givenTime'];
      $data[$arrDoseIds[$row['doseId']]]['isGiven'] = true;
      $data[$arrDoseIds[$row['doseId']]]['givenMedicineStored'] = true;
    }

			return $data;
	}

	public function getAllMedicines() {
		$app = \Slim\Slim::getInstance();

		$sql = "SELECT m.id, m.name, m.isActive
			FROM medicine m
			INNER JOIN patient_has_medicine has_med ON m.id = has_med.medicine_id
			WHERE has_med.patient_id = {$app->currentUser->patientId}";
		$db = connect_db();
		$data = array();
		$result = $db->query( $sql );
		while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$data[] = $row;
		}
		return $data;
	}


	/** Stores used medicine in medicine usage log */
	public function storeMedication($arrInput) {
		$app = \Slim\Slim::getInstance();
		foreach ($arrInput as $currentInput) {
      $sql ="
        INSERT INTO medicineUsageLog (medicineDose_id, assistant_id, medicineGiven)
        VALUES(".$currentInput['doseId'].", {$currentInput['givenByAssistantId']}, NOW())
      ";
      $db = connect_db();
      $db->query($sql);
    }
	}

	public function inactivateMedicine($medicineId) {
		$app = \Slim\Slim::getInstance();

		// $sql = 'UPDATE medicine SET isActive = 0 WHERE id = '. intval($medicineId);
		$sql = "DELETE FROM patient_has_medicine WHERE medicine_id = {$medicineId} AND patient_id = {$app->currentUser->patientId}";
		$db = connect_db();
		$db->query($sql);
	}

	/** Stores changes in medicines */
	public function storeMedicines($arrInput) {
		$db = connect_db();
		$app = \Slim\Slim::getInstance();

		foreach ($arrInput as $currentInput) {
			if($currentInput['id'] == 0) {
				// New medicine is being added; add to database and let DB generate an id
				// TODO New patients adding a medicine gives new rows in medicine and patient_has_medicine, but removal only removes from patient_has_medicine
				$sql = "
	        INSERT INTO medicine (name, isActive)
	        VALUES('{$currentInput['name']}', 1)
	      ";

				$db->query($sql); // Store new medicine
				$intMedicineId = $db->insert_id;
			} else {
				$sql = "
					UPDATE medicine SET name = '{$currentInput['name']}'
					WHERE id = {$currentInput['id']}
					";
				$db->query($sql); // Store medicine update
				$intMedicineId = $currentInput['id'];
			}


      $sqlPatientMapping = "
				REPLACE INTO patient_has_medicine (medicine_id, patient_id)
				VALUES({$intMedicineId}, {$app->currentUser->patientId})
			";

			$db->query($sqlPatientMapping); // Store mapping of new medicine to patient
		}
	}

	/**
	 * Store change of dose
	 */
	 public function storeDoseChange($arrInput) {
		 $app = \Slim\Slim::getInstance();
		 $db = connect_db();
		 $arrCreatedDoseIds = array();

		 $sqlCreateNewDose =
			 "INSERT INTO medicineDose (medicine_id, startDate, endDate, dose, preferredTime, giveWhenNeeded, patient_id)
			 VALUES(?, NOW(), NULL, ?, ?, ?, ?)";
		 $stmtCreateNewDose = $db->prepare($sqlCreateNewDose);

		 $sqlInactivateOldDose =
			 "UPDATE medicineDose SET endDate = NOW() WHERE id = ? ";
		$stmtInactivateOldDose = $db->prepare($sqlInactivateOldDose);

		 foreach ($arrInput as $arrDose) {

				$stmtCreateNewDose->bind_param('issii', $arrDose['medicineId'], $arrDose['dose'], $arrDose['time'], intval($arrDose['giveWhenNeeded']), $app->currentUser->patientId);
				$stmtCreateNewDose->execute();
				error_log($db->error);
				$arrCreatedDoseIds[] = $db->insert_id;


				$stmtInactivateOldDose->bind_param('i', $arrDose['doseId']);
				$stmtInactivateOldDose->execute();
				error_log($db->error);
		 }

		 return $arrCreatedDoseIds;

	 }
}
?>
