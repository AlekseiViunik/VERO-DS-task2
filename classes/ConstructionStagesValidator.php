<?php
require_once __DIR__ . '/ConstructionStagesValidator.php';

class ConstructionStagesValidator
{
    public static function validateName($name)
    {
        if (strlen($name) > 8) {
            throw new Exception('Name is too long');
        }
    }

    public static function validateStartDate($startDate)
    {
        if (!DateTime::createFromFormat(DateTime::ISO8601, $startDate)) {
            throw new Exception('Invalid start date format');
        }
    }

    public static function validateEndDate($endDate, $startDate)
    {
        if (!empty($endDate)) {
            if (!DateTime::createFromFormat(DateTime::ISO8601, $endDate)) {
                throw new Exception('Invalid end date format');
            }

            $startDateTime = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);

            if ($startDateTime > $endDateTime) {
                throw new Exception('End date must be later than start date');
            }
        }
    }

    public static function validateDurationUnit($durationUnit)
    {
        $allowedUnits = ['HOURS', 'DAYS', 'WEEKS'];

        if (!empty($durationUnit) && !in_array($durationUnit, $allowedUnits)) {
            throw new Exception('Invalid duration unit');
        }
    }

    public static function validateColor($color)
    {
        if (!empty($color) && !preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            throw new Exception('Invalid color format');
        }
    }

    public static function validateExternalId($externalId)
    {
        if (!empty($externalId) && strlen($externalId) > 8) {
            throw new Exception('External ID is too long');
        }
    }

    public static function validateStatus($status)
    {
        $allowedStatuses = ['NEW', 'PLANNED', 'DELETED'];

        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Invalid status');
        }
    }
}
