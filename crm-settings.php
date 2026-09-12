<?php
/**
 * CRM Settings, E-Mail Editor & PDF Editor
 * 
 * Standalone CRM Configuration module for WordPress.
 * Manages Email templates, PDF snippets, and general CRM settings.
 *
 * @version 2.17.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/helpers/crm-pdf-sections.php';
require_once __DIR__ . '/helpers/crm-email-sections.php';
require_once __DIR__ . '/controler/settings-controler.php';
require_once __DIR__ . '/views/settings/components/cheat-sheet.php';
require_once __DIR__ . '/views/settings/components/field-editor.php';

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
 * Bestimmt, ob ein E-Mail-Feld eine vollstÃ¤ndige Vorlage ('full_email')
 * oder eine wiederverwendbare Komponente ('component') ist.
 *
 * @param string $title
 * @param array $field
 * @return string 'full_email'|'component'
 */
function crm_get_email_field_type(string $title, array $field = []): string
{
    if (!empty($field['email_type']) && in_array($field['email_type'], ['full_email', 'component'], true)) {
        return $field['email_type'];
    }

    $t = strtolower(trim($title));

    // SchlÃ¼sselwÃ¶rter fÃ¼r wiederverwendbare Komponenten (Signatur, Footer, Buchung, AGB, Bank etc.)
    $component_keywords = [
        'signatur',
        'footer',
        'buchung',
        'hinweis',
        'ps',
        'p.s.',
        'agb',
        'bank',
        'garantie',
        'fee',
        'anhang',
        'klausel',
        'baustein',
        'komponente',
        'zusatzleistung',
    ];

    foreach ($component_keywords as $ck) {
        if (strpos($t, $ck) !== false) {
            return 'component';
        }
    }

    // SchlÃ¼sselwÃ¶rter fÃ¼r gesamte Master-E-Mails
    if (
        strpos($t, 'e-mail angebot') !== false ||
        strpos($t, 'e-mail anmelde') !== false ||
        strpos($t, 'e-mail kursantritt') !== false ||
        strpos($t, 'e-mail kurszeit') !== false ||
        strpos($t, 'e-mail teilnahme') !== false ||
        strpos($t, 'e-mail diplom') !== false ||
        strpos($t, 'e-mail honorarnote') !== false ||
        strpos($t, 'e-mail rechnung') !== false
    ) {
        return 'full_email';
    }

    // Fallback: Wenn der Titel mit "e-mail " beginnt, eher vollstÃ¤ndige Mail, sonst Komponente
    if (strpos($t, 'e-mail') === 0 || strpos($t, 'email') === 0) {
        return 'full_email';
    }

    return 'component';
}

/**
 * Ermittelt den Platzhalter-Code fÃ¼r eine E-Mail-Komponente zur Verwendung in gesamten E-Mails.
 *
 * @param string $title
 * @return string
 */
function crm_get_component_placeholder_for_title(string $title): string
{
    $t = strtolower(trim($title));
    $map = [
        'e-mail signatur'                   => '{signatur_email}',
        'signatur'                          => '{signatur_email}',
        'e-mail-footer'                     => '{email_footer}',
        'e-mail footer'                     => '{email_footer}',
        'footer'                            => '{email_footer}',
        'anmeldung buchung e-mail text'     => '{buchung_email}',
        'buchung e-mail text'               => '{buchung_email}',
        'buchung e-mail'                    => '{buchung_email}',
        'buchungshinweis'                   => '{buchung_email}',
        'agb text'                          => '{agb_claim}',
        'agb-klausel'                       => '{agb_claim}',
        'agb'                               => '{agb_claim}',
        'bankverbindung'                    => '{bankverbindung}',
        'angebot e-mail hinweis'            => '{angebot_hinweis}',
        'angebot ps'                        => '{angebot_ps}',
        'durchfÃ¼hrungs garantie'            => '{durchfuehrungs_garantie}',
        'durchfuehrungs garantie'           => '{durchfuehrungs_garantie}',
        'teilnahme_fee'                     => '{teilnahme_fee}',
        'anhang 2 | exklusive zusatzleistungen' => '{anhang_2}',
    ];

    if (isset($map[$t])) {
        return $map[$t];
    }

    // Automatische Erzeugung aus dem Titel
    $slug = sanitize_title_with_dashes(str_replace(['Ã¤', 'Ã¶', 'Ã¼', 'ÃŸ'], ['ae', 'oe', 'ue', 'ss'], $t));
    $slug = str_replace(['-', ' '], '_', $slug);
    $slug = preg_replace('/[^a-z0-9_]/', '', $slug);

    return '{' . ($slug ?: 'komponente') . '}';
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
        'company_accreditations' => 'pma / IPMAÂ®, SystemCERT (ISO 17024), TÃœV Austria, wba, CERT NÃ–, AMS',

        // Schulungsinstitut & Demographie
        'company_name'           => 'X SIEBEN Wirtschaftstraining GmbH',
        'company_short_name'     => 'X SIEBEN',
        'company_legal_form'     => 'GmbH',
        'company_management'     => 'Mag. Dr. Johannes Gasberger',

        // Kanzleisitz / Hauptadresse (Wr. Neustadt / LichtenwÃ¶rth)
        'company_street'         => 'Kurzegasse 7',
        'company_zip'            => '2493',
        'company_city'           => 'LichtenwÃ¶rth',
        'company_country'        => 'Ã–sterreich',

        // Schulungszentrum / Standort Wien
        'location_wien_name'     => 'Seminarzentrum Wien',
        'location_wien_street'   => 'Rochusgasse 6',
        'location_wien_zip'      => '1030',
        'location_wien_city'     => 'Wien',
        'location_wien_notice'   => 'Online Unterricht | vor Ort in unseren VeranstaltungsrÃ¤umen | Blended Learning',

        // Kontaktdaten
        'company_phone'          => '0800 700 170',
        'company_email'          => 'office@x-sieben.at',
        'company_website'        => 'https://x-sieben.at',

        // Backoffice Kontakt
        'backoffice_name'        => 'Anna Brauer',
        'backoffice_email'       => 'abrauer@x-sieben.at',
        'backoffice_phone'       => '0800 700 170',

        // BehÃ¶rdliche Daten & Rechtliches
        'company_uid'            => 'ATU76624137',
        'company_fn'             => 'FN 550277 g',
        'company_court'          => 'Landesgericht Wiener Neustadt',
        'company_chamber'        => 'Wirtschaftskammer NiederÃ¶sterreich / Wien',
        'company_bank'           => 'Erste Bank | IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN',

        // Rechtliche Links
        'legal_agb_url'          => 'https://x-sieben.at/wp-content/uploads/2025/09/AGB_X_SIEBEN_2025.pdf',
        'legal_privacy_url'      => 'https://x-sieben.at/datenschutzerklaerung/',
        'legal_imprint_url'      => 'https://x-sieben.at/impressum/',

        // Mailer
        'test_email'             => get_option('crm_test_email', $admin_email),

        // Performance & Cache-Busting (NEXUS Cache Operator C)
        'auto_js_cache_clean'    => get_option('crm_auto_js_cache_clean', '1'),
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
        // --- KurszeitenbestÃ¤tigung (KB) ---
        'KB - Titel' => [
            'content' => 'BestÃ¤tigung Kurszeiten',
            'doc'     => 'kb',
            'badge'   => 'KB - Titel',
            'desc'    => 'Dokumententitel der KurszeitenbestÃ¤tigung.',
        ],
        'KB - Kursinstitut Name' => [
            'content' => 'X SIEBEN Wirtschaftstraining GmbH',
            'doc'     => 'kb',
            'badge'   => 'KB - Institut',
            'desc'    => 'Name des durchfÃ¼hrenden Kursinstituts in der KB.',
        ],
        'KB - Schulungsort' => [
            'content' => 'Rochusgasse 6, 1030 Wien bzw. online',
            'doc'     => 'kb',
            'badge'   => 'KB - Schulungsort',
            'desc'    => 'Standard-Schulungsort (Adresse) in der KB.',
        ],
        'KB - Hinweistext' => [
            'content' => 'Bei unregelmÃ¤ÃŸigen Kurszeiten ist ein Ablaufplan der einzelnen Kurswochen beizulegen.',
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
            'desc'    => 'Unterschriftenzeile fÃ¼r den/die Kursteilnehmer/in.',
        ],

        // --- TeilnahmebestÃ¤tigung (TB) ---
        'TB - Titel' => [
            'content' => 'TeilnahmebestÃ¤tigung',
            'doc'     => 'tb',
            'badge'   => 'TB - Titel',
            'desc'    => 'Dokumententitel der TeilnahmebestÃ¤tigung.',
        ],
        'TB - Einleitung' => [
            'content' => 'Wir bestÃ¤tigen, dass',
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
            'content' => 'LichtenwÃ¶rth',
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
            'desc'    => 'BestÃ¤tigungssatz Ã¼ber die Teilnahme am Lehrgang.',
        ],
        'TB - Datum' => [
            'content' => 'Datum: {current_date}',
            'doc'     => 'tb',
            'badge'   => 'TB - Datum',
            'desc'    => 'Datumszeile auf der TeilnahmebestÃ¤tigung.',
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
            'desc'    => 'GroÃŸe HauptÃ¼berschrift des Abschlussdiploms.',
        ],
        'Diplom - Lehrgang Text' => [
            'content' => 'HAT {kurstyp}',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Lehrgang',
            'desc'    => 'Zwischentitel Ã¼ber dem Kursnamen (dynamisch z. B. "HAT DEN LEHRGANG", "HAT DAS SEMINAR" via {kurstyp}).',
        ],
        'Diplom - Einheiten Text' => [
            'content' => '{anzahl_le} Lehreinheiten Ã  45 Minuten',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Einheiten',
            'desc'    => 'Textzeile fÃ¼r Lehreinheiten / Umfang.',
        ],
        'Diplom - Zeitraum Text' => [
            'content' => 'Im Zeitraum vom {start_datum} bis zum {end_datum}',
            'doc'     => 'diplom',
            'badge'   => 'Diplom - Zeitraum',
            'desc'    => 'Textzeile fÃ¼r Lehrgangszeitraum (Start- bis Enddatum).',
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
            'desc'    => 'Rechtlicher FuÃŸzeilentext auf Seite 1 des Diploms.',
        ],

        // --- Angebot & Anmeldung ---
        'Angebot - Einleitung' => [
            'content' => 'Danke fÃ¼r Ihr Interesse und willkommen bei der beliebten X SIEBEN Veranstaltung {title} mit lernfÃ¶rderndem Kleingruppen-Unterricht.<br><br>Diese Veranstaltung fokussiert auf {zielgruppe}',
            'doc'     => 'angebot',
            'badge'   => 'Angebot - Einleitung',
            'desc'    => 'Einleitungstext auf Seite 1 des Angebots-PDFs.',
        ],
        'Angebot - GruÃŸformel' => [
            'content' => "Ich freue mich Ã¼ber Ihre RÃ¼ckmeldung / Buchung.<br>\nMit freundlichen GrÃ¼ÃŸen,",
            'doc'     => 'angebot',
            'badge'   => 'Angebot - GruÃŸformel',
            'desc'    => 'GruÃŸformel vor der Signatur auf Seite 1 des Angebots.',
        ],
        'Angebot - Ort und DurchfÃ¼hrung' => [
            'content' => '<table class="text"><tr><td style="width:92%; font-size: 10.5pt;"><strong>ORT:</strong> X SIEBEN Wirtschaftstraining, Rochusgasse 6 in 1030 Wien</td></tr></table><div style="font-size:12pt">&nbsp;</div><table class="text"><tr><td style="line-height: 16pt; color: #334155;">DurchfÃ¼hrung unserer Schulungen: Online Unterricht | vor Ort in unseren VeranstaltungsrÃ¤umen | Blended Learning<br><span style="color: #475569; font-size: 9.5pt;">Hinweis: Die Schulung wird bis zur TeilnehmerInnen-Anzahl von drei Personen adÃ¤quat verkÃ¼rzt, wobei alle Inhalte vermittelt werden.</span></td></tr></table>',
            'doc'     => 'angebot',
            'badge'   => 'Angebot - Schulungsort',
            'desc'    => 'Schulungsort und DurchfÃ¼hrungshinweis im Angebots-PDF.',
        ],
        'AGB text' => [
            'content' => 'Bitte beachten Sie unsere Allgemeinen GeschÃ¤ftsbedingungen (AGB). Mit Ihrer Buchung akzeptieren Sie unsere Richtlinien.',
            'doc'     => 'angebot',
            'badge'   => 'AGB & Klausel',
            'desc'    => 'Rechtlicher Anmelde- und AGB-Hinweis im Anhang von Angebot & Anmeldung.',
        ],
        'Bankverbindung' => [
            'content' => '<strong>Bankverbindung:</strong> Erste Bank | IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN',
            'doc'     => 'angebot',
            'badge'   => 'Bankverbindung',
            'desc'    => 'Bank- und Ãœberweisungsdaten im PDF-Angebot und der Honorarnote.',
        ],
        'Anhang 2 | Exklusive Zusatzleistungen' => [
            'content' => '3-fach sicher mit unserer DurchfÃ¼hrungsgarantie, Zufriedenheitsgarantie und Zertifizierungsbegleitung.',
            'doc'     => 'angebot',
            'badge'   => 'Anhang 2',
            'desc'    => 'Details zu 3-fach sicher, Garantien, Storno und Ersatzteilnehmern.',
        ],
        'DurchfÃ¼hrungs Garantie' => [
            'content' => 'Unsere Seminare finden bereits ab 1 Person garantiert statt.',
            'doc'     => 'angebot',
            'badge'   => 'Garantie',
            'desc'    => 'DurchfÃ¼hrungsgarantie ab Kleingruppe im PDF-Angebot.',
        ],
        'Angebot PS' => [
            'content' => 'PS: Profitieren Sie von unseren flexiblen TeilzahlungsmÃ¶glichkeiten und ProvenExpert-Top-Bewertungen.',
            'doc'     => 'angebot',
            'badge'   => 'Postskriptum',
            'desc'    => 'PS-Hinweis auf ProvenExpert-Bewertungen und Klarna-Ratenzahlung.',
        ],
        'Teilnahme_fee' => [
            'content' => 'Die TeilnahmegebÃ¼hr ist 14 Tage vor Kursbeginn spesenfrei fÃ¤llig.',
            'doc'     => 'angebot',
            'badge'   => 'Zahlungsfristen',
            'desc'    => 'Konditionen zur ZahlungsfÃ¤lligkeit im PDF.',
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
            'desc'    => 'Einleitender Text Ã¼ber der Leistungstabelle.',
        ],
        'Honorarnote - Zahlungsanweisung' => [
            'content' => 'Bitte Ã¼berweisen Sie den Betrag bis zum [Datum] auf das Konto von X SIEBEN Wirtschaftstraining GmbH.<br>IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN',
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
        'e-mail anmeldebestÃ¤tigung' => [
            'badge' => 'Anmeldung (E-Mail)',
            'desc'  => 'Wird bei Buchungs- und AnmeldebestÃ¤tigungen als Nachrichtentext geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail diplom' => [
            'badge' => 'Diplom (E-Mail)',
            'desc'  => 'Wird beim Versenden des Diploms / Abschlusszertifikats geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail kursantrittsbestÃ¤tigung' => [
            'badge' => 'Kursantritt (E-Mail)',
            'desc'  => 'Wird beim Versenden der KursantrittsbestÃ¤tigung geladen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail teilnahmebestÃ¤tigung - allgemein' => [
            'badge' => 'TB Standard (E-Mail)',
            'desc'  => 'Wird bei regulÃ¤ren TeilnahmebestÃ¤tigungen (TB) verwendet.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'e-mail teilnahmebestÃ¤tigung - fÃ¶rderung' => [
            'badge' => 'TB FÃ¶rderung (E-Mail)',
            'desc'  => 'Wird bei FÃ¶rderungsbestÃ¤tigungen (AMS, waff, FÃ¶rderstellen) verwendet.',
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
            'desc'  => 'ErgÃ¤nzender Hinweistext fÃ¼r Buchungen und RÃ¼ckmeldungen.',
            'color' => '#0284c7',
            'doc'   => 'email',
        ],
        'angebot e-mail hinweis' => [
            'badge' => 'GÃ¼ltigkeit (E-Mail)',
            'desc'  => 'Hinweis auf limitierte GruppengrÃ¶ÃŸe und Angebotsfrist.',
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
        'desc'  => 'Benutzerdefinierter Textbaustein fÃ¼r PDF-Vorlagen.',
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

            // Sync auto_js_cache_clean flag
            $auto_cache = isset($_POST['crm_general']['auto_js_cache_clean']) ? 1 : 0;
            update_option('crm_auto_js_cache_clean', $auto_cache);
            $sanitized['auto_js_cache_clean'] = $auto_cache;

            update_option('crm_general_settings', $sanitized);

            if ($auto_cache && function_exists('crm_on_partial_cache_update')) {
                crm_on_partial_cache_update('general_settings');
            }
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
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Standard-PDF-Felder erfolgreich synchronisiert (%d neue Bausteine hinzugefÃ¼gt). Bereits existierende Bausteine blieben unverÃ¤ndert.', 'custom-crm'), $added_count) . '</p></div>';
        }
        // Master Header & Footer PDF Settings Save
        elseif (isset($_POST['submit_pdf_master_hf']) || isset($_POST['crm_pdf_master_hf'])) {
            $master_input = isset($_POST['crm_pdf_master_hf']) && is_array($_POST['crm_pdf_master_hf']) ? $_POST['crm_pdf_master_hf'] : [];
            crm_save_pdf_master_header_footer($master_input);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Kopf- & FuÃŸzeilen Master-Einstellungen erfolgreich gespeichert.', 'custom-crm') . '</p></div>';
        }
        // 3. Normal Email Tab Save
        elseif ($saved_tab === 'emails') {
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

            // Speichere die Standard-Betreffzeilen der 7 E-Mail-Typen
            if (!empty($_POST['crm_email_subject']) && is_array($_POST['crm_email_subject'])) {
                foreach ($_POST['crm_email_subject'] as $doc_k => $doc_subj) {
                    $clean_k = sanitize_key($doc_k);
                    $clean_subj = sanitize_text_field(wp_unslash($doc_subj));
                    update_option('crm_email_subject_' . $clean_k, $clean_subj);
                }
            }

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

    // Partition email fields into Full Emails vs Reusable Components
    $full_emails      = [];
    $component_emails = [];

    foreach ($email_fields as $orig_idx => $field) {
        $sub_type = crm_get_email_field_type($field['title'] ?? '', $field);
        if ($sub_type === 'full_email') {
            $full_emails[$orig_idx] = $field;
        } else {
            $component_emails[$orig_idx] = $field;
        }
    }

    uasort($full_emails, function ($a, $b) {
        return strcmp($a['title'] ?? '', $b['title'] ?? '');
    });
    uasort($component_emails, function ($a, $b) {
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
                <?php esc_html_e('PDF Editor & Abschnitte', 'custom-crm'); ?>
                <span class="crm-tab-count">5</span>
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
                <?php require __DIR__ . '/views/settings/tab-general.php'; ?>
            <?php elseif ($active_tab === 'emails') : ?>
                <?php require __DIR__ . '/views/settings/tab-emails.php'; ?>
            <?php elseif ($active_tab === 'pdf') : ?>
                <?php require __DIR__ . '/views/settings/tab-pdf.php'; ?>
            <?php endif; ?>
        </form>
    </div>
    <?php
}