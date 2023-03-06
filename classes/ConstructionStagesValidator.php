<?php

/**
* A class to incoming data validation.
*/

class ConstructionStagesValidator
{
    /**
    * Validates the length of the name's field.
    *
    * @param string $name Name of construction stage.
    * 
    * @throws Exception if name is too long (more than 255).
    */
    public static function validateName($name)
    {
        if (strlen($name) > 255) {
            throw new Exception('Name is too long. Its length should be less than 255.');
        }
    }
    
    /**
    * Validates the format of the startDate field.
    *
    * @param string $startDate Date of the construction stage's begining.
    * 
    * @throws Exception If the format of the startDate is incorrect.
    */
    public static function validateStartDate($startDate)
    {
        if (!DateTime::createFromFormat(DateTime::ISO8601, $startDate)) {
            throw new Exception('Invalid start date format. Please write it in ISO format (e.g. 2025-09-10T00:10:00Z).');
        }
    }
	
	/**
    * Validates the format of the endDate field.
    *
    * @param string $endDate Date of the construction stage's ending.
    * @param string $startDate Date of the construction stage's begining.
    * 
    * @throws Exception If the format of the endDate is not null or incorrect.
    * @throws Exception If endDate is earlier than startDate.
    */
    public static function validateEndDate($endDate, $startDate)
    {
        if (!empty($endDate)) {
            if (!DateTime::createFromFormat(DateTime::ISO8601, $endDate)) {
                throw new Exception('Invalid end date format. Please write it in ISO format (e.g. 2025-09-10T00:10:00Z).');
            }

            $startDateTime = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);

            if ($startDateTime > $endDateTime) {
                throw new Exception('End date must be later than start date.');
            }
        }
    }
	
	/**
    * Validates the type of the durationUnit field.
    *
    * @param string $durationUnit Temporal type for duration between startDate and endDate.
    * 
    * @throws Exception If the given type is not in the allowed-types list.
    */
    public static function validateDurationUnit($durationUnit)
    {
        $allowedUnits = ['HOURS', 'DAYS', 'WEEKS'];

        if (!empty($durationUnit) && !in_array($durationUnit, $allowedUnits)) {
            throw new Exception('Invalid duration unit. Should be HOURS, DAYS or WEEKS.');
        }
    }

	/**
    * Validates the type of the color field.
    *
    * @param string $color Color field.
    * 
    * @throws Exception If the given color doesn't match the HEX format.
    */
    public static function validateColor($color)
    {
        if (!empty($color) && !preg_match("/^#[a-f0-9]{6}$/i", $color)) {
            throw new Exception('Invalid color format. It should be in HEX format (e.g. #0A3C9F).');
        }
    }

    /**
    * Validates the length of the externalId's field.
    *
    * @param string $externalId Additional ID, if needed.
    * 
    * @throws Exception if id is too long (more than 255).
    */
    public static function validateExternalId($externalId)
    {
        if (!empty($externalId) && strlen($externalId) > 255) {
            throw new Exception('External ID is too long. Its length should be less than 255.');
        }
    }

	/**
    * Validates the type of the status.
    *
    * @param string $status Current construction stage's status.
    * 
    * @throws Exception If the given status is not in the allowed-statuses list.
    */
    public static function validateStatus($status)
    {
        $allowedStatuses = ['NEW', 'PLANNED', 'DELETED'];

        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Invalid status. Should be NEW, PLANNED or DELETED.');
        }
    }
}
