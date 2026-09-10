<?php
/**
 * CRM Settings, E-Mail Editor & PDF Editor
 * 
 * Standalone CRM Configuration module for WordPress.
 * Manages Email templates, PDF snippets, and general CRM settings.
 *
 * @version 2.10.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Determine the category of a custom field ('email' or 'pdf').
 *
 * @param array $field
 * @return string
 */
function crm_get_field_category(array $field): string
{
    if (!empty($field['category']) && in_array($field['category'], ['email', 'pdf'], true)) {
        return $field['category'];
    }

    $title = strtolower(trim($field['title'] ?? ''));

    // Check for E-Mail keywords
    if (
        strpos($title, 'e-mail') !== false ||
        strpos($title, 'email') !== false ||
        strpos($title, 'mail') !== false ||
        strpos($title, 'signatur') !== false ||
        strpos($title, 'footer') !== false ||
        strpos($title, 'buchung e-mail') !== false ||
        strpos($title, 'beratung e-mail') !== false ||
        strpos($title, 'anfrage') !== false
    ) {
        return 'email';
    }

    return 'pdf';
}

/**
 * Defines default demographical and CI values for the CRM and training institute.
 *
 * @return array<string, string>
 */
function crm_get_general_defaults(): array
{
    $default_logo = get_template_directory_uri() . '/inc/core/crm/assets/xsieben_logo.png';
    $current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
    $admin_email  = ($current_user && !empty($current_user->user_email)) ? $current_user->user_email : 'abrauer@x-sieben.at';

    return [
        // CI & Branding
        'logo_url'               => $default_logo,
        'logo_secondary_url'     => '',
        'ci_primary_color'       => '#007C90',
        'ci_secondary_color'     => '#0284c7',
        'ci_accent_color'        => '#0f172a',
        'company_claim'          => 'Wirtschaftstraining, Seminare & Personenzertifizierungen',
        'company_accreditations' => 'pma / IPMA®, SystemCERT (ISO 17024), TÜV Austria, wba, CERT NÖ, AMS',

        // Schulungsinstitut & Demographie
        'company_name'           => 'X SIEBEN Wirtschaftstraining GmbH',
        'company_short_name'     => 'X SIEBEN',
        'company_legal_form'     => 'GmbH',
        'company_management'     => 'Mag. Dr. Johannes Gasberger',

        // Kanzleisitz / Hauptadresse (Wr. Neustadt / Lichtenwörth)
        'company_street'         => 'Kurzegasse 7',
        'company_zip'            => '2493',
        'company_city'           => 'Lichtenwörth',
        'company_country'        => 'Österreich',

        // Schulungszentrum / Standort Wien
        'location_wien_name'     => 'Seminarzentrum Wien',
        'location_wien_street'   => 'Rochusgasse 6',
        'location_wien_zip'      => '1030',
        'location_wien_city'     => 'Wien',
        'location_wien_notice'   => 'Online Unterricht | vor Ort in unseren Veranstaltungsräumen | Blended Learning',

        // Kontaktdaten
        'company_phone'          => '0800 700 170',
        'company_email'          => 'office@x-sieben.at',
        'company_website'        => 'https://x-sieben.at',

        // Backoffice Kontakt
        'backoffice_name'        => 'Anna Brauer',
        'backoffice_email'       => 'abrauer@x-sieben.at',
        'backoffice_phone'       => '0800 700 170',

        // Behördliche Daten & Rechtliches
        'company_uid'            => 'ATU76624137',
        'company_fn'             => 'FN 550277 g',
        'company_court'          => 'Landesgericht Wiener Neustadt',
        'company_chamber'        => 'Wirtschaftskammer Niederösterreich / Wien',
        'company_bank'           => 'Erste Bank | IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN',

        // Rechtliche Links
        'legal_agb_url'          => 'https://x-sieben.at/wp-content/uploads/2025/09/AGB_X_SIEBEN_2025.pdf',
        'legal_privacy_url'      => 'https://x-sieben.at/datenschutzerklaerung/',
        'legal_imprint_url'      => 'https://x-sieben.at/impressum/',

        // Mailer
        'test_email'             => get_option('crm_test_email', $admin_email),
    ];
}

/**
 * Retrieve all general demographic and CI settings, merged with defaults.
 *
 * @return array<string, string>
 */
function crm_get_general_settings(): array
{
    $defaults = crm_get_general_defaults();
    $saved    = get_option('crm_general_settings', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    // Also respect legacy crm_test_email if set
    $legacy_test = get_option('crm_test_email');
    if (!empty($legacy_test) && empty($saved['test_email'])) {
        $saved['test_email'] = $legacy_test;
    }
    return wp_parse_args($saved, $defaults);
}

/**
 * Retrieve a specific general demographic or CI setting by key.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function crm_get_general_setting(string $key, $default = '')
{
    $settings = crm_get_general_settings();
    if (array_key_exists($key, $settings) && $settings[$key] !== '') {
        return $settings[$key];
    }
    return $default;
}

/**
 * Defines all standard default PDF fields, templates, document affiliations, badges, and descriptions.
 *
 * @return array<string, array{content: string, doc: string, badge: string, desc: string}>
 */
function crm_get_default_pdf_fields(): array
{
    return [
        // --- Kurszeitenbestätigung (KB) ---
        'KB - Titel' => [
            'content' => 'Bestätigung Kurszeiten',
            'doc'     => 'kb',
            'badge'   => 'KB - Titel',
            'desc'    => 'Dokumententitel der Kurszeitenbestätigung.',
        ],
        'KB - Kursinstitut Name' => [
            'content' => 'X SIEBEN Wirtschaftstraining GmbH',
            'doc'     => 'kb',
            'badge'   => 'KB - Institut',
            'desc'    => 'Name des durchführenden Kursinstituts in der KB.',
        ],
        'KB - Schulungsort' => [
            'content' => 'Rochusgasse 6, 1030 Wien bzw. online',
            'doc'     => 'kb',
            'badge'   => 'KB - Schulungsort',
            'desc'    => 'Standard-Schulungsort (Adresse) in der KB.',
        ],
        'KB - Hinweistext' => [
            'content' => 'Bei unregelmäßigen Kurszeiten ist ein Ablaufplan der einzelnen Kurswochen beizulegen.',
            'doc'     => 'kb',
            'badge'   => 'KB - Hinweistext',
            'desc'    => 'Hinweistext unter der Kurszeitentabelle.',
        ],
        'KB - Signatur Institut' => [
            'content' => "Wien, {current_date}<br>Unterschrift, Stampiglie Kursinstitut",
            'doc'     => 'kb',
            'badge'   => 'KB - Signatur Institut',
            'desc'    => 'Unterschriftenzeile und Stampiglie des Kursinstituts.',
        ],
        'KB - Signatur Kunde' => [
            'content' => 'Ort, Datum, Unterschrift, Kunde/Kundin',
            'doc'     => 'kb',
            'badge'   => 'KB - Signatur Kunde',
            'desc'    => 'Unterschriftenzeile für den/die Kursteilnehmer/in.',
        ],

        // --- Teilnahmebestätigung (TB) ---
        'TB - Titel' => [
            'content' => 'Teilnahmebestätigung',
            'doc'     => 'tb',
            'badge'   => 'TB - Titel',
            'desc'    => 'Dokumententitel der Teilnahmebestätigung.',
        ],
        'TB - Einleitung' => [
            'content' => 'Wir bestätigen, dass',
            'doc'     => 'tb',
            'badge'   => 'TB - Einleitung',
            'desc'    => 'Einleitende Formel vor den Teilnehmerdaten.',
        ],
        'TB - Betrieb Name' => [
            'content' => 'X SIEBEN Wirtschaftstraining GmbH',
            'doc'     => 'tb',
            'badge'   => 'TB - Betrieb Name',
            'desc'    => 'Bezeichnung des Betriebes / der Ausbildungseinrichtung in der TB.',
        ],
        'TB - Betrieb Strasse' => [
            'content' => 'Kurzegasse 7',
            'doc'     => 'tb',
            'badge'   => 'TB - Betrieb Str.',
            'desc'    => 'Strasse und Hausnummer des Kanzleisitzes / Betriebes.',
        ],
        'TB - Betrieb PLZ' => [
            'content' => '2493',
            'doc'     => 'tb',
            'badge'   => 'TB - Betrieb PLZ',
            'desc'    => 'Postleitzahl des Kanzleisitzes.',
        ],
        'TB - Betrieb Ort' => [
            'content' => 'Lichtenwörth',
            'doc'     => 'tb',
            'badge'   => 'TB - Betrieb Ort',
            'desc'    => 'Ort des Kanzleisitzes.',
        ],
        'TB - Schulungsort Strasse' => [
            'content' => 'Rochusgasse 6 bzw. online',
            'doc'     => 'tb',
            'badge'   => 'TB - Schulungsort Str.',
            'desc'    => 'Strasse des Seminarzentrums in Wien.',
        ],
        'TB - Schulungsort PLZ' => [
            'content' => '1030',
            'doc'     => 'tb',
            'badge'   => 'TB - Schulungsort PLZ',
            'desc'    => 'PLZ des Wiener Schulungsortes.',
        ],
        'TB - Schulungsort Ort' => [
            'content' => 'Wien',
            'doc'     => 'tb',
            'badge'   => 'TB - Schulungsort Ort',
            'desc'    => 'Ort des Schulungszentrums.',
        ],
        'TB - Teilnahme Text' => [
            'content' => 'an der Ausbildung: <strong>"{title}"</strong> ({anzahl_le} LE) teilgenommen hat.',
            'doc'     => 'tb',
            'badge'   => 'TB - Teilnahme Text',
            'desc'    => 'Bestätigungssatz über die Teilnahme am Lehrgang.',
        ],
        'TB - Datum' => [
            'content' => 'Datum: {current_date}',
            'doc'     => 'tb',
            'badge'   => 'TB - Datum',
            'desc'    => 'Datumszeile auf der Teilnahmebestätigung.',
        ],
        'TB - Unterschrift Label' => [
            'content' => 'Unterschrift:',
            'doc'     => 'tb',
            'badge'   => 'TB - Signatur Label',
            'desc'    => 'Bezeichnung des Unterschriftenfeldes auf der TB.',
        ],

        // --- Diplom ---
        'Diplom - Titel' => [
            'content' => 'Diplom',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Titel',
            'desc'    => 'Große Hauptüberschrift des Abschlussdiploms.',
        ],
        'Diplom - Lehrgang Text' => [
            'content' => 'Hat den Lehrgang',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Lehrgang',
            'desc'    => 'Zwischentitel über dem Kursnamen.',
        ],
        'Diplom - Einheiten Text' => [
            'content' => '{anzahl_le} Lehreinheiten à 45 Minuten',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Einheiten',
            'desc'    => 'Textzeile für Lehreinheiten / Umfang.',
        ],
        'Diplom - Zeitraum Text' => [
            'content' => 'Im Zeitraum vom {start_datum} bis zum {end_datum}',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Zeitraum',
            'desc'    => 'Textzeile für Lehrgangszeitraum (Start- bis Enddatum).',
        ],
        'Diplom - Abschluss Text' => [
            'content' => '{diplom_success}abgeschlossen',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Erfolg',
            'desc'    => 'Abschlussstatus (z. B. "mit gutem Erfolg abgeschlossen").',
        ],
        'Diplom - Datum Unterschrift' => [
            'content' => "Datum: {current_date}<br><br>Unterschrift:",
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Signatur',
            'desc'    => 'Unterschriftenblock und Ausstellungsdatum.',
        ],
        'Diplom - Footer' => [
            'content' => "UID: ATU76624137 | Firmenbuchgericht: Landesgericht Wiener Neustadt\nFirmenbuchnummer: FN 550277 g",
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Footer',
            'desc'    => 'Rechtlicher Fußzeilentext auf Seite 1 des Diploms.',
        ],

        // --- Angebot & Anmeldung ---
        'Angebot - Einleitung' => [
            'content' => 'Danke für Ihr Interesse und willkommen bei der beliebten X SIEBEN Veranstaltung {title} mit lernförderndem Kleingruppen-Unterricht.<br><br>Diese Veranstaltung fokussiert auf {zielgruppe}',
            'doc'     => 'angebot',
            'badge'   => 'Angebot - Einleitung',
            'desc'    => 'Einleitungstext auf Seite 1 des Angebots-PDFs.',
        ],
        'Angebot - Grußformel' => [
            'content' => "Ich freue mich über Ihre Rückmeldung / Buchung.<br>\nMit freundlichen Grüßen,",
            'doc'     => 'angebot',
            'badge'   => 'Angebot - Grußformel',
            'desc'    => 'Grußformel vor der Signatur auf Seite 1 des Angebots.',
        ],
        'Angebot - Ort und Durchführung' => [
            'content' => '<table class="text"><tr><td style="width:92%; font-size: 10.5pt;"><strong>ORT:</strong> X SIEBEN Wirtschaftstraining, Rochusgasse 6 in 1030 Wien</td></tr></table><div style="font-size:12pt">&nbsp;</div><table class="text"><tr><td style="line-height: 16pt; color: #334155;">Durchführung unserer Schulungen: Online Unterricht | vor Ort in unseren Veranstaltungsräumen | Blended Learning<br><span style="color: #475569; font-size: 9.5pt;">Hinweis: Die Schulung wird bis zur TeilnehmerInnen-Anzahl von drei Personen adäquat verkürzt, wobei alle Inhalte vermittelt werden.</span></td></tr></table>',
            'doc'     => 'angebot',
            'badge'   => 'Angebot - Schulungsort',
            'desc'    => 'Schulungsort und Durchführungshinweis im Angebots-PDF.',
        ],
        'AGB text' => [
            'content' => 'Bitte beachten Sie unsere Allgemeinen Geschäftsbedingungen (AGB). Mit Ihrer Buchung akzeptieren Sie unsere Richtlinien.',
            'doc'     => 'angebot',
            'badge'   => 'AGB & Klausel',
            'desc'    => 'Rechtlicher Anmelde- und AGB-Hinweis im Anhang von Angebot & Anmeldung.',
        ],
        'Bankverbindung' => [
            'content' => '<strong>Bankverbindung:</strong> Erste Bank | IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN',
            'doc'     => 'angebot',
            'badge'   => 'Bankverbindung',
            'desc'    => 'Bank- und Überweisungsdaten im PDF-Angebot und der Honorarnote.',
        ],
        'Anhang 2 | Exklusive Zusatzleistungen' => [
            'content' => '3-fach sicher mit unserer Durchführungsgarantie, Zufriedenheitsgarantie und Zertifizierungsbegleitung.',
            'doc'     => 'angebot',
            'badge'   => 'Anhang 2',
            'desc'    => 'Details zu 3-fach sicher, Garantien, Storno und Ersatzteilnehmern.',
        ],
        'Durchführungs Garantie' => [
            'content' => 'Unsere Seminare finden bereits ab 1 Person garantiert statt.',
            'doc'     => 'angebot',
            'badge'   => 'Garantie',
            'desc'    => 'Durchführungsgarantie ab Kleingruppe im PDF-Angebot.',
        ],
        'Angebot PS' => [
            'content' => 'PS: Profitieren Sie von unseren flexiblen Teilzahlungsmöglichkeiten und ProvenExpert-Top-Bewertungen.',
            'doc'     => 'angebot',
            'badge'   => 'Postskriptum',
            'desc'    => 'PS-Hinweis auf ProvenExpert-Bewertungen und Klarna-Ratenzahlung.',
        ],
        'Teilnahme_fee' => [
            'content' => 'Die Teilnahmegebühr ist 14 Tage vor Kursbeginn spesenfrei fällig.',
            'doc'     => 'angebot',
            'badge'   => 'Zahlungsfristen',
            'desc'    => 'Konditionen zur Zahlungsfälligkeit im PDF.',
        ],

        // --- Honorarnote ---
        'Honorarnote - Titel' => [
            'content' => 'Honorarnote',
            'doc'     => 'invoice',
            'badge'   => 'Honorarnote - Titel',
            'desc'    => 'Dokumententitel der Honorarnote / Rechnung.',
        ],
        'Honorarnote - Einleitung' => [
            'content' => '<p>Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</p>',
            'doc'     => 'invoice',
            'badge'   => 'Honorarnote - Intro',
            'desc'    => 'Einleitender Text über der Leistungstabelle.',
        ],
        'Honorarnote - Zahlungsanweisung' => [
            'content' => 'Bitte überweisen Sie den Betrag bis zum [Datum] auf das Konto von X SIEBEN Wirtschaftstraining GmbH.<br>IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN',
            'doc'     => 'invoice',
            'badge'   => 'Honorarnote - Zahlung',
            'desc'    => 'Zahlungsfrist und Kontoverbindung auf der Honorarnote.',
        ],
    ];
}

/**
 * Get usage badge, explanation, document category, and badge color for CRM fields.
 *
 * @param string $title
 * @return array ['badge' => string, 'desc' => string, 'color' => string, 'doc' => string]
 */
function crm_get_field_usage_info(string $title): array
{
    $key = strtolower(trim($title));

    // 1. Email Templates Map
    $email_map = [
        'e-mail angebot' => [
            'badge' => 'Angebot (E-Mail)',
            'desc'  => 'Wird beim Versenden des Angebots im Mailer als Nachrichtentext geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail anmeldebestätigung' => [
            'badge' => 'Anmeldung (E-Mail)',
            'desc'  => 'Wird bei Buchungs- und Anmeldebestätigungen als Nachrichtentext geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail diplom' => [
            'badge' => 'Diplom (E-Mail)',
            'desc'  => 'Wird beim Versenden des Diploms / Abschlusszertifikats geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail kursantrittsbestätigung' => [
            'badge' => 'Kursantritt (E-Mail)',
            'desc'  => 'Wird beim Versenden der Kursantrittsbestätigung geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail teilnahmebestätigung - allgemein' => [
            'badge' => 'TB Standard (E-Mail)',
            'desc'  => 'Wird bei regulären Teilnahmebestätigungen (TB) verwendet.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail teilnahmebestätigung - förderung' => [
            'badge' => 'TB Förderung (E-Mail)',
            'desc'  => 'Wird bei Förderungsbestätigungen (AMS, waff, Förderstellen) verwendet.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail signatur' => [
            'badge' => 'Signatur (Global)',
            'desc'  => 'Standard-Signatur von Anna Brauer / Backoffice in allen E-Mails.',
            'color' => '#059669',
            'doc'   => 'email',
        ],
        'e-mail-footer' => [
            'badge' => 'Footer (Global)',
            'desc'  => 'Rechtlicher E-Mail-Footer mit Hotline, ProvenExpert und Kanzleisitz.',
            'color' => '#059669',
            'doc'   => 'email',
        ],
        'anmeldung buchung e-mail text' => [
            'badge' => 'Buchungshinweis (E-Mail)',
            'desc'  => 'Ergänzender Hinweistext für Buchungen und Rückmeldungen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'angebot e-mail hinweis' => [
            'badge' => 'Gültigkeit (E-Mail)',
            'desc'  => 'Hinweis auf limitierte Gruppengröße und Angebotsfrist.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
    ];

    if (isset($email_map[$key])) {
        return $email_map[$key];
    }

    // 2. Check in standard default PDF fields
    $default_pdf = crm_get_default_pdf_fields();
    $doc_colors = [
        'kb'      => '#0d9488', // Teal
        'tb'      => '#059669', // Emerald
        'diplom'  => '#d97706', // Amber / Gold
        'angebot' => '#7c3aed', // Purple
        'invoice' => '#db2777', // Magenta
    ];

    foreach ($default_pdf as $def_title => $def_info) {
        if (strtolower(trim($def_title)) === $key) {
            $doc = $def_info['doc'] ?? 'angebot';
            return [
                'badge' => $def_info['badge'] . ' (PDF)',
                'desc'  => $def_info['desc'],
                'color' => $doc_colors[$doc] ?? '#7c3aed',
                'doc'   => $doc,
            ];
        }
    }

    // 3. Fallback prefix detection
    $is_email = crm_get_field_category(['title' => $title]) === 'email';
    if ($is_email) {
        return [
            'badge' => 'E-Mail Baustein',
            'desc'  => 'Benutzerdefinierter E-Mail-Textbaustein.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ];
    }

    // PDF prefix detection
    $doc   = 'general';
    $color = '#64748b';
    $badge = 'PDF Baustein';

    if (strpos($key, 'kb - ') === 0 || strpos($key, 'kurszeiten') !== false) {
        $doc   = 'kb';
        $color = '#0d9488';
        $badge = 'KB Baustein (PDF)';
    } elseif (strpos($key, 'tb - ') === 0 || strpos($key, 'teilnahme') !== false) {
        $doc   = 'tb';
        $color = '#059669';
        $badge = 'TB Baustein (PDF)';
    } elseif (strpos($key, 'diplom') !== false) {
        $doc   = 'diplom';
        $color = '#d97706';
        $badge = 'Diplom Baustein (PDF)';
    } elseif (strpos($key, 'angebot') !== false || strpos($key, 'agb') !== false || strpos($key, 'anmeldung') !== false || strpos($key, 'bank') !== false || strpos($key, 'garantie') !== false || strpos($key, 'anhang') !== false || strpos($key, 'teilnahme_fee') !== false) {
        $doc   = 'angebot';
        $color = '#7c3aed';
        $badge = 'Angebot Baustein (PDF)';
    } elseif (strpos($key, 'honorarnote') !== false || strpos($key, 'invoice') !== false || strpos($key, 'rechnung') !== false) {
        $doc   = 'invoice';
        $color = '#db2777';
        $badge = 'Honorarnote Baustein (PDF)';
    }

    return [
        'badge' => $badge,
        'desc'  => 'Benutzerdefinierter Textbaustein für PDF-Vorlagen.',
        'color' => $color,
        'doc'   => $doc,
    ];
}

/**
 * Retrieve sample Course ID and Entry ID for PDF preview rendering.
 *
 * @return array{course_id: int, course_title: string, entry_id: int, client_name: string}
 */
function crm_get_preview_sample_data(): array
{
    $courses = get_posts([
        'post_type'      => 'courses',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    ]);
    $course_id    = !empty($courses) ? $courses[0]->ID : 0;
    $course_title = !empty($courses) ? $courses[0]->post_title : 'Musterkurs';

    global $wpdb;
    $table       = $wpdb->prefix . 'wpforms_entries';
    $entry_id    = 0;
    $client_name = 'Musterteilnehmer';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
        $entry = $wpdb->get_row("SELECT entry_id, fields FROM {$table} WHERE form_id = 60468 ORDER BY entry_id DESC LIMIT 1");
        if (!$entry) {
            $entry = $wpdb->get_row("SELECT entry_id, fields FROM {$table} ORDER BY entry_id DESC LIMIT 1");
        }
        if ($entry) {
            $entry_id = intval($entry->entry_id);
            if (!empty($entry->fields)) {
                $fields_data = json_decode($entry->fields, true);
                if (is_array($fields_data)) {
                    $fn = $fields_data[86]['value'] ?? '';
                    $ln = $fields_data[89]['value'] ?? '';
                    if ($fn || $ln) {
                        $client_name = trim("$fn $ln");
                    }
                }
            }
        }
    }

    return [
        'course_id'    => $course_id,
        'course_title' => $course_title,
        'entry_id'     => $entry_id,
        'client_name'  => $client_name,
    ];
}

/**
 * Main function to render the CRM settings page.
 */
function render_crm_settings_page()
{
    // Determine active tab
    $current_page = sanitize_text_field($_GET['page'] ?? 'crm-settings');
    $tab_param    = sanitize_text_field($_GET['tab'] ?? '');

    if ($current_page === 'crm-emails') {
        $active_tab = 'emails';
    } elseif ($current_page === 'crm-pdf') {
        $active_tab = 'pdf';
    } elseif ($tab_param !== '') {
        $active_tab = in_array($tab_param, ['emails', 'pdf', 'general'], true) ? $tab_param : 'emails';
    } else {
        // Default tab for crm-settings
        $active_tab = 'general';
    }

    // Handle Form Submissions
    if (isset($_POST['crm_settings_nonce']) && wp_verify_nonce($_POST['crm_settings_nonce'], 'save_crm_settings')) {
        $saved_tab = sanitize_text_field($_POST['crm_active_tab'] ?? $active_tab);

        // 1. General settings
        if ($saved_tab === 'general' || isset($_POST['submit_general']) || isset($_POST['crm_general'])) {
            $general_input = isset($_POST['crm_general']) && is_array($_POST['crm_general']) ? $_POST['crm_general'] : [];
            $defaults      = crm_get_general_defaults();
            $sanitized     = [];

            // Fallback for legacy crm_test_email field if passed individually
            if (isset($_POST['crm_test_email']) && empty($general_input['test_email'])) {
                $general_input['test_email'] = $_POST['crm_test_email'];
            }

            foreach ($defaults as $key => $default_val) {
                $raw_val = $general_input[$key] ?? '';

                if (in_array($key, ['logo_url', 'logo_secondary_url', 'company_website', 'legal_agb_url', 'legal_privacy_url', 'legal_imprint_url'], true)) {
                    $sanitized[$key] = esc_url_raw(trim($raw_val));
                } elseif (in_array($key, ['company_email', 'backoffice_email', 'test_email'], true)) {
                    $sanitized[$key] = sanitize_email(trim($raw_val));
                } elseif (in_array($key, ['ci_primary_color', 'ci_secondary_color', 'ci_accent_color'], true)) {
                    $color = sanitize_hex_color(trim($raw_val));
                    $sanitized[$key] = !empty($color) ? $color : $default_val;
                } elseif (in_array($key, ['location_wien_notice', 'company_accreditations', 'company_bank'], true)) {
                    $sanitized[$key] = sanitize_textarea_field(wp_unslash($raw_val));
                } else {
                    $sanitized[$key] = sanitize_text_field(wp_unslash($raw_val));
                }
            }

            // Sync legacy option crm_test_email
            if (!empty($sanitized['test_email'])) {
                update_option('crm_test_email', $sanitized['test_email']);
            }

            update_option('crm_general_settings', $sanitized);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Allgemeine CRM-Einstellungen, Demographie & CI-Stammdaten erfolgreich gespeichert.', 'custom-crm') . '</p></div>';
        }

        // 2. Sync / Initialize Default PDF Fields
        if (isset($_POST['sync_default_pdf_fields'])) {
            $all_fields = get_option('crm_custom_fields', []);
            if (!is_array($all_fields)) {
                $all_fields = [];
            }

            $existing_titles = [];
            foreach ($all_fields as $f) {
                if (!empty($f['title'])) {
                    $existing_titles[strtolower(trim($f['title']))] = true;
                }
            }

            $default_pdf_fields = crm_get_default_pdf_fields();
            $added_count = 0;
            $next_idx = !empty($all_fields) ? max(array_keys($all_fields)) + 1 : 100;

            foreach ($default_pdf_fields as $d_title => $d_info) {
                $key = strtolower(trim($d_title));
                if (!isset($existing_titles[$key])) {
                    $all_fields[$next_idx++] = [
                        'title'    => $d_title,
                        'content'  => $d_info['content'],
                        'category' => 'pdf',
                    ];
                    $existing_titles[$key] = true;
                    $added_count++;
                }
            }

            update_option('crm_custom_fields', $all_fields);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Standard-PDF-Felder erfolgreich synchronisiert (%d neue Bausteine hinzugefügt). Bereits existierende Bausteine blieben unverändert.', 'custom-crm'), $added_count) . '</p></div>';
        }
        // 3. Normal Email or PDF Tab Save
        elseif ($saved_tab === 'emails' || $saved_tab === 'pdf') {
            $all_fields = get_option('crm_custom_fields', []);
            if (!is_array($all_fields)) {
                $all_fields = [];
            }

            $target_category = ($saved_tab === 'emails') ? 'email' : 'pdf';

            // Keep all fields from the OTHER category
            $remaining_fields = [];
            foreach ($all_fields as $idx => $f) {
                if (crm_get_field_category($f) !== $target_category) {
                    $remaining_fields[$idx] = $f;
                }
            }

            // Append updated fields from current submission
            if (!empty($_POST['crm_fields']) && is_array($_POST['crm_fields'])) {
                foreach ($_POST['crm_fields'] as $key => $field) {
                    $field_cat = sanitize_text_field($field['category'] ?? $target_category);
                    if (!in_array($field_cat, ['email', 'pdf'], true)) {
                        $field_cat = $target_category;
                    }

                    $remaining_fields[$key] = [
                        'title'    => sanitize_text_field($field['title'] ?? ''),
                        'content'  => wp_kses_post($field['content'] ?? ''),
                        'category' => $field_cat,
                    ];
                }
            }

            update_option('crm_custom_fields', $remaining_fields);

            $tab_label = ($saved_tab === 'emails') ? __('E-Mail Vorlagen', 'custom-crm') : __('PDF Bausteine', 'custom-crm');
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%s erfolgreich gespeichert.', 'custom-crm'), $tab_label) . '</p></div>';
        }
    }

    // Load current options
    $all_fields = get_option('crm_custom_fields', []);
    if (!is_array($all_fields)) {
        $all_fields = [];
    }

    // Filter fields for the active tab
    $email_fields = [];
    $pdf_fields   = [];

    foreach ($all_fields as $orig_idx => $field) {
        $cat = crm_get_field_category($field);
        if ($cat === 'email') {
            $email_fields[$orig_idx] = $field;
        } else {
            $pdf_fields[$orig_idx] = $field;
        }
    }

    // Sort alphabetically by title
    uasort($email_fields, function ($a, $b) {
        return strcmp($a['title'] ?? '', $b['title'] ?? '');
    });
    uasort($pdf_fields, function ($a, $b) {
        return strcmp($a['title'] ?? '', $b['title'] ?? '');
    });

    $general_settings   = crm_get_general_settings();
    $current_test_email = $general_settings['test_email'] ?? get_option('crm_test_email', wp_get_current_user()->user_email);
    ?>
    <div class="wrap crm-settings-wrap">
        <h1 class="crm-main-title">
            <span class="dashicons dashicons-forms" style="font-size: 28px; width: 28px; height: 28px; color: #007C90;"></span>
            <span><?php esc_html_e('CRM Konfiguration & Editoren', 'custom-crm'); ?></span>
            <span class="crm-version-badge">
                <span class="dashicons dashicons-tag" style="font-size:13px; width:13px; height:13px; line-height:13px;"></span>
                Version <?php echo esc_html(defined('CRM_VERSION') ? CRM_VERSION : '2.10.0'); ?>
            </span>
        </h1>

        <!-- Top Tab Navigation -->
        <nav class="nav-tab-wrapper wp-clearfix crm-tab-nav" style="margin-top: 15px; margin-bottom: 20px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-settings&tab=emails')); ?>"
               class="nav-tab <?php echo ($active_tab === 'emails') ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-email-alt" style="margin-right: 4px; vertical-align: text-bottom;"></span>
                <?php esc_html_e('E-Mail Editor', 'custom-crm'); ?>
                <span class="crm-tab-count"><?php echo count($email_fields); ?></span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-settings&tab=pdf')); ?>"
               class="nav-tab <?php echo ($active_tab === 'pdf') ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-media-document" style="margin-right: 4px; vertical-align: text-bottom;"></span>
                <?php esc_html_e('PDF Editor & Bausteine', 'custom-crm'); ?>
                <span class="crm-tab-count"><?php echo count($pdf_fields); ?></span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-settings&tab=general')); ?>"
               class="nav-tab <?php echo ($active_tab === 'general') ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-admin-generic" style="margin-right: 4px; vertical-align: text-bottom;"></span>
                <?php esc_html_e('Allgemeine Einstellungen', 'custom-crm'); ?>
            </a>
        </nav>

        <form method="post" id="crm-main-form">
            <?php wp_nonce_field('save_crm_settings', 'crm_settings_nonce'); ?>
            <input type="hidden" name="crm_active_tab" value="<?php echo esc_attr($active_tab); ?>" />

            <?php if ($active_tab === 'general') : ?>
                <!-- ========================================== -->
                <!-- TAB 1: ALLGEMEINE EINSTELLUNGEN            -->
                <!-- ========================================== -->
                <div class="crm-tab-panel" id="crm-tab-general">
                    <!-- Intro Header -->
                    <div class="crm-settings-card" style="background: linear-gradient(to right, #f8fafc, #f1f5f9); border-left: 4px solid #007C90;">
                        <h2 style="margin: 0 0 6px 0; font-size: 17px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons dashicons-admin-generic" style="color: #007C90; font-size: 24px;"></span>
                            <?php esc_html_e('Demographische Stammdaten & Corporate Identity (CI)', 'custom-crm'); ?>
                        </h2>
                        <p style="margin: 0; color: #475569; font-size: 13.5px; line-height: 1.5;">
                            <?php esc_html_e('Hinterlegen Sie hier die zentralen Unternehmens- und Demographiedaten des Schulungsinstituts sowie Ihr CI-Branding (Logo, Farbschema, Slogan). Alle Angaben werden im CRM-System, in generierten PDF-Dokumenten (Angebot, Bestätigungen, Diplome) und in E-Mail-Vorlagen über dynamische Platzhalter verwendet.', 'custom-crm'); ?>
                        </p>
                    </div>

                    <!-- CARD 1: Corporate Identity & Logo -->
                    <div class="crm-settings-card">
                        <div class="crm-settings-card-header">
                            <h2>
                                <span class="dashicons dashicons-format-image" style="color: #007C90; font-size: 22px;"></span>
                                <?php esc_html_e('Corporate Identity & Logo', 'custom-crm'); ?>
                            </h2>
                            <span class="crm-section-tag"><?php esc_html_e('Branding & CI', 'custom-crm'); ?></span>
                        </div>

                        <!-- Hauptlogo -->
                        <div style="margin-bottom: 22px;">
                            <label class="crm-form-label" for="crm_logo_url">
                                <?php esc_html_e('Instituts-Logo (Hauptlogo für Angebote, PDFs & CRM):', 'custom-crm'); ?>
                            </label>
                            <div class="crm-logo-upload-box">
                                <div class="crm-logo-preview-wrap">
                                    <?php
                                    $current_logo_url = !empty($general_settings['logo_url']) ? $general_settings['logo_url'] : get_template_directory_uri() . '/inc/core/crm/assets/xsieben_logo.png';
                                    ?>
                                    <img id="crm_logo_preview" src="<?php echo esc_url($current_logo_url); ?>" alt="Instituts-Logo" />
                                </div>
                                <div class="crm-logo-controls">
                                    <input type="url" name="crm_general[logo_url]" id="crm_logo_url" value="<?php echo esc_attr($general_settings['logo_url'] ?? ''); ?>" class="regular-text widefat" placeholder="https://x-sieben.at/wp-content/..." style="height: 36px; margin-bottom: 8px;" />
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                        <button type="button" class="button button-secondary crm-media-upload-btn" data-target-input="crm_logo_url" data-target-preview="crm_logo_preview">
                                            <span class="dashicons dashicons-upload" style="vertical-align: text-top; font-size: 15px;"></span>
                                            <?php esc_html_e('Logo aus Mediathek wählen / hochladen', 'custom-crm'); ?>
                                        </button>
                                        <button type="button" class="button crm-media-remove-btn" data-target-input="crm_logo_url" data-target-preview="crm_logo_preview" style="color: #dc2626; border-color: #fca5a5;">
                                            <span class="dashicons dashicons-trash" style="vertical-align: text-top; font-size: 15px;"></span>
                                            <?php esc_html_e('Entfernen', 'custom-crm'); ?>
                                        </button>
                                        <button type="button" class="button button-link crm-reset-default-logo-btn" data-default-logo="<?php echo esc_url(get_template_directory_uri() . '/inc/core/crm/assets/xsieben_logo.png'); ?>" data-target-input="crm_logo_url" data-target-preview="crm_logo_preview">
                                            <?php esc_html_e('Standard-Logo wiederherstellen', 'custom-crm'); ?>
                                        </button>
                                    </div>
                                    <p class="crm-form-help">
                                        <?php esc_html_e('Empfohlenes Format: PNG oder SVG mit transparentem Hintergrund. Erscheint im Header aller PDF-Dokumente und kann per {company_logo} eingebunden werden.', 'custom-crm'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sekundärlogo -->
                        <div style="margin-bottom: 22px;">
                            <label class="crm-form-label" for="crm_logo_secondary_url">
                                <?php esc_html_e('Sekundär-Logo / Signatur-Logo (Optional):', 'custom-crm'); ?>
                            </label>
                            <div class="crm-logo-upload-box">
                                <div class="crm-logo-preview-wrap">
                                    <img id="crm_logo_secondary_preview" src="<?php echo esc_url($general_settings['logo_secondary_url'] ?? ''); ?>" alt="Sekundär-Logo" style="<?php echo empty($general_settings['logo_secondary_url']) ? 'display:none;' : ''; ?>" />
                                </div>
                                <div class="crm-logo-controls">
                                    <input type="url" name="crm_general[logo_secondary_url]" id="crm_logo_secondary_url" value="<?php echo esc_attr($general_settings['logo_secondary_url'] ?? ''); ?>" class="regular-text widefat" placeholder="https://x-sieben.at/wp-content/..." style="height: 36px; margin-bottom: 8px;" />
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                        <button type="button" class="button button-secondary crm-media-upload-btn" data-target-input="crm_logo_secondary_url" data-target-preview="crm_logo_secondary_preview">
                                            <span class="dashicons dashicons-upload" style="vertical-align: text-top; font-size: 15px;"></span>
                                            <?php esc_html_e('Logo auswählen / hochladen', 'custom-crm'); ?>
                                        </button>
                                        <button type="button" class="button crm-media-remove-btn" data-target-input="crm_logo_secondary_url" data-target-preview="crm_logo_secondary_preview" style="color: #dc2626; border-color: #fca5a5;">
                                            <span class="dashicons dashicons-trash" style="vertical-align: text-top; font-size: 15px;"></span>
                                            <?php esc_html_e('Entfernen', 'custom-crm'); ?>
                                        </button>
                                    </div>
                                    <p class="crm-form-help">
                                        <?php esc_html_e('Für alternative E-Mail-Signaturen oder Dokumenten-Fußzeilen.', 'custom-crm'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- CI-Farbschema -->
                        <div style="margin-bottom: 20px;">
                            <label class="crm-form-label"><?php esc_html_e('CI-Farbschema (Corporate Design Farben):', 'custom-crm'); ?></label>
                            <div class="crm-color-group">
                                <div class="crm-color-item">
                                    <input type="color" class="crm-color-picker" data-target-hex="crm_ci_primary_color" value="<?php echo esc_attr($general_settings['ci_primary_color'] ?? '#007C90'); ?>" />
                                    <div>
                                        <label for="crm_ci_primary_color" style="display:block; font-size:11px; font-weight:600; color:#475569;">Primärfarbe</label>
                                        <input type="text" id="crm_ci_primary_color" name="crm_general[ci_primary_color]" class="crm-color-hex-input" value="<?php echo esc_attr($general_settings['ci_primary_color'] ?? '#007C90'); ?>" />
                                    </div>
                                </div>
                                <div class="crm-color-item">
                                    <input type="color" class="crm-color-picker" data-target-hex="crm_ci_secondary_color" value="<?php echo esc_attr($general_settings['ci_secondary_color'] ?? '#0284c7'); ?>" />
                                    <div>
                                        <label for="crm_ci_secondary_color" style="display:block; font-size:11px; font-weight:600; color:#475569;">Sekundärfarbe</label>
                                        <input type="text" id="crm_ci_secondary_color" name="crm_general[ci_secondary_color]" class="crm-color-hex-input" value="<?php echo esc_attr($general_settings['ci_secondary_color'] ?? '#0284c7'); ?>" />
                                    </div>
                                </div>
                                <div class="crm-color-item">
                                    <input type="color" class="crm-color-picker" data-target-hex="crm_ci_accent_color" value="<?php echo esc_attr($general_settings['ci_accent_color'] ?? '#0f172a'); ?>" />
                                    <div>
                                        <label for="crm_ci_accent_color" style="display:block; font-size:11px; font-weight:600; color:#475569;">Akzent / Dunkel</label>
                                        <input type="text" id="crm_ci_accent_color" name="crm_general[ci_accent_color]" class="crm-color-hex-input" value="<?php echo esc_attr($general_settings['ci_accent_color'] ?? '#0f172a'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Claim & Akkreditierungen -->
                        <div class="crm-form-row">
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_claim"><?php esc_html_e('Instituts-Slogan / Claim:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_claim]" id="crm_company_claim" value="<?php echo esc_attr($general_settings['company_claim'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_accreditations"><?php esc_html_e('Akkreditierungen & Partnerschaften:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_accreditations]" id="crm_company_accreditations" value="<?php echo esc_attr($general_settings['company_accreditations'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: Schulungsinstitut & Demographische Stammdaten -->
                    <div class="crm-settings-card">
                        <div class="crm-settings-card-header">
                            <h2>
                                <span class="dashicons dashicons-building" style="color: #007C90; font-size: 22px;"></span>
                                <?php esc_html_e('Schulungsinstitut & Demographie', 'custom-crm'); ?>
                            </h2>
                            <span class="crm-section-tag"><?php esc_html_e('Stammdaten & Standorte', 'custom-crm'); ?></span>
                        </div>

                        <!-- Firmenname & Kurzbezeichnung -->
                        <div class="crm-form-row">
                            <div class="crm-form-col" style="flex: 2;">
                                <label class="crm-form-label" for="crm_company_name"><?php esc_html_e('Offizieller Instituts- / Firmenname:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_name]" id="crm_company_name" value="<?php echo esc_attr($general_settings['company_name'] ?? ''); ?>" class="widefat" style="height: 36px; font-weight: 600;" required />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_short_name"><?php esc_html_e('Markenname / Kurzform:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_short_name]" id="crm_company_short_name" value="<?php echo esc_attr($general_settings['company_short_name'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_legal_form"><?php esc_html_e('Rechtsform:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_legal_form]" id="crm_company_legal_form" value="<?php echo esc_attr($general_settings['company_legal_form'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>

                        <!-- Geschäftsführung -->
                        <div class="crm-form-row">
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_management"><?php esc_html_e('Geschäftsführung / Vertretungsberechtigte:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_management]" id="crm_company_management" value="<?php echo esc_attr($general_settings['company_management'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>

                        <!-- Kanzleisitz / Hauptadresse -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px; margin-bottom: 16px;">
                            <h4 style="margin: 0 0 10px 0; color: #1e293b; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                                <span class="dashicons dashicons-location" style="color: #0284c7; font-size: 16px;"></span>
                                <?php esc_html_e('Kanzleisitz / Firmenzentrale (Wr. Neustadt / Lichtenwörth):', 'custom-crm'); ?>
                            </h4>
                            <div class="crm-form-row" style="margin-bottom: 0;">
                                <div class="crm-form-col" style="flex: 2;">
                                    <label class="crm-form-label" for="crm_company_street"><?php esc_html_e('Straße & Hausnummer:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[company_street]" id="crm_company_street" value="<?php echo esc_attr($general_settings['company_street'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col" style="flex: 1;">
                                    <label class="crm-form-label" for="crm_company_zip"><?php esc_html_e('PLZ:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[company_zip]" id="crm_company_zip" value="<?php echo esc_attr($general_settings['company_zip'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col" style="flex: 1.5;">
                                    <label class="crm-form-label" for="crm_company_city"><?php esc_html_e('Ort:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[company_city]" id="crm_company_city" value="<?php echo esc_attr($general_settings['company_city'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col" style="flex: 1;">
                                    <label class="crm-form-label" for="crm_company_country"><?php esc_html_e('Land:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[company_country]" id="crm_company_country" value="<?php echo esc_attr($general_settings['company_country'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                            </div>
                        </div>

                        <!-- Schulungszentrum Wien -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px;">
                            <h4 style="margin: 0 0 10px 0; color: #1e293b; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                                <span class="dashicons dashicons-welcome-learn-more" style="color: #0f766e; font-size: 16px;"></span>
                                <?php esc_html_e('Seminarzentrum & Schulungsort Wien:', 'custom-crm'); ?>
                            </h4>
                            <div class="crm-form-row">
                                <div class="crm-form-col" style="flex: 1.5;">
                                    <label class="crm-form-label" for="crm_location_wien_name"><?php esc_html_e('Standortbezeichnung:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[location_wien_name]" id="crm_location_wien_name" value="<?php echo esc_attr($general_settings['location_wien_name'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col" style="flex: 2;">
                                    <label class="crm-form-label" for="crm_location_wien_street"><?php esc_html_e('Straße & Hausnummer:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[location_wien_street]" id="crm_location_wien_street" value="<?php echo esc_attr($general_settings['location_wien_street'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col" style="flex: 1;">
                                    <label class="crm-form-label" for="crm_location_wien_zip"><?php esc_html_e('PLZ:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[location_wien_zip]" id="crm_location_wien_zip" value="<?php echo esc_attr($general_settings['location_wien_zip'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col" style="flex: 1.5;">
                                    <label class="crm-form-label" for="crm_location_wien_city"><?php esc_html_e('Ort:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[location_wien_city]" id="crm_location_wien_city" value="<?php echo esc_attr($general_settings['location_wien_city'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                            </div>
                            <div class="crm-form-row" style="margin-bottom: 0;">
                                <div class="crm-form-col-full">
                                    <label class="crm-form-label" for="crm_location_wien_notice"><?php esc_html_e('Hinweis zur Schulungsdurchführung (z.B. für Angebot-PDF & Bestätigungen):', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[location_wien_notice]" id="crm_location_wien_notice" value="<?php echo esc_attr($general_settings['location_wien_notice'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: Kontaktdaten & Backoffice-Kommunikation -->
                    <div class="crm-settings-card">
                        <div class="crm-settings-card-header">
                            <h2>
                                <span class="dashicons dashicons-phone" style="color: #007C90; font-size: 22px;"></span>
                                <?php esc_html_e('Kontaktdaten & Backoffice', 'custom-crm'); ?>
                            </h2>
                            <span class="crm-section-tag"><?php esc_html_e('Kommunikation', 'custom-crm'); ?></span>
                        </div>

                        <!-- Allgemeine Kontaktdaten -->
                        <div class="crm-form-row">
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_phone"><?php esc_html_e('Zentrale Telefonnummer:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_phone]" id="crm_company_phone" value="<?php echo esc_attr($general_settings['company_phone'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_email"><?php esc_html_e('Zentrale E-Mail-Adresse:', 'custom-crm'); ?></label>
                                <input type="email" name="crm_general[company_email]" id="crm_company_email" value="<?php echo esc_attr($general_settings['company_email'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_website"><?php esc_html_e('Website-URL:', 'custom-crm'); ?></label>
                                <input type="url" name="crm_general[company_website]" id="crm_company_website" value="<?php echo esc_attr($general_settings['company_website'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>

                        <!-- Backoffice Betreuung (Anna Brauer) -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px;">
                            <h4 style="margin: 0 0 10px 0; color: #1e293b; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                                <span class="dashicons dashicons-businesswoman" style="color: #6366f1; font-size: 16px;"></span>
                                <?php esc_html_e('Backoffice-Ansprechperson (z.B. für E-Mail-Signaturen & Angebote):', 'custom-crm'); ?>
                            </h4>
                            <div class="crm-form-row" style="margin-bottom: 0;">
                                <div class="crm-form-col">
                                    <label class="crm-form-label" for="crm_backoffice_name"><?php esc_html_e('Name:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[backoffice_name]" id="crm_backoffice_name" value="<?php echo esc_attr($general_settings['backoffice_name'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col">
                                    <label class="crm-form-label" for="crm_backoffice_email"><?php esc_html_e('E-Mail-Adresse:', 'custom-crm'); ?></label>
                                    <input type="email" name="crm_general[backoffice_email]" id="crm_backoffice_email" value="<?php echo esc_attr($general_settings['backoffice_email'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                                <div class="crm-form-col">
                                    <label class="crm-form-label" for="crm_backoffice_phone"><?php esc_html_e('Direktwahl / Telefon:', 'custom-crm'); ?></label>
                                    <input type="text" name="crm_general[backoffice_phone]" id="crm_backoffice_phone" value="<?php echo esc_attr($general_settings['backoffice_phone'] ?? ''); ?>" class="widefat" style="height: 34px;" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 4: Rechtliche Stammdaten, Bank & Compliance -->
                    <div class="crm-settings-card">
                        <div class="crm-settings-card-header">
                            <h2>
                                <span class="dashicons dashicons-shield" style="color: #007C90; font-size: 22px;"></span>
                                <?php esc_html_e('Behördliche Stammdaten & Bankverbindung', 'custom-crm'); ?>
                            </h2>
                            <span class="crm-section-tag"><?php esc_html_e('Compliance & Recht', 'custom-crm'); ?></span>
                        </div>

                        <!-- UID, FN, Gericht -->
                        <div class="crm-form-row">
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_uid"><?php esc_html_e('UID-Nummer:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_uid]" id="crm_company_uid" value="<?php echo esc_attr($general_settings['company_uid'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_fn"><?php esc_html_e('Firmenbuchnummer (FN):', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_fn]" id="crm_company_fn" value="<?php echo esc_attr($general_settings['company_fn'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_court"><?php esc_html_e('Firmenbuchgericht:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_court]" id="crm_company_court" value="<?php echo esc_attr($general_settings['company_court'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>

                        <!-- Kammer & Bankverbindung -->
                        <div class="crm-form-row">
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_company_chamber"><?php esc_html_e('Kammer / Aufsichtsbehörde:', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_chamber]" id="crm_company_chamber" value="<?php echo esc_attr($general_settings['company_chamber'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col" style="flex: 2;">
                                <label class="crm-form-label" for="crm_company_bank"><?php esc_html_e('Standard-Bankverbindung (IBAN / BIC):', 'custom-crm'); ?></label>
                                <input type="text" name="crm_general[company_bank]" id="crm_company_bank" value="<?php echo esc_attr($general_settings['company_bank'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>

                        <!-- Rechtliche URLs -->
                        <div class="crm-form-row" style="margin-bottom: 0;">
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_legal_agb_url"><?php esc_html_e('AGB-Link (PDF oder Seite):', 'custom-crm'); ?></label>
                                <input type="url" name="crm_general[legal_agb_url]" id="crm_legal_agb_url" value="<?php echo esc_attr($general_settings['legal_agb_url'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_legal_privacy_url"><?php esc_html_e('Datenschutzerklärung-Link:', 'custom-crm'); ?></label>
                                <input type="url" name="crm_general[legal_privacy_url]" id="crm_legal_privacy_url" value="<?php echo esc_attr($general_settings['legal_privacy_url'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                            <div class="crm-form-col">
                                <label class="crm-form-label" for="crm_legal_imprint_url"><?php esc_html_e('Impressum-Link:', 'custom-crm'); ?></label>
                                <input type="url" name="crm_general[legal_imprint_url]" id="crm_legal_imprint_url" value="<?php echo esc_attr($general_settings['legal_imprint_url'] ?? ''); ?>" class="widefat" style="height: 36px;" />
                            </div>
                        </div>
                    </div>

                    <!-- CARD 5: Test-E-Mail & Mailer-Einstellungen -->
                    <div class="crm-settings-card">
                        <div class="crm-settings-card-header">
                            <h2>
                                <span class="dashicons dashicons-email-alt" style="color: #007C90; font-size: 22px;"></span>
                                <?php esc_html_e('Test-E-Mail & Mailer-Einstellungen', 'custom-crm'); ?>
                            </h2>
                            <span class="crm-section-tag"><?php esc_html_e('Mailer', 'custom-crm'); ?></span>
                        </div>
                        <p style="color: #475569; font-size: 13.5px; line-height: 1.5; margin-top: 0;">
                            <?php esc_html_e('Definieren Sie die Standard-Test-E-Mail-Adresse für das Backoffice. Im CRM-Mailer kann jede E-Mail (inklusive PDF-Anlagen und Signaturen) wahlweise als Test versendet werden, ohne Kundendaten zu verändern.', 'custom-crm'); ?>
                        </p>
                        <div class="crm-form-row" style="margin-bottom: 0;">
                            <div class="crm-form-col" style="max-width: 460px;">
                                <label class="crm-form-label" for="crm_test_email"><?php esc_html_e('Standard Test-Empfänger:', 'custom-crm'); ?></label>
                                <input name="crm_general[test_email]" type="email" id="crm_test_email" value="<?php echo esc_attr($general_settings['test_email'] ?? $current_test_email); ?>" class="widefat" style="height: 36px;" placeholder="test@x-sieben.at" required />
                                <p class="crm-form-help"><?php esc_html_e('Wird im CRM-Mailer automatisch bei jedem Versand vorausgewählt.', 'custom-crm'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 6: CRM-Platzhalter Übersicht für Demographie & CI -->
                    <div class="crm-settings-card" style="background: #f8fafc; border: 1px solid #cbd5e1;">
                        <h3 style="margin-top: 0; color: #1e293b; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons dashicons-info" style="color: #007C90;"></span>
                            <?php esc_html_e('Verfügbare CRM-Platzhalter für Vorlagen (Klick zum Kopieren)', 'custom-crm'); ?>
                        </h3>
                        <p style="font-size: 13px; color: #475569; margin: 0 0 14px 0;">
                            <?php esc_html_e('Diese Platzhalter können Sie in allen E-Mail-Vorlagen und PDF-Bausteinen verwenden. Sie werden beim Rendern automatisch durch die hier hinterlegten Stammdaten ersetzt:', 'custom-crm'); ?>
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span style="font-size:11.5px; font-weight:600; color:#475569; width:130px;">Institut & Recht:</span>
                                <a href="#" class="crm-chip" data-code="{company_name}">{company_name}</a>
                                <a href="#" class="crm-chip" data-code="{company_short_name}">{company_short_name}</a>
                                <a href="#" class="crm-chip" data-code="{company_management}">{company_management}</a>
                                <a href="#" class="crm-chip" data-code="{company_uid}">{company_uid}</a>
                                <a href="#" class="crm-chip" data-code="{company_fn}">{company_fn}</a>
                                <a href="#" class="crm-chip" data-code="{company_court}">{company_court}</a>
                                <a href="#" class="crm-chip" data-code="{company_bank}">{company_bank}</a>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span style="font-size:11.5px; font-weight:600; color:#475569; width:130px;">Standorte & Adressen:</span>
                                <a href="#" class="crm-chip" data-code="{company_address}">{company_address}</a>
                                <a href="#" class="crm-chip" data-code="{company_street}">{company_street}</a>
                                <a href="#" class="crm-chip" data-code="{company_zip}">{company_zip}</a>
                                <a href="#" class="crm-chip" data-code="{company_city}">{company_city}</a>
                                <a href="#" class="crm-chip" data-code="{location_wien}">{location_wien}</a>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span style="font-size:11.5px; font-weight:600; color:#475569; width:130px;">Kontakt & Team:</span>
                                <a href="#" class="crm-chip" data-code="{company_phone}">{company_phone}</a>
                                <a href="#" class="crm-chip" data-code="{company_email}">{company_email}</a>
                                <a href="#" class="crm-chip" data-code="{company_website}">{company_website}</a>
                                <a href="#" class="crm-chip" data-code="{backoffice_name}">{backoffice_name}</a>
                                <a href="#" class="crm-chip" data-code="{backoffice_email}">{backoffice_email}</a>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span style="font-size:11.5px; font-weight:600; color:#475569; width:130px;">CI & Links:</span>
                                <a href="#" class="crm-chip" data-code="{company_logo}">{company_logo} (Bild-Tag)</a>
                                <a href="#" class="crm-chip" data-code="{company_logo_url}">{company_logo_url} (URL)</a>
                                <a href="#" class="crm-chip" data-code="{agb_url}">{agb_url}</a>
                                <a href="#" class="crm-chip" data-code="{privacy_url}">{privacy_url}</a>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <p style="margin-top: 25px;">
                        <button type="submit" name="submit_general" class="button button-primary button-large" style="background:#007C90; border-color:#007C90; font-size:14px; height:40px; padding:0 26px;">
                            <span class="dashicons dashicons-saved" style="vertical-align:text-bottom; margin-right: 4px;"></span>
                            <?php esc_html_e('Allgemeine Einstellungen & Stammdaten speichern', 'custom-crm'); ?>
                        </button>
                    </p>
                </div>

            <?php elseif ($active_tab === 'emails') : ?>
                <!-- ========================================== -->
                <!-- TAB 2: E-MAIL EDITOR                       -->
                <!-- ========================================== -->
                <div class="crm-tab-panel" id="crm-tab-emails">
                    <!-- Intro & Header -->
                    <div class="crm-editor-header-box" style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 18px 22px; margin-bottom: 20px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                            <div>
                                <h2 style="margin:0 0 6px 0; color:#0f172a; font-size:18px; display:flex; align-items:center; gap:8px;">
                                    <span class="dashicons dashicons-email-alt" style="color:#0284c7; font-size:24px;"></span>
                                    <?php esc_html_e('E-Mail Vorlagen & Texte', 'custom-crm'); ?>
                                </h2>
                                <p style="margin:0; color:#475569; font-size:13.5px;">
                                    <?php esc_html_e('Verwalten Sie hier alle E-Mail-Texte, die beim Versenden von Angeboten, Anmeldungen, Diplomen und Bestätigungen dynamisch generiert werden.', 'custom-crm'); ?>
                                </p>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <button type="button" class="button button-secondary" id="crm-toggle-all-accordions">
                                    <span class="dashicons dashicons-sort" style="vertical-align:text-top;"></span> <?php esc_html_e('Alle auf-/zuklappen', 'custom-crm'); ?>
                                </button>
                                <button type="button" class="button button-primary" id="add-crm-email-field" style="background:#0284c7; border-color:#0284c7;">
                                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:text-top;"></span> <?php esc_html_e('Neue E-Mail-Vorlage anlegen', 'custom-crm'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Variable Cheat Sheet Box -->
                        <?php crm_render_placeholders_cheat_sheet('email'); ?>
                    </div>

                    <!-- Filter / Search bar -->
                    <div style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                        <input type="text" id="crm-field-search" placeholder="<?php esc_attr_e('Vorlagen filtern...', 'custom-crm'); ?>" style="max-width: 320px; width: 100%; height: 32px; border-radius: 4px; border: 1px solid #cbd5e1; padding: 0 10px;" />
                        <span style="color:#64748b; font-size:12px;"><?php echo sprintf(esc_html__('%d E-Mail-Vorlagen vorhanden', 'custom-crm'), count($email_fields)); ?></span>
                    </div>

                    <div id="crm-fields-wrapper">
                        <?php
                        if (!empty($email_fields)) {
                            foreach ($email_fields as $orig_index => $field) {
                                crm_render_editor_field($orig_index, $field['title'] ?? '', $field['content'] ?? '', 'email');
                            }
                        } else {
                            echo '<p class="description">' . esc_html__('Keine E-Mail-Vorlagen hinterlegt. Klicken Sie auf "Neue E-Mail-Vorlage anlegen".', 'custom-crm') . '</p>';
                        }
                        ?>
                    </div>

                    <p style="margin-top: 25px;">
                        <button type="submit" name="submit_emails" class="button button-primary button-large" style="background:#007C90; border-color:#007C90; font-size:14px; height:38px; padding:0 24px;">
                            <span class="dashicons dashicons-saved" style="vertical-align:text-bottom;"></span> <?php esc_html_e('Alle E-Mail-Vorlagen speichern', 'custom-crm'); ?>
                        </button>
                    </p>
                </div>

            <?php elseif ($active_tab === 'pdf') : ?>
                <!-- ========================================== -->
                <!-- TAB 3: PDF EDITOR                          -->
                <!-- ========================================== -->
                <div class="crm-tab-panel" id="crm-tab-pdf">
                    <!-- Intro & Header -->
                    <div class="crm-editor-header-box" style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 18px 22px; margin-bottom: 20px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                            <div>
                                <h2 style="margin:0 0 6px 0; color:#0f172a; font-size:18px; display:flex; align-items:center; gap:8px;">
                                    <span class="dashicons dashicons-media-document" style="color:#7c3aed; font-size:24px;"></span>
                                    <?php esc_html_e('PDF Bausteine, Anhänge & Klauseln', 'custom-crm'); ?>
                                </h2>
                                <p style="margin:0; color:#475569; font-size:13.5px;">
                                    <?php esc_html_e('Verwalten Sie hier alle Textbausteine, AGBs, Bankverbindungen und Klauseln aller PDF-Dokumente: Kurszeitenbestätigung (KB), Teilnahmebestätigung (TB), Diplom, Angebot & Honorarnote.', 'custom-crm'); ?>
                                </p>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                <button type="button" class="button button-secondary" id="crm-toggle-all-accordions">
                                    <span class="dashicons dashicons-sort" style="vertical-align:text-top;"></span> <?php esc_html_e('Alle auf-/zuklappen', 'custom-crm'); ?>
                                </button>
                                <button type="submit" name="sync_default_pdf_fields" value="1" class="button button-secondary" style="border-color:#7c3aed; color:#6d28d9;" onclick="return confirm('<?php echo esc_js(__('Standard-PDF-Felder initialisieren? Bereits existierende oder angepasste Bausteine bleiben vollständig unverändert.', 'custom-crm')); ?>');">
                                    <span class="dashicons dashicons-update" style="vertical-align:text-top;"></span> <?php esc_html_e('Standard-Felder laden', 'custom-crm'); ?>
                                </button>
                                <button type="button" class="button button-primary" id="add-crm-pdf-field" style="background:#7c3aed; border-color:#7c3aed;">
                                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:text-top;"></span> <?php esc_html_e('Neuen PDF-Baustein anlegen', 'custom-crm'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Variable Cheat Sheet Box -->
                        <?php crm_render_placeholders_cheat_sheet('pdf'); ?>
                    </div>

                    <!-- Filter pills & Live Search -->
                    <div class="crm-doc-pills-bar" style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div class="crm-doc-pills" style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="button crm-doc-pill active" data-doc="all">
                                <?php esc_html_e('Alle Dokumente', 'custom-crm'); ?> (<?php echo count($pdf_fields); ?>)
                            </button>
                            <button type="button" class="button crm-doc-pill" data-doc="kb" style="color: #0f766e;">
                                <span class="dashicons dashicons-calendar-alt" style="font-size:14px; vertical-align:text-top;"></span> Kurszeiten (KB)
                            </button>
                            <button type="button" class="button crm-doc-pill" data-doc="tb" style="color: #047857;">
                                <span class="dashicons dashicons-id-alt" style="font-size:14px; vertical-align:text-top;"></span> Teilnahme (TB)
                            </button>
                            <button type="button" class="button crm-doc-pill" data-doc="diplom" style="color: #b45309;">
                                <span class="dashicons dashicons-awards" style="font-size:14px; vertical-align:text-top;"></span> Diplom
                            </button>
                            <button type="button" class="button crm-doc-pill" data-doc="angebot" style="color: #6d28d9;">
                                <span class="dashicons dashicons-media-document" style="font-size:14px; vertical-align:text-top;"></span> Angebot & Anhang
                            </button>
                            <button type="button" class="button crm-doc-pill" data-doc="invoice" style="color: #be185d;">
                                <span class="dashicons dashicons-money-alt" style="font-size:14px; vertical-align:text-top;"></span> Honorarnote
                            </button>
                        </div>
                        <div>
                            <input type="text" id="crm-field-search" placeholder="<?php esc_attr_e('Bausteine filtern...', 'custom-crm'); ?>" style="max-width: 260px; width: 100%; height: 32px; border-radius: 4px; border: 1px solid #cbd5e1; padding: 0 10px;" />
                        </div>
                    </div>

                    <div id="crm-fields-wrapper">
                        <?php
                        if (!empty($pdf_fields)) {
                            foreach ($pdf_fields as $orig_index => $field) {
                                crm_render_editor_field($orig_index, $field['title'] ?? '', $field['content'] ?? '', 'pdf');
                            }
                        } else {
                            ?>
                            <div class="notice notice-info inline" style="padding: 18px 20px; margin: 15px 0; border-radius: 8px; background: #f8fafc; border: 1px solid #cbd5e1;">
                                <h3 style="margin-top: 0; color: #1e293b;">
                                    <?php esc_html_e('Noch keine benutzerdefinierten PDF-Bausteine geladen', 'custom-crm'); ?>
                                </h3>
                                <p style="font-size: 13.5px; color: #475569; margin: 0 0 14px 0;">
                                    <?php esc_html_e('Klicken Sie auf den Button unten, um alle Standard-Textbausteine für alle PDF-Vorlagen (Kurszeitenbestätigung, Teilnahmebestätigung, Diplom, Angebot & Honorarnote) sofort zu laden und bearbeitbar zu machen.', 'custom-crm'); ?>
                                </p>
                                <p style="margin: 0;">
                                    <button type="submit" name="sync_default_pdf_fields" value="1" class="button button-primary button-large" style="background:#7c3aed; border-color:#7c3aed;">
                                        <span class="dashicons dashicons-download" style="vertical-align:text-bottom;"></span>
                                        <?php esc_html_e('Standard-Felder für alle PDFs jetzt initialisieren', 'custom-crm'); ?>
                                    </button>
                                </p>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <p style="margin-top: 25px; margin-bottom: 25px; display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                        <button type="submit" name="submit_pdf" class="button button-primary button-large" style="background:#007C90; border-color:#007C90; font-size:14px; height:38px; padding:0 24px;">
                            <span class="dashicons dashicons-saved" style="vertical-align:text-bottom;"></span> <?php esc_html_e('Alle PDF-Bausteine speichern', 'custom-crm'); ?>
                        </button>
                        <a href="#crm-pdf-preview-section" class="button button-secondary" style="height:38px; line-height:36px; padding:0 18px; color:#6d28d9; border-color:#7c3aed;">
                            <span class="dashicons dashicons-visibility" style="vertical-align:text-top; font-size:16px;"></span> <?php esc_html_e('Zur Live-Vorschau springen ↓', 'custom-crm'); ?>
                        </a>
                    </p>

                    <!-- PDF Live-Vorschau Bereich -->
                    <div id="crm-pdf-preview-section" class="crm-preview-card" style="margin-top: 28px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;">
                        <div class="crm-preview-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                    <span class="dashicons dashicons-visibility" style="color: #7c3aed; font-size: 19px;"></span>
                                    <span><?php esc_html_e('Live-Vorschau:', 'custom-crm'); ?></span>
                                    <span id="crm-preview-doc-title" style="color: #6d28d9;"><?php esc_html_e('Kurszeitenbestätigung (KB)', 'custom-crm'); ?></span>
                                </h3>
                                <span id="crm-preview-sample-info" style="font-size: 11.5px; color: #5b21b6; background: #ede9fe; padding: 2px 8px; border-radius: 12px; font-weight: 500;">
                                    <?php esc_html_e('Wird geladen...', 'custom-crm'); ?>
                                </span>
                            </div>

                            <!-- Preview switcher pills inside preview card -->
                            <div class="crm-preview-doc-switcher" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span style="font-size: 12px; font-weight: 600; color: #64748b; margin-right: 4px;"><?php esc_html_e('Dokument:', 'custom-crm'); ?></span>
                                <button type="button" class="button crm-preview-switch-btn active" data-doc="kb" title="<?php esc_attr_e('Kurszeitenbestätigung', 'custom-crm'); ?>">
                                    <span class="dashicons dashicons-calendar-alt" style="font-size:13px; vertical-align:text-top;"></span> KB
                                </button>
                                <button type="button" class="button crm-preview-switch-btn" data-doc="tb" title="<?php esc_attr_e('Teilnahmebestätigung', 'custom-crm'); ?>">
                                    <span class="dashicons dashicons-id-alt" style="font-size:13px; vertical-align:text-top;"></span> TB
                                </button>
                                <button type="button" class="button crm-preview-switch-btn" data-doc="diplom" title="<?php esc_attr_e('Diplom / Zertifikat', 'custom-crm'); ?>">
                                    <span class="dashicons dashicons-awards" style="font-size:13px; vertical-align:text-top;"></span> Diplom
                                </button>
                                <button type="button" class="button crm-preview-switch-btn" data-doc="angebot" title="<?php esc_attr_e('Angebot & Anhang', 'custom-crm'); ?>">
                                    <span class="dashicons dashicons-media-document" style="font-size:13px; vertical-align:text-top;"></span> Angebot
                                </button>
                                <button type="button" class="button crm-preview-switch-btn" data-doc="invoice" title="<?php esc_attr_e('Honorarnote / Rechnung', 'custom-crm'); ?>">
                                    <span class="dashicons dashicons-money-alt" style="font-size:13px; vertical-align:text-top;"></span> Honorarnote
                                </button>
                            </div>

                            <!-- Actions toolbar -->
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <button type="button" class="button button-secondary" id="crm-preview-reload-btn" title="<?php esc_attr_e('Vorschau aktualisieren / neu generieren', 'custom-crm'); ?>" style="height: 30px; line-height: 28px; padding: 0 10px;">
                                    <span class="dashicons dashicons-update crm-reload-icon" style="font-size: 14px; vertical-align: text-top;"></span>
                                    <span class="crm-btn-text"><?php esc_html_e('Neu laden', 'custom-crm'); ?></span>
                                </button>
                                <a href="#" target="_blank" class="button button-secondary" id="crm-preview-newtab-btn" title="<?php esc_attr_e('In neuem Tab / Vollbild öffnen', 'custom-crm'); ?>" style="height: 30px; line-height: 28px; padding: 0 10px;">
                                    <span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: text-top;"></span>
                                    <?php esc_html_e('Vollbild', 'custom-crm'); ?>
                                </a>
                                <a href="#" download class="button button-secondary" id="crm-preview-download-btn" title="<?php esc_attr_e('PDF-Datei herunterladen', 'custom-crm'); ?>" style="height: 30px; line-height: 28px; padding: 0 10px;">
                                    <span class="dashicons dashicons-download" style="font-size: 14px; vertical-align: text-top;"></span>
                                    <?php esc_html_e('Download', 'custom-crm'); ?>
                                </a>
                                <button type="button" class="button button-secondary" id="crm-preview-toggle-size-btn" title="<?php esc_attr_e('Vorschau-Höhe vergrößern / verkleinern', 'custom-crm'); ?>" style="height: 30px; line-height: 28px; padding: 0 8px;">
                                    <span class="dashicons dashicons-editor-expand" style="font-size: 14px; vertical-align: text-top;"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Iframe & Loading Container -->
                        <div class="crm-preview-body" style="position: relative; width: 100%; min-height: 640px; background: #525659;">
                            <!-- Spinner Overlay -->
                            <div id="crm-preview-loading" style="position: absolute; inset: 0; background: rgba(255,255,255,0.88); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10;">
                                <span class="dashicons dashicons-update spin" style="font-size: 40px; width: 40px; height: 40px; color: #7c3aed;"></span>
                                <p style="margin-top: 12px; font-weight: 600; color: #334155; font-size: 14px;" id="crm-preview-loading-text">
                                    <?php esc_html_e('PDF-Vorschau wird mit aktuellen Bausteinen generiert...', 'custom-crm'); ?>
                                </p>
                            </div>

                            <!-- Error Message Box -->
                            <div id="crm-preview-error" style="display: none; position: absolute; inset: 0; background: #fff; padding: 40px; text-align: center; z-index: 10;">
                                <span class="dashicons dashicons-warning" style="font-size: 48px; width: 48px; height: 48px; color: #dc2626;"></span>
                                <h4 style="color: #dc2626; margin: 10px 0 6px 0; font-size: 16px;"><?php esc_html_e('Vorschau konnte nicht gerendert werden', 'custom-crm'); ?></h4>
                                <p id="crm-preview-error-msg" style="color: #64748b; font-size: 13px; max-width: 500px; margin: 0 auto 16px auto;"></p>
                                <button type="button" class="button button-primary" onclick="jQuery('#crm-preview-reload-btn').trigger('click');">
                                    <?php esc_html_e('Erneut versuchen', 'custom-crm'); ?>
                                </button>
                            </div>

                            <!-- Embedded Iframe -->
                            <iframe id="crm-pdf-preview-iframe"
                                    src="about:blank"
                                    style="width: 100%; height: 680px; border: none; display: block;"
                                    title="<?php esc_attr_e('PDF Live-Vorschau', 'custom-crm'); ?>">
                            </iframe>
                        </div>

                        <div class="crm-preview-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 8px 18px; display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; color: #64748b; flex-wrap: wrap; gap: 8px;">
                            <span>
                                <span class="dashicons dashicons-info" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle; color: #7c3aed;"></span>
                                <?php esc_html_e('Hinweis: Nach Bearbeitung eines Bausteins und Klick auf „Feld speichern“ aktualisiert sich diese PDF-Vorschau automatisch.', 'custom-crm'); ?>
                            </span>
                            <span id="crm-preview-updated-at" style="font-weight: 500;"></span>
                        </div>
                    </div>

                    <p style="margin-top: 20px;">
                        <button type="submit" name="submit_pdf" class="button button-primary button-large" style="background:#007C90; border-color:#007C90; font-size:14px; height:38px; padding:0 24px;">
                            <span class="dashicons dashicons-saved" style="vertical-align:text-bottom;"></span> <?php esc_html_e('Alle PDF-Bausteine speichern', 'custom-crm'); ?>
                        </button>
                    </p>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- JavaScript Controller for Fields and Accordions -->
    <script>
    jQuery(document).ready(function($) {
        let fieldCount = <?php echo !empty($all_fields) ? max(array_keys($all_fields)) + 1 : 100; ?>;
        let activeDocFilter = 'all';

        // WordPress Media Uploader for Logos
        $(document).on('click', '.crm-media-upload-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const targetInputId = btn.data('target-input');
            const targetPreviewId = btn.data('target-preview');
            const inputField = $('#' + targetInputId);
            const previewImg = $('#' + targetPreviewId);

            if (typeof wp === 'undefined' || !wp.media) {
                alert('<?php echo esc_js(__('Die WordPress Medienverwaltung konnte nicht geladen werden.', 'custom-crm')); ?>');
                return;
            }

            const customUploader = wp.media({
                title: '<?php echo esc_js(__('Logo auswählen oder hochladen', 'custom-crm')); ?>',
                button: {
                    text: '<?php echo esc_js(__('Als Logo verwenden', 'custom-crm')); ?>'
                },
                multiple: false
            });

            customUploader.on('select', function() {
                const attachment = customUploader.state().get('selection').first().toJSON();
                inputField.val(attachment.url);
                previewImg.attr('src', attachment.url).show();
            });

            customUploader.open();
        });

        // Remove Logo Button
        $(document).on('click', '.crm-media-remove-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const targetInputId = btn.data('target-input');
            const targetPreviewId = btn.data('target-preview');
            $('#' + targetInputId).val('');
            $('#' + targetPreviewId).attr('src', '').hide();
        });

        // Reset to default Logo
        $(document).on('click', '.crm-reset-default-logo-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const defaultLogo = btn.data('default-logo');
            const targetInputId = btn.data('target-input');
            const targetPreviewId = btn.data('target-preview');
            $('#' + targetInputId).val(defaultLogo);
            $('#' + targetPreviewId).attr('src', defaultLogo).show();
        });

        // CI Color Picker Synchronizer
        $(document).on('input change', '.crm-color-picker', function() {
            const hexInputId = $(this).data('target-hex');
            $('#' + hexInputId).val($(this).val());
        });
        $(document).on('input', '.crm-color-hex-input', function() {
            const val = $(this).val().trim();
            const picker = $(this).closest('.crm-color-item').find('.crm-color-picker');
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                picker.val(val);
            }
        });

        // Toggle Accordion for single block
        $(document).on('click', '.crm-field-header', function(e) {
            if ($(e.target).closest('button, input, select, .crm-action-group').length) {
                return; // don't toggle when clicking actions
            }
            const content = $(this).next('.crm-field-content');
            content.slideToggle(180);
            $(this).find('.crm-accordion-arrow').toggleClass('dashicons-arrow-down dashicons-arrow-right');
        });

        // Toggle all accordions
        $('#crm-toggle-all-accordions').on('click', function(e) {
            e.preventDefault();
            const contents = $('.crm-field-content');
            const anyVisible = contents.is(':visible');
            if (anyVisible) {
                contents.slideUp(180);
                $('.crm-accordion-arrow').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
            } else {
                contents.slideDown(180);
                $('.crm-accordion-arrow').removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
            }
        });

        // Combined Live Filter (Document Pill + Keyword Search)
        function filterFields() {
            const val = $('#crm-field-search').val().toLowerCase().trim();
            $('.crm-field-block').each(function() {
                const docType = $(this).attr('data-doc') || 'general';
                const title   = $(this).find('.crm-field-title-text').text().toLowerCase();
                const badge   = $(this).find('.crm-usage-badge').text().toLowerCase();

                const matchesDoc    = (activeDocFilter === 'all' || docType === activeDocFilter);
                const matchesSearch = (!val || title.indexOf(val) !== -1 || badge.indexOf(val) !== -1);

                if (matchesDoc && matchesSearch) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        // Live Filter / Search
        $('#crm-field-search').on('keyup', function() {
            filterFields();
        });

        // Document filter pills
        $(document).on('click', '.crm-doc-pill', function(e) {
            e.preventDefault();
            $('.crm-doc-pill').removeClass('active');
            $(this).addClass('active');
            activeDocFilter = $(this).attr('data-doc') || 'all';
            filterFields();

            // Synchronize PDF preview if on PDF tab
            if (activeDocFilter !== 'all' && activeDocFilter !== 'general') {
                loadPdfPreview(activeDocFilter, false);
            }
        });

        // Click-to-copy placeholder chips
        $('.crm-chip').on('click', function(e) {
            e.preventDefault();
            const code = $(this).data('code');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    const original = $(this).text();
                    $(this).text('✓ Kopiert!').css('background', '#dcfce7');
                    setTimeout(() => {
                        $(this).text(original).css('background', '');
                    }, 1200);
                });
            }
        });

        // Add new field via AJAX
        function addFieldAjax(category) {
            const newIndex = fieldCount++;
            const ajaxData = {
                action: 'crm_add_field_editor',
                index: newIndex,
                category: category
            };
            $.post(ajaxurl, ajaxData, function(response) {
                $('#crm-fields-wrapper').append(response);
                const newBlock = $('.crm-field-block[data-index="' + newIndex + '"]');

                // If adding PDF field, reset document pills to show all
                if (category === 'pdf') {
                    activeDocFilter = 'all';
                    $('.crm-doc-pill').removeClass('active').filter('[data-doc="all"]').addClass('active');
                    $('#crm-field-search').val('');
                    $('.crm-field-block').show();
                }

                newBlock.find('.crm-field-content').show();
                newBlock.find('.crm-accordion-arrow').removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
                $('html, body').animate({
                    scrollTop: newBlock.offset().top - 100
                }, 300);
            });
        }

        $('#add-crm-email-field').on('click', function(e) {
            e.preventDefault();
            addFieldAjax('email');
        });

        $('#add-crm-pdf-field').on('click', function(e) {
            e.preventDefault();
            addFieldAjax('pdf');
        });

        // Remove field
        $(document).on('click', '.remove-crm-field', function(e) {
            e.preventDefault();
            if (confirm('<?php echo esc_js(__('Diesen Textbaustein wirklich löschen?', 'custom-crm')); ?>')) {
                $(this).closest('.crm-field-block').slideUp(180, function() {
                    $(this).remove();
                });
            }
        });

        // Individual field save via AJAX
        $(document).on('click', '.crm-save-field', function(e) {
            e.preventDefault();
            const btn = $(this);
            const fieldBlock = btn.closest('.crm-field-block');
            const index = fieldBlock.data('index');
            const title = fieldBlock.find('input[name="crm_fields[' + index + '][title]"]').val();
            const category = fieldBlock.find('select[name="crm_fields[' + index + '][category]"]').val();
            const statusIndicator = fieldBlock.find('.save-status');

            let content = '';
            const editorId = 'crm_fields_' + index + '_content';
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                content = tinymce.get(editorId).getContent();
            } else {
                content = fieldBlock.find('textarea[name="crm_fields[' + index + '][content]"]').val();
            }

            btn.prop('disabled', true).text('Speichern...');

            const ajaxData = {
                action: 'crm_save_field_individual',
                nonce: '<?php echo wp_create_nonce('save_crm_field_individual'); ?>',
                index: index,
                title: title,
                content: content,
                category: category
            };

            $.post(ajaxurl, ajaxData, function(response) {
                btn.prop('disabled', false).text('<?php echo esc_js(__('Feld speichern', 'custom-crm')); ?>');
                if (response.success) {
                    fieldBlock.find('.crm-field-title-text').text(title || '<?php echo esc_js(__('Neues Feld', 'custom-crm')); ?>');
                    if (response.data && response.data.usage) {
                        fieldBlock.attr('data-doc', response.data.usage.doc || 'general');
                        fieldBlock.find('.crm-usage-badge').text(response.data.usage.badge).css('background-color', response.data.usage.color);
                    }
                    statusIndicator.text('✓ Gespeichert!').css({color: '#16a34a', fontWeight: '600'}).fadeIn().delay(2500).fadeOut();

                    // Auto-refresh PDF preview if on PDF tab
                    if ($('#crm-pdf-preview-section').length) {
                        loadPdfPreview(currentPreviewDoc, true);
                    }
                } else {
                    statusIndicator.text('Fehler beim Speichern.').css({color: '#dc2626'}).fadeIn().delay(2500).fadeOut();
                }
            });
        });

        // ==========================================
        // PDF LIVE PREVIEW CONTROLLER
        // ==========================================
        let currentPreviewDoc = 'kb';
        let isPreviewExpanded = false;
        const docTitles = {
            'kb': '<?php echo esc_js(__('Kurszeitenbestätigung (KB)', 'custom-crm')); ?>',
            'tb': '<?php echo esc_js(__('Teilnahmebestätigung (TB)', 'custom-crm')); ?>',
            'diplom': '<?php echo esc_js(__('Diplom / Zertifikat', 'custom-crm')); ?>',
            'angebot': '<?php echo esc_js(__('Angebot & Anhang', 'custom-crm')); ?>',
            'invoice': '<?php echo esc_js(__('Honorarnote / Rechnung', 'custom-crm')); ?>'
        };

        function loadPdfPreview(docType, forceReload) {
            if (!$('#crm-pdf-preview-section').length) {
                return;
            }

            if (!docType || docType === 'all' || docType === 'general') {
                docType = currentPreviewDoc || 'kb';
            }

            currentPreviewDoc = docType;

            // Update UI state in preview header
            $('#crm-preview-doc-title').text(docTitles[docType] || docType.toUpperCase());
            $('.crm-preview-switch-btn').removeClass('active');
            $('.crm-preview-switch-btn[data-doc="' + docType + '"]').addClass('active');

            // Show loading overlay
            $('#crm-preview-error').hide();
            $('#crm-preview-loading').fadeIn(150);
            $('#crm-preview-reload-btn .crm-reload-icon').addClass('spin');

            const previewNonce = window.crmPreviewNonce || (typeof crmData !== 'undefined' && crmData.nonce ? crmData.nonce : '<?php echo wp_create_nonce('crm_pdf_preview_nonce'); ?>');

            const ajaxData = {
                action: 'crm_get_pdf_preview_url',
                nonce: previewNonce,
                doc_type: docType
            };

            $.post(ajaxurl, ajaxData, function(response) {
                if (response.success && response.data && response.data.url) {
                    if (response.data.new_nonce) {
                        window.crmPreviewNonce = response.data.new_nonce;
                    }
                    if (response.data.sample_info) {
                        $('#crm-preview-sample-info').text(response.data.sample_info);
                    }
                    const bustParam = (forceReload ? '&reload=' : '&t=') + Date.now();
                    const previewUrl = response.data.url + (response.data.url.indexOf('?') !== -1 ? bustParam : '?' + bustParam.substr(1)) + '#toolbar=0';

                    $('#crm-pdf-preview-iframe').attr('src', previewUrl);
                    $('#crm-preview-newtab-btn').attr('href', response.data.url);
                    $('#crm-preview-download-btn').attr('href', response.data.url);
                    $('#crm-preview-updated-at').text('<?php echo esc_js(__('Stand: ', 'custom-crm')); ?>' + new Date().toLocaleTimeString());

                    $('#crm-pdf-preview-iframe').off('load').on('load', function() {
                        $('#crm-preview-loading').fadeOut(200);
                    });
                    setTimeout(function() {
                        $('#crm-preview-loading').fadeOut(200);
                    }, 1800);
                } else {
                    const errMsg = (response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('Fehler beim Generieren der PDF-Vorschau.', 'custom-crm')); ?>';
                    $('#crm-preview-error-msg').text(errMsg);
                    $('#crm-preview-loading').hide();
                    $('#crm-preview-error').fadeIn(150);
                }
            }).fail(function() {
                $('#crm-preview-error-msg').text('<?php echo esc_js(__('Serververbindung fehlgeschlagen.', 'custom-crm')); ?>');
                $('#crm-preview-loading').hide();
                $('#crm-preview-error').fadeIn(150);
            }).always(function() {
                $('#crm-preview-reload-btn .crm-reload-icon').removeClass('spin');
            });
        }

        // Preview document switcher inside preview card
        $(document).on('click', '.crm-preview-switch-btn', function(e) {
            e.preventDefault();
            const targetDoc = $(this).attr('data-doc');
            if (targetDoc) {
                loadPdfPreview(targetDoc, false);
                // Also sync with document filter pill if matching
                if ($('.crm-doc-pill[data-doc="' + targetDoc + '"]').length) {
                    $('.crm-doc-pill').removeClass('active');
                    $('.crm-doc-pill[data-doc="' + targetDoc + '"]').addClass('active');
                    activeDocFilter = targetDoc;
                    filterFields();
                }
            }
        });

        // Preview reload button
        $('#crm-preview-reload-btn').on('click', function(e) {
            e.preventDefault();
            loadPdfPreview(currentPreviewDoc, true);
        });

        // Preview height expand/contract button
        $('#crm-preview-toggle-size-btn').on('click', function(e) {
            e.preventDefault();
            isPreviewExpanded = !isPreviewExpanded;
            const newHeight = isPreviewExpanded ? '960px' : '680px';
            $('#crm-pdf-preview-iframe').css('height', newHeight);
            $('.crm-preview-body').css('min-height', newHeight);
            $(this).find('.dashicons').toggleClass('dashicons-editor-expand dashicons-editor-contract');
        });

        // "Vorschau" button on individual field block
        $(document).on('click', '.crm-preview-this-doc', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const docType = $(this).attr('data-doc');
            if (docType && docType !== 'general') {
                loadPdfPreview(docType, false);
                if ($('#crm-pdf-preview-section').length) {
                    $('html, body').animate({
                        scrollTop: $('#crm-pdf-preview-section').offset().top - 40
                    }, 350);
                }
            }
        });

        // Initial preview load if on PDF tab
        if ($('#crm-pdf-preview-section').length) {
            const initialDoc = ($('.crm-doc-pill.active').length && $('.crm-doc-pill.active').attr('data-doc') !== 'all')
                ? $('.crm-doc-pill.active').attr('data-doc')
                : 'kb';
            loadPdfPreview(initialDoc, false);
        }
    });
    </script>

    <!-- Page Specific Styles -->
    <style>
        .crm-settings-wrap {
            max-width: 1100px;
        }
        .crm-main-title {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .crm-version-badge {
            font-size: 11px;
            font-weight: 600;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            padding: 3px 9px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .crm-tab-nav .nav-tab {
            font-size: 13.5px;
            font-weight: 600;
            padding: 7px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        .crm-tab-count {
            background: #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 10px;
            margin-left: 2px;
        }
        .nav-tab-active .crm-tab-count {
            background: #0284c7;
            color: #fff;
        }
        .crm-doc-pill {
            font-size: 12px !important;
            font-weight: 600 !important;
            padding: 3px 12px !important;
            height: 30px !important;
            border-radius: 15px !important;
            line-height: 22px !important;
            cursor: pointer;
            border: 1px solid #cbd5e1 !important;
            background: #fff !important;
            color: #475569 !important;
            transition: all 0.15s ease !important;
        }
        .crm-doc-pill:hover {
            border-color: #94a3b8 !important;
            background: #f8fafc !important;
        }
        .crm-doc-pill.active {
            background: #007C90 !important;
            border-color: #007C90 !important;
            color: #fff !important;
            box-shadow: 0 1px 3px rgba(0, 124, 144, 0.3) !important;
        }
        .crm-doc-pill.active span.dashicons {
            color: #fff !important;
        }
        .crm-field-block {
            margin-bottom: 12px;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            transition: border-color 0.15s ease;
        }
        .crm-field-block:hover {
            border-color: #94a3b8;
        }
        .crm-field-header {
            cursor: pointer;
            background: #f8fafc;
            padding: 10px 14px;
            border-bottom: 1px solid transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
        }
        .crm-field-header:hover {
            background: #f1f5f9;
        }
        .crm-field-header h3 {
            margin: 0;
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .crm-usage-badge {
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: -0.01em;
        }
        .crm-category-tag {
            font-size: 10.5px;
            font-weight: 600;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 1px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .crm-field-content {
            padding: 16px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: none; /* Initially collapsed */
        }
        .crm-action-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .save-status {
            font-size: 12px;
            margin-left: 6px;
            display: none;
        }
        .crm-chip {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 11px;
            font-family: monospace;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        /* PDF Preview Card & Toolbar */
        .crm-preview-card {
            transition: box-shadow 0.2s ease;
        }
        .crm-preview-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .crm-preview-switch-btn {
            height: 30px !important;
            line-height: 28px !important;
            padding: 0 10px !important;
            font-size: 12px !important;
            border-color: #cbd5e1 !important;
            background: #ffffff !important;
            color: #334155 !important;
            transition: all 0.15s ease !important;
        }
        .crm-preview-switch-btn:hover {
            border-color: #7c3aed !important;
            color: #6d28d9 !important;
        }
        .crm-preview-switch-btn.active {
            background: #7c3aed !important;
            border-color: #6d28d9 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }
        @keyframes crm-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spin {
            animation: crm-spin 1.1s linear infinite !important;
            display: inline-block !important;
        }
        .crm-preview-this-doc {
            height: 30px !important;
            line-height: 28px !important;
            padding: 0 9px !important;
            font-size: 12px !important;
            transition: all 0.15s ease;
        }
        .crm-preview-this-doc:hover {
            background: #f5f3ff !important;
            border-color: #7c3aed !important;
            color: #5b21b6 !important;
        }
    </style>
    <?php
}

/**
 * Render collapsible placeholder chips cheat-sheet box.
 *
 * @param string $type 'email' or 'pdf'
 */
function crm_render_placeholders_cheat_sheet(string $type = 'email')
{
    ?>
    <div class="crm-cheat-sheet" style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
        <details>
            <summary style="cursor: pointer; font-size: 12.5px; font-weight: 600; color: #0369a1; outline: none; user-select: none;">
                <span class="dashicons dashicons-editor-code" style="vertical-align: text-top; font-size: 16px;"></span>
                <?php esc_html_e('Klickbare Platzhalter / Variablen anzeigen (Klick kopiert in die Zwischenablage)', 'custom-crm'); ?>
            </summary>
            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span style="font-size:11.5px; font-weight:600; color:#475569; width:90px;">Person:</span>
                    <a href="#" class="crm-chip" data-code="{salutation}">{salutation} (Sehr geehrte/r...)</a>
                    <a href="#" class="crm-chip" data-code="{anrede}">{anrede}</a>
                    <a href="#" class="crm-chip" data-code="{titel}">{titel}</a>
                    <a href="#" class="crm-chip" data-code="{vorname}">{vorname}</a>
                    <a href="#" class="crm-chip" data-code="{nachname}">{nachname}</a>
                    <a href="#" class="crm-chip" data-code="{email}">{email}</a>
                    <a href="#" class="crm-chip" data-code="{svr}">{svr} (SV-Nummer)</a>
                    <a href="#" class="crm-chip" data-code="{street}">{street} (Straße & Nr.)</a>
                    <a href="#" class="crm-chip" data-code="{zip_code}">{zip_code} (PLZ)</a>
                    <a href="#" class="crm-chip" data-code="{city}">{city} (Ort)</a>
                </div>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span style="font-size:11.5px; font-weight:600; color:#475569; width:90px;">Kurs:</span>
                    <a href="#" class="crm-chip" data-code="{title}">{title} (Voller Kurstitel)</a>
                    <a href="#" class="crm-chip" data-code="{titel_short}">{titel_short}</a>
                    <a href="#" class="crm-chip" data-code="{kurstyp}">{kurstyp}</a>
                    <a href="#" class="crm-chip" data-code="{start_datum}">{start_datum}</a>
                    <a href="#" class="crm-chip" data-code="{end_datum}">{end_datum}</a>
                    <a href="#" class="crm-chip" data-code="{anzahl_le}">{anzahl_le} (LE)</a>
                    <a href="#" class="crm-chip" data-code="{preis_brutto}">{preis_brutto} €</a>
                    <a href="#" class="crm-chip" data-code="{preis_netto}">{preis_netto} €</a>
                    <a href="#" class="crm-chip" data-code="{trainer}">{trainer}</a>
                    <a href="#" class="crm-chip" data-code="{expire}">{expire} (Gültigkeit)</a>
                    <a href="#" class="crm-chip" data-code="{zielgruppe}">{zielgruppe}</a>
                </div>
                <?php if ($type === 'email') : ?>
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <span style="font-size:11.5px; font-weight:600; color:#475569; width:90px;">Bausteine:</span>
                        <a href="#" class="crm-chip" data-code="{signatur}">{signatur}</a>
                        <a href="#" class="crm-chip" data-code="{email_footer}">{email_footer}</a>
                        <a href="#" class="crm-chip" data-code="{bankverbindung}">{bankverbindung}</a>
                    </div>
                <?php else : ?>
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <span style="font-size:11.5px; font-weight:600; color:#475569; width:90px;">PDF Spezial:</span>
                        <a href="#" class="crm-chip" data-code="{current_date}">{current_date} (Datum d.m.Y)</a>
                        <a href="#" class="crm-chip" data-code="{diplom_success}">{diplom_success} (mit [Erfolg])</a>
                    </div>
                <?php endif; ?>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span style="font-size:11.5px; font-weight:600; color:#475569; width:90px;">Institut & CI:</span>
                    <a href="#" class="crm-chip" data-code="{company_name}">{company_name}</a>
                    <a href="#" class="crm-chip" data-code="{company_address}">{company_address}</a>
                    <a href="#" class="crm-chip" data-code="{location_wien}">{location_wien}</a>
                    <a href="#" class="crm-chip" data-code="{company_phone}">{company_phone}</a>
                    <a href="#" class="crm-chip" data-code="{company_email}">{company_email}</a>
                    <a href="#" class="crm-chip" data-code="{company_uid}">{company_uid}</a>
                    <a href="#" class="crm-chip" data-code="{company_fn}">{company_fn}</a>
                    <a href="#" class="crm-chip" data-code="{company_bank}">{company_bank}</a>
                    <a href="#" class="crm-chip" data-code="{company_logo}">{company_logo}</a>
                    <a href="#" class="crm-chip" data-code="{backoffice_name}">{backoffice_name}</a>
                </div>
            </div>
        </details>
    </div>
    <?php
}

/**
 * Render a single field inside an accordion block.
 *
 * @param int|string $index
 * @param string $title
 * @param string $content
 * @param string $category 'email' or 'pdf'
 */
function crm_render_editor_field($index, $title, $content, $category = 'email')
{
    $usage = crm_get_field_usage_info($title);
    $doc   = $usage['doc'] ?? ($category === 'email' ? 'email' : 'general');
    ?>
    <div class="crm-field-block" data-index="<?php echo esc_attr($index); ?>" data-doc="<?php echo esc_attr($doc); ?>">
        <div class="crm-field-header">
            <h3>
                <span class="dashicons dashicons-arrow-right crm-accordion-arrow" style="color: #64748b;"></span>
                <span class="crm-field-title-text"><?php echo $title ? esc_html($title) : esc_html__('Neuer Textbaustein', 'custom-crm'); ?></span>
                <span class="crm-usage-badge" style="background-color: <?php echo esc_attr($usage['color']); ?>;">
                    <?php echo esc_html($usage['badge']); ?>
                </span>
            </h3>
            <div class="crm-action-group">
                <span class="save-status"></span>
                <button type="button" class="button crm-save-field" style="border-color: #cbd5e1;">
                    <span class="dashicons dashicons-saved" style="vertical-align: text-top; font-size: 15px;"></span>
                    <?php esc_html_e('Feld speichern', 'custom-crm'); ?>
                </button>
                <?php if ($category === 'pdf' && $doc !== 'general') : ?>
                    <button type="button" class="button crm-preview-this-doc" data-doc="<?php echo esc_attr($doc); ?>" title="<?php esc_attr_e('Dieses PDF in der Live-Vorschau anzeigen', 'custom-crm'); ?>" style="border-color: #cbd5e1; color: #7c3aed;">
                        <span class="dashicons dashicons-visibility" style="vertical-align: text-top; font-size: 15px;"></span>
                        <?php esc_html_e('Vorschau', 'custom-crm'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="button remove-crm-field" style="color: #dc2626; border-color: #fca5a5;">
                    <span class="dashicons dashicons-trash" style="vertical-align: text-top; font-size: 15px;"></span>
                    <?php esc_html_e('Löschen', 'custom-crm'); ?>
                </button>
            </div>
        </div>

        <div class="crm-field-content">
            <div style="display: flex; gap: 16px; margin-bottom: 12px; align-items: flex-start; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 250px;">
                    <label style="font-weight: 600; font-size: 12.5px; color: #334155; display: block; margin-bottom: 4px;">
                        <?php esc_html_e('Titel des Bausteins (z.B. E-Mail Angebot oder Bankverbindung):', 'custom-crm'); ?>
                    </label>
                    <input type="text"
                           name="crm_fields[<?php echo esc_attr($index); ?>][title]"
                           value="<?php echo esc_attr($title); ?>"
                           class="regular-text widefat"
                           style="height: 34px; border-radius: 4px; font-weight: 600;"
                           required />
                </div>

                <div style="flex: 1; min-width: 180px;">
                    <label style="font-weight: 600; font-size: 12.5px; color: #334155; display: block; margin-bottom: 4px;">
                        <?php esc_html_e('Kategorie / Zuordnung:', 'custom-crm'); ?>
                    </label>
                    <select name="crm_fields[<?php echo esc_attr($index); ?>][category]" style="height: 34px; width: 100%; border-radius: 4px;">
                        <option value="email" <?php selected($category, 'email'); ?>><?php esc_html_e('✉️ E-Mail Vorlage', 'custom-crm'); ?></option>
                        <option value="pdf" <?php selected($category, 'pdf'); ?>><?php esc_html_e('📄 PDF Baustein', 'custom-crm'); ?></option>
                    </select>
                </div>
            </div>

            <?php if (!empty($usage['desc'])) : ?>
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #64748b; font-style: italic;">
                    <span class="dashicons dashicons-info" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                    <?php echo esc_html($usage['desc']); ?>
                </p>
            <?php endif; ?>

            <div>
                <label style="font-weight: 600; font-size: 12.5px; color: #334155; display: block; margin-bottom: 4px;">
                    <?php esc_html_e('Inhalt / Textvorlage:', 'custom-crm'); ?>
                </label>
                <?php
                wp_editor(
                    $content,
                    "crm_fields_{$index}_content",
                    [
                        'textarea_name' => "crm_fields[{$index}][content]",
                        'textarea_rows' => 8,
                        'media_buttons' => true,
                        'tinymce'       => true,
                        'quicktags'     => true,
                    ]
                );
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler to add a new field editor dynamically.
 */
add_action('wp_ajax_crm_add_field_editor', 'crm_add_field_editor_ajax_handler');
function crm_add_field_editor_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    $index    = intval($_POST['index'] ?? 0);
    $category = sanitize_text_field($_POST['category'] ?? 'email');
    if (!in_array($category, ['email', 'pdf'], true)) {
        $category = 'email';
    }

    $default_title = ($category === 'email') ? 'Neue E-Mail Vorlage' : 'Neuer PDF Baustein';
    crm_render_editor_field($index, $default_title, '', $category);
    wp_die();
}

/**
 * AJAX handler to save a single field individually.
 */
add_action('wp_ajax_crm_save_field_individual', 'crm_save_field_individual_ajax_handler');
function crm_save_field_individual_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $nonce = $_POST['nonce'] ?? ($_REQUEST['nonce'] ?? '');
    $nonce_valid = false;
    if (!empty($nonce)) {
        if (wp_verify_nonce($nonce, 'save_crm_field_individual') || wp_verify_nonce($nonce, 'crm_ajax_nonce') || wp_verify_nonce($nonce, 'save_crm_settings')) {
            $nonce_valid = true;
        }
    }
    if (!$nonce_valid && current_user_can('manage_options')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    $index    = intval($_POST['index'] ?? 0);
    $title    = sanitize_text_field($_POST['title'] ?? '');
    $content  = wp_kses_post($_POST['content'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? 'email');
    if (!in_array($category, ['email', 'pdf'], true)) {
        $category = crm_get_field_category(['title' => $title]);
    }

    $fields = get_option('crm_custom_fields', []);
    if (!is_array($fields)) {
        $fields = [];
    }

    $fields[$index] = [
        'title'    => $title,
        'content'  => $content,
        'category' => $category,
    ];

    update_option('crm_custom_fields', $fields);

    $usage = crm_get_field_usage_info($title);
    wp_send_json_success([
        'message' => __('Field saved successfully.', 'custom-crm'),
        'usage'   => $usage,
    ]);
}

/**
 * AJAX handler to generate and return a PDF preview URL.
 */
add_action('wp_ajax_crm_get_pdf_preview_url', 'crm_get_pdf_preview_url_ajax_handler');
function crm_get_pdf_preview_url_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $nonce = $_POST['nonce'] ?? ($_REQUEST['nonce'] ?? '');
    $nonce_valid = false;
    if (!empty($nonce)) {
        if (wp_verify_nonce($nonce, 'crm_pdf_preview_nonce') || wp_verify_nonce($nonce, 'crm_ajax_nonce') || wp_verify_nonce($nonce, 'save_crm_field_individual') || wp_verify_nonce($nonce, 'save_crm_settings')) {
            $nonce_valid = true;
        }
    }
    // Authenticated administrators with manage_options are authorized for preview rendering
    if (!$nonce_valid && current_user_can('manage_options')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error(['message' => __('Ungültige Sicherheitsprüfung (Nonce).', 'custom-crm')]);
    }

    $doc_type = sanitize_key($_POST['doc_type'] ?? 'kb');
    $sample   = crm_get_preview_sample_data();

    $entry_id  = $sample['entry_id'];
    $course_id = $sample['course_id'];

    if (!$entry_id || !$course_id) {
        wp_send_json_error(['message' => __('Keine Beispieldaten (Kurs oder Anfrage) in der Datenbank gefunden.', 'custom-crm')]);
    }

    require_once __DIR__ . '/crm-model.php';

    // Suppress any PHP notices during PDF rendering to prevent corrupted JSON output
    $prev_error_reporting = error_reporting(0);
    ob_start();

    $url = '';
    try {
        switch ($doc_type) {
            case 'kb':
                require_once __DIR__ . '/pdf/kurszeitenbestaetigung.php';
                $url = xsieben_kurszeitenbestaetigung_pdf($entry_id, $course_id, false);
                break;

            case 'tb':
                require_once __DIR__ . '/pdf/teilnamebestaetigung.php';
                $url = xsieben_teilnahmebestaetigung_pdf($entry_id, $course_id, false);
                break;

            case 'diplom':
                require_once __DIR__ . '/pdf/diplom.php';
                $url = xsieben_diplom_pdf($entry_id, $course_id, false);
                break;

            case 'angebot':
                require_once __DIR__ . '/pdf/offer.php';
                $url = xsieben_offer_pdf($entry_id, $course_id, false);
                break;

            case 'invoice':
                require_once __DIR__ . '/pdf/invoice.php';
                $url = xsieben_invoice_pdf($entry_id, $course_id, false);
                break;

            default:
                $url = '';
        }
    } catch (\Throwable $e) {
        error_log('CRM PDF Preview Error: ' . $e->getMessage());
    }

    ob_end_clean();
    error_reporting($prev_error_reporting);

    if (!empty($url)) {
        wp_send_json_success([
            'url'         => $url,
            'doc_type'    => $doc_type,
            'new_nonce'   => wp_create_nonce('crm_pdf_preview_nonce'),
            'sample_info' => sprintf(
                __('Musterdaten: Kurs #%d (%s) & Anfrage #%d (%s)', 'custom-crm'),
                $course_id,
                $sample['course_title'],
                $entry_id,
                $sample['client_name']
            ),
        ]);
    } else {
        wp_send_json_error(['message' => __('Das PDF konnte nicht gerendert werden.', 'custom-crm')]);
    }
}