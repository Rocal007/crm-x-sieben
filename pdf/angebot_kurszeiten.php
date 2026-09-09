<?php

/**
 * Generates both the Offer and the Course Times Confirmation PDFs.
 *
 * @param int $entry_id
 * @param int $course_id
 */
function xsieben_angebot_kurszeiten_pdf($entry_id, $course_id)
{
    
    // Generate the Offer PDF
    xsieben_offer_pdf($entry_id, $course_id, false);
        // Generate the Course Times Confirmation PDF
    xsieben_kurszeitenbestaetigung_pdf($entry_id, $course_id, false);
}
