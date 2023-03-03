<?php

class ConstructionStages
{
	private $db;
	
	// Function which validates the data
	private function validateField($fieldName, $fieldValue, $startDate=null)
    	{
            switch ($fieldName) {
                
                // Name field validation (lenght must be less than 255)
                case 'name':
                    if (strlen($fieldValue) > 5) {
                        throw new Exception('Max chars amount is 255.');
                    }
                    break;
                
                // startDate field validation (must be in ISO format)    
                case 'startDate':
		    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $fieldValue)) {
    			throw new Exception('Invalid date format. Use ISO format, e.g. 2022-12-31T14:59:00Z');
		    }
		    break;
                
                // endDate field validation (must be either in ISO format or null)
                // Also there should be correct format of startDate and endDate cannot be earlier than startDate    
                case 'endDate':
                    if (!is_null($fieldValue)) {
                        $timestamp = strtotime($fieldValue);
                        if ($timestamp === false) {
                            throw new Exception('Invalid end_date format. Use ISO format, e.g. 2022-12-31T14:59:00Z');
                        }
                        $startTimestamp = strtotime($startDate);
                        if ($startTimestamp === false) {
                            throw new Exception('Invalid start date format. Use ISO format, e.g. 2022-12-31T14:59:00Z');
                        }
                        if ($timestamp < $startTimestamp) {
                            throw new Exception('End date cannot be earlier than start date');
                        }
                    }
                    break;
                
                // durationUnit field validation (must be either "HOURS", "DAYS" or "WEEK")
                case 'durationUnit':
                    if (!in_array($fieldValue, ['HOURS', 'DAYS', 'WEEKS'])) {
                        throw new Exception('Invalid duration unit. Use HOURS, DAYS, or WEEKS');
                    }
                    break;
                
                // color field validation (must be either in HEX format or null)     
                case 'color':
                    if (!is_null($fieldValue)) {
                        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $fieldValue)) {
                            throw new Exception('Invalid color format. Use HEX format, e.g. #FF0000');
                        }
                    }
                    break;
                
                // externalId field validation (lenght must be less than 255 or it must be null)    
                case 'externalId':
                    if (!is_null($fieldValue) && strlen($fieldValue) > 5) {
                        throw new Exception('External ID is too long');
                    }
                    break;
                
                // status field validation (must be either "NEW", "PLANNED" or "DELETED")      
                case 'status':
                    if (!in_array($fieldValue, ['NEW', 'PLANNED', 'DELETED'])) {
                        throw new Exception('Invalid status. Use NEW, PLANNED, or DELETED');
                    }
                    break;
            }
        }

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
		// Add a validation to post method
		$startDate = $data->startDate;
	        foreach ($data as $fieldName => $fieldValue) {
            	    $this->validateField($fieldName, $fieldValue, $startDate);
                }
	
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
		
		
		// Create an array which binds names of parameters with the 'data' array's keys
		$binds = array(
			':name' => 'name',
			':start_date' => 'start_date',
			':end_date' => 'end_date',
			':duration' => 'duration',
			':durationUnit' => 'durationUnit',
			':color' => 'color',
			':externalId' => 'externalId',
			':status' => 'status'
		);

		$binds_values = array_values($binds);

		// Check if the response with given id exists
		$this->getSingle($id); 
		
		// Add a validation to patch method
		/*
		"name": "asd11",
  		"startDate": "2021-09-10T00:00:00Z",
  		"endDate": "2022-09-10T00:00:00Z",
  		"duration": null,
  		"durationUnit": "HOURS",
  		"color": "#123456",
  		"externalId": "12345",
  		"status": "NEW"
		*/
		$startDate = $this->getSingle($id)[0]['startDate'];
		var_dump($startDate);
		foreach ($data as $key => $value) {
    		    $this->validateField($key, $value, $startDate);
		}
		
		// Prepare SQL query. Later we will concatenate $query and $fields in a SQL update request
		$query = 'UPDATE construction_stages SET ';
		$fields = array();

		// Add to the $fields only those fields wich are in $data except 'status' data
		foreach($data as $key => $value) 
		{
			if(in_array($key, $binds_values)) 
			{
				$fields[] = $key . ' = :' . $key;
			}
		}
		
		// If there are no fields to update, then rise an Exception
		if (empty($fields)) 
		{
			throw new Exception('There are no fields to update');
		}

		// Upgrade our 'query' concatenating with 'fields'
		$query .= implode(', ', $fields) . ' WHERE ID = :id';

		// Create a PDO object '$stmt'
		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);

		// Run through the array and if the current array's key is in data, we bind it to the appropriate request parameter
		foreach ($binds as $bind => $key)
		{
			if (isset($data[$key]))
			{
				$stmt->bindValue($bind, $data[$key]);
			}
		}

		// execute 'stmt' request
		$stmt->execute();

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
