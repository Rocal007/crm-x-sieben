<?php

require_once __DIR__ . '/crm-bootstrap.php';
require_once __DIR__ . '/helpers/normalize.php';

class CRM_Model
{
    public ?ParticipantDTO $participantDTO = null;
    public ?CourseDTO $courseDTO = null;
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

    /**
     * Konstruktor der Klasse.
     * @param int $post_id Die ID des Beitrags.
     * @param int|null $entry_id The ID of the WPForms entry (optional).
     */
    public function __construct(int $post_id, ?int $entry_id = null)
    {
        $this->post_id = $post_id;

        // 1. Participant-Daten über WPFormsRepository (DTO)
        $participantRepo = new WPFormsRepository();
        $this->participantDTO = $participantRepo->findParticipant($entry_id ?? 0);
        $this->entry_data = $participantRepo->getRawEntryData($entry_id ?? 0);

        $this->salutation   = $this->participantDTO->salutation;
        $this->anrede       = $this->participantDTO->anrede;
        $this->titel        = $this->participantDTO->title;
        $this->vorname      = $this->participantDTO->firstName;
        $this->nachname     = $this->participantDTO->lastName;
        $this->email        = $this->participantDTO->email;
        $this->svr          = $this->participantDTO->svr;
        $this->street       = $this->participantDTO->street;
        $this->house_number = $this->participantDTO->houseNumber;
        $this->city         = $this->participantDTO->city;
        $this->zip_code     = $this->participantDTO->zipCode;
        $this->country      = $this->participantDTO->country;

        // 2. Kurs-Daten über CourseRepository (DTO)
        $courseRepo = new CourseRepository();
        $this->courseDTO = $courseRepo->findById($post_id, $entry_id);

        $this->title                = $this->courseDTO->title;
        $this->titel_short          = $this->courseDTO->shortTitle;
        $this->permalink            = $this->courseDTO->permalink;
        $this->start_datum          = $this->courseDTO->startDate;
        $this->end_datum            = $this->courseDTO->endDate;
        $this->preis_netto          = $this->courseDTO->getFormattedNetPrice();
        $this->preis_brutto         = !empty($this->courseDTO->netPrice) ? number_format($this->courseDTO->grossPrice, 2, '.', '') : 0;
        $this->title_preis          = $this->courseDTO->title;
        $this->angebot_beschreibung = $this->courseDTO->description;
        $this->anzahl_le            = $this->courseDTO->totalLE;
        $this->le_single            = number_format($this->courseDTO->pricePerLE, 2);
        $this->kurstyp              = $this->courseDTO->courseType;
        $this->zielgruppe           = $this->courseDTO->targetGroup;
        $this->kurszeiten           = $this->courseDTO->courseTimes;
        $this->selbststudium        = $this->courseDTO->selfStudy;
        $this->zertifizierungen     = $this->courseDTO->certifications;
        $this->voraussetzungen      = $this->courseDTO->prerequisites;

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

        $this->load_icons();
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

        if ($this->anrede === 'Herr') {
            $this->salutation = "Sehr geehrter Herr";
        } elseif ($this->anrede === 'Frau') {
            $this->salutation = "Sehr geehrte Frau";
        } else {
            $this->salutation = "Sehr geehrte(r) Frau/Herr";
        }
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
        // Case-insensitive search remains the same
        $index = array_search(
            strtolower($title),
            array_map('strtolower', array_column($fields, 'title'))
        );
        // Check if the field was found
        if ($index !== false && isset($fields[$index]['content'])) {
            $content = $fields[$index]['content'];
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
        // If not found, return an empty string
        return '';
    }

    // Inside the CRM_Model class
    public function parse_string_with_data(string $template_string): string
    {
        // Use preg_replace_callback to find all {variable} placeholders.
        // The pattern now looks for an opening curly brace, then captures one or more alphanumeric characters and underscores, followed by a closing curly brace.
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) {
            $key = $matches[1];
            // Check if the property exists in the current object
            if (property_exists($this, $key)) {
                // Return the property value. Use a ternary operator to handle non-string types gracefully.
                return is_scalar($this->$key) ? $this->$key : '';
            }
            // If the property doesn't exist, return the original placeholder to avoid breaking the template.
            return $matches[0];
        }, $template_string);
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
     * Lädt und formatiert HTML-Tags für verschiedene Icons und Logos aus dem Theme-Assets-Verzeichnis.
     */
    private function load_icons()
    {
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
            $this->$prop = sprintf(
                '<img%s%s src="%s%s">',
                $width_attr,
                $style_attr,
                $assets_url,
                $config['file']
            );
        }
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

        $html = '<table cellpadding="4" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 10pt;">';
        $html .= '<thead>
            <tr style="background-color: #f1f5f9; border-bottom: 1.5px solid #007C90;">
                <th style="width: 20%; text-align: left; color: #007C90; font-weight: bold;">Gliederung</th>
                <th style="width: 70%; text-align: left; color: #007C90; font-weight: bold;">Beschreibung</th>
                <th style="width: 10%; text-align: right; color: #007C90; font-weight: bold;">LE</th>
            </tr>
        </thead><tbody>';

        // 1. Modul- und Themeninhalte
        foreach ($module_rows as $row) {
            $html .= '<tr>
                <td valign="top" style="width: 20%; font-weight: bold; color: #1e293b; padding-top: 4px; padding-bottom: 4px;">' . esc_html($row['modul']) . '</td>
                <td valign="top" style="width: 70%; color: #334155; padding-top: 4px; padding-bottom: 4px;">' . esc_html($row['titel']) . '</td>
                <td valign="top" style="width: 10%; text-align: right; color: #64748b; padding-top: 4px; padding-bottom: 4px;">' . (!empty($row['le']) ? esc_html($row['le']) : '') . '</td>
            </tr>';
        }

        // 2. Zeiteinteilung / Lehreinheiten-Aufteilung
        if (!empty($breakdown_rows)) {
            if (!empty($module_rows)) {
                $html .= '<tr>
                    <td colspan="3" style="border-top: 1px solid #cbd5e1; padding-top: 8px; padding-bottom: 3px;">
                        <span style="font-size: 9.5pt; font-weight: bold; color: #007C90; text-transform: uppercase;">Zeiteinteilung / Lehreinheiten:</span>
                    </td>
                </tr>';
            }
            foreach ($breakdown_rows as $row) {
                $html .= '<tr>
                    <td valign="top" colspan="2" style="width: 90%; color: #334155; padding-top: 3px; padding-bottom: 3px; padding-left: 8px;">
                        <strong style="color: #007C90;">' . esc_html($row['prefix']) . '</strong> ' . esc_html($row['titel']) . '
                    </td>
                    <td valign="top" style="width: 10%; text-align: right; font-weight: bold; color: #0f172a; padding-top: 3px; padding-bottom: 3px;">' . esc_html($row['le']) . '</td>
                </tr>';
            }
        }

        // 3. Mehrwert / Inklusive Leistungen
        if (!empty($extra_rows) || !empty($section_title)) {
            $title_display = !empty($section_title) ? $section_title : 'Ihr Mehrwert';
            $html .= '<tr>
                <td colspan="3" style="border-top: 1px solid #cbd5e1; padding-top: 8px; padding-bottom: 4px;">
                    <span style="font-size: 9.5pt; font-weight: bold; color: #007C90; text-transform: uppercase;">&lt; ' . esc_html($title_display) . ' &gt;</span>
                </td>
            </tr>';
            foreach ($extra_rows as $row) {
                $html .= '<tr>
                    <td valign="top" style="width: 20%; font-weight: bold; color: #007C90; padding-top: 3px; padding-bottom: 3px;">' . esc_html($row['label']) . '</td>
                    <td valign="top" style="width: 80%; color: #334155; padding-top: 3px; padding-bottom: 3px;" colspan="2">' . esc_html($row['titel']) . '</td>
                </tr>';
            }
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Gibt den HTML-Code für die Anmelde- und AGB-Hinweise zurück.
     * @return string Der HTML-String mit AGB- und Datenschutzlinks.
     */
    private function get_anmeldung_agb_html()
    {
        return '<p>Mit Ihrer Anmeldung bestätigen Sie die <a href="https://www.x-sieben.at/wp-content/uploads/2018/05/AGB_X_SIEBEN.pdf">AGB</a> samt Widerrufsbelehrung der X SIEBEN Wirtschaftstraining GmbH gelesen und akzeptiert zu haben. Diese finden Sie auf unserer Website unter ‚AGB‘ oder auf Wunsch per E-Mail. Die Datenschutzerklärung finden Sie <a href="https://www.x-sieben.at/datenschutzerklaerung/">hier</a></p>';
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
        $zert_images_url = [];
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
                        $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                        if ($image_url) {
                            $zert_images_url[] = $image_url;
                        }
                    }
                }
                wp_reset_postdata();
            }
        }
        $zert_images = '<table cellpadding="5"><tr>';
        foreach ($zert_images_url as $image) {
            $zert_images .= '
            <td cellpadding="15" style="width:80px; border: solid black 1px;">
                <img src="' . esc_url($image) . '" style="max-width:100%;">
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
        // Verwaiste Schlusstags und führende Trennzeichen entfernen
        $chunk = preg_replace('/^(?:\s*<\/(?:strong|b|span|em|i|h[1-6]|p|div)>\s*)+/iu', '', $chunk);
        $chunk = preg_replace('/^[:\s–\-]+/u', '', $chunk);

        $mod_title = '';
        $mod_body = $chunk;

        if (preg_match('/^(?:<(?:strong|b|span|em|i|h[1-6]|p)[^>]*>\s*)*(.*?)(?:<\/(?:strong|b|span|em|i|h[1-6]|p)>\s*)*(?:\n\n|\n|<br\s*\/?>|<\/h[1-6]>|<\/p>|<p>|<ul>|<hr)/isu', $chunk, $title_match)) {
            $candidate = trim(strip_tags($title_match[1]));
            $candidate = preg_replace('/^[:\s–\-]+/u', '', $candidate);
            if (!empty($candidate) && !preg_match('/^\b(?:Zielgruppe|Ziel|Ziele|Inhalte|Inhalt|Methodik|Didaktik|Voraussetzungen)\b\s*[:\s–\-]/iu', $candidate) && strlen($candidate) < 180) {
                $mod_title = $candidate;
                $mod_body = substr($chunk, strlen($title_match[0]));
            }
        }

        $mod_body = preg_replace('/^(?:\s*<\/(?:strong|b|span|em|i|h[1-6]|p|div)>\s*)+/iu', '', $mod_body);
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

        // 2. Standard-Labels (Ziel, Inhalte etc.) hervorheben
        $labels_regex = '/(?:<(?:p|div|h[4-6])[^>]*>\s*)?(?:<(?:strong|b|span)[^>]*>\s*)?\b(Zielgruppe|Ziel|Ziele|Inhalte|Inhalt|Methodik|Didaktik|Voraussetzungen)\b\s*[:\s–\-]*(?:\s*<\/(?:strong|b|span)>)*\s*[:\s–\-]*(?:<\/(?:p|div|h[4-6])>)?/iu';
        $body = preg_replace_callback($labels_regex, function($m) {
            $lbl = ucfirst(strtolower($m[1]));
            if ($lbl === 'Inhalt') $lbl = 'Inhalte';
            return "\n\n<p><strong class=\"modul-label\">" . $lbl . ":</strong></p>\n";
        }, $body);

        // 3. Listen bereinigen
        $body = preg_replace('/<li[^>]*>\s*<\/li>/iu', '', $body);
        $body = preg_replace('/<li[^>]*>\s*<p[^>]*>(.*?)<\/p>\s*<\/li>/isu', '<li>$1</li>', $body);
        $body = preg_replace('/<ul[^>]*>/iu', '<ul class="modul-list">', $body);

        // 4. Absätze sauber formatieren
        $paragraphs = preg_split('/\n{2,}/', $body);
        $clean_paras = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            if (preg_match('/^<(?:p|ul|ol|table|div|h[1-6]|blockquote)/i', $p)) {
                $clean_paras[] = $p;
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
        $html = preg_replace('/<hr[^>]*>/iu', '', $html);
        $html = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/isu', '<p><strong>$1</strong></p>', $html);
        $html = preg_replace('/<ul[^>]*>/iu', '<ul class="modul-list">', $html);
        $paragraphs = preg_split('/\n{2,}/', $html);
        $clean_paras = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            if (preg_match('/^<(?:p|ul|ol|table|div|blockquote)/i', $p)) {
                $clean_paras[] = $p;
            } else {
                $clean_paras[] = '<p class="modul-text">' . $p . '</p>';
            }
        }
        return implode("\n", $clean_paras);
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
        $cat_slug_custom = 'LEHRGANG';
        if (has_term('Lehrgang', 'coursecategory', $this->post_id)) {
            $cat_slug_custom = 'Lehrgang';
        } elseif (has_term('Seminar', 'coursecategory', $this->post_id)) {
            $cat_slug_custom = 'Seminar';
        } elseif (has_term('Crashkurs', 'coursecategory', $this->post_id)) {
            $cat_slug_custom = 'Crashkurs';
        } elseif (has_term('Bundle', 'coursecategory', $this->post_id)) {
            $cat_slug_custom = 'Bundle';
        } elseif (has_term('eLearning', 'coursecategory', $this->post_id)) {
            $cat_slug_custom = 'eLearning';
        } elseif (has_term('Blended Learning', 'coursecategory', $this->post_id)) {
            $cat_slug_custom = 'Blended-learning';
        }
        return $cat_slug_custom;
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
        return '<div>' . $this->signatur_icon . '</div>
    <span style="font-size: 10pt;">Mag. Dr. Johannes Gasberger<br></span>
    <span style="font-size: 9pt; margin-top: -15px; margin-bottom: 30px;">Geschäftsführer | X SIEBEN Wirtschaftstraining GmbH</span></div>';
    }

    public function get_diplom_success(): ?string
    {
        // Hole den Wert aus dem Feld 101
        $status_string = $this->get_wpforms_field_by_id(101);

        if (empty($status_string)) {
            return null;
        }

        // Nur den ersten nicht-leeren Eintrag nehmen
        $lines = explode("\n", $status_string);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                return $line;
            }
        }

        return null;
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

    /**
     * Get typed ParticipantDTO.
     */
    public function getParticipant(): ?ParticipantDTO
    {
        return $this->participantDTO;
    }

    /**
     * Get typed CourseDTO.
     */
    public function getCourse(): ?CourseDTO
    {
        return $this->courseDTO;
    }
}
