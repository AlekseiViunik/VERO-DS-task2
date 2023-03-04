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

	// Add an update function
	public function patch($data, $id)
	{
		$id = intval($id);
		$data = get_object_vars($data);
		
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
        /*
        "id": 117,
        "name": "abcdef",
        "startDate": "2024-09-10T00:10:00Z",
        "endDate": "2026-09-10T00:10:00Z",
        "durationUnit": "HOURS",
        "color": "#FFFFFF",
        "externalId": "54321",
        "status": "NEW"   
        */ 	
        var_dump($data);
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

    	// If there are no fields to update, then rise an Exception
    	if (empty($fields)) {
        	throw new Exception('There are no fields to update');
    	}    	
    	
    	// Concatenate $query and $fields in a SQL update request
    	$query .= implode(', ', $fields) . ' WHERE ID = :id';

    	// Add the ID value to $values
    	$values['id'] = $id;

    	// Prepare the SQL query
    	$stmt = $this->db->prepare($query);

    	// Bind values to the query
    	foreach ($values as $key => $value) {
        	$stmt->bindValue(':' . $key, $value);
    	}

    	// Execute the query
    	$stmt->execute();
    	
    	// Return the updated record
		return $this->getSingle($id);
	}

	// Add a delete function
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
}
