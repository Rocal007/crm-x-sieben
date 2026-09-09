<?php

require_once dirname(__DIR__) . '/dto/CourseDTO.php';
require_once dirname(__DIR__) . '/services/CoursePricingService.php';

/**
 * Repository to query course posts, ACF fields, and metadata into CourseDTOs.
 */
class CourseRepository
{
    private CoursePricingService $pricingService;

    public function __construct(?CoursePricingService $pricingService = null)
    {
        $this->pricingService = $pricingService ?? new CoursePricingService();
    }

    /**
     * Find course by post ID, optionally incorporating frozen snapshot dates from an entry.
     */
    public function findById(int $courseId, ?int $entryId = null): CourseDTO
    {
        if ($courseId <= 0) {
            return new CourseDTO(id: 0);
        }

        $title      = esc_html(get_the_title($courseId));
        $shortTitle = (string) get_field('title_im_slider', $courseId);
        $permalink  = (string) get_permalink($courseId);

        // Date resolution (Frozen snapshot vs Post meta fallback)
        $snapshot = null;
        if ($entryId && class_exists('CrmStatusRepository')) {
            $statusRepo = new CrmStatusRepository();
            $snapshot = $statusRepo->getCourseSnapshot($entryId);
        } elseif ($entryId && function_exists('crm_get_entry_course_dates')) {
            $snapshot = crm_get_entry_course_dates($entryId);
        }

        if (!empty($snapshot['start_date'])) {
            $startDate = date('d.m.Y', strtotime($snapshot['start_date']));
        } else {
            $rawStart = get_post_meta($courseId, 'start_datum', true);
            $startDate = $rawStart ? date('d.m.Y', strtotime($rawStart)) : '';
        }

        if (!empty($snapshot['end_date'])) {
            $endDate = date('d.m.Y', strtotime($snapshot['end_date']));
        } else {
            $rawEnd = get_post_meta($courseId, 'end_datum', true);
            $endDate = $rawEnd ? date('d.m.Y', strtotime($rawEnd)) : '';
        }

        // Pricing calculations
        $netPrice = (float) get_post_meta($courseId, 'kosten', true);
        $grossPrice = $this->pricingService->calculateGrossPrice($netPrice);
        $totalLE = (float) get_post_meta($courseId, 'lehreinheiten_gesamt', true);
        $pricePerLE = $this->pricingService->calculatePricePerLE($grossPrice, $totalLE);

        // Additional course attributes
        $targetGroup  = sanitize_text_field((string) get_field('teilnehmeruberblick', $courseId));
        $description  = (string) get_post_meta($courseId, 'angebot_beschreibung', true);
        $courseType   = (string) get_post_meta($courseId, 'kurstyp', true);
        $courseTimes  = $this->buildDays(get_field('kurszeiten', $courseId));
        $selfStudy    = $this->buildDays(get_field('selbstudium', $courseId), true);
        $prerequisites= get_field('voraussetzungen_abschluss', $courseId);
        $trainers     = get_field('trainer_details', $courseId);

        // Certifications repeater
        $certifications = [];
        if (function_exists('have_rows') && have_rows('zertifizierungen', $courseId)) {
            while (have_rows('zertifizierungen', $courseId)) {
                the_row();
                $certifications[] = [
                    'name'  => get_sub_field('name-zert'),
                    'preis' => get_sub_field('preis'),
                    'ust'   => get_sub_field('Ust_satz'),
                ];
            }
        }

        return new CourseDTO(
            id:             $courseId,
            title:          $title,
            shortTitle:     $shortTitle,
            permalink:      $permalink,
            startDate:      $startDate,
            endDate:        $endDate,
            netPrice:       $netPrice,
            grossPrice:     $grossPrice,
            totalLE:        $totalLE,
            pricePerLE:     $pricePerLE,
            courseType:     $courseType,
            targetGroup:    $targetGroup,
            description:    $description,
            courseTimes:    $courseTimes,
            selfStudy:      $selfStudy,
            certifications: $certifications,
            prerequisites:  $prerequisites,
            trainers:       $trainers
        );
    }

    /**
     * Helper to map weekday names to timeslot strings.
     */
    public function buildDays(mixed $daysArray, bool $suffix = false): array
    {
        $map = [];
        $timeslot = "09.00 - 17.00 Uhr";
        $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

        foreach ($weekdays as $day) {
            if (is_array($daysArray) && in_array($day, $daysArray, true)) {
                $key = strtolower($day) . ($suffix ? '_s' : '');
                $map[$key] = $timeslot;
            }
        }
        return $map;
    }
}
