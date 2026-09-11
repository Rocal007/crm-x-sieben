<?php
function xsieben_offer_pdf($entry_id, $course_id, $output_to_browser=true, $custom_sections=null)
{
    // Load course data
    $course = new CRM_Model($course_id, $entry_id);

    $nummer = $entry_id . '-' . $course_id;
    $safe_title = preg_replace('/[^\p{L}0-9_\-]/u', '_', $course->titel_short);
    $angebotsnummer = "A_" . $nummer;
    $pdfAuthor = "XSieben Wirtschaftstraining";

    // --- HTML Styles (to be included in all parts) ---
    $styles = '<style>
        div {font-size:11pt;}
        .title {
            text-align: left;
            font-size: 16px;
            background-color: #007C90;
            padding:5pt 5pt 2pt 5pt;
            color: white;
            width:100%;
        }
        .text {
            line-height: 14pt;
            font-size: 10.5pt;
        }
        .clear {font-size:unset;}
        a {color: #04b3ce}
        hr {border: 0; height: 1px;}
        .table-dot {font-size:9pt; color: #04b3ce; border: 1px dashed #16A0B9;}
        strong, b {font-weight: bold;}
        h3.modul-heading {
            font-size: 11pt;
            font-weight: bold;
            color: #007C90;
            border-bottom: 1px solid #007C90;
            padding-bottom: 2pt;
            margin-top: 12pt;
            margin-bottom: 5pt;
        }
        .modul-label {
            font-weight: bold;
            color: #0f172a;
        }
        p.modul-text {
            font-size: 10pt;
            line-height: 15pt;
            margin-bottom: 5pt;
        }
        ul.modul-list {
            margin-top: 2pt;
            margin-bottom: 6pt;
        }
        ul.modul-list li {
            font-size: 10pt;
            line-height: 15pt;
        }
        .modul-intro {
            margin-bottom: 10pt;
        }
        .abschluss-heading {
            font-size: 11pt;
            font-weight: bold;
            color: #007C90;
            border-bottom: 1px solid #007C90;
            padding-bottom: 2pt;
            margin-top: 14pt;
            margin-bottom: 5pt;
        }
    </style>';

    // --- Modular HTML Sections for Dynamic Ordering ---
    require_once dirname(__DIR__) . '/helpers/crm-pdf-sections.php';

    // 1. Deckblatt / Anschreiben
    $angebot_default_intro = 'Danke für Ihr Interesse und willkommen bei der beliebten X SIEBEN Veranstaltung ' . $course->title . ' mit lernförderndem Kleingruppen-Unterricht.<br><br>Diese Veranstaltung fokussiert auf ' . $course->zielgruppe;
    $angebot_intro         = $course->get_crm_field_with_default('Angebot - Einleitung', $angebot_default_intro);
    $angebot_gruss         = $course->get_crm_field_with_default('Angebot - Grußformel', "Ich freue mich über Ihre Rückmeldung / Buchung.<br>\nMit freundlichen Grüßen,");

    // Clean up wpautop / HTML paragraph wrappers for clean, uniform spacing inside table cell
    $clean_pdf_text = function ($html) {
        $html = preg_replace('/<div[^>]*>\s*(?:&nbsp;|\x{00a0})?\s*<\/div>/iu', '<br><br>', $html);
        $html = preg_replace('/^\s*<p[^>]*>/iu', '', $html);
        $html = preg_replace('/<\/p>\s*<p[^>]*>/iu', '<br><br>', $html);
        $html = preg_replace('/<\/p>\s*$/iu', '', $html);
        $html = preg_replace('/(?:<br\s*\/?>\s*){3,}/iu', '<br><br>', $html);
        return trim($html);
    };

    $angebot_intro = $clean_pdf_text($angebot_intro);
    $angebot_gruss = $clean_pdf_text($angebot_gruss);

    $salutation_name = trim($course->salutation . ' ' . trim($course->titel . ' ' . $course->vorname . ' ' . $course->nachname));
    $salutation_name = preg_replace('/\s+/', ' ', $salutation_name);

    $default_ort_durchfuehrung = '<table class="text">   
                        <tr>
                            <td style="width:92%; font-size: 10.5pt;"><strong>ORT:</strong> X SIEBEN Wirtschaftstraining, Rochusgasse 6 in 1030 Wien</td>
                        </tr>
                    </table>
                    <div style="font-size:12pt">&nbsp;</div>
                    <table class="text">
                    <tr>
                        <td style="line-height: 16pt; color: #334155;">Durchführung unserer Schulungen: Online Unterricht | vor Ort in unseren Veranstaltungsräumen | Blended Learning<br>
                        <span style="color: #475569; font-size: 9.5pt;">Hinweis: Die Schulung wird bis zur TeilnehmerInnen-Anzahl von drei Personen adäquat verkürzt, wobei alle Inhalte vermittelt werden.</span>
                        </td>
                    </tr>
            </table>';

    // Subsections Mapping per Standard Section
    $subsections_generators = [
        'deckblatt' => [
            'empfaenger' => '<table class="text" style="width: 100%;">
                <tr>
                    <td style="vertical-align:top; width: 55%; font-size: 10pt; line-height: 14pt;"><div style="font-size:24pt">&nbsp;</div>' . $course->format_postal_address('A', true) . '</td>
                    <td style="vertical-align:top; text-align: right; font-size: 9pt; line-height: 14pt; width: 45%;">
                        Angebotsnummer: ' . $angebotsnummer . '<br>Angebotsdatum: ' . $course->current . '<br> Angebot gültig bis: ' . $course->expire . '
                    </td>
                </tr>
            </table>
            <div style="font-size:10pt">&nbsp;</div>',

            'titel' => $course->get_pdf_title($course->titel_short, 'Angebot') . '<div style="font-size:10pt">&nbsp;</div>',

            'anrede_text' => '<table class="text" style="width: 100%;">
                <tr>
                    <td>' . esc_html($salutation_name) . ',<br><br>' .
                        $angebot_intro . '
                    </td>
                </tr>
            </table>
            <div style="font-size:6pt">&nbsp;</div>',

            'gruss' => '<table class="text" style="width: 100%;">
                <tr>
                    <td>' . $angebot_gruss . '</td>
                </tr>
            </table>
            <div style="font-size:6pt">&nbsp;</div>',

            'signatur' => $course->signatur,

            'ps' => (!empty($course->ps) ? ('<div style="font-size:10pt">&nbsp;</div>' . $course->ps) : ''),

            'hinweis_nachstehend' => '<div style="font-size:8pt">&nbsp;</div>
            <div style="font-size: 9pt;"><strong>Nachstehend: </strong>Veranstaltungsinformationen | Anhang 1: Details zu den Inhalten der Veranstaltung | Anhang 2: Exklusive Zusatzleistungen</div>',
        ],

        'veranstaltung' => [
            'titel' => $course->get_pdf_title($course->titel_short, 'Veranstaltungsinformationen') . '<div style="font-size:16pt">&nbsp;</div>',

            'zeitraum' => '<table style="font-size: 11pt;">
                <tr>
                    <td style="width:6%;">' . $course->calender_icon . '</td>
                    <td style="width:94%;"><div style="font-size:3pt">&nbsp;</div> Vom <strong>' . $course->start_datum . '</strong> bis einschließlich<strong> ' . $course->end_datum . '</strong></td>
                </tr>
            </table>' . crm_pdf_divider('#cbd5e1', 6, 8),

            'lehreinheiten' => '<div style="font-size: 11pt;">Diese Veranstaltung beinhaltet <strong>' . $course->anzahl_le . ' Lehreinheiten</strong> (LE, 1 LE = 45min).</div>',

            'module' => $course->module_html . '<div style="font-size:8pt">&nbsp;</div>' . crm_pdf_divider('#cbd5e1', 6, 8),

            'zertifizierungen' => '<table class="text">
                <tr>
                    <td><strong style="color: #0f172a; font-size: 10.5pt;">ZERTIFIZIERUNGSPARTNER ...</strong></td>
                </tr>
            </table>
            <div style="font-size:6pt">&nbsp;</div>'
            . $course->zertifizierungen_images_html
            . crm_pdf_divider('#cbd5e1', 8, 10),

            'ort_durchfuehrung' => $course->get_crm_field_with_default('Angebot - Ort und Durchführung', $default_ort_durchfuehrung),
        ],

        'abschluss' => [
            'titel' => $course->get_pdf_title($course->titel_short) . '<div style="font-size:20pt">&nbsp;</div>',

            'abschluss_box' => '<table class="text">
                <tr>
                    <td style="width:5%;">' . $course->abschluss_icon . '<div style="padding: 2pt;">&nbsp;</div></td>
                    <td style="width:93%;"><strong> IHR PERSÖNLICHER ABSCHLUSS</strong></td>
                </tr>
            </table>'
            . crm_pdf_divider('#cbd5e1', 4, 8) .
            '<table class="text">
                <tr>
                    <td style="text-align:center; font-size:13pt; border-bottom: 1px solid #007C90; padding-bottom: 6px;">
                       ' . $course->abschluss . '
                        <div style="font-size:6pt">&nbsp;</div>
                    </td>
                </tr>
            </table>
            <div style="font-size:12pt">&nbsp;</div>',

            'voraussetzungen' => '<table class="text">
                <tr>
                    <td style="width:5%;">' . $course->danger_icon . '</td>
                    <td style="width:93%;"><strong>Vorausetzungen</strong></td>
                </tr>
                <tr>
                    <td colspan="2">' . $course->voraussetzungen_html . '</td>
                </tr>
            </table>' . crm_pdf_divider('#cbd5e1', 6, 8),

            'beratung' => $course->beratung_email,
        ],

        'kosten' => [
            'titel' => $course->get_pdf_title('Kursgebühr inkl. optionale Zertifizierungen', 'Ihre Investition') . '<div style="font-size:20pt">&nbsp;</div>',

            'preistabelle' => $course->get_gesamt_kosten_html() . '<div style="font-size:40pt">&nbsp;</div>',

            'gueltigkeit' => '<table class="text">
                <tr>
                    <td><strong>ANGEBOT GÜLTIG</strong> bis max. Gruppengrösse erreicht bzw.: <span> ' . $course->expire . '</span></td>
                </tr>
            </table>' . crm_pdf_divider('#cbd5e1', 6, 8),

            'bankverbindung' => $course->bankverbindung,
        ],

        'anmeldung' => [
            'titel' => $course->get_pdf_title($course->title, 'ANMELDUNG') . '<div style="font-size:20pt">&nbsp;</div>',

            'kundendaten' => $course->get_contact_info_html(),

            'agb' => $course->anmeldung_agb,

            'signatur_kunde' => $course->signatur . '<div style="font-size:10pt">&nbsp;</div>',

            'anhang_hinweise' => '<div style="font-size:10pt">
                <strong>Anhang 1: </strong>Details zu den Inhalten der Veranstaltung<br>
                <strong>Anhang 2: </strong>Exklusive Zusatzleistungen<br>
            </div>',
        ],

        'inhalte' => [
            'titel' => $course->get_pdf_title('Details zu den Inhalten', 'Anhang 1'),
            'curriculum' => $course->inhalte,
        ],

        'zusatzleistungen' => [
            'titel' => $course->get_pdf_title('Exklusive Zusatzleistungen', 'Anhang 2') . '<div style="font-size:20pt">&nbsp;</div>',
            'garantien' => $course->garantie,
        ],
    ];

    // --- PDF Document Generation ---
    if (!class_exists('MYPDFA_Angebot')) {
        class MYPDFA_Angebot extends TCPDF
        {
            public $master_config = [];
            public $current_section_config = [];
            public $page_configs = [];
            public $company_info = [];
            public $course_obj = null;
            public $header_content = '';
            public $logo_html = '';
            public $footer_text = '';

            public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false)
            {
                parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
            }

            public function setSectionConfig($config)
            {
                $this->current_section_config = is_array($config) ? $config : [];
            }

            public function resolveHeaderMode($cfg)
            {
                $mode = $cfg['header_mode'] ?? 'master';
                if ($mode === 'master') {
                    $mode = $this->master_config['header_mode'] ?? 'full';
                }

                if ($mode !== 'none' && $mode !== 'custom') {
                    $has_logo = isset($cfg['header_logo']) ? !empty($cfg['header_logo']) : (!empty($this->master_config['header_logo']));
                    $has_addr = isset($cfg['header_address']) ? !empty($cfg['header_address']) : (!empty($this->master_config['header_address']));

                    if (!$has_logo && !$has_addr) {
                        return 'none';
                    }
                    if ($has_logo && !$has_addr) {
                        return 'logo_only';
                    }
                    if (!$has_logo && $has_addr) {
                        return 'address_only';
                    }
                    return 'full';
                }
                return $mode;
            }

            public function resolveFooterMode($cfg)
            {
                $mode = $cfg['footer_mode'] ?? 'master';
                if ($mode === 'master') {
                    $mode = $this->master_config['footer_mode'] ?? 'standard';
                }
                return $mode;
            }

            public function AddPage($orientation='', $format='', $keepmargins=false, $tocpage=false)
            {
                $h_mode = $this->resolveHeaderMode($this->current_section_config);
                if ($h_mode === 'none') {
                    $this->SetTopMargin(18);
                } elseif ($h_mode === 'logo_only') {
                    $this->SetTopMargin(30);
                } elseif ($h_mode === 'address_only') {
                    $this->SetTopMargin(26);
                } else {
                    $this->SetTopMargin(42);
                }

                $f_mode = $this->resolveFooterMode($this->current_section_config);
                if ($f_mode === 'none') {
                    $this->SetAutoPageBreak(TRUE, 15);
                } else {
                    $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                }

                parent::AddPage($orientation, $format, $keepmargins, $tocpage);
                $this->page_configs[$this->page] = $this->current_section_config;
            }

            public function Header()
            {
                if (!isset($this->page_configs[$this->page])) {
                    $this->page_configs[$this->page] = $this->current_section_config;
                }
                $cfg = $this->page_configs[$this->page] ?? $this->current_section_config;
                $mode = $this->resolveHeaderMode($cfg);

                if ($mode === 'none') {
                    return;
                }

                $html = '';
                $y = 12;

                if ($mode === 'custom') {
                    $custom_html = !empty($cfg['header_custom']) ? $cfg['header_custom'] : ($this->master_config['header_custom'] ?? '');
                    $html = function_exists('crm_replace_pdf_placeholders') ? crm_replace_pdf_placeholders($custom_html, $this->course_obj) : $custom_html;
                } elseif ($mode === 'logo_only') {
                    $logo = !empty($this->company_info['xsieben_logo']) ? $this->company_info['xsieben_logo'] : $this->logo_html;
                    $html = '<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
                        <tr>
                            <td style="font-size: 9pt; width: 100%; text-align: left;">' . $logo . '</td>
                        </tr>
                    </table>';
                } elseif ($mode === 'address_only') {
                    $c_name  = !empty($this->company_info['company_name']) ? $this->company_info['company_name'] : 'X SIEBEN Wirtschaftstraining GmbH';
                    $c_addr  = !empty($this->company_info['company_address']) ? $this->company_info['company_address'] : 'Kurzegasse 7, 2493 Lichtenwörth';
                    $c_phone = !empty($this->company_info['company_phone']) ? $this->company_info['company_phone'] : '0800 700 170';
                    $c_email = !empty($this->company_info['company_email']) ? $this->company_info['company_email'] : 'office@x-sieben.at';

                    $html = '<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
                        <tr>
                            <td style="font-size: 8.5pt; width: 100%; text-align: right; line-height: 12pt; color: #475569;">
                                <strong style="color: #0f172a;">' . htmlspecialchars($c_name) . '</strong><br>
                                ' . htmlspecialchars($c_addr) . '<br>
                                Telefon: ' . htmlspecialchars($c_phone) . ' | E-Mail: ' . htmlspecialchars($c_email) . '
                            </td>
                        </tr>
                    </table>';
                } else {
                    // Full default header: Logo left, address right
                    if (!empty($this->header_content)) {
                        $html = $this->header_content;
                    } else {
                        $logo    = !empty($this->company_info['xsieben_logo']) ? $this->company_info['xsieben_logo'] : $this->logo_html;
                        $c_name  = !empty($this->company_info['company_name']) ? $this->company_info['company_name'] : 'X SIEBEN Wirtschaftstraining GmbH';
                        $c_addr  = !empty($this->company_info['company_address']) ? $this->company_info['company_address'] : 'Kurzegasse 7, 2493 Lichtenwörth';
                        $c_phone = !empty($this->company_info['company_phone']) ? $this->company_info['company_phone'] : '0800 700 170';
                        $c_email = !empty($this->company_info['company_email']) ? $this->company_info['company_email'] : 'office@x-sieben.at';

                        $html = '<table cellspacing="0" cellpadding="0" border="0" style="text-align: left; width: 100%;">
                            <tr>
                                <td style="font-size: 9pt; width: 55%; vertical-align: top;">' . $logo . '
                                <div style="font-size:11pt">&nbsp;</div>
                                </td>
                                <td style="font-size: 9pt; width: 45%; text-align: right; line-height: 13pt; color: #334155; vertical-align: top;">
                                    <strong>' . htmlspecialchars($c_name) . '</strong><br>
                                    ' . htmlspecialchars($c_addr) . '<br>
                                    Telefon: ' . htmlspecialchars($c_phone) . '<br>
                                    E-Mail: ' . htmlspecialchars($c_email) . '
                                </td>
                            </tr>
                        </table>';
                    }
                }

                if (!empty($html)) {
                    $this->writeHTMLCell(
                        $w = 0,
                        $h = 0,
                        $x = '14.1',
                        $y = $y,
                        $html,
                        $border = 0,
                        $ln = 1,
                        $fill = 0,
                        $reseth = true,
                        $align = 'top',
                        $autopadding = true
                    );
                }
            }

            public function Footer()
            {
                $cfg = $this->page_configs[$this->page] ?? $this->current_section_config;
                $mode = $this->resolveFooterMode($cfg);

                if ($mode === 'none') {
                    return;
                }

                $this->SetY(-15);
                $this->SetFont('dejavusans', '', 7);
                $this->SetTextColor(100, 116, 139);

                if ($mode === 'custom') {
                    $custom_footer = !empty($cfg['footer_custom']) ? $cfg['footer_custom'] : ($this->master_config['footer_custom'] ?? '');
                    if (function_exists('crm_replace_pdf_placeholders')) {
                        $custom_footer = crm_replace_pdf_placeholders($custom_footer, $this->course_obj);
                    }
                    $custom_footer = str_replace(['{PAGENO}', '{pno}'], $this->getAliasNumPage(), $custom_footer);
                    $custom_footer = str_replace(['{NB}', '{nbpg}'], $this->getAliasNbPages(), $custom_footer);
                    $custom_footer = str_replace('{datum}', date('d.m.Y'), $custom_footer);
                    $this->Cell(0, 10, $custom_footer, 0, false, 'C');
                    return;
                }

                $show_company  = isset($cfg['footer_company']) ? !empty($cfg['footer_company']) : ($this->master_config['footer_company'] ?? true);
                $show_page_num = isset($cfg['footer_page_num']) ? !empty($cfg['footer_page_num']) : ($this->master_config['footer_page_num'] ?? true);
                $show_date     = isset($cfg['footer_date']) ? !empty($cfg['footer_date']) : ($this->master_config['footer_date'] ?? false);

                // Override flags if specific mode chosen
                if ($mode === 'page_numbers_only') {
                    $show_company  = false;
                    $show_page_num = true;
                    $show_date     = false;
                } elseif ($mode === 'company_only') {
                    $show_company  = true;
                    $show_page_num = false;
                    $show_date     = false;
                } elseif ($mode === 'full') {
                    $show_company  = true;
                    $show_page_num = true;
                    $show_date     = true;
                } elseif ($mode === 'standard') {
                    $show_company  = true;
                    $show_page_num = true;
                    $show_date     = false;
                }

                if (!$show_company && !$show_page_num && !$show_date) {
                    return;
                }

                $date_str = date('d.m.Y');

                if ($show_company) {
                    $c_uid   = !empty($this->company_info['company_uid']) ? $this->company_info['company_uid'] : 'ATU76624137';
                    $c_court = !empty($this->company_info['company_court']) ? $this->company_info['company_court'] : 'Landesgericht Wiener Neustadt';
                    $c_fn    = !empty($this->company_info['company_fn']) ? $this->company_info['company_fn'] : 'FN 550277 g';

                    $line1 = "UID: " . $c_uid . " | Firmenbuchgericht: " . $c_court;
                    $line2 = "Firmenbuchnummer: " . $c_fn;

                    if ($show_date) {
                        $line2 .= "  |  Datum: " . $date_str;
                    }
                    if ($show_page_num) {
                        $line2 .= "  |  Seite " . $this->getAliasNumPage() . " von " . $this->getAliasNbPages();
                    }

                    $this->Cell(0, 4, $line1, 0, 1, 'C');
                    $this->Cell(0, 4, $line2, 0, 0, 'C');
                } else {
                    $single_line = '';
                    if ($show_date && $show_page_num) {
                        $single_line = "Datum: " . $date_str . "  |  Seite " . $this->getAliasNumPage() . " von " . $this->getAliasNbPages();
                    } elseif ($show_page_num) {
                        $single_line = "Seite " . $this->getAliasNumPage() . " von " . $this->getAliasNbPages();
                    } elseif ($show_date) {
                        $single_line = "Datum: " . $date_str;
                    }

                    $this->Cell(0, 10, $single_line, 0, false, 'C');
                }
            }

            public function cleanupTempFiles()
            {
                unset(self::$cleaned_ids[$this->file_id]);
                $this->_destroy(true);
            }
        }
    }

    $pdf = new MYPDFA_Angebot(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf_name = "A_" . $nummer . "_" . $safe_title . "_" . $course->vorname . "_" . $course->nachname . ".pdf";

    $header_company_name    = !empty($course->company_name) ? $course->company_name : 'X SIEBEN Wirtschaftstraining GmbH';
    $header_company_address = !empty($course->company_address) ? $course->company_address : 'Kurzegasse 7, 2493 Lichtenwörth';
    $header_company_phone   = !empty($course->company_phone) ? $course->company_phone : '0800 700 170';
    $header_company_email   = !empty($course->company_email) ? $course->company_email : 'office@x-sieben.at';

    $pdf->company_info = [
        'company_name'    => $header_company_name,
        'company_address' => $header_company_address,
        'company_phone'   => $header_company_phone,
        'company_email'   => $header_company_email,
        'company_uid'     => !empty($course->company_uid) ? $course->company_uid : 'ATU76624137',
        'company_court'   => !empty($course->company_court) ? $course->company_court : 'Landesgericht Wiener Neustadt',
        'company_fn'      => !empty($course->company_fn) ? $course->company_fn : 'FN 550277 g',
        'xsieben_logo'    => $course->xsieben_logo,
    ];
    $pdf->master_config = function_exists('crm_get_pdf_master_header_footer') ? crm_get_pdf_master_header_footer() : [];
    $pdf->course_obj    = $course;

    $header_html_content = '<table cellspacing="0" cellpadding="0" border="0" style="text-align: left;">
        <tr>
            <td style="font-size: 9pt; width:60%;">' . $course->xsieben_logo . '
            <div style="font-size:11pt">&nbsp;</div>
            </td>
            <td style="font-size: 9pt; width:40%; text-align: right">
                ' . htmlspecialchars($header_company_name) . ' <br>
                ' . htmlspecialchars($header_company_address) . ' <br>
                Telefon: ' . htmlspecialchars($header_company_phone) . ' <br>
                E-Mail: ' . htmlspecialchars($header_company_email) . '
            </td>
        </tr>
    </table>';

    $pdf->header_content = $header_html_content;
    $pdf->logo_html       = $course->xsieben_logo;
    $pdf->footer_text     = !empty($course->company_uid)
        ? ("UID: " . $course->company_uid . " | Firmenbuchgericht: " . $course->company_court . "\nFirmenbuchnummer: " . $course->company_fn)
        : "UID: ATU76624137 | Firmenbuchgericht: Landesgericht Wiener Neustadt\nFirmenbuchnummer: FN 550277 g";

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Angebot' . $angebotsnummer);
    $pdf->SetSubject('Angebot ' . $angebotsnummer);

    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, 42, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER - 1);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetCellPadding(0);

    // Holen der hierarchischen Abschnitte (inkl. Subsections & Custom-Sections)
    $all_sections = crm_get_pdf_section_order('angebot', $entry_id);

    // Filter falls $custom_sections übergeben wurde
    if (is_array($custom_sections) && !empty($custom_sections)) {
        $allowed_keys = is_string(reset($custom_sections)) ? $custom_sections : array_column($custom_sections, 'key');
        $filtered = [];
        foreach ($all_sections as $sec) {
            if (in_array($sec['key'], $allowed_keys, true)) {
                $filtered[] = $sec;
            }
        }
        $all_sections = $filtered;
    }

    foreach ($all_sections as $sec) {
        if (empty($sec['enabled'])) {
            continue;
        }

        $sec_key   = $sec['key'];
        $is_custom = !empty($sec['is_custom']);
        $sec_html  = '';

        if ($is_custom) {
            // Benutzerdefinierte Seite
            $badge = !empty($sec['badge']) ? $sec['badge'] : 'Zusatz';
            $sec_html .= $course->get_pdf_title($sec['title'], $badge) . '<div style="font-size:14pt">&nbsp;</div>';
            if (!empty($sec['content'])) {
                $sec_html .= '<div style="font-size:10pt; line-height:1.6;">' . crm_replace_pdf_placeholders($sec['content'], $course) . '</div>';
            }
            if (!empty($sec['subsections'])) {
                foreach ($sec['subsections'] as $sub) {
                    if (!empty($sub['enabled']) && !empty($sub['content'])) {
                        $sec_html .= '<div style="font-size:10pt; line-height:1.6; margin-top:8px;">' . crm_replace_pdf_placeholders($sub['content'], $course) . '</div>';
                    }
                }
            }
        } else {
            // Vordefinierte Standard-Seite: Unterabschnitte der Reihe nach zusammensetzen
            if (!empty($sec['subsections'])) {
                foreach ($sec['subsections'] as $sub) {
                    if (empty($sub['enabled'])) {
                        continue;
                    }
                    $sub_key = $sub['key'];
                    if (!empty($sub['is_custom'])) {
                        // Benutzerdefinierter Unterabschnitt
                        if (!empty($sub['title'])) {
                            $sec_html .= '<div style="font-size:11pt; font-weight:bold; margin-top:10px; margin-bottom:4px; color:#0f172a;">' . esc_html($sub['title']) . '</div>';
                        }
                        if (!empty($sub['content'])) {
                            $sec_html .= '<div style="font-size:10pt; line-height:1.6;">' . crm_replace_pdf_placeholders($sub['content'], $course) . '</div>';
                        }
                    } elseif (isset($subsections_generators[$sec_key][$sub_key])) {
                        $default_sub_html = $subsections_generators[$sec_key][$sub_key];
                        if (!empty($sub['content'])) {
                            if (strpos($sub['content'], '{standard}') !== false) {
                                $custom_sub_html = str_replace('{standard}', $default_sub_html, $sub['content']);
                            } else {
                                $custom_sub_html = $sub['content'];
                            }
                            $sec_html .= crm_replace_pdf_placeholders($custom_sub_html, $course);
                        } else {
                            $sec_html .= $default_sub_html;
                        }
                    }
                }
            } else {
                // Fallback: Alle Generatoren des Abschnitts
                if (isset($subsections_generators[$sec_key])) {
                    $sec_html = implode('', $subsections_generators[$sec_key]);
                }
            }
        }

        if (!empty(trim(strip_tags($sec_html, '<img>')))) {
            $pdf->setSectionConfig($sec);
            $pdf->AddPage();
            $pdf->writeHTML($styles . $sec_html, true, false, true, false, '');
        }
    }

    $pdf->Output(get_template_directory() . '/angebote/' . $pdf_name, 'F');
    $pdf->cleanupTempFiles();
    $pdf_url = get_template_directory_uri() . '/angebote/' . $pdf_name;
    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'xsieben_angebot');
    }
    else {
        return $pdf_url;
    }
}
