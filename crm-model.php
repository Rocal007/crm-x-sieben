<?php

require_once __DIR__ . '/helpers/normalize.php';

class CRM_Model
{
    public $post_id, $title, $titel_short, $permalink, $start_datum, $end_datum, $preis_netto, $preis_brutto, $title_preis, $kurstyp, $abschluss;
    public $angebot_beschreibung, $anzahl_le, $le_single, $kursart, $kursart_t = '', $kursart_a = '', $kursart_we = '';
    public $voraussetzungen = [], $kurszeiten = [], $module_html = [], $selbststudium = [], $termine_pdf, $zertifizierungen = [], $zertifizierungen_images = [];
    public $address_components = [], $nummer, $kurszeiten_datum, $pdfAuthor;
    public $ams_img, $web_icon, $mail_icon, $fax_icon, $phone_icon;
    public $calender_icon, $ort_icon, $abschluss_icon, $diplom_icon, $proven_icon;
    public $sitting_icon, $danger_icon, $signatur_icon, $proven_wide;
    public $wba_logo, $cert_noe_logo, $tuef_logo, $sys_zert_logo, $pma_logo, $ipma_logo, $xsieben_logo;
    public $email_logos, $inhalte, $zielgruppe, $anmeldung_agb, $trainer;
    public $current, $expire;
    public $zertifizierungen_images_html, $ps, $garantie;
    public $zertifizierungen_loop_html;
    public $signatur;
    public $entry_data;
    public $voraussetzungen_html;
    public $anrede;
    public $salutation;
    public $titel;
    public $vorname;
    public $nachname;
    public $email;
    public $svr;
    public $customer_company;
    public $customer_type;

    // Eigenschaften für getrennte Adressfelder
    public $street;
    public $house_number;
    public $city;
    public $zip_code;
    public $country;
    public $form_certifications;
    public $agb_claim;
    public $bankverbindung;
    public $texte_fur_diplom_links;
    public $texte_fur_diplom_rechts;
    public $diplom_success;
    public $termine_link;
    public $email_footer;
    public $kursgebuehr_html;
    public $beratung_email;
    public $buchung_email;
    public $signatur_email;
    public $teilnahmebestaetigung_email;
    public $teilnahmebestaetigung_f_email;
    public $angebot_email;
    public $anmeldebestaetigung_email;
    public $anmeldung_email;
    public $diplom_email;

    // Standardisierte Platzhalter & Aliase für E-Mails und PDFs
    public $kurstitel;
    public $kurstitel_short;
    public $startdatum;
    public $enddatum;
    public $uhrzeit;
    public $le;
    public $schulungsort;
    public $ort;
    public $preis;
    public $gesamtpreis;
    public $kunden_firma;
    public $telefon;
    public $datum;

    // Demographie, Firmendaten & CI-Stammdaten
    public $company_name;
    public $company_short_name;
    public $company_legal_form;
    public $company_management;
    public $company_street;
    public $company_zip;
    public $company_city;
    public $company_country;
    public $company_address;
    public $location_wien;
    public $location_wien_name;
    public $location_wien_street;
    public $location_wien_zip;
    public $location_wien_city;
    public $location_wien_notice;
    public $company_phone;
    public $company_email;
    public $company_website;
    public $backoffice_name;
    public $backoffice_email;
    public $backoffice_phone;
    public $company_uid;
    public $company_fn;
    public $company_court;
    public $company_chamber;
    public $company_bank;
    public $company_slogan;
    public $company_accreditations;
    public $agb_url;
    public $privacy_url;
    public $imprint_url;
    public $ci_primary_color;
    public $ci_secondary_color;
    public $ci_accent_color;
    public $company_logo;
    public $company_logo_url;
    public $company_logo_secondary_url;

    /**
     * Konstruktor der Klasse.
     * @param int $post_id Die ID des Beitrags.
     * @param int|null $entry_id The ID of the WPForms entry (optional).
     */
    public function __construct($post_id = 0, $entry_id = null)
    {
        $this->post_id = absint($post_id);
        $entry_id      = !empty($entry_id) ? absint($entry_id) : null;

        // Fallback: Falls keine gültige Kurs-ID übergeben wurde, ersten publizierten Kurs als Muster wählen
        if ($this->post_id <= 0) {
            $fallback_courses = get_posts([
                'post_type'      => 'courses',
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ]);
            if (!empty($fallback_courses)) {
                $this->post_id = $fallback_courses[0]->ID;
            }
        }
        $post_id = $this->post_id;

        // WP-Forms Entry
        $this->entry_data = $this->get_wpforms_entry_data($entry_id);

        // NEU: Adress- und Namensdaten aus WPForms-Daten aufteilen
        $this->set_address_components();
        $this->set_personal_data();


        // Basic Fields
        $raw_title = get_the_title($post_id);
        $this->title = trim(wp_strip_all_tags(html_entity_decode(html_entity_decode($raw_title, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')));
        $raw_short = get_field('title_im_slider', $post_id);
        $this->titel_short = !empty($raw_short) ? trim(wp_strip_all_tags(html_entity_decode(html_entity_decode($raw_short, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'))) : '';
        $this->permalink = get_permalink($post_id);
        // Persistent snapshot dates: Use frozen inquiry dates if available, otherwise fallback to course post meta
        $snapshot_dates = ($entry_id && function_exists('crm_get_entry_course_dates')) ? crm_get_entry_course_dates($entry_id) : null;
        if (!empty($snapshot_dates['start_date'])) {
            $this->start_datum = date('d.m.Y', strtotime($snapshot_dates['start_date']));
        } else {
            $this->start_datum = $this->format_date_meta('start_datum');
        }

        if (!empty($snapshot_dates['end_date'])) {
            $this->end_datum = date('d.m.Y', strtotime($snapshot_dates['end_date']));
        } else {
            $this->end_datum = $this->format_date_meta('end_datum');
        }
        $this->preis_netto = number_format((float)get_post_meta($post_id, 'kosten', true), 2, ',', '');
        $this->preis_brutto = !empty($tempKosten = get_post_meta($post_id, "kosten", true))
            ? number_format((float)$tempKosten * 1.20, 2, '.', '')
            : 0;
        $this->title_preis = $this->title;
        $this->angebot_beschreibung = get_post_meta($post_id, 'angebot_beschreibung', true);

        // Direktaufrufe statt Cache
        $this->zertifizierungen_images = $this->get_zertifizierungen_images();
        $this->zertifizierungen_images_html = $this->get_zertifizierungen_images_html();
        $this->module_html = $this->get_module_html();
        $this->anmeldung_agb = $this->get_anmeldung_agb_html();
        $this->inhalte = $this->get_inhalte_dyn();
        $this->zertifizierungen_loop_html = $this->get_zertifizierungen_loop_html();
        $this->voraussetzungen = get_field('voraussetzungen_abschluss', $post_id);
        $this->voraussetzungen_html = $this->get_voraussetzungen_list_html();
        $this->garantie = $this->get_garantie_html();
        $this->trainer = $this->get_trainer_html();
        $this->ps = $this->get_ps_html();

        $this->anzahl_le = get_post_meta($post_id, 'lehreinheiten_gesamt', true);
        $this->termine_pdf = get_field('kurszeiten_details_pdf', $post_id);
        $this->zielgruppe = sanitize_text_field(get_field('teilnehmeruberblick', $post_id));
        $this->le_single = number_format($this->preis_brutto / $this->anzahl_le, 2);
        $this->kurstyp = $this->get_coursetype();
        $this->abschluss = get_post_meta($post_id, ["zertifikat"][0], true);

        $this->form_certifications = $this->get_form_zertifizierungen_loop_html();
        $this->kursgebuehr_html = $this->get_kursgebuehr_html();
        $this->texte_fur_diplom_links = get_field('texte_fur_diplom_links', $post_id) ?? '';
        $this->texte_fur_diplom_rechts = get_field('texte_fur_diplom_rechts', $post_id) ?? '';
        $this->termine_link = get_field('kurszeiten_details_pdf', $post_id) ?? '';

        // Kursart Handling
        $this->set_kursart(get_post_meta($post_id, 'tages_abend_wochenende_', true));

        // Kurszeiten
        $this->kurszeiten    = $this->build_days(get_field("kurszeiten", $post_id));
        $this->selbststudium = $this->build_days(get_field("selbstudium", $post_id));


        // Zertifizierungen
        if (have_rows('zertifizierungen', $post_id)) {
            while (have_rows('zertifizierungen', $post_id)) {
                the_row();
                $this->zertifizierungen[] = [
                    'name' => get_sub_field('name-zert'),
                    'preis' => get_sub_field('preis'),
                    'ust' => get_sub_field('Ust_satz')
                ];
            }
        }

        // Misc
        $this->nummer = time();
        $this->kurszeiten_datum = date("d.m.Y");
        $this->pdfAuthor = "X-Sieben Wirtschaftstraining";
        $this->current = date('d.m.Y');
        $this->_setExpireDate();

        $this->bankverbindung = $this->get_crm_field('Bankverbindung');
        $this->agb_claim = $this->get_crm_field('AGB text');

        $this->load_company_settings();
        $this->load_icons();

        // Kurszeiten / Uhrzeit berechnen
        $uhr_von = trim((string)get_post_meta($post_id, 'uhrzeit', true));
        $uhr_bis = trim((string)get_post_meta($post_id, 'uhrzeit_ende', true));
        if (!empty($uhr_von) && !empty($uhr_bis)) {
            $this->uhrzeit = $uhr_von . ' – ' . $uhr_bis . ' Uhr';
        } elseif (!empty($uhr_von)) {
            $this->uhrzeit = $uhr_von . ' Uhr';
        } elseif (!empty($this->kurszeiten) && is_array($this->kurszeiten)) {
            $first_time = reset($this->kurszeiten);
            $this->uhrzeit = !empty($first_time) ? $first_time : '09:00 – 17:00 Uhr';
        } else {
            $this->uhrzeit = '09:00 – 17:00 Uhr';
        }

        // Standardisierte Platzhalter & Aliase (vor get_crm_field() initialisieren!)
        $this->kurstitel       = $this->title;
        $this->kurstitel_short = $this->titel_short;
        $this->startdatum      = $this->start_datum;
        $this->enddatum        = $this->end_datum;
        $this->le              = $this->anzahl_le;
        $this->schulungsort    = !empty($this->location_wien) ? $this->location_wien : 'Rochusgasse 6, 1030 Wien';
        $this->ort             = $this->schulungsort;
        $this->preis           = $this->preis_netto;
        $this->gesamtpreis     = $this->preis_brutto;
        $this->kunden_firma    = $this->customer_company;
        $this->telefon         = !empty($this->company_phone) ? $this->company_phone : '0800 700 170';
        $this->datum           = date('d.m.Y');

        $this->signatur = $this->get_signature_html();
        $this->beratung_email = $this->get_crm_field('Beratung E-Mail Text');
        $this->buchung_email = $this->get_crm_field('Anmeldung Buchung E-Mail Text');
        $this->signatur_email = $this->get_crm_field('E-Mail Signatur');
        $this->email_footer = $this->get_crm_field('E-Mail-Footer');
        $this->teilnahmebestaetigung_email = $this->get_crm_field('E-Mail Teilnahmebestätigung - Allgemein');
        $this->teilnahmebestaetigung_f_email = $this->get_crm_field('E-Mail Teilnahmebestätigung - Förderung');
        $this->angebot_email = $this->get_crm_field('E-Mail Angebot');
        $this->anmeldung_email = $this->get_crm_field('E-Mail Anmeldebestätigung');
        $this->diplom_email = $this->get_crm_field('E-Mail Diplom');
    }

    /**
     * Extrahiert Titel, Vorname und Nachname aus den WPForms-Eintragsdaten.
     */
    private function set_personal_data(): void
    {
        $this->anrede = $this->get_wpforms_field_by_id(88);
        $this->titel = $this->get_wpforms_field_by_id(90);
        $this->vorname = $this->get_wpforms_field_by_id(86);
        $this->nachname = $this->get_wpforms_field_by_id(89);
        $this->email = $this->get_wpforms_field_by_id(93);
        $this->svr = $this->get_wpforms_field_by_id(29);
        $this->customer_company = trim((string)$this->get_wpforms_field_by_id(25));
        $this->customer_type    = trim((string)$this->get_wpforms_field_by_id(3));

        // Fallback: Falls Feld 25 leer ist, im gesamten Entry nach Feldern wie 'firma' suchen
        if (empty($this->customer_company) && !empty($this->entry_data) && is_array($this->entry_data)) {
            foreach ($this->entry_data as $fld) {
                if (!empty($fld['name']) && (stripos($fld['name'], 'firma') !== false || stripos($fld['name'], 'unternehmung') !== false)) {
                    if (!empty($fld['value']) && is_string($fld['value']) && stripos($fld['name'], 'privat') === false) {
                        $this->customer_company = trim($fld['value']);
                        break;
                    }
                }
            }
        }

        if ($this->anrede === 'Herr') {
            $this->salutation = "Sehr geehrter Herr";
        } elseif ($this->anrede === 'Frau') {
            $this->salutation = "Sehr geehrte Frau";
        } else {
            $this->salutation = "Sehr geehrte(r) Frau/Herr";
        }
    }

    /**
     * Formatiert die Empfängeradresse normgerecht nach DIN 5008 / ÖNORM A 1080.
     * Unterstützt Privatpersonen, akademische Titel sowie Unternehmen (Variante A Geschäftlich vs. Variante B Vertraulich).
     *
     * @param string $variant 'A' (Standard: Geschäftlich, Firma zuerst) oder 'B' (Persönlich/Vertraulich, Person zuerst)
     * @param bool $as_html Wenn true, wird HTML mit <br> zurückgegeben, sonst Plaintext mit \n
     * @return string
     */
    public function format_postal_address(string $variant = 'A', bool $as_html = true): string
    {
        $lines = [];

        // 1. Postalischer Akkusativ für Anrede
        $salutation_clean = trim((string)($this->anrede ?? ''));
        $postal_salutation = '';
        if (strcasecmp($salutation_clean, 'Herr') === 0 || strcasecmp($salutation_clean, 'Herrn') === 0) {
            $postal_salutation = 'Herrn';
        } elseif (strcasecmp($salutation_clean, 'Frau') === 0) {
            $postal_salutation = 'Frau';
        } elseif (strcasecmp($salutation_clean, 'Familie') === 0) {
            $postal_salutation = 'Familie';
        } elseif (strcasecmp($salutation_clean, 'Eheleute') === 0) {
            $postal_salutation = 'Eheleute';
        } elseif (strcasecmp($salutation_clean, 'Firma') === 0) {
            $postal_salutation = '';
        } else {
            $postal_salutation = $salutation_clean;
        }

        $title_clean    = trim((string)($this->titel ?? ''));
        $vorname_clean  = trim((string)($this->vorname ?? ''));
        $nachname_clean = trim((string)($this->nachname ?? ''));
        $firma_clean    = trim((string)($this->customer_company ?? ''));

        // Namenszusammensetzung (Vermeidung von Dopplungen falls Titel bereits im Namen steht)
        $name_parts = [];
        if (!empty($vorname_clean)) $name_parts[] = $vorname_clean;
        if (!empty($nachname_clean)) $name_parts[] = $nachname_clean;
        $full_name = implode(' ', $name_parts);

        $person_line = '';
        if (!empty($full_name)) {
            if (!empty($title_clean) && stripos($full_name, $title_clean) === false) {
                // Mit akademischem Titel: Anrede + Titel + Name in einer Zeile (DIN 5008 / ÖNORM)
                $person_line = trim($postal_salutation . ' ' . $title_clean . ' ' . $full_name);
            } else {
                $person_line = $full_name;
            }
        }

        if (!empty($firma_clean)) {
            // Fall 4: Firmen und Unternehmen
            $company_person = trim($postal_salutation . ' ' . (!empty($title_clean) && stripos($full_name, $title_clean) === false ? $title_clean . ' ' : '') . $full_name);
            if (strtoupper($variant) === 'B') {
                // Variante B: Persönlich/Vertraulich (Person zuerst, Firma darunter)
                if (!empty($company_person)) $lines[] = $company_person;
                $lines[] = $firma_clean;
            } else {
                // Variante A: Geschäftlich (Firma in der ersten Zeile, Ansprechpartner darunter)
                $lines[] = $firma_clean;
                if (!empty($company_person)) $lines[] = $company_person;
            }
        } else {
            // Fall 2 & 3: Privatpersonen
            if (!empty($title_clean) && !empty($person_line)) {
                // Mit Titel: z. B. "Frau Dr. Martina Muster" bzw. "Herrn Prof. Dr. Max Muster"
                $lines[] = $person_line;
            } else {
                // Ohne Titel:
                // Zeile 1: Anrede (z. B. "Herrn" oder "Frau" oder "Familie" oder "Eheleute")
                // Zeile 2: Name
                if (!empty($postal_salutation)) {
                    $lines[] = $postal_salutation;
                }
                if (!empty($person_line)) {
                    $lines[] = $person_line;
                }
            }
        }

        // Straße und Hausnummer
        $street_full = trim(($this->street ?? '') . ' ' . ($this->house_number ?? ''));
        if (!empty($street_full)) {
            $lines[] = $street_full;
        }

        // PLZ und Ort
        $city_parts = [];
        if (!empty($this->zip_code)) $city_parts[] = trim((string)$this->zip_code);
        if (!empty($this->city))     $city_parts[] = trim((string)$this->city);
        if (!empty($city_parts)) {
            $lines[] = implode(' ', $city_parts);
        }

        // Land (nur wenn Ausland und nicht Österreich)
        $country_clean = trim((string)($this->country ?? ''));
        if (!empty($country_clean) && !in_array(strtoupper($country_clean), ['AT', 'AUT', 'ÖSTERREICH', 'OESTERREICH'], true)) {
            $lines[] = strtoupper($country_clean);
        }

        if ($as_html) {
            return implode("<br>\n", array_map('htmlspecialchars', $lines));
        }
        return implode("\n", $lines);
    }

    /**
     * Get the value of a CRM custom field by title
     *
     * @param string $title  The field title to search for
     * @return string        The content of the field or empty string
     */

    // Inside the CRM_Model class
    public function get_crm_field($title)
    {
        $fields = get_option('crm_custom_fields', []);
        if (empty($fields) || !is_array($fields)) {
            return '';
        }

        $target = strtolower(trim($title));
        foreach ($fields as $field) {
            if (isset($field['title']) && strtolower(trim($field['title'])) === $target) {
                $content = $field['content'] ?? '';
                // --- Add the parsing step here ---
                $content = $this->parse_string_with_data($content);
                // --- End of parsing step ---
                // Format content for emails using wpautop and do_shortcode directly.
                // We intentionally do NOT use apply_filters('the_content') here because global theme
                // filters (such as in functions.php) strip domain names from image and link URLs,
                // and cookie consent plugins mangle <img> src attributes into consent attributes.
                $content = wpautop($content);
                $content = do_shortcode($content);
                if (function_exists('crm_prepare_email_html_for_sending')) {
                    $content = crm_prepare_email_html_for_sending($content);
                }
                return $content;
            }
        }

        // If not found, return an empty string
        return '';
    }

    /**
     * Ersetzt dynamische CRM-Platzhalter, Kursdaten, Personenmerkmale und Komponenten in einem Text.
     * Führt bis zu 3 Ersetzungsrunden durch, um verschachtelte Platzhalter (z.B. {signatur_email} enthält {company_name})
     * vollständig aufzulösen.
     *
     * @param string $template_string
     * @return string
     */
    public function parse_string_with_data(string $template_string): string
    {
        if (empty($template_string)) {
            return '';
        }

        $max_passes = 3;
        $current = $template_string;

        for ($pass = 0; $pass < $max_passes; $pass++) {
            if (strpos($current, '{') === false) {
                break;
            }

            $prev = $current;
            $current = preg_replace_callback('/\{([a-zA-Z0-9_\-\.]+)\}/', function ($matches) {
                $key = strtolower(trim($matches[1]));

                // 1. Spezielle dynamische Werte
                if ($key === 'current_date' || $key === 'datum') {
                    return date('d.m.Y');
                }
                if ($key === 'diplom_success') {
                    $succ = $this->get_diplom_success();
                    return !empty($succ) ? 'mit ' . esc_html($succ) . ' ' : '';
                }
                if ($key === 'kurstyp_upper') {
                    return mb_strtoupper((string)($this->kurstyp ?: 'Lehrgang'), 'UTF-8');
                }
                if ($key === 'kurstyp_lower') {
                    return mb_strtolower((string)($this->kurstyp ?: 'lehrgang'), 'UTF-8');
                }
                if ($key === 'hat_den_kurstyp' || $key === 'kurstyp_phrase') {
                    return function_exists('crm_get_diplom_kurstyp_phrase')
                        ? crm_get_diplom_kurstyp_phrase($this->kurstyp)
                        : ('HAT DEN ' . mb_strtoupper((string)($this->kurstyp ?: 'LEHRGANG'), 'UTF-8'));
                }

                // 2. Direkte Objekt-Eigenschaften prüfen
                if (property_exists($this, $key) && is_scalar($this->$key)) {
                    return (string)$this->$key;
                }

                // 3. Umfassende Aliase (Kursdaten, Personen, Firma & CI)
                $aliases = [
                    // Kurs- und Veranstaltungsdaten
                    'kurstitel'          => 'title',
                    'kurstitel_short'    => 'titel_short',
                    'kurs_titel'         => 'title',
                    'startdatum'         => 'start_datum',
                    'start_datum'        => 'start_datum',
                    'enddatum'           => 'end_datum',
                    'end_datum'          => 'end_datum',
                    'uhrzeit'            => 'uhrzeit',
                    'kurszeiten'         => 'uhrzeit',
                    'zeiten'             => 'uhrzeit',
                    'le'                 => 'anzahl_le',
                    'anzahl_le'          => 'anzahl_le',
                    'lehreinheiten'      => 'anzahl_le',
                    'preis'              => 'preis_netto',
                    'preis_netto'        => 'preis_netto',
                    'preis_brutto'       => 'preis_brutto',
                    'gesamtpreis'        => 'preis_brutto',
                    'le_single'          => 'le_single',
                    'schulungsort'       => 'location_wien',
                    'ort'                => 'location_wien',
                    'standort_wien'      => 'location_wien',
                    'schulungsort_wien'  => 'location_wien',
                    'dauer'              => 'anzahl_le',
                    'expire'             => 'expire',

                    // Personen- & Kundendaten
                    'kunden_firma'       => 'customer_company',
                    'firma'              => 'customer_company',
                    'anrede_brief'       => 'salutation',
                    'telefon'            => 'company_phone',

                    // Instituts- & Firmendaten
                    'schulungsinstitut'  => 'company_name',
                    'institut_name'      => 'company_name',
                    'institut_kurz'      => 'company_short_name',
                    'institut_adresse'   => 'company_address',
                    'institut_telefon'   => 'company_phone',
                    'institut_email'     => 'company_email',
                    'institut_website'   => 'company_website',
                    'institut_uid'       => 'company_uid',
                    'institut_fn'        => 'company_fn',
                    'institut_gericht'   => 'company_court',
                    'institut_bank'      => 'company_bank',
                    'institut_logo'      => 'company_logo',
                    'geschaeftsfuehrung' => 'company_management',
                    'institutsleiter'    => 'company_management',
                ];

                if (isset($aliases[$key])) {
                    $prop = $aliases[$key];
                    if (property_exists($this, $prop) && is_scalar($this->$prop)) {
                        return (string)$this->$prop;
                    }
                }

                // 4. E-Mail-Komponenten (Bausteine) auflösen
                $component_map = [
                    'signatur_email'          => 'E-Mail Signatur',
                    'signatur'                => 'E-Mail Signatur',
                    'email_footer'            => 'E-Mail-Footer',
                    'footer'                  => 'E-Mail-Footer',
                    'buchung_email'           => 'Anmeldung Buchung E-Mail Text',
                    'agb_claim'               => 'AGB text',
                    'bankverbindung'          => 'Bankverbindung',
                    'angebot_hinweis'         => 'Angebot E-Mail Hinweis',
                    'angebot_ps'              => 'Angebot PS',
                    'durchfuehrungs_garantie' => 'Durchführungs Garantie',
                    'anhang_2'                => 'Anhang 2 | Exklusive Zusatzleistungen',
                    'teilnahme_fee'           => 'Teilnahme_Fee',
                ];

                if (isset($component_map[$key])) {
                    $comp_content = $this->get_crm_field($component_map[$key]);
                    if ($comp_content !== '') {
                        return $comp_content;
                    }
                }

                // 5. Dynamische Prüfung in crm_custom_fields für alle benutzerdefinierten Bausteine
                $custom_fields = get_option('crm_custom_fields', []);
                if (is_array($custom_fields)) {
                    foreach ($custom_fields as $cf) {
                        if (empty($cf['title'])) {
                            continue;
                        }
                        if (function_exists('crm_get_component_placeholder_for_title')) {
                            $token = trim(crm_get_component_placeholder_for_title($cf['title']), '{}');
                            if (strcasecmp($token, $key) === 0) {
                                return !empty($cf['content']) ? wp_kses_post($cf['content']) : '';
                            }
                        }
                    }
                }

                // Falls kein passender Wert gefunden wird, Platzhalter unverändert beibehalten
                return $matches[0];
            }, $current);

            if ($current === $prev) {
                break;
            }
        }

        return $current;
    }

    /**
     * Get the value of a CRM custom field by title with a fallback default.
     *
     * @param string $title   The field title to search for
     * @param string $default Fallback string if field is missing or empty
     * @return string
     */
    public function get_crm_field_with_default(string $title, string $default = ''): string
    {
        $content = $this->get_crm_field($title);
        if (!empty(trim(strip_tags($content)))) {
            return $content;
        }

        // Parse default string with data placeholders as well
        $default = $this->parse_string_with_data($default);
        if (function_exists('crm_prepare_email_html_for_sending')) {
            $default = crm_prepare_email_html_for_sending($default);
        }
        return $default;
    }


    /**
     * Extrahiert separate Adresskomponenten aus den WPForms-Eintragsdaten.
     * Nutzt die spezifische WPForms-Feld-ID für das Adressfeld (ID 35).
     */
    private function set_address_components(): void
    {
        // Überprüfen, ob WPForms-Daten für das Adressfeld (ID 35) vorhanden sind
        if (empty($this->entry_data[35])) {
            return;
        }

        $address_fields = $this->entry_data[35];

        // Zuweisen der Adresskomponenten aus den Unterfeldern
        $this->street = $address_fields['address1'] ?? '';
        // Hausnummer ist oft in address1 enthalten, hier nicht explizit getrennt
        $this->house_number = '';
        $this->city = $address_fields['city'] ?? '';
        $this->zip_code = $address_fields['postal'] ?? '';
        $this->country = $address_fields['country'] ?? '';
    }
    public function get_certifications_from_form_field(): array
    {
        // Check for the entry data from the form field with ID 99.
        $cert_data_string = $this->get_wpforms_field_by_id(99);

        // Return an empty array if no data is found.
        if (empty($cert_data_string)) {
            return [];
        }

        // Split the string into individual lines.
        $lines = explode("\n", $cert_data_string);
        $data = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Regex to capture the name, price, and optional percentage
            $pattern = '/(.+?) - € ([\d\.,]+)(?: \((\d+)%\))?/';

            if (preg_match($pattern, $line, $matches)) {
                $name = trim($matches[1]);
                $price = trim($matches[2]);
                $percentage = isset($matches[3]) ? $matches[3] . '%' : 'N/A';

                $data[] = [
                    "name" => $name,
                    "price" => $price,
                    "percentage" => $percentage
                ];
            } else {
                // Special case for "FachtrainerIn" which doesn't have a price in the original text.
                if (strpos($line, 'FachtrainerIn') !== false) {
                    $data[] = [
                        "name" => $line,
                        "price" => "324,00",
                        "percentage" => "N/A"
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Formatiert ein Datum, das aus Post-Metadaten abgerufen wird.
     * @param string $meta_key Der Schlüssel der Post-Meta.
     * @return string Das formatierte Datum (d.m.Y) oder ein leerer String, wenn kein Datum gefunden wird.
     */
    private function format_date_meta($meta_key)
    {
        $raw = get_post_meta($this->post_id, $meta_key, true);
        return $raw ? date('d.m.Y', strtotime($raw)) : '';
    }

    /**
     * Erstellt ein Array von Tagen mit Standardzeiten, basierend auf einem Eingabearray.
     * @param array $days_array Ein Array von Wochentagen (z.B. ['Montag', 'Dienstag']).
     * @param bool $suffix Wenn true, wird '_s' an den Schlüssel angehängt (für Selbststudium).
     * @return array Ein assoziatives Array mit Wochentagen als Schlüsseln und Zeiten als Werten.
     */
    private function build_days($days_array, $suffix = false)
    {
        $map = [];
        $timeslot = "09.00 - 17.00 Uhr";
        $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

        foreach ($weekdays as $day) {
            if (is_array($days_array) && in_array($day, $days_array)) {
                $key = strtolower($day) . ($suffix ? '_s' : '');
                $map[$key] = $timeslot;
            }
        }
        return $map;
    }

    /**
     * Setzt die Kursart-Eigenschaften basierend auf dem übergebenen Array.
     * @param array|string $kursart_array Ein Array von Kursarten oder ein einzelner String.
     */
    private function set_kursart($kursart_array)
    {
        $this->kursart = is_array($kursart_array) ? implode(', ', $kursart_array) : $kursart_array;

        $map = [
            'Tageskurs' => 'kursart_t',
            'Abendkurs' => 'kursart_a',
            'Wochenendkurs' => 'kursart_we',
        ];

        foreach ($map as $label => $prop) {
            if (
                (is_array($kursart_array) && in_array($label, $kursart_array)) ||
                (!is_array($kursart_array) && $kursart_array === $label)
            ) {
                $this->$prop = "<strong>X</strong>";
            }
        }
    }

    /**
     * Lädt die demographischen Stammdaten und CI-Einstellungen aus dem CRM.
     */
    private function load_company_settings(): void
    {
        $settings = function_exists('crm_get_general_settings') ? crm_get_general_settings() : [];
        $this->company_name        = $settings['company_name'] ?? 'X SIEBEN Wirtschaftstraining GmbH';
        $this->company_short_name  = $settings['company_short_name'] ?? 'X SIEBEN';
        $this->company_legal_form  = $settings['company_legal_form'] ?? 'GmbH';
        $this->company_management  = $settings['company_management'] ?? 'Mag. Dr. Johannes Gasberger';
        $this->company_street      = $settings['company_street'] ?? 'Kurzegasse 7';
        $this->company_zip         = $settings['company_zip'] ?? '2493';
        $this->company_city        = $settings['company_city'] ?? 'Lichtenwörth';
        $this->company_country     = $settings['company_country'] ?? 'Österreich';
        $this->company_address     = trim($this->company_street . ', ' . $this->company_zip . ' ' . $this->company_city);
        $this->location_wien_name  = $settings['location_wien_name'] ?? 'Seminarzentrum Wien';
        $this->location_wien_street= $settings['location_wien_street'] ?? 'Rochusgasse 6';
        $this->location_wien_zip   = $settings['location_wien_zip'] ?? '1030';
        $this->location_wien_city  = $settings['location_wien_city'] ?? 'Wien';
        $this->location_wien       = trim($this->location_wien_street . ', ' . $this->location_wien_zip . ' ' . $this->location_wien_city);
        $this->location_wien_notice= $settings['location_wien_notice'] ?? 'Online Unterricht | vor Ort in unseren Veranstaltungsräumen | Blended Learning';
        $this->company_phone       = $settings['company_phone'] ?? '0800 700 170';
        $this->company_email       = $settings['company_email'] ?? 'office@x-sieben.at';
        $this->company_website     = $settings['company_website'] ?? 'https://x-sieben.at';
        $this->backoffice_name     = $settings['backoffice_name'] ?? 'Anna Brauer';
        $this->backoffice_email    = $settings['backoffice_email'] ?? 'abrauer@x-sieben.at';
        $this->backoffice_phone    = $settings['backoffice_phone'] ?? '0800 700 170';
        $this->company_uid         = $settings['company_uid'] ?? 'ATU76624137';
        $this->company_fn          = $settings['company_fn'] ?? 'FN 550277 g';
        $this->company_court       = $settings['company_court'] ?? 'Landesgericht Wiener Neustadt';
        $this->company_chamber     = $settings['company_chamber'] ?? 'Wirtschaftskammer Niederösterreich / Wien';
        $this->company_bank        = $settings['company_bank'] ?? 'Erste Bank | IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN';
        $this->company_slogan      = $settings['company_claim'] ?? 'Wirtschaftstraining, Seminare & Personenzertifizierungen';
        $this->company_accreditations = $settings['company_accreditations'] ?? 'pma / IPMA®, SystemCERT (ISO 17024), TÜV Austria, wba, CERT NÖ, AMS';
        $this->agb_url             = $settings['legal_agb_url'] ?? 'https://x-sieben.at/wp-content/uploads/2025/09/AGB_X_SIEBEN_2025.pdf';
        $this->privacy_url         = $settings['legal_privacy_url'] ?? 'https://x-sieben.at/datenschutzerklaerung/';
        $this->imprint_url         = $settings['legal_imprint_url'] ?? 'https://x-sieben.at/impressum/';
        $this->ci_primary_color    = $settings['ci_primary_color'] ?? '#007C90';
        $this->ci_secondary_color  = $settings['ci_secondary_color'] ?? '#0284c7';
        $this->ci_accent_color     = $settings['ci_accent_color'] ?? '#0f172a';
        $this->company_logo_url    = $settings['logo_url'] ?? '';
        $this->company_logo_secondary_url = $settings['logo_secondary_url'] ?? '';
    }

    /**
     * Lädt und formatiert HTML-Tags für verschiedene Icons und Logos aus dem Theme-Assets-Verzeichnis.
     */
    private function load_icons()
    {
        $assets_dir = get_template_directory() . '/inc/core/crm/assets/';
        $assets_url = get_template_directory_uri() . '/inc/core/crm/assets/';
        $icons = [
            'web_icon'       => ['file' => 'kontakt.png', 'width' => '25px'],
            'mail_icon'      => ['file' => 'email.png', 'width' => '25px'],
            'fax_icon'       => ['file' => 'fax.png', 'width' => '25px'],
            'phone_icon'     => ['file' => 'tel.png', 'width' => '25px'],
            'calender_icon'  => ['file' => 'kalender.png', 'width' => '25px'],
            'ort_icon'       => ['file' => 'ort.png', 'width' => '25px'],
            'abschluss_icon' => ['file' => 'abschluss.png', 'width' => '25px'],
            'diplom_icon'    => ['file' => 'diplom.png', 'width' => '25px'],
            'danger_icon'    => ['file' => 'danger.png', 'width' => '25px'],
            'sitting_icon'   => ['file' => 'sitting.png', 'width' => '25px'],
            'proven_icon'    => ['file' => 'proven.png', 'width' => ''],
            'proven_wide'    => ['file' => 'proven_wide.png', 'width' => ''],
            'wba_logo'       => ['file' => 'wba-1.png', 'width' => '100px'],
            'cert_noe_logo'  => ['file' => 'cert-1.png', 'width' => '100px'],
            'tuef_logo'      => ['file' => 'tuef.png', 'width' => ''],
            'sys_zert_logo'  => ['file' => 'system-1.png', 'width' => ''],
            'pma_logo'       => ['file' => 'PMA-1.png', 'width' => ''],
            'ipma_logo'      => ['file' => 'impa.png', 'width' => ''],
            'email_logos'    => ['file' => 'email_zerts.png', 'width' => '100px', 'style' => 'padding-left: 54px;'],
            'ams_img'        => ['file' => 'ams.png', 'width' => '160px'],
            'signatur_icon'  => ['file' => 'Signatur_Blau.png', 'width' => '180px'],
            'xsieben_logo'   => ['file' => 'xsieben_logo.png', 'width' => '200px'],
        ];
        foreach ($icons as $prop => $config) {
            $width_attr = !empty($config['width']) ? " width=\"{$config['width']}\"" : '';
            $style_attr = isset($config['style']) ? " style=\"{$config['style']}\"" : '';
            // For TCPDF: use local filesystem path if file exists to prevent slow/blocked HTTP loopback requests
            $img_src = (file_exists($assets_dir . $config['file'])) ? ($assets_dir . $config['file']) : ($assets_url . $config['file']);
            $this->$prop = sprintf(
                '<img%s%s src="%s">',
                $width_attr,
                $style_attr,
                $img_src
            );
        }

        // Falls ein benutzerdefiniertes Logo in den CRM-Einstellungen hinterlegt ist, dieses für das Hauptlogo verwenden
        if (!empty($this->company_logo_url)) {
            $logo_src = $this->company_logo_url;
            // Resolve local filesystem path if available
            $upload_dir = wp_upload_dir();
            $base_url   = $upload_dir['baseurl'] ?? '';
            $base_dir   = $upload_dir['basedir'] ?? '';
            if (!empty($base_url) && !empty($base_dir) && strpos($logo_src, $base_url) !== false) {
                $local_candidate = str_replace($base_url, $base_dir, $logo_src);
                if (file_exists($local_candidate)) {
                    $logo_src = $local_candidate;
                }
            } elseif (file_exists($assets_dir . basename($logo_src))) {
                $logo_src = $assets_dir . basename($logo_src);
            }
            $this->xsieben_logo = sprintf(
                '<img width="200px" style="max-width:200px; height:auto;" src="%s">',
                esc_attr($logo_src)
            );
        }
        $this->company_logo = $this->xsieben_logo;
    }

    /**
     * Zählt die Anzahl der Module, die einem Post zugewiesen sind.
     * @return int Die Anzahl der Module oder 0, wenn keine gefunden werden.
     */
    public function get_module_count()
    {
        $modules = get_field('module', $this->post_id);
        return is_array($modules) ? count($modules) : 0;
    }

    /**
     * Generiert HTML für die Module und Zeiteinteilung, basierend auf den ACF-Daten.
     * Strukturiert die Gliederung, Zeiteinteilung und Mehrwerte in ein sauberes Tabellenlayout.
     *
     * @return string Der generierte HTML-Tabellen-String der Module.
     */
    private function get_module_html(): string
    {
        if (!have_rows('module', $this->post_id)) {
            return '';
        }

        $module_rows    = [];
        $breakdown_rows = [];
        $extra_rows     = [];
        $section_title  = '';

        while (have_rows('module', $this->post_id)) {
            the_row();
            $mod = trim((string) get_sub_field('modul'));
            $tit = trim((string) get_sub_field('modul_titel'));
            $le  = trim((string) get_sub_field('anzahl_le'));

            // Leere Zeilen komplett überspringen
            if (empty($mod) && empty($tit) && empty($le)) {
                continue;
            }

            // Erkennung von Zwischenüberschriften wie "< IHR MEHRWERT >"
            $tit_clean = trim(str_replace(['<', '>', '&lt;', '&gt;', '&LT;', '&GT;'], '', $tit));
            if (empty($mod) && empty($le) && !empty($tit_clean) && (strpos($tit, '<') !== false || mb_strtoupper($tit_clean) === $tit_clean)) {
                $section_title = $tit_clean;
                continue;
            }

            // Emojis aus Modul-Labels bereinigen (verhindert '??' in TCPDF DejaVu Sans)
            $mod_clean = trim(preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}]/u', '', $mod));

            // Prüfen, ob es sich um die Zeiteinteilung / Lehreinheiten-Aufteilung handelt (+, ->, -)
            if (in_array($mod, ['+', '->', '-']) || (!empty($le) && !preg_match('/^modul\b/i', $mod) && !preg_match('/^abschnitt\b/i', $mod))) {
                $prefix = in_array($mod, ['+', '->', '-']) ? $mod : '•';
                $breakdown_rows[] = [
                    'prefix' => $prefix,
                    'titel'  => $tit,
                    'le'     => $le
                ];
            } elseif (stripos($mod_clean, 'inklusive') !== false || stripos($mod_clean, 'mehrwert') !== false) {
                $extra_rows[] = [
                    'label' => !empty($mod_clean) ? $mod_clean : 'INKLUSIVE',
                    'titel' => $tit
                ];
            } else {
                $module_rows[] = [
                    'modul' => !empty($mod_clean) ? $mod_clean : $mod,
                    'titel' => $tit,
                    'le'    => $le
                ];
            }
        }

        if (empty($module_rows) && empty($breakdown_rows) && empty($extra_rows)) {
            return '';
        }

        $html = '';

        // 1. Modul- und Themeninhalte
        if (!empty($module_rows)) {
            $html .= '<table cellpadding="4" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 10pt;">';
            $html .= '<thead>
                <tr style="background-color: #f1f5f9; border-bottom: 1.5px solid #007C90;">
                    <th style="width: 20%; text-align: left; color: #007C90; font-weight: bold;">Gliederung</th>
                    <th style="width: 70%; text-align: left; color: #007C90; font-weight: bold;">Beschreibung</th>
                    <th style="width: 10%; text-align: right; color: #007C90; font-weight: bold;">LE</th>
                </tr>
            </thead><tbody>';

            foreach ($module_rows as $row) {
                $html .= '<tr>
                    <td valign="top" style="width: 20%; font-weight: bold; color: #1e293b; padding-top: 4px; padding-bottom: 4px;">' . esc_html($row['modul']) . '</td>
                    <td valign="top" style="width: 70%; color: #334155; padding-top: 4px; padding-bottom: 4px;">' . esc_html($row['titel']) . '</td>
                    <td valign="top" style="width: 10%; text-align: right; color: #64748b; padding-top: 4px; padding-bottom: 4px;">' . (!empty($row['le']) ? esc_html($row['le']) : '') . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        }

        // 2. Zeiteinteilung / Lehreinheiten-Aufteilung (Layout exakt wie "Ihre Investition")
        if (!empty($breakdown_rows)) {
            if (!empty($html)) {
                $html .= '<div style="font-size:10pt">&nbsp;</div>';
            }
            $total_breakdown_le = 0;
            $html .= '<table cellpadding="6" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; font-size: 10pt;">';
            $html .= '<thead>
                <tr style="background-color:#f2f2f2;">
                    <th style="text-align:left; width:85%; border-bottom:1px solid #aaa; font-weight: bold; color: #1e293b;">Zeiteinteilung / Lehreinheiten</th>
                    <th style="text-align:right; width:15%; border-bottom:1px solid #aaa; font-weight: bold; color: #1e293b;">LE</th>
                </tr>
            </thead><tbody>';

            foreach ($breakdown_rows as $row) {
                $le_num = intval(preg_replace('/[^0-9]/', '', $row['le']));
                $total_breakdown_le += $le_num;

                $clean_tit = ltrim($row['titel'], "+-• \t\n\r");
                $prefix_symbol = !empty($row['prefix']) ? $row['prefix'] : '+';
                $prefix_html = '<span style="color: #007C90; font-weight: bold;">' . esc_html($prefix_symbol) . '</span> ';

                $html .= '<tr>
                    <td style="width:85%; color: #334155; line-height: 1.4;">' . $prefix_html . esc_html($clean_tit) . '</td>
                    <td style="width:15%; text-align:right; font-weight: bold; color: #0f172a;">' . esc_html($row['le']) . '</td>
                </tr>';
                $html .= '<tr><td colspan="2" style="border-bottom:0.5pt dashed #ccc;"></td></tr>';
            }

            // Summenzeile wie bei "Ihre Investition" (Gesamt Brutto)
            if ($total_breakdown_le > 0) {
                $html .= '<tr style="background-color:#f9f9f9;">
                    <td style="width:85%;"><strong>Gesamt Lehreinheiten</strong></td>
                    <td style="width:15%; text-align:right;"><strong>' . $total_breakdown_le . ' LE</strong></td>
                </tr>';
            }

            $html .= '</tbody></table>';
        }

        // 3. Mehrwert / Inklusive Leistungen
        if (!empty($extra_rows) || !empty($section_title)) {
            if (!empty($html)) {
                $html .= '<div style="font-size:10pt">&nbsp;</div>';
            }
            $raw_title = !empty($section_title) ? $section_title : 'Ihr Mehrwert';
            $clean_title = trim(str_replace(['<', '>', '&lt;', '&gt;', '&LT;', '&GT;'], '', html_entity_decode($raw_title, ENT_QUOTES, 'UTF-8')));
            $clean_title = !empty($clean_title) ? mb_strtoupper($clean_title, 'UTF-8') : 'IHR MEHRWERT';
            $html .= '<table cellpadding="5" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; font-size: 10pt;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th colspan="2" style="text-align: left; border-bottom: 1px solid #aaa; font-weight: bold; color: #007C90;">&lt; ' . esc_html($clean_title) . ' &gt;</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($extra_rows as $row) {
                $html .= '<tr>
                    <td valign="top" style="width: 25%; font-weight: bold; color: #007C90; padding-top: 4px; padding-bottom: 4px;">' . esc_html($row['label']) . '</td>
                    <td valign="top" style="width: 75%; color: #334155; padding-top: 4px; padding-bottom: 4px;">' . esc_html($row['titel']) . '</td>
                </tr>
                <tr><td colspan="2" style="border-bottom: 0.5pt dashed #ccc;"></td></tr>';
            }
            $html .= '</tbody></table>';
        }

        return $html;
    }

    /**
     * Gibt den HTML-Code für die Anmelde- und AGB-Hinweise zurück.
     * @return string Der HTML-String mit AGB- und Datenschutzlinks.
     */
    private function get_anmeldung_agb_html()
    {
        $agb_custom = $this->get_crm_field('AGB text');
        if (!empty(trim(strip_tags($agb_custom)))) {
            return $agb_custom;
        }
        return '<p>Mit Ihrer Anmeldung bestätigen Sie die <a href="https://x-sieben.at/wp-content/uploads/2025/09/AGB_X_SIEBEN_2025.pdf">AGB</a> samt Widerrufsbelehrung der X SIEBEN Wirtschaftstraining GmbH gelesen und akzeptiert zu haben. Diese finden Sie auf unserer Website unter ‚AGB‘ oder auf Wunsch per E-Mail. Die Datenschutzerklärung finden Sie <a href="https://x-sieben.at/datenschutzerklaerung/">hier</a></p>';
    }

    /**
     * Ruft Daten eines WPForms-Eintrags ab und gibt sie als assoziatives Array zurück,
     * wobei die Schlüssel die Feld-IDs sind.
     * @param int $entry_id Die ID des WPForms-Eintrags.
     * @return array Ein assoziatives Array der Felddaten des Eintrags oder ein leeres Array bei Fehler.
     */
    public function get_wpforms_entry_data($entry_id)
    {
        if (!function_exists('wpforms')) return [];
        $entry = wpforms()->entry->get($entry_id);
        if (!$entry) return [];

        $fields = is_string($entry->fields) ? json_decode($entry->fields, true) : $entry->fields;

        $result = [];
        if (is_array($fields)) {
            foreach ($fields as $field_id => $field) {
                $result[$field_id] = $field;
            }
        }

        return $result;
    }

    /**
     * Ruft den Wert eines WPForms-Feldes über seine ID ab.
     * @param int $field_id Die ID des Feldes, das ausgelesen werden soll.
     * @return mixed Der Wert des Feldes oder ein leerer String, wenn das Feld nicht existiert.
     */
    public function get_wpforms_field_by_id($field_id)
    {
        // Überprüft, ob die Eintragsdaten vorhanden sind
        if (empty($this->entry_data)) {
            return '';
        }

        // Überprüft, ob das Feld mit der gegebenen ID existiert und gibt den Wert zurück
        return $this->entry_data[$field_id]['value'] ?? '';
    }

    /**
     * Ruft die Thumbnail-Bilder von zertifizierenden Organisationen ab, die mit dem Post verknüpft sind.
     * @return array Ein Array von HTML-Thumbnail-Bild-Tags.
     */
    private function get_zertifizierungen_images()
    {
        $images = [];
        $ca_meta = get_post_meta($this->post_id, "zertifikate", true);
        if (!empty($ca_meta) && is_array($ca_meta)) {
            $query = new WP_Query([
                "post__in" => $ca_meta,
                "post_type" => 'ca',
                "posts_per_page" => -1
            ]);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    if (get_the_ID() != 5793) {
                        $thumb = get_the_post_thumbnail(null, 'thumbnail');
                        if ($thumb) {
                            $images[] = $thumb;
                        }
                    }
                }
                wp_reset_postdata();
            }
        }
        return $images;
    }

    /**
     * Generiert einen HTML-Tabellen-String mit den vollständigen Bildern der Zertifizierungen.
     * @return string Der HTML-Tabellen-String mit Zertifizierungsbildern.
     */
    private function get_zertifizierungen_images_html()
    {
        $zert_images_src = [];
        $ca_meta = get_post_meta($this->post_id, "zertifikate", true);
        if (!empty($ca_meta) && is_array($ca_meta)) {
            $query = new WP_Query([
                "post__in" => $ca_meta,
                "post_type" => 'ca',
                "posts_per_page" => -1
            ]);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    if (get_the_ID() != 5793) {
                        $thumb_id = get_post_thumbnail_id(get_the_ID());
                        $local_file = $thumb_id ? get_attached_file($thumb_id) : '';
                        if ($local_file && file_exists($local_file)) {
                            $zert_images_src[] = $local_file;
                        } else {
                            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                            if ($image_url) {
                                $zert_images_src[] = $image_url;
                            }
                        }
                    }
                }
                wp_reset_postdata();
            }
        }
        $zert_images = '<table cellpadding="0" cellspacing="5" border="0"><tr>';
        foreach ($zert_images_src as $image) {
            $zert_images .= '
            <td cellpadding="6" style="width:58px; height:34px; border: 1px solid #cbd5e1; text-align: center; vertical-align: middle;">
                <img src="' . esc_attr($image) . '" style="max-width: 100%; max-height: 24px;">
            </td>';
        }
        $zert_images .= '</tr></table>';
        return $zert_images;
    }

    private function get_accordion_content_by_title($accordionData, $partialTitle)
    {
        if (!is_array($accordionData)) {
            return null;
        }
        foreach ($accordionData as $item) {
            if (
                isset($item['title']) &&
                stripos($item['title'], $partialTitle) !== false
            ) {
                return $item['content'];
            }
        }
        return null;
    }

    /**
     * Ruft dynamische Inhalte aus den Post-Metadaten ab und formatiert sie als Modul-HTML.
     * @param string $accordion_title Der Titel des Akkordeon-Eintrags.
     * @return string Der generierte HTML-Inhalt für die Module oder eine Standardnachricht.
     */
    private function get_inhalte_dyn(string $accordion_title = 'Inhalte'): string
    {
        $accordion_data = get_post_meta($this->post_id, 'courses_accordion', true);
        $content = $this->get_accordion_content_by_title($accordion_data, $accordion_title);
        if ($content) {
            return $this->_format_content_modules($content);
        }
        return '<p>Keine Inhalte gefunden.</p>';
    }

    /**
     * Teilt den gegebenen String in Module auf und formatiert sie als sauberes HTML für TCPDF.
     * Bereinigt verwaiste Tags, unpassende Doppelpunkte, leere Headings und optimiert Listen & Abstände.
     *
     * @param string $content Der String, der die Moduldaten enthält.
     * @return string Der generierte HTML-Code für die Module.
     */
    private function _format_content_modules(string $content): string
    {
        if (empty(trim($content))) {
            return '<p>Keine Inhalte gefunden.</p>';
        }

        // 1. Whitespace & Sonderzeichen normalisieren
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $content);

        // 2. Ungewollte Buttons und Links bereinigen
        $content = preg_replace('/<a[^>]*>\s*<button[^>]*>.*?<\/button>\s*<\/a>/isu', '', $content);
        $content = preg_replace('/<button[^>]*>.*?<\/button>/isu', '', $content);

        // 3. Leere HTML-Tags und Spacer entfernen
        $content = preg_replace('/<h[1-6][^>]*>\s*<\/h[1-6]>/iu', '', $content);
        $content = preg_replace('/<p[^>]*>\s*<\/p>/iu', '', $content);
        $content = preg_replace('/<div[^>]*>\s*<\/div>/iu', '', $content);
        $content = preg_replace('/<div[^>]*>\s*<hr[^>]*>\s*<\/div>/iu', '<hr />', $content);

        // 4. Modul-Grenzen identifizieren
        $mod_pattern = '/(?:<hr[^>]*>\s*)?(?:<(?:h[1-6]|p|div)[^>]*>\s*)?(?:<(?:strong|b|span|em)[^>]*>\s*)*\b(?<!\bin\s)(?<!\bim\s)(?<!\baus\s)(?<!\bvon\s)(?<!\bab\s)(?<!\bmit\s)(?<!\bjedem\s)(?<!\bdiesem\s)modul\s+(\d+|[ivxlcdm]+|ki)\b(?:\s*<\/(?:strong|b|span|em)>)*\s*[:\s–\-]*/iu';

        if (!preg_match_all($mod_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $this->_clean_generic_section_html($content);
        }

        $num_modules = count($matches[0]);
        $output = '';

        // Intro-Sektion vor dem ersten Modul
        $first_offset = $matches[0][0][1];
        if ($first_offset > 0) {
            $intro = substr($content, 0, $first_offset);
            $clean_intro = $this->_clean_generic_section_html($intro);
            if (!empty(trim($clean_intro))) {
                $output .= '<div class="modul-intro">' . $clean_intro . '</div>';
            }
        }

        // Module durchlaufen
        for ($i = 0; $i < $num_modules; $i++) {
            $mod_num = strtoupper(trim($matches[1][$i][0]));
            $start_pos = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end_pos = ($i + 1 < $num_modules) ? $matches[0][$i + 1][1] : strlen($content);
            $mod_chunk = substr($content, $start_pos, $end_pos - $start_pos);

            // Abschluss-Sektion beim letzten Modul prüfen
            $closing_html = '';
            if ($i === $num_modules - 1) {
                $closing_pattern = '/(?:<hr[^>]*>\s*)?(?:<(?:h[1-6]|p|div)[^>]*>\s*)?(?:<(?:strong|b|span|em)[^>]*>\s*)*(?:Lehrgangsabschluss|Abschluss\s*&amp;\s*Zertifizierung|Abschluss\s*&amp;\s*Diplom|Abschluss\s*:\s*Diplom|Abschluss\s*Diplom|Voraussetzungen\s+zum\s+Erwerb\s+des\s+Diploms)\b/iu';
                if (preg_match($closing_pattern, $mod_chunk, $closing_match, PREG_OFFSET_CAPTURE)) {
                    $closing_pos = $closing_match[0][1];
                    $closing_chunk = substr($mod_chunk, $closing_pos);
                    $mod_chunk = substr($mod_chunk, 0, $closing_pos);
                    $closing_html = $this->_clean_closing_section_html($closing_chunk);
                }
            }

            // Titel und Body trennen
            list($mod_title, $mod_body) = $this->_extract_module_title_and_body($mod_chunk);

            // Modul-Überschrift zusammenbauen
            $heading_text = 'MODUL ' . $mod_num;
            if (!empty($mod_title)) {
                $heading_text .= ': ' . $mod_title;
            }

            $output .= '<h3 class="modul-heading">' . esc_html($heading_text) . '</h3>';
            $output .= '<div class="modul-body">' . $this->_clean_module_body_html($mod_body) . '</div>';

            if (!empty($closing_html)) {
                $output .= $closing_html;
            }
        }

        return $output;
    }

    /**
     * Extrahiert den Modultitel und bereinigt den verbleibenden Body.
     *
     * @param string $chunk Der Textabschnitt des Moduls.
     * @return array [string $mod_title, string $mod_body]
     */
    private function _extract_module_title_and_body(string $chunk): array
    {
        $chunk = trim($chunk);
        $chunk = preg_replace('/^[:\s–\-]+/u', '', $chunk);

        $mod_title = '';
        $mod_body  = $chunk;

        // Der Titel endet bei </strong>, </b>, </span>, </p>, </h[1-6]>, <br> oder \n
        // Alles Folgende (einschließlich nachfolgender <ul>, <p> etc.) gehört zum Body
        if (preg_match('/^(.*?)(<\/(?:strong|b|span|em|i|h[1-6]|p)>|<br\s*\/?>|\n)/isu', $chunk, $m)) {
            $candidate = trim(strip_tags($m[1]));
            $candidate = preg_replace('/^[:\s–\-]+/u', '', $candidate);
            if (!empty($candidate) && !preg_match('/^\b(?:Zielgruppe|Ziel|Ziele|Inhalte|Inhalt|Methodik|Didaktik|Voraussetzungen)\b\s*[:\s–\-]/iu', $candidate) && strlen($candidate) < 250) {
                $mod_title = $candidate;
                $mod_body  = substr($chunk, strlen($m[0]));
            }
        }

        // Verwaiste Schlusstags und führende Trennzeichen entfernen
        $mod_body = preg_replace('/^(?:\s*(?:<\/(?:strong|b|span|em|i|h[1-6]|p|div)>|<br\s*\/?>)\s*)+/iu', '', $mod_body);
        $mod_body = preg_replace('/^[:\s–\-]+/u', '', $mod_body);

        return [trim($mod_title), trim($mod_body)];
    }

    /**
     * Bereinigt und strukturiert den Modul-Body (Labels, Absätze, Listen).
     *
     * @param string $body Der rohe HTML-Body des Moduls.
     * @return string Das formatierte HTML.
     */
    private function _clean_module_body_html(string $body): string
    {
        // 1. Redundante Trennlinien entfernen
        $body = preg_replace('/<hr[^>]*>/iu', '', $body);

        // 2. Standard-Labels (Ziel:, Inhalte: etc.) hervorheben
        // Nur matchen, wenn zwingend ein Doppelpunkt oder Gedankenstrich folgt (z.B. "Inhalte:", "Ziele:"),
        // damit Wörter im Fließtext wie "Inhalte, die verkaufen" oder "gesetzter Ziele" unberührt bleiben!
        $labels_regex = '/(?:<(?:p|div|h[4-6])[^>]*>\s*)?(?:<(?:strong|b|span)[^>]*>\s*)?\b(Zielgruppe|Ziele|Ziel|Inhalte|Inhalt|Methodik|Didaktik|Voraussetzungen)\b(?:\s*<\/(?:strong|b|span)>)*\s*[:–\-]\s*(?:<\/(?:p|div|h[4-6])>)?/iu';
        $body = preg_replace_callback($labels_regex, function($m) {
            $lbl = ucfirst(strtolower($m[1]));
            if ($lbl === 'Inhalt') $lbl = 'Inhalte';
            return "\n\n<p><strong class=\"modul-label\">" . $lbl . ":</strong></p>\n";
        }, $body);

        // 3. Listen bereinigen
        $body = preg_replace('/<li[^>]*>\s*<\/li>/iu', '', $body);
        $body = preg_replace('/<li[^>]*>\s*<p[^>]*>(.*?)<\/p>\s*<\/li>/isu', '<li>$1</li>', $body);
        $body = preg_replace('/<ul[^>]*>/iu', '<ul class="modul-list">', $body);

        // Vor <ul> und nach </ul> Doppelzeilenumbrüche erzwingen, damit Listen nicht in <p> eingeschlossen werden
        $body = preg_replace('/(?<!\n)\s*(<ul\b|<ol\b)/iu', "\n\n$1", $body);
        $body = preg_replace('/(<\/ul>|<\/ol>)\s*(?!\n)/iu', "$1\n\n", $body);

        // Newlines innerhalb von <ul> und <ol> normalisieren (keine \n\n innerhalb von Listen)
        $body = preg_replace_callback('/<(ul|ol)[^>]*>.*?<\/\1>/isu', function($matches) {
            return preg_replace('/\n{2,}/', "\n", $matches[0]);
        }, $body);

        // 4. Absätze sauber formatieren
        $paragraphs = preg_split('/\n{2,}/', $body);
        $clean_paras = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            if (preg_match('/^<(?:p|ul|ol|table|div|h[1-6]|blockquote)/i', $p)) {
                $clean_paras[] = $p;
            } elseif (preg_match('/^<li/i', $p)) {
                // Falls verwaiste li-Tags existieren, in saubere ul einbetten
                $clean_paras[] = '<ul class="modul-list">' . $p . '</ul>';
            } else {
                $clean_paras[] = '<p class="modul-text">' . $p . '</p>';
            }
        }
        $body = implode("\n", $clean_paras);

        // 5. Leere und doppelt geschachtelte Absätze bereinigen
        $body = preg_replace('/<p[^>]*>\s*<\/p>/iu', '', $body);
        $body = preg_replace('/<p class="modul-text">\s*(<p>.*?<\/p>)\s*<\/p>/isu', '$1', $body);

        return $body;
    }

    /**
     * Bereinigt generische Abschnitte (z. B. Intro ohne Module).
     *
     * @param string $html Der rohe HTML-Inhalt.
     * @return string Das bereinigte HTML.
     */
    private function _clean_generic_section_html(string $html): string
    {
        return $this->_clean_module_body_html($html);
    }

    /**
     * Bereinigt die Lehrgangsabschluss-Sektion.
     *
     * @param string $html Der rohe HTML-Inhalt des Abschlusses.
     * @return string Das formatierte HTML.
     */
    private function _clean_closing_section_html(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^(?:\s*<hr[^>]*>\s*)+/iu', '', $html);
        return '<div class="modul-abschluss">' .
               '<h3 class="abschluss-heading">Lehrgangsabschluss &amp; Zertifizierung</h3>' .
               $this->_clean_module_body_html($html) .
               '</div>';
    }

    /**
     * Teilt den gegebenen String in einzelne Module auf, basierend auf dem "MODUL X" Muster (Fallback / Abwärtskompatibilität).
     * @param string $content Der zu teilende String.
     * @return array Ein Array von Strings, wobei jeder String ein Modul darstellt.
     */
    private function splitByModul(string $content): array
    {
        $pattern = '/(?=modul\s*\d+\b(?![\)\.\w]))/i';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_NO_EMPTY);
        $modules = array_map('trim', $parts);
        return $modules;
    }

    private function get_trainer_html(): string
    {
        $post_id = $this->post_id;
        if (!$post_id) {
            return '';
        }
        $vt_meta = get_post_meta($post_id, 'vortragende', true);
        if (empty($vt_meta) || !is_array($vt_meta)) {
            return '';
        }
        $query = new WP_Query([
            'post__in' => $vt_meta,
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'post__in',
        ]);
        if (!$query->have_posts()) {
            return '';
        }
        $html = '';
        $count = 0;
        while ($query->have_posts()) {
            $query->the_post();
            if ($count > 0) {
                $html .= '<span style="margin-left: -4px;">, </span>';
            }
            $html .= sprintf(
                '<a href="%s" title="%s">%s</a>',
                esc_url(get_permalink()),
                esc_attr(get_the_title()),
                esc_html(get_the_title())
            );
            $count++;
        }
        $html .= '';
        wp_reset_postdata();
        return $html;
    }

    private function get_coursetype(): string
    {
        // 1. Priorität: Taxonomie 'coursetype'
        $types = get_the_terms($this->post_id, 'coursetype');
        if (!empty($types) && !is_wp_error($types)) {
            $first_type = reset($types);
            if (!empty($first_type->name)) {
                return $first_type->name;
            }
        }

        // 2. Fallback: Taxonomie 'coursecategory'
        if (has_term('Lehrgang', 'coursecategory', $this->post_id)) {
            return 'Lehrgang';
        } elseif (has_term('Seminar', 'coursecategory', $this->post_id)) {
            return 'Seminar';
        } elseif (has_term('Crashkurs', 'coursecategory', $this->post_id)) {
            return 'Crashkurs';
        } elseif (has_term('Bundle', 'coursecategory', $this->post_id)) {
            return 'Bundle';
        } elseif (has_term('eLearning', 'coursecategory', $this->post_id)) {
            return 'eLearning';
        } elseif (has_term('Blended Learning', 'coursecategory', $this->post_id)) {
            return 'Blended Learning';
        } elseif (has_term('Coaching', 'coursecategory', $this->post_id)) {
            return 'Coaching';
        }
        return 'Lehrgang';
    }

    private function get_garantie_html(): string
    {
        return '<table class="text">
                    <tr>
                        <td>' . $this->get_crm_field('Anhang 2 | Exklusive Zusatzleistungen') . '</td>
                    </tr>
                </table>';
    }

    private function get_zertifizierungen_loop_html(): string
    {
        $html = '<table>';
        $html .= '<tr><td>' . esc_html(get_post_meta($this->post_id, 'zertifizierung-zusatztext', true)) . '</td></tr>';
        $html .= '<tr>';
        $html .= '<th style="text-align:left"></th>';
        $html .= '<th style="text-align:right"></th>';
        $html .= '</tr>';
        if (have_rows('zertifizierungen', $this->post_id)) {
            while (have_rows('zertifizierungen', $this->post_id)) : the_row();
                $preis = (float)get_sub_field('preis');
                $ust_satz = (float)get_sub_field('Ust_satz');
                $ust = ($preis / (100 + $ust_satz)) * $ust_satz;
                $zert_preis_netto = $preis - $ust;
                $zert_preis_brutto = $zert_preis_netto + $ust;
                $html .= '<tr>';
                $html .= '<td style="width: 70%">' . esc_html(get_sub_field('name-zert')) . '</td>';
                $html .= '<td style="width: 30%; text-align:right">' . number_format($zert_preis_netto, 2, ',', '.') . ' €</td>';
                $html .= '</tr><tr>';
                $html .= '<td>+ ' . esc_html($ust_satz) . '% (von ' . number_format($zert_preis_netto, 2, ',', '.') . ' €) </td>';
                $html .= '<td style="text-align:right">' . number_format($ust, 2, ',', '.') . '€ </td>';
                $html .= '</tr><tr><td colspan="2" style="border-top: 1px solid #cbd5e1; height: 1px; font-size: 1pt;">&nbsp;</td></tr><tr>';
                $html .= '<td><strong>Gesamt Brutto</strong> </td><td style="text-align:right"><strong>' . number_format($zert_preis_brutto, 2, ',', '.') . ' €</strong> </td></tr>';
            endwhile;
        }
        $html .= '</table>';
        $html .= '<div style="font-size:10pt">&nbsp;</div>';
        return $html;
    }

    public function get_form_zertifizierungen_loop_html(): string
    {
        $certifications_data = $this->get_certifications_from_form_field();

        if (empty($certifications_data)) {
            return '';
        }

        $total_brutto = 0.00;
        $total_netto = 0.00;

        $html = '
    <div style="font-family:dejavusans; font-size:14pt; margin-bottom:6px;">Optionale Zertifizierungen</div>
    <div style="font-family:dejavusans; font-size:10pt; margin-bottom:8px;">
        Zu dieser Veranstaltung können wir Ihnen optional folgende Zertifizierungen anbieten:
    </div>
    <hr style="border-top:1px solid #aaa; margin-bottom: 5px;">
    <div style="font-family:dejavusans; font-size:10pt;">
        <span style="display:inline-block; width:70%;"><strong>Zertifizierung</strong></span>
        <span style="display:inline-block; width:29%; text-align:right;"><strong>Preis</strong></span>
    </div>
    <hr style="border-top:1px solid #aaa; margin-bottom: 8px;">';

        foreach ($certifications_data as $cert) {
            $name = htmlspecialchars($cert['name']);
            $price = str_replace(['.', ','], ['', '.'], $cert['price']);
            $percentage_raw = rtrim($cert['percentage'], '%');

            $ust_satz = ($percentage_raw === 'N/A') ? 20.00 : (float) $percentage_raw;
            $price = (float) $price;

            $ust = ($price / (100 + $ust_satz)) * $ust_satz;
            $zert_preis_netto = $price - $ust;
            $zert_preis_brutto = $zert_preis_netto + $ust;

            $total_brutto += $zert_preis_brutto;
            $total_netto += $zert_preis_netto;

            $html .= '
            <div style="font-family:dejavusans; font-size:10pt; margin-bottom: 2px;">
                <span style="display:inline-block; width:70%;">' . $name . '</span>
                <span style="display:inline-block; width:29%; text-align:right;">' . number_format($zert_preis_netto, 2, ',', '.') . ' €</span>
            </div>
            <div style="font-family:dejavusans; color:#555; font-size:9pt; margin-bottom: 2px;">
                <span style="display:inline-block; width:70%;">+ ' . number_format($ust_satz, 2, ',', '.') . '% (von ' . number_format($zert_preis_netto, 2, ',', '.') . ' €)</span>
                <span style="display:inline-block; width:29%; text-align:right;">' . number_format($ust, 2, ',', '.') . ' €</span>
            </div>
            <hr style="border-top:0.5pt dashed #ccc; margin-top: 2px; margin-bottom: 2px;">
        ';
        }

        // Totals
        $html .= '
    <div style="font-family:dejavusans; font-size:10pt; margin-top: 8px;">
        <span style="display:inline-block; width:70%;">Gesamt Netto</span>
        <span style="display:inline-block; width:29%; text-align:right;">' . number_format($total_netto, 2, ',', '.') . ' €</span>
    </div>
    <div style="font-family:dejavusans; font-size:10pt; background-color:#f9f9f9; padding:2px 0;">
        <span style="display:inline-block; width:70%;"><strong>Gesamt Brutto</strong></span>
        <span style="display:inline-block; width:29%; text-align:right;"><strong>' . number_format($total_brutto, 2, ',', '.') . ' €</strong></span>
    </div>
    ';

        return $html;
    }

    public function get_gesamt_kosten_html(): string
    {
        $netto_kurs = (float) $this->preis_netto;
        $brutto_kurs = (float) $this->preis_brutto;
        $ust_satz = 20.00;
        $ust_kurs = ($netto_kurs / 100) * $ust_satz;

        $certifications_data = $this->get_certifications_from_form_field();

        $total_netto = $netto_kurs;
        $total_ust = $ust_kurs;
        $total_brutto = $brutto_kurs;

        $html = '
    <table cellpadding="6" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; font-size:10pt;">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th style="text-align:left; width:70%; border-bottom:1px solid #aaa;">Beschreibung</th>
                <th style="text-align:right; width:30%; border-bottom:1px solid #aaa;">Preis</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width:70%;">Kursgebühr
                    <span style="color:#555; font-size:9pt;">
                        ' . $this->anzahl_le . ' Lehreinheiten (' . number_format($this->le_single, 2, ',', '.') . ' €/LE, 1 LE = 45min)
                    </span>
                </td>
                <td style="width:30%; text-align:right;">' . number_format($netto_kurs, 2, ',', '.') . ' €</td>
            </tr>
            <tr style="color:#555; font-size:9pt;">
                <td>+ ' . number_format($ust_satz, 2, ',', '.') . '% MwSt. (von ' . number_format($netto_kurs, 2, ',', '.') . ' €)</td>
                <td style="text-align:right;">' . number_format($ust_kurs, 2, ',', '.') . ' €</td>
            </tr>
            <tr><td colspan="2" style="border-bottom:0.5pt dashed #ccc;"></td></tr>';

        // Zertifizierungen hinzufügen
        if (!empty($certifications_data)) {
            foreach ($certifications_data as $cert) {
                $name = htmlspecialchars($cert['name']);
                $price = str_replace(['.', ','], ['', '.'], $cert['price']);
                $percentage_raw = rtrim($cert['percentage'], '%');

                $ust_satz_cert = ($percentage_raw === 'N/A') ? 20.00 : (float) $percentage_raw;
                $price = (float) $price;

                $ust_cert = ($price / (100 + $ust_satz_cert)) * $ust_satz_cert;
                $netto_cert = $price - $ust_cert;
                $brutto_cert = $netto_cert + $ust_cert;

                // Summen erhöhen
                $total_netto += $netto_cert;
                $total_ust += $ust_cert;
                $total_brutto += $brutto_cert;

                $html .= '
            <tr>
                <td style="width:70%;">' . $name . '</td>
                <td style="width:30%; text-align:right;">' . number_format($netto_cert, 2, ',', '.') . ' €</td>
            </tr>
            <tr style="color:#555; font-size:9pt;">
                <td>+ ' . number_format($ust_satz_cert, 2, ',', '.') . '% MwSt. (von ' . number_format($netto_cert, 2, ',', '.') . ' €)</td>
                <td style="text-align:right;">' . number_format($ust_cert, 2, ',', '.') . ' €</td>
            </tr>
            <tr><td colspan="2" style="border-bottom:0.5pt dashed #ccc;"></td></tr>';
            }
        }

        // Gesamtsummen
        $html .= '
            <tr style="color:#555; font-size:9pt;">
                <td><strong>Gesamt Netto</strong></td>
                <td style="text-align:right;"><strong>' . number_format($total_netto, 2, ',', '.') . ' €</strong></td>
            </tr>
            <tr style="color:#555; font-size:9pt;">
                <td><strong>Gesamt MwSt.</strong></td>
                <td style="text-align:right;">' . number_format($total_ust, 2, ',', '.') . ' €</td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:0.5pt dashed #ccc;">
            </td>
            </tr>
            <tr style="background-color:#f9f9f9;">
                <td><strong>Gesamt Brutto</strong></td>
                <td style="text-align:right;"><strong>' . number_format($total_brutto, 2, ',', '.') . ' €</strong></td>
            </tr>
        </tbody>
    </table>';

        return $html;
    }


    /**
     * Generates an HTML unordered list from an array of prerequisites.
     * @param array|null $voraussetzungen_list An array of strings, each being a prerequisite.
     * @return string The generated HTML list, or an empty string if no prerequisites are provided.
     */
    private function get_voraussetzungen_list_html(): string
    {
        $voraussetzungen_list = $this->voraussetzungen;

        if (empty($voraussetzungen_list)) {
            return '';
        }

        $html = '<ul>';

        // Repeater: Array von Arrays (mit Key "requirements")
        if (is_array($voraussetzungen_list) && isset($voraussetzungen_list[0]) && is_array($voraussetzungen_list[0])) {
            foreach ($voraussetzungen_list as $row) {
                if (isset($row['requirements']) && !empty(trim($row['requirements']))) {
                    $html .= '<li>' . esc_html(trim($row['requirements'])) . '</li>';
                }
            }
        }
        // Checkbox/Select: Array von Strings
        elseif (is_array($voraussetzungen_list)) {
            foreach ($voraussetzungen_list as $item) {
                if (is_string($item) && !empty(trim($item))) {
                    $html .= '<li>' . esc_html(trim($item)) . '</li>';
                }
            }
        }
        // Textfeld: Einfacher String mit evtl. Zeilenumbrüchen
        elseif (is_string($voraussetzungen_list)) {
            $lines = preg_split('/\r\n|\r|\n/', $voraussetzungen_list);
            foreach ($lines as $line) {
                if (!empty(trim($line))) {
                    $html .= '<li>' . esc_html(trim($line)) . '</li>';
                }
            }
        }

        $html .= '</ul>';

        return $html;
    }

    public function get_contact_info_html(): string
    {
        $html = '<table class="text" style="padding-bottom: 30pt;">';
        $html .= '    <tr style="padding-bottom: 10pt;">';
        $html .= '        <td style="width:7%;">' . $this->web_icon . '<div style="font-size:5pt">&nbsp;</div> </td>';
        $html .= '        <td style="width:43%;"><div style="font-size:2pt">&nbsp;</div> <a href="http://x-sieben.at/kontakt">www.x-sieben.at/kontakt</a></td>';
        $html .= '        <td style="width:7%;">' . $this->fax_icon . '<div style="font-size:5pt">&nbsp;</div> </td>';
        $html .= '        <td style="width:43%;"><div style="font-size:2pt">&nbsp;</div> Fax: (+43) 2622 / 351 10 14</td>';
        $html .= '    </tr>';
        $html .= '    <tr>';
        $html .= '        <td style="width:7%;">' . $this->mail_icon . '<div style="font-size:5pt">&nbsp;</div> </td>';
        $html .= '        <td style="width:43%;"><div style="font-size:2pt">&nbsp;</div><a href="mailto:office@x-sieben.at">office@x-sieben.at</a></td>';
        $html .= '        <td style="width:7%;">' . $this->phone_icon . '<div style="font-size:5pt">&nbsp;</div> </td>';
        $html .= '        <td style="width:43%;"><div style="font-size:2pt">&nbsp;</div> Rückfragen: <a href="tel: 0043800700170">(+43) 800 700 170</a></td>';
        $html .= '    </tr>';
        $html .= '</table>';

        return $html;
    }
    private function get_ps_html(): string
    {
        $ps_custom = $this->get_crm_field('Angebot PS');
        if (!empty(trim(strip_tags($ps_custom)))) {
            return '<table style="font-size:10pt;"><tr><td style="margin:0; padding:0;">' . $ps_custom . '</td></tr></table>';
        }
        return '<table style="font-size:10pt;">
                    <tr>
                        <td style="margin:0; padding:0;">PS: Die <strong>Bewertungen unserer Kursteilnehmer</strong> finden Sie auf der externen Bewertungsplattform <a href="https://www.x-sieben.at/provenexpert.com/x-sieben-wirtschaftstraining/?utm_source=Widget&utm_medium=Widget&utm_campaign=Widget">ProvenExpert</a>! <br>
                        </td>
                        </tr>
                        <tr>
                        <td style="margin:0; padding:0;">PPS: <strong>Keine Förderung?</strong> Dennoch <strong>jetzt weiterbilden</strong> und bis in zu <strong>24 Monatsraten</strong> bezahlen. Mit <a href="https://www.x-sieben.at/jetzt-weiterbilden-bezahlen-in-bis-zu-24-raten-mit-klarna/">Klarna</a>.
                        </td>
                    </tr>
                </table>';
    }

    private function _setExpireDate()
    {
        $fourteen_days_from_now_YMD = date('Y-m-d', strtotime('+14 days'));
        $start_date_formatted_YMD = $this->start_datum ? date('Y-m-d', strtotime($this->start_datum)) : null;
        $expire_date_YMD = $fourteen_days_from_now_YMD;
        if (!empty($start_date_formatted_YMD) && $start_date_formatted_YMD < $expire_date_YMD) {
            $expire_date_YMD = $start_date_formatted_YMD;
        }
        $this->expire = date('d.m.Y', strtotime($expire_date_YMD));
    }

    public function get_kursgebuehr_html(): string
    {


        $netto = (float) $this->preis_netto;
        $brutto = (float) $this->preis_brutto;
        $ust_satz = 20.00;
        $ust = ($netto / 100) * $ust_satz;

        $html = '
    
    <table cellpadding="6" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; font-size:10pt;">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th style="text-align:left; width:70%; border-bottom:1px solid #aaa;">Beschreibung</th>
                <th style="text-align:right; width:30%; border-bottom:1px solid #aaa;">Preis</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width:70%;">Kursgebühr
                    <span style="color:#555; font-size:9pt;">
                        ' . $this->anzahl_le . ' Lehreinheiten (' . number_format($this->le_single, 2, ',', '.') . ' €/LE, 1 LE = 45min)
                    </span>
                </td>
                <td style="width:30%; text-align:right;">' . number_format($netto, 2, ',', '.') . ' €</td>
            </tr>
            <tr style="color:#555; font-size:9pt;">
                <td>+ ' . number_format($ust_satz, 2, ',', '.') . '% MwSt. (von ' . number_format($netto, 2, ',', '.') . ' €)</td>
                <td style="text-align:right;">' . number_format($ust, 2, ',', '.') . ' €</td>
            </tr>
            <tr><td colspan="2" style="border-bottom:0.5pt dashed #ccc;"></td></tr>
            <tr style="background-color:#f9f9f9;">
                <td><strong>Gesamt Brutto</strong></td>
                <td style="text-align:right;"><strong>' . number_format($brutto, 2, ',', '.') . ' €</strong></td>
            </tr>
        </tbody>
    </table>
    ';

        return $html;
    }

    private function get_signature_html(): string
    {
        return '<div>' . $this->signatur_icon . '<br>
    <span style="font-size: 10pt;">Mag. Dr. Johannes Gasberger<br></span>
    <span style="font-size: 9pt; color: #475569;">Geschäftsführer | X SIEBEN Wirtschaftstraining GmbH</span></div>';
    }

    public function get_diplom_success(): ?string
    {
        if (!empty($this->diplom_success)) {
            return $this->diplom_success;
        }

        // Hole den Wert aus WPForms Feld 100 (Abschluss Erfolg) mit Fallback auf 101
        $status_string = $this->get_wpforms_field_by_id(100);
        if (empty($status_string)) {
            $status_string = $this->get_wpforms_field_by_id(101);
        }

        if (empty($status_string)) {
            return null;
        }

        // Nur den ersten nicht-leeren Eintrag nehmen
        $lines = explode("\n", (string)$status_string);
        $raw_val = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $raw_val = $line;
                break;
            }
        }

        if (empty($raw_val)) {
            return null;
        }

        $lower = mb_strtolower($raw_val, 'UTF-8');
        if (strpos($lower, 'ausgezeichnet') !== false) {
            return 'mit ausgezeichnetem Erfolg';
        } elseif (strpos($lower, 'sehr gut') !== false) {
            return 'mit sehr gutem Erfolg';
        } elseif (strpos($lower, 'gut') !== false) {
            return 'mit gutem Erfolg';
        } elseif (strpos($lower, 'erfolg') !== false) {
            return 'erfolgreich';
        }

        return $raw_val;
    }

    /**
     * Generates a formatted HTML title block 
     * 
     * This method creates a table with a stylized title for use in PDF 
     *
     * @param string $prefix  (e.g., 'Anhang 1').
     * @param string $title The title (e.g., 'Exklusive Zusatzleistungen').
     * @return string The HTML table string for the appendix title.
     */
    public function get_pdf_title(string $title, ?string $prefix = null): string
    {
        // Conditionally create the prefix HTML part only if $prefix is not null or empty.
        $prefix_html = !empty($prefix) ? '<strong>' . esc_html($prefix) . '</strong> - ' : '';

        return '
        <table class="title" style="width: 100%;">
            <tr>
                <td style="font-size: 14pt; padding-bottom: 5px;">
                    ' . $prefix_html . '<span style="font-size: 12pt;">' . esc_html($title) . '</span>
                </td>
            </tr>
        </table>
    ';
    }
}
