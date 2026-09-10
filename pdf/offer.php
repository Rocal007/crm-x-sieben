<?php
function xsieben_offer_pdf($entry_id, $course_id, $output_to_browser=true)
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

    // --- HTML Part 1: Front Page & General Info ---
    $html_part1 = $styles;
    $html_part1 .= '<table>
                <tr>
                    <td>
                        ' . $course->anrede . '<br>
                        ' . $course->titel . ' ' . $course->vorname . ' ' . $course->nachname . '<br>
                        ' . $course->street . '<br>
                        ' . $course->zip_code  . ' ' . $course->city . '<br>
                    </td>
                    <td style="vertical-align:top; text-align: right; font-size: 9pt;">
                        Angebotsnummer: ' . $angebotsnummer . '<br>Angebotsdatum: ' . $course->current . '<br> Angebot gültig bis: ' . $course->expire . '
                    </td>
                </tr>
            </table>';
    $html_part1 .= '<div style="font-size:6pt">&nbsp;</div>';
    $html_part1 .= $course->get_pdf_title($course->titel_short, 'Angebot');
    $html_part1 .= '<div style="font-size:6pt">&nbsp;</div>';
    // Dynamische Texte aus dem CRM Model (PDF Editor) mit Default-Fallback
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

    $html_part1 .= '<table class="text" style="width: 100%;">
                <tr>
                    <td>' . esc_html($salutation_name) . ',<br><br>' .
                        $angebot_intro . '<br><br>' .
                        $angebot_gruss . '
                    </td>
                </tr>
            </table>';
    $html_part1 .= '<div style="font-size:6pt">&nbsp;</div>';
    $html_part1 .= $course->signatur;
    $html_part1 .= '<div style="font-size:10pt">&nbsp;</div>';
    $html_part1 .= $course->ps;
    $html_part1 .= '<div style="font-size:8pt">&nbsp;</div>';
    $html_part1 .= '
    <div style="font-size: 9pt;"><strong>Nachstehend: </strong>Veranstaltungsinformationen | Anhang 1: Details zu den Inhalten der Veranstaltung | Anhang 2: Exklusive Zusatzleistungen</div>';
    $html_part1 .= '<tcpdf method="AddPage" />';
    $html_part1 .= $course->get_pdf_title($course->titel_short, 'Veranstaltungsinformationen');
    $html_part1 .= '<div style="font-size:16pt">&nbsp;</div>';
    $html_part1 .= '<table style="font-size: 11pt;">
            <tr>
                <td style="width:6%;">' . $course->calender_icon . '</td>
                <td style="width:94%;"><div style="font-size:3pt">&nbsp;</div> Vom <strong>' . $course->start_datum . '</strong> bis einschließlich<strong> ' . $course->end_datum . '</strong></td>
            </tr>
        </table>';
    $html_part1 .= crm_pdf_divider('#cbd5e1', 6, 8);
    $html_part1 .= '<div style="font-size: 11pt;">Diese Veranstaltung beinhaltet <strong>' . $course->anzahl_le . ' Lehreinheiten</strong> (LE, 1 LE = 45min).</div>';
    $html_part1 .= $course->module_html;
    $html_part1 .= '<div style="font-size:8pt">&nbsp;</div>';
    $html_part1 .= crm_pdf_divider('#cbd5e1', 6, 8);
    $html_part1 .= '
        <table class="text">
                    <tr>
                        <td><strong style="color: #0f172a; font-size: 10.5pt;">ZERTIFIZIERUNGSPARTNER ...</strong></td>
                    </tr>
        </table>';
    $html_part1 .= '<div style="font-size:6pt">&nbsp;</div>';
    $html_part1 .= $course->zertifizierungen_images_html;
    $html_part1 .= crm_pdf_divider('#cbd5e1', 8, 10);

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
    $html_part1 .= $course->get_crm_field_with_default('Angebot - Ort und Durchführung', $default_ort_durchfuehrung);
    $html_abschluss = $styles;
    $html_abschluss .= $course->get_pdf_title($course->titel_short);
    $html_abschluss .= '<div style="font-size:20pt">&nbsp;</div>';
    $html_abschluss .= '<table class="text">
            <tr>
                <td style="width:5%;">' . $course->abschluss_icon . '<div style="padding: 2pt;">&nbsp;</div></td>
                <td style="width:93%;"><strong> IHR PERSÖNLICHER ABSCHLUSS</strong></td>
            </tr>
        </table>';
    $html_abschluss .= crm_pdf_divider('#cbd5e1', 4, 8);
    $html_abschluss .= '<table class="text">
            <tr>
                <td style="text-align:center; font-size:13pt; border-bottom: 1px solid #007C90; padding-bottom: 6px;">
                   ' . $course->abschluss . '
                    <div style="font-size:6pt">&nbsp;</div>
                </td>
            </tr>
        </table>';
    $html_abschluss .= '<div style="font-size:12pt">&nbsp;</div>';
    $html_abschluss .= <<<HTML
                        <table class="text">
                            <tr>
                                <td style="width:5%;">{$course->danger_icon}
                                    
                                </td>
                                <td style="width:93%;">
                                    <strong>Vorausetzungen</strong>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">{$course->voraussetzungen_html}</td>
                            </tr>
                        </table>
                    HTML;
    $html_abschluss .= crm_pdf_divider('#cbd5e1', 6, 8);
    $html_abschluss .= $course->beratung_email;

    // $html_kosten .= $course->get_gesamt_kosten_html();
    $html_kosten = $styles;
    $html_kosten .= $course->get_pdf_title('Kursgebühr inkl. optionale Zertifizierungen', 'Ihre Investition');;
    $html_kosten .= '<div style="font-size:20pt">&nbsp;</div>';
    $html_kosten .= $course->get_gesamt_kosten_html();
    $html_kosten .= '<div style="font-size:40pt">&nbsp;</div>';
    $html_kosten .= '
        <table class="text">
            <tr>
                <td><strong>ANGEBOT GÜLTIG</strong> bis max. Gruppengrösse erreicht bzw.: <span> ' . $course->expire . '</span></td>
            </tr>
        </table>';
    $html_kosten .= crm_pdf_divider('#cbd5e1', 6, 8);
    $html_kosten .= $course->bankverbindung;


    // // --- HTML Part 2: Certifications Page ---
    // $html_certifications = $styles;
    // $html_certifications .= $course->get_pdf_title ($course->title);
    // $html_certifications .= '<div style="font-size:10pt">&nbsp;</div>';
    // $html_certifications .= $course->form_certifications;


    // --- HTML Part 3: Anmeldung & Appendices ---
    $html_anmeldung = $styles;
    $html_anmeldung .= $course->get_pdf_title($course->title, 'ANMELDUNG');;
    $html_anmeldung .= '<div style="font-size:20pt">&nbsp;</div>';
    $html_anmeldung .= $course->get_contact_info_html();
    $html_anmeldung .= $course->anmeldung_agb;
    $html_anmeldung .= $course->signatur;
    $html_anmeldung .= '<div style="font-size:10pt">&nbsp;</div>';
    $html_anmeldung .= '<div style="font-size:10pt">
                      <strong>Anhang 1: </strong>Details zu den Inhalten der Veranstaltung<br>
                      <strong>Anhang 2: </strong>Exklusive Zusatzleistungen<br>
                   </div>';
    $html_anmeldung .= '<tcpdf method="AddPage" />';
    $html_anmeldung .= $course->get_pdf_title('Details zu den Inhalten', 'Anhang 1',);
    $html_anmeldung .= $course->inhalte;
    $html_anmeldung .= '<tcpdf method="AddPage" />';
    $html_anmeldung .= $course->get_pdf_title('Exklusive Zusatzleistungen', 'Anhang 2',);
    $html_anmeldung .= '<div style="font-size:20pt">&nbsp;</div>';
    $html_anmeldung .= $course->garantie;


    // --- PDF Document Generation ---
    if (!class_exists('MYPDFA_Angebot')) {
        class MYPDFA_Angebot extends TCPDF
        {
            public $header_content = '';
            public $logo_html = '';
            public $footer_text = '';

            public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false)
            {
                parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
                // Schützt temporäre Cache-Dateien (z. B. Alpha-Masken von PNG-Logos) vor vorzeitiger Löschung durch TCPDF-Tabellen-Transaktionskopien
                self::$cleaned_ids[$this->file_id] = true;
            }

            public function Header()
            {
                if ($this->getPage() == 1) {
                    $this->writeHTMLCell(0, 0, 15, 6, $this->header_content, 0, 1, 0, true, 'L', true);
                } else {
                    $this->writeHTMLCell(0, 0, 15, 6, $this->logo_html, 0, 1, 0, true, 'L', true);
                }
            }
            public function Footer()
            {
                $this->SetY(-15);
                if ($this->getPage() == 1) {
                    $this->SetFont('dejavusans', '', 8);
                    $footer_txt = !empty($this->footer_text) ? $this->footer_text : ("UID: ATU76624137 | Firmenbuchgericht: Landesgericht Wiener Neustadt\nFirmenbuchnummer: FN 550277 g");
                    $this->MultiCell(
                        0,
                        4,
                        $footer_txt,
                        0,
                        'C'
                    );
                } else {
                    $this->SetFont('dejavusans', 'I', 8);
                    $this->Cell(
                        0,
                        10,
                        'Seite ' . $this->getAliasNumPage() . ' von ' . $this->getAliasNbPages(),
                        0,
                        false,
                        'C'
                    );
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

    // Write the first part of the HTML
    $pdf->AddPage();
    $pdf->writeHTML($html_part1, true, false, true, false, '');
    
    // Write the Abschluss part   
    $pdf->AddPage();
    $pdf->writeHTML($html_abschluss, true, false, true, false, '');

    //Write the Kosten
    $pdf->AddPage();
    $pdf->writeHTML($html_kosten, true, false, true, false, '');

    // Write the Anmeldung & Appendices
    $pdf->AddPage();
    $pdf->writeHTML($html_anmeldung, true, false, true, false, '');

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
