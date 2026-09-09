<?php

/**
 * Participant Data Transfer Object (DTO)
 * Immutable representation of a participant/lead in X-SIEBEN CRM.
 */
class ParticipantDTO
{
    public function __construct(
        public readonly string $salutation = '',
        public readonly string $anrede = '',
        public readonly string $title = '',
        public readonly string $firstName = '',
        public readonly string $lastName = '',
        public readonly string $email = '',
        public readonly string $svr = '',
        public readonly string $street = '',
        public readonly string $houseNumber = '',
        public readonly string $city = '',
        public readonly string $zipCode = '',
        public readonly string $country = 'Österreich'
    ) {}

    /**
     * Get formatted full name with academic title.
     */
    public function getFullName(): string
    {
        $parts = array_filter([$this->title, $this->firstName, $this->lastName]);
        return implode(' ', $parts);
    }

    /**
     * Get formatted postal address.
     */
    public function getFormattedAddress(): string
    {
        $streetLine = trim($this->street . ' ' . $this->houseNumber);
        $cityLine = trim($this->zipCode . ' ' . $this->city);
        return trim($streetLine . ', ' . $cityLine);
    }

    /**
     * Convert DTO to array for backward-compatibility.
     */
    public function toArray(): array
    {
        return [
            'salutation'   => $this->salutation,
            'anrede'       => $this->anrede,
            'titel'        => $this->title,
            'vorname'      => $this->firstName,
            'nachname'     => $this->lastName,
            'email'        => $this->email,
            'svr'          => $this->svr,
            'street'       => $this->street,
            'house_number' => $this->houseNumber,
            'city'         => $this->city,
            'zip_code'     => $this->zipCode,
            'country'      => $this->country,
        ];
    }
}
