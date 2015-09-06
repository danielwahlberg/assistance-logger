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

			return $data;
	}

	public function storeMedication($arrInput) {
		foreach ($arrInput as $currentInput) {
      $sql ="
        INSERT INTO medicineUsageLog (medicineDose_id, assistant_id, medicineGiven)
        VALUES(".$currentInput['id'].", {$currentInput['givenByAssistantId']}, NOW())
      ";
      $db = connect_db();
      $db->query($sql);
    }
	}
}
?>
