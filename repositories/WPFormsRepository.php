<?php

require_once dirname(__DIR__) . '/dto/ParticipantDTO.php';

/**
 * Repository to query and parse WPForms entries into typed DTOs.
 */
class WPFormsRepository
{
    /**
     * Get raw decoded fields for an entry ID.
     */
    public function getRawEntryData(int $entryId): ?array
    {
        if ($entryId <= 0 || !function_exists('wpforms')) {
            return null;
        }

        $entry = wpforms()->entry->get($entryId);
        if ($entry && !empty($entry->fields)) {
            return json_decode($entry->fields, true);
        }

        return null;
    }

    /**
     * Find participant data by WPForms entry ID and return typed ParticipantDTO.
     */
    public function findParticipant(int $entryId): ParticipantDTO
    {
        $fields = $this->getRawEntryData($entryId);
        if (empty($fields)) {
            return new ParticipantDTO();
        }

        $anrede   = $this->extractField($fields, 88);
        $title    = $this->extractField($fields, 90);
        $vorname  = $this->extractField($fields, 86);
        $nachname = $this->extractField($fields, 89);
        $email    = $this->extractField($fields, 93);
        $svr      = $this->extractField($fields, 29);

        // Salutation calculation (Austrian standard)
        $salutation = match (trim($anrede)) {
            'Herr'  => 'Sehr geehrter Herr',
            'Frau'  => 'Sehr geehrte Frau',
            default => 'Sehr geehrte(r) Frau/Herr',
        };

        // Address components (Field ID 35)
        $street      = '';
        $houseNumber = '';
        $city        = '';
        $zipCode     = '';
        $country     = 'Österreich';

        if (!empty($fields[35]) && is_array($fields[35])) {
            $street      = $fields[35]['address1'] ?? '';
            $city        = $fields[35]['city'] ?? '';
            $zipCode     = $fields[35]['postal'] ?? '';
            $country     = !empty($fields[35]['country']) ? $fields[35]['country'] : 'Österreich';
        }

        return new ParticipantDTO(
            salutation:  $salutation,
            anrede:      $anrede,
            title:       $title,
            firstName:   $vorname,
            lastName:    $nachname,
            email:       $email,
            svr:         $svr,
            street:      $street,
            houseNumber: $houseNumber,
            city:        $city,
            zipCode:     $zipCode,
            country:     $country
        );
    }

    /**
     * Extract field value from WPForms entry array by ID.
     */
    public function extractField(array $fields, int $fieldId): string
    {
        if (!isset($fields[$fieldId])) {
            return '';
        }

        $field = $fields[$fieldId];

        if (is_array($field)) {
            if (isset($field['value'])) {
                return trim((string) $field['value']);
            }
            if (isset($field['name'])) {
                return trim((string) $field['name']);
            }
        }

        return is_scalar($field) ? trim((string) $field) : '';
    }
}
