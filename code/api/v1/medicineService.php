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
	        ORDER BY d.preferredTime
	          ";

	    $result = $db->query( $sql );
	    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
	      $data[] = $row;
	    }
			return $data;
	}

	/**
     * Retrieve logged medication given because it was needed (not given regularly)
     */
	public function getLoggedWhenNeededMedication($forDate){
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
	          ";

	    $result = $db->query( $sql );
	    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
	      $data[] = $row;
	    }
	    return $data;
	}

	public function getMedicineList($forDate) {
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

    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[$arrDoseIds[$row['doseId']]]['givenBy'] = $row['givenBy'];
      $data[$arrDoseIds[$row['doseId']]]['givenTime'] = $row['givenTime'];
      $data[$arrDoseIds[$row['doseId']]]['isGiven'] = true;
      $data[$arrDoseIds[$row['doseId']]]['givenMedicineStored'] = true;
    }

			return $data;
	}

	public function getAllMedicines() {
		$sql = "SELECT id, name, isActive FROM medicine";
		$db = connect_db();
		$result = $db->query( $sql );
		while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$data[] = $row;
		}
		return $data;
	}


	/** Stores used medicine in medicine usage log */
	public function storeMedication($arrInput) {
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
		$sql = 'UPDATE medicine SET isActive = 0 WHERE id = '. intval($medicineId);
		$db = connect_db();
		$db->query($sql);
	}

	/** Stores changes in medicines */
	public function storeMedicines($arrInput) {
		foreach ($arrInput as $currentInput) {
      $sql = "
        REPLACE INTO medicine (id, name, isActive)
        VALUES(". $currentInput['medicine'].", {$currentInput['name']}, 1)
      ";
      $db = connect_db();
      $db->query($sql);
		}
	}

	/**
	 * Store change of dose
	 */
	 public function storeDoseChange($arrInput) {
		 $db = connect_db();
		 $arrCreatedDoseIds = array();

		 $sqlCreateNewDose =
			 "INSERT INTO medicineDose (medicine_id, startDate, endDate, dose, preferredTime, giveWhenNeeded)
			 VALUES(?, NOW(), NULL, ?, ?, ?)";
		 $stmtCreateNewDose = $db->prepare($sqlCreateNewDose);

		 $sqlInactivateOldDose =
			 "UPDATE medicineDose SET endDate = NOW() WHERE id = ? ";
		$stmtInactivateOldDose = $db->prepare($sqlInactivateOldDose);

		 foreach ($arrInput as $arrDose) {

				$stmtCreateNewDose->bind_param('issi', $arrDose['medicineId'], $arrDose['dose'], $arrDose['time'], intval($arrDose['giveWhenNeeded']));
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
