<?php

class ConstructionStages
{
	private $db;

	public function __construct()
	{
		$this->db = Api::getDb();
	}

	public function getAll()
	{
		$stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
		");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getSingle($id)
	{
		$stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
			WHERE ID = :id
		");
		$stmt->execute(['id' => $id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function post(ConstructionStagesCreate $data)
	{
	    // Set default value for durationUnit if not explicitly set
    	if (!$data->durationUnit) {
        	$data->durationUnit = 'DAYS';
    	}
    	
    	// Set default value for status if not explicitly set
    	if (!$data->status) {
        	$data->status = 'NEW';
    	}
    	
		// Call calcDuration function to calculate duration field (if endDate is null, then duration is also null).
	    $data->duration = $this->calcDuration($data->startDate, $data->endDate, $data->durationUnit);	    
	    
		// Validate input data
		ConstructionStagesValidator::validateName($data->name);
		ConstructionStagesValidator::validateStartDate($data->startDate);
		ConstructionStagesValidator::validateEndDate($data->endDate, $data->startDate);
		ConstructionStagesValidator::validateDurationUnit($data->durationUnit);
		ConstructionStagesValidator::validateColor($data->color);
		ConstructionStagesValidator::validateExternalId($data->externalId);
		ConstructionStagesValidator::validateStatus($data->status);
    		
		$stmt = $this->db->prepare("
			INSERT INTO construction_stages
			    (name, start_date, end_date, duration, durationUnit, color, externalId, status)
			    VALUES (:name, :start_date, :end_date, :duration, :durationUnit, :color, :externalId, :status)
			");
		$stmt->execute([
			'name' => $data->name,
			'start_date' => $data->startDate,
			'end_date' => $data->endDate,
			'duration' => $data->duration,
			'durationUnit' => $data->durationUnit,
			'color' => $data->color,
			'externalId' => $data->externalId,
			'status' => $data->status,
		]);
		return $this->getSingle($this->db->lastInsertId());
	}

    /**
    * Sends a validated request to update a specific construction stage with new data in database.
    *
    * @param object $data Object with data to update.
    * @param string $id The identifier of the record that has to be updated.
    *
    * @return array Data array of updated construction stage
    * 
    * @throws Exception if there are no fields to update.
    */
	public function patch($data, $id)
	{
		$id = intval($id);
		$data = get_object_vars($data);

		// Call calcDuration function to calculate duration field (if endDate is null, then duration is also null).
		// First of all we have to check if there are necessary fields in $data. If it's false, we take it from object.
		if (isset($data['startDate'])) {
			$start = $data['startDate'];
		} else {
		    $start = $this->getSingle($id)[0]['startDate'];
		}
		
		if (isset($data['durationUnit'])) {
			$unit = $data['durationUnit'];
		} else {
		    $unit = $this->getSingle($id)[0]['durationUnit'];
		}
		
		// We use here array_key_exists because giving "endDate: null" is the same as not giving endDate at all.
		// And we need to separate this meanings
		if (array_key_exists('endDate', $data)) {
			$end = $data['endDate'];
			if ($end === null) {
				$duration = null;
			} else {
				$duration = $this->calcDuration($start, $end, $unit);
			}
		} else {
		    $end = $this->getSingle($id)[0]['endDate'];
		    $duration = $this->calcDuration($start, $end, $unit);
		}

		
    	// Build the SQL query
    	$query = 'UPDATE construction_stages SET ';	
    		
    	// Add to the $fields and $values arrays only those fields which are in $data
    	foreach ($data as $key => $value) {
    	
        	// Convert startDate and endDate to start_date and end_date respectively
        	if ($key === 'startDate') {
            	$key = 'start_date';
        	} elseif ($key === 'endDate') {
            	$key = 'end_date';
        	}
        	$fields[] = $key . ' = :' . $key;
        	$values[$key] = $value;
    	}
    	
    	if ($end !== null && $duration !== $this->getSingle($id)['duration']) {
    		$fields[] = 'duration = :duration';
    		$values['duration'] = $duration;
		} elseif ($duration === null && !in_array('duration = :duration', $fields)) {
    		$fields[] = 'duration = :duration';
    		$values['duration'] = null;
		}
    	
    	// Validate data to update
		foreach ($data as $key => $value) {
    		switch ($key) {
        		case 'name':
            		ConstructionStagesValidator::validateName($value);
            		break;
        		case 'startDate':
            		ConstructionStagesValidator::validateStartDate($value);
            		break;
        		case 'endDate':
        			$start_date = $this->getSingle($id)['start_date'];
            		ConstructionStagesValidator::validateEndDate($value, $start_date);
            		break;
        		case 'durationUnit':
           		    ConstructionStagesValidator::validateDurationUnit($value);
            		break;
        		case 'color':
            		ConstructionStagesValidator::validateColor($value);
            		break;
        		case 'externalId':
            		ConstructionStagesValidator::validateExternalId($value);
            		break;
        		case 'status':
            		ConstructionStagesValidator::validateStatus($value);
            		break;
        		default:
            		break;
    		}
		}
		
    	// If there are no fields to update, then rise an Exception.
    	if (empty($fields)) {
        	throw new Exception('There are no fields to update');
    	}    	
    	
    	// Concatenate $query and $fields in a SQL update request.
    	$query .= implode(', ', $fields) . ' WHERE ID = :id';

    	// Add the ID value to $values.
    	$values['id'] = $id;

    	// Prepare the SQL query.
    	$stmt = $this->db->prepare($query);

    	// Bind values to the query.
    	foreach ($values as $key => $value) {
        	$stmt->bindValue(':' . $key, $value);
    	}

    	// Execute the query.
    	$stmt->execute();
    	
    	// Return the updated record.
		return $this->getSingle($id);
	}

    /**
    * Sends a request to update a specific construction stage with "DELETED" status to database.
    *
    * @param string $id The identifier of the record that has to be updated.
    *
    * @return array Data array of updated construction stage
    */
	public function delete($id)
	{
		$stmt = $this->db->prepare("
			UPDATE construction_stages
			SET status = :status
			WHERE ID = :id
		");

		$stmt->execute([
			'status' => 'DELETED',
			'id' => $id
		]);

		return $this->getSingle($id);
	}
	
    /**
    * Calculates duration between given startDate and endDate which depends on .
    *
    * @param string $start_date The date of the construction stage begining.
    * @param string $end_date The date of the construction stage ending.
    * @param string $unit Temporal unit in which the time difference will be calculated.    
    *
    * @return float $duration result of calculation in given units.
    */
	public function calcDuration($start_date, $end_date, $unit) {
		
		if (!$start_date) {
			return null;
		}
		
		$start = new DateTime($start_date);
		if ($end_date) {
			$end = new DateTime($end_date);
			
			// Set minutes and seconds to 0 to exclude them from the calculation
			$start->setTime($start->format('H'), 0);
			$end->setTime($end->format('H'), 0);
			
			// Find the difference
			$difference = $start->diff($end);
			
			// Calculate the difference according to given durationUnit
			switch ($unit) {
				case "HOURS":
					$duration = $difference->days * 24 + $difference->h;
					break;
					
				case "DAYS":
					$duration = $difference->days + $difference->h / 24;
					break;
				
				// There is no current accuracy for "WEEKS" value in task 3
				case "WEEKS":
				    $duration = $difference->days / 7 + $difference->h/ 7 / 24;
				    break;
			}
		} else {
			$duration = null;
		}
		return $duration;
	}
}
