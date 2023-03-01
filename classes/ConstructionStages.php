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
	public function update($id, $data) 
	{	
		// Create an array which binds names of parameters with the 'data' array's keys
		$binds = array(
			':name' => 'name',
			':start_date' => 'startDate',
			':end_date' => 'endDate',
			':duration' => 'duration',
			':durationUnit' => 'durationUnit',
			':color' => 'color',
			':externalId' => 'externalId',
			':status' => 'status'
		);

		$binds_values = array_values($binds);

		// Check if the response with given id exists
		$this->getSingle($id); 
		
		// Prepare SQL query. Later we will concatenate $query and $fields in a SQL update request
		$query = 'UPDATE construction_stages SET ';
		$fields = array();

		// Add to the $fields only those fields wich are in $data except 'status' data
		foreach($data as $key => $value) 
		{
			if(in_array($key, $binds_values)) 
			{
				if ($key === 'status') 
				{
					$status = strtoupper($value);
					if (!in_array($status, ['NEW', 'PLANNED', 'DELETED'])) 
					{
						throw new Exception('Invalid status');
					}
				}
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
		$stmt->bindValue(':id', $id);

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