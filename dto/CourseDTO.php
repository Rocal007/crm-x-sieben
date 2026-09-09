<?php

/**
 * Course Data Transfer Object (DTO)
 * Immutable representation of course data in X-SIEBEN CRM.
 */
class CourseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title = '',
        public readonly string $shortTitle = '',
        public readonly string $permalink = '',
        public readonly string $startDate = '',
        public readonly string $endDate = '',
        public readonly float $netPrice = 0.0,
        public readonly float $grossPrice = 0.0,
        public readonly float $totalLE = 0.0,
        public readonly float $pricePerLE = 0.0,
        public readonly string $courseType = '',
        public readonly string $targetGroup = '',
        public readonly string $description = '',
        public readonly array $courseTimes = [],
        public readonly array $selfStudy = [],
        public readonly array $certifications = [],
        public readonly mixed $prerequisites = null,
        public readonly mixed $trainers = null
    ) {}

    /**
     * Get net price formatted in Austrian standard (e.g. "1.250,00").
     */
    public function getFormattedNetPrice(): string
    {
        return number_format($this->netPrice, 2, ',', '');
    }

    /**
     * Get gross price formatted in Austrian standard (e.g. "1.500,00").
     */
    public function getFormattedGrossPrice(): string
    {
        return number_format($this->grossPrice, 2, ',', '');
    }

    /**
     * Convert DTO to array for backward-compatibility.
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'short_title'    => $this->shortTitle,
            'permalink'      => $this->permalink,
            'start_date'     => $this->startDate,
            'end_date'       => $this->endDate,
            'net_price'      => $this->netPrice,
            'gross_price'    => $this->grossPrice,
            'total_le'       => $this->totalLE,
            'price_per_le'   => $this->pricePerLE,
            'course_type'    => $this->courseType,
            'target_group'   => $this->targetGroup,
            'description'    => $this->description,
            'course_times'   => $this->courseTimes,
            'self_study'     => $this->selfStudy,
            'certifications' => $this->certifications,
        ];
    }
}
