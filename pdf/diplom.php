<?php
if (!function_exists('crm_clean_diplom_html')) {
    /**
     * Bereinigt WYSIWYG-Texte für das Diplom-PDF von unsichtbaren TinyMCE-/Screenreader-Tags
     * und filtert unberührte ACF-Standardplatzhalter ("Text links" / "Text rechts") heraus.
     */
    function crm_clean_diplom_html($content)
    {
        if (empty($content)) {
            return '';
        }
        // Entferne versteckte Screenreader/TinyMCE-Container (z. B. clip-path Divs)
        $content = preg_replace('/<div[^>]*style="[^"]*clip[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<div[^>]*role="(status|alert)"[^>]*>.*?<\/div>/is', '', $content);
        // Prüfen, ob nach Bereinigung relevanter Text vorhanden ist
        $text_only = trim(str_replace(['&nbsp;', '&#160;'], '', strip_tags($content)));
        if (empty($text_only)) {
            return '';
        }
        // Filtert unberührte ACF-Default-Platzhalter wie "Text rechts", "Text links", "Text-links"
        $normalized = mb_strtolower(trim(str_replace(['-', ' ', '.'], '', $text_only)));
        if ($normalized === 'textrechts' || $normalized === 'textlinks') {
            return '';
        }
        return trim($content);
    }
}

function xsieben_diplom_pdf($entry_id, $course_id, $output_to_browser = true)
{
    // Lade Kurs- und Adressdaten
    $course = new CRM_Model($course_id, $entry_id);

    $pdfAuthor = 'X-Sieben Wirtschaftstraining GmbH';

    // Sauberen Dateinamen erzeugen
    $safe_title    = sanitize_file_name($course->titel_short);
    $safe_vorname  = sanitize_file_name($course->vorname);
    $safe_nachname = sanitize_file_name($course->nachname);
    $pdf_name      = 'Diplom_' . $safe_title . '_' . $safe_vorname . '_' . $safe_nachname . '.pdf';

    // Zusätzliche Diplomtexte (z. B. Lehrinhalte / Schwerpunkte in zwei Spalten)
    $clean_links  = crm_clean_diplom_html($course->texte_fur_diplom_links ?? '');
    $clean_rechts = crm_clean_diplom_html($course->texte_fur_diplom_rechts ?? '');

    $diplom_zusatz_html = '';
    if (!empty($clean_links) || !empty($clean_rechts)) {
        $diplom_zusatz_html = '
        <tr>
            <td style="padding-top: 8px; padding-bottom: 8px;">
                <table cellspacing="0" cellpadding="0" style="width: 100%; text-align: left;">
                    <tr>
                        <td style="width: 48%; vertical-align: top; font-size: 8.5pt; line-height: 1.3;">' . (!empty($clean_links) ? $clean_links : '&nbsp;') . '</td>
                        <td style="width: 4%;"></td>
                        <td style="width: 48%; vertical-align: top; font-size: 8.5pt; line-height: 1.3;">' . (!empty($clean_rechts) ? $clean_rechts : '&nbsp;') . '</td>
                    </tr>
                </table>
            </td>
        </tr>';
    }

    // Helper zur sauberen Extraktion reiner Textwerte aus CRM-Feldern (verhindert TCPDF &nbsp;/HTML-Bugs)
    $clean_field = function ($val) {
        $val = strip_tags($val);
        $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $val = str_replace(["\xc2\xa0", '&nbsp;', "\n", "\r"], ' ', $val);
        return trim(preg_replace('/\s+/', ' ', $val));
    };

    $success = $course->get_diplom_success();
    $success_text = !empty($success) ? 'mit ' . esc_html($success) . ' ' : '';

    // Dynamische Texte aus dem CRM Model (PDF Editor) mit Default-Fallback
    $default_footer    = !empty($course->company_uid) ? ("UID: " . $course->company_uid . " | Firmenbuchgericht: " . $course->company_court . "\nFirmenbuchnummer: " . $course->company_fn) : "UID: ATU76624137 | Firmenbuchgericht: Landesgericht Wiener Neustadt\nFirmenbuchnummer: FN 550277 g";
    $diplom_title      = $clean_field($course->get_crm_field_with_default('Diplom - Titel', 'Diplom'));
    $diplom_lehrgang   = $clean_field($course->get_crm_field_with_default('Diplom - Lehrgang Text', 'hat den Lehrgang'));
    $diplom_einheiten  = $clean_field($course->get_crm_field_with_default('Diplom - Einheiten Text', intval($course->anzahl_le) . ' Lehreinheiten à 45 Minuten'));
    $diplom_zeitraum   = $clean_field($course->get_crm_field_with_default('Diplom - Zeitraum Text', 'im Zeitraum vom ' . esc_html($course->start_datum) . ' bis zum ' . esc_html($course->end_datum)));
    $diplom_abschluss  = $clean_field($course->get_crm_field_with_default('Diplom - Abschluss Text', $success_text . 'abgeschlossen'));
    $diplom_footer_txt = $course->get_crm_field_with_default('Diplom - Footer', $default_footer);

    // Bereinigung des Fußzeilentextes von HTML-Tags und Formatierung als saubere Einzeile
    $diplom_footer_clean = trim(strip_tags(str_replace(["\r\n", "\r", "\n", '<br>', '<br/>', '<br />', '</p>'], ' | ', $diplom_footer_txt)));
    $diplom_footer_clean = preg_replace('/\s*\|\s*\|\s*/', ' | ', $diplom_footer_clean);
    $diplom_footer_clean = trim(preg_replace('/\s+/', ' ', $diplom_footer_clean), " |");

    // Vollständiger Name des Teilnehmers
    $name_parts = array_filter([$course->anrede, $course->titel, $course->vorname, $course->nachname]);
    $full_name  = implode(' ', $name_parts);

    // HTML-Inhalt des Diploms im Querformat
    $html = '
    <table cellspacing="0" cellpadding="0" style="width: 100%; text-align: center;">
        <tr>
            <td>
                <div style="font-size: 28pt; font-weight: bold; color: #007C90; letter-spacing: 4px;">' . esc_html(mb_strtoupper($diplom_title, 'UTF-8')) . '</div>
                <div style="font-size: 6pt;">&nbsp;</div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="font-size: 9pt; color: #64748b; letter-spacing: 3px;">VERLEIHUNG AN</div>
                <div style="font-size: 4pt;">&nbsp;</div>
                <div style="font-size: 20pt; font-weight: bold; color: #0f172a; letter-spacing: 1px;">' . esc_html(mb_strtoupper($full_name, 'UTF-8')) . '</div>
                <div style="font-size: 6pt;">&nbsp;</div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="font-size: 9.5pt; color: #475569; letter-spacing: 2px;">' . esc_html(mb_strtoupper($diplom_lehrgang, 'UTF-8')) . '</div>
                <div style="font-size: 4pt;">&nbsp;</div>
                <div style="font-size: 16pt; font-weight: bold; color: #007C90;">„' . esc_html(mb_strtoupper(trim($course->titel_short), 'UTF-8')) . '“</div>
                <div style="font-size: 6pt;">&nbsp;</div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="font-size: 10pt; color: #334155; line-height: 1.5;">
                    ' . esc_html($diplom_einheiten) . ' &nbsp;|&nbsp; ' . esc_html($diplom_zeitraum) . '
                </div>
                <div style="font-size: 12pt; font-weight: bold; color: #0f172a; letter-spacing: 1.5px;">
                    ' . esc_html(mb_strtoupper($diplom_abschluss, 'UTF-8')) . '
                </div>
            </td>
        </tr>' . $diplom_zusatz_html . '
        <tr>
            <td style="padding-top: 10px;">
                <table cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr>
                        <td style="width: 38%; text-align: left; vertical-align: bottom;">
                            <span style="font-size: 9.5pt; color: #334155;">Wien, am ' . date('d.m.Y') . '</span>
                        </td>
                        <td style="width: 24%;"></td>
                        <td style="width: 38%; text-align: center; vertical-align: bottom;">
                            <div>' . $course->signatur_icon . '</div>
                            <div style="border-top: 1px solid #94a3b8; width: 100%; margin-top: 2px; padding-top: 3px;">
                                <span style="font-size: 9.5pt; font-weight: bold; color: #0f172a;">Mag. Dr. Johannes Gasberger</span><br>
                                <span style="font-size: 8pt; color: #64748b;">Geschäftsführer | X SIEBEN Wirtschaftstraining GmbH</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';

    // --- PDF Document Generation (Querformat / Landscape) ---
    if (!class_exists('MYPDFA_diplom')) {
        class MYPDFA_diplom extends TCPDF
        {
            public $header_content = '';
            public $logo_html = '';
            public $footer_text = '';

            public function Header()
            {
                // Zertifikats-Doppelrahmen in X-SIEBEN CI
                // Äußerer Rahmen in Petrol
                $this->SetLineStyle(array('width' => 0.6, 'color' => array(0, 124, 144)));
                $this->Rect(8, 8, 281, 194);
                // Innerer Akzentrahmen in dezentem Hellgrau
                $this->SetLineStyle(array('width' => 0.25, 'color' => array(203, 213, 225)));
                $this->Rect(10.5, 10.5, 276, 189);

                $this->writeHTMLCell(0, 0, 20, 11.5, $this->header_content, 0, 1, 0, true, 'L', true);
            }

            public function Footer()
            {
                $this->SetY(-14.5);
                $this->SetFont('dejavusans', '', 7.5);
                $this->SetTextColor(100, 116, 139);
                $this->Cell(
                    0,
                    4,
                    $this->footer_text,
                    0,
                    0,
                    'C'
                );
            }
        }
    }

    $pdf = new MYPDFA_diplom('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

    $header_html_content = '<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
        <tr>
            <td style="width: 50%; text-align: left;">' . $course->xsieben_logo . '</td>
            <td style="width: 50%; text-align: right; vertical-align: middle;">
                <span style="font-size: 8.5pt; color: #64748b; font-weight: bold; letter-spacing: 1px;">PERSONENZERTIFIZIERUNG & WIRTSCHAFTSTRAINING</span>
            </td>
        </tr>
    </table>';

    $pdf->header_content = $header_html_content;
    $pdf->logo_html = $course->xsieben_logo;
    $pdf->footer_text = $diplom_footer_clean;

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Diplom ' . esc_html($course->nummer));
    $pdf->SetSubject('Diplom ' . esc_html($course->nummer));

    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetMargins(20, 28, 20);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(12);

    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetCellPadding(0);

    $pdf->AddPage('L', 'A4');
    $pdf->writeHTML($html, true, false, true, false, '');

    // Sicherstellen, dass der Zielordner existiert
    $save_dir = get_template_directory() . '/angebote/';
    if (!file_exists($save_dir)) {
        wp_mkdir_p($save_dir);
    }

    $save_path = $save_dir . $pdf_name;
    $pdf->Output($save_path, 'F');

    $pdf_url = get_template_directory_uri() . '/angebote/' . rawurlencode($pdf_name);

    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'xsieben_diplom');
    } else {
        return $pdf_url;
    }
}
