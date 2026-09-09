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

    $pdfAuthor = 'X- Sieben Wirtschaftstraining GmbH';

    // Sauberen Dateinamen erzeugen (WordPress Funktion nutzt)
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
            <td style="padding-top: 15px; padding-bottom: 15px;">
                <table cellspacing="0" cellpadding="0" style="width: 100%; text-align: left;">
                    <tr>
                        <td style="width: 48%; vertical-align: top; font-size: 10pt; line-height: 1.4;">' . (!empty($clean_links) ? $clean_links : '&nbsp;') . '</td>
                        <td style="width: 4%;"></td>
                        <td style="width: 48%; vertical-align: top; font-size: 10pt; line-height: 1.4;">' . (!empty($clean_rechts) ? $clean_rechts : '&nbsp;') . '</td>
                    </tr>
                </table>
            </td>
        </tr>';
    }

    $success = $course->get_diplom_success();
    $success_text = !empty($success) ? 'mit ' . esc_html($success) . ' ' : '';

    // HTML-Inhalt des Diploms
    $html = '<table cellspacing="0" style="width: 100%; text-align: center;">
        <tr>
            <td>
                <h1 style="font-size:22pt; font-weight: bold; line-height: 1.8;">Diplom</h1>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:18pt; line-height: 3;text-transform: uppercase;">' . esc_html($course->anrede) . ' ' . esc_html($course->vorname) . ' ' . esc_html($course->nachname) . '</span>
                <br>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:12pt; line-height: 3;text-transform: uppercase;">Hat den Lehrgang</span>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:18pt;line-height: 3; text-transform: uppercase;">"' . trim($course->titel_short) . '"</span>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:12pt;line-height: 3; text-transform: uppercase;">' . intval($course->anzahl_le) . ' Lehreinheiten à 45 Minuten</span>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:12pt; line-height: 3; text-transform: uppercase;">Im Zeitraum vom ' . esc_html($course->start_datum) . ' bis zum ' . esc_html($course->end_datum) . '</span>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:12pt; line-height: 3; text-transform: uppercase;">' . $success_text . 'abgeschlossen</span>
            </td>
        </tr>' . $diplom_zusatz_html . '
         <tr>
            <td>
            <div style="font-size:18pt; font-weight: bold; line-height: 1.8;"> </div>
            Datum: ' . date('d.m.Y') . '
            </td>
        </tr>
        <tr>
            <td>Unterschrift:</td>
        </tr>
    </table>
       
';

    // --- PDF Document Generation ---
    if (!class_exists('MYPDFA_diplom')) {
        class MYPDFA_diplom extends TCPDF
        {
            public $header_content = '';
            public $logo_html = '';

            public function Header()
            {
                $this->SetY(15);
                $this->writeHTMLCell(0, 0, 10, 10, ($this->getPage() == 1 ? $this->header_content : $this->logo_html), 0, 1, 0, true, 'L', true);
            }

            public function Footer()
            {
                $this->SetY(-12);
                if ($this->getPage() == 1) {
                    $this->SetFont('dejavusans', '', 8);
                    $this->MultiCell(
                        0,
                        5,
                        "UID: ATU76624137 | Firmenbuchgericht: Landesgericht Wiener Neustadt\n" .
                            "Firmenbuchnummer: FN 550277 g",
                        0,
                        'C'
                    );
                } else {
                    $this->SetFont('dejavusans', 'I', 8);
                    $this->Cell(0, 10, 'Seite ' . $this->getAliasNumPage() . ' von ' . $this->getAliasNbPages(), 0, false, 'C');
                }
            }
        }
    }

    $pdf = new MYPDFA_diplom(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $header_html_content = '<table cellspacing="0" cellpadding="0" border="0" style="text-align: left;">
        <tr>
            <td>' . $course->xsieben_logo . '</td>
        </tr>
    </table>';

    $pdf->header_content = $header_html_content;
    $pdf->logo_html = $course->xsieben_logo;

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Diplom ' . esc_html($course->nummer));
    $pdf->SetSubject('Diplom ' . esc_html($course->nummer));

    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetMargins(PDF_MARGIN_LEFT, 42, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER - 1);

    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetCellPadding(0);

    $pdf->AddPage();
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
