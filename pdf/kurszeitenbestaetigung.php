<?php
function xsieben_kurszeitenbestaetigung_pdf($entry_id, $course_id, $output_to_browser = true)
{
    // Model laden
    $course = new CRM_Model($course_id, $entry_id);

    $clean_text = function ($val) {
        $decoded = html_entity_decode($val ?? '', ENT_QUOTES, 'UTF-8');
        return trim(strip_tags($decoded));
    };

    $clean_inline_html = function ($val) {
        $val = preg_replace('/^\s*<p[^>]*>/iu', '', $val ?? '');
        $val = preg_replace('/<\/p>\s*$/iu', '', $val);
        return trim($val);
    };

    // Variablen aus dem Model
    $file_title       = preg_replace('/[^a-zA-Z0-9_\-äöüÄÖÜß]/u', '-', $course->titel_short);
    $title            = $clean_text($course->title);
    $startdatum       = $course->start_datum;
    $enddatum         = $course->end_datum;
    $vorname          = $course->vorname;
    $nachname         = $course->nachname;
    $svr              = $course->svr;
    $kursart_t        = $course->kursart_t;
    $kursart_a        = $course->kursart_a;
    $kursart_we       = $course->kursart_we;
    $kurszeiten       = $course->kurszeiten;    // now a key-value array
    $selbststudium    = $course->selbststudium;  // now a key-value array
    $kurszeiten_datum = date('d.m.Y');

    // Dateiname
    $pdfName = "Kurszeitenbestaetigung_" . $vorname . "_" . $nachname . "_" . $file_title . ".pdf";

    $table_style = 'style="border: 1px solid black;"';

    // Dynamische Texte aus dem CRM Model (PDF Editor) mit Default-Fallback
    $default_kb_institut = !empty($course->company_name) ? $course->company_name : 'X SIEBEN Wirtschaftstraining GmbH';
    $default_kb_ort      = !empty($course->location_wien) ? ($course->location_wien . ' bzw. online') : 'Rochusgasse 6, 1030 Wien bzw. online';

    $kb_title        = $clean_text($course->get_crm_field_with_default('KB - Titel', 'Bestätigung Kurszeiten'));
    $kb_institut     = $clean_text($course->get_crm_field_with_default('KB - Kursinstitut Name', $default_kb_institut));
    $kb_ort          = $clean_text($course->get_crm_field_with_default('KB - Schulungsort', $default_kb_ort));
    $kb_hinweis      = $clean_text($course->get_crm_field_with_default('KB - Hinweistext', 'Bei unregelmäßigen Kurszeiten ist ein Ablaufplan der einzelnen Kurswochen beizulegen.'));
    $kb_sig_institut = $clean_inline_html($course->get_crm_field_with_default('KB - Signatur Institut', 'Wien, ' . $kurszeiten_datum . '<br>Unterschrift, Stampiglie Kursinstitut'));
    $kb_sig_kunde    = $clean_inline_html($course->get_crm_field_with_default('KB - Signatur Kunde', 'Ort, Datum, Unterschrift, Kunde/Kundin'));

    // HTML Aufbau
    $html = '
<div style="font-size:14pt">&nbsp;</div>
<table cellspacing="0" cellpadding="0" style="width: 100%;">
    <tr>
        <td style="font-size:16pt; font-weight: bold; text-align: center;">
            <span>' . htmlspecialchars($kb_title) . '</span>
        </td>
    </tr>
</table>
<div style="font-size:18pt">&nbsp;</div>';

    // Kursinstitut
    $html .= '
<table cellspacing="0" cellpadding="2" style="width: 100%;">
    <tr>
        <td style="font-size:11pt; padding-bottom: 3px;">
            <span style="text-decoration: underline;"><strong>Kursinstitut</strong></span>
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1.5px solid black; font-size:10pt; line-height: 1.75;">
            Name des Kursinstituts: ' . htmlspecialchars($kb_institut) . '
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1.5px solid black; font-size:10pt; line-height: 1.75;">
            Schulungsort (Adresse): ' . htmlspecialchars($kb_ort) . '
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1.5px solid black; font-size:10pt; line-height: 1.75;">
            Kursbezeichnung: ' . htmlspecialchars($title) . '
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1.5px solid black; font-size:10pt; line-height: 1.75;">
            Kurs von-bis: ' . htmlspecialchars($startdatum) . ' bis ' . htmlspecialchars($enddatum) . '
        </td>
    </tr>
</table>
<div style="font-size:20pt">&nbsp;</div>';

    // KursteilnehmerIn
    $html .= '
<table cellspacing="0" cellpadding="2" style="width: 100%;">
    <tr>
        <td style="font-size:11pt; padding-bottom: 3px;">
            <span style="text-decoration: underline;"><strong>KursteilnehmerIn:</strong></span>
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1.5px solid black; font-size:10pt; line-height: 1.75;">
            Name: ' . htmlspecialchars($vorname . ' ' . $nachname) . '
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1.5px solid black; font-size:10pt; line-height: 1.75;">
            SV-Nummer: ' . (!empty($svr) ? htmlspecialchars((string)$svr) : '&nbsp;') . '
        </td>
    </tr>
</table>
<div style="font-size:20pt">&nbsp;</div>';

    // Kurstyp
    $html .= '
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 2px solid black; font-size: 10pt;">
    <tr>
        <td style="width: 34%;">Kurstyp: Tageskurs ' . $kursart_t . '</td>
        <td style="width: 33%;">Abendkurs ' . $kursart_a . '</td>
        <td style="width: 33%;">Wochenendkurs ' . $kursart_we . '</td>
    </tr>
</table>
<div style="font-size:20pt">&nbsp;</div>';

    /* ==========
        Dynamische Tabelle Kurszeiten
       ========== */
    $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

    $html .= '<table cellspacing="0" cellpadding="5" style="width: 100%; border: 2px solid black; font-size: 9.5pt;">
        <tr style="background-color: #f1f5f9;">
            <td ' . $table_style . ' style="width: 20%;"><strong>Kurstage</strong></td>
            <td ' . $table_style . ' align="center" style="width: 26%;"><strong>Kurszeit (von - bis)</strong><br><span style="font-size: 8pt; color: #475569;">exkl. Mittagspause</span></td>
            <td ' . $table_style . ' align="center" style="width: 27%;"><strong>Tele-/Selbstlernzeit</strong><br><span style="font-size: 8pt; color: #475569;">bei und unter Aufsicht</span></td>
            <td ' . $table_style . ' align="center" style="width: 27%;"><strong>Tele-/Selbstlernzeit</strong><br><span style="font-size: 8pt; color: #475569;">außerhalb des Kursinstitutes</span></td>
        </tr>';

    foreach ($weekdays as $day) {
        $key        = strtolower($day);
        $kurszeit   = isset($kurszeiten[$key]) ? $kurszeiten[$key] : '';
        $selbstzeit = isset($selbststudium[$key]) ? $selbststudium[$key] : '';

        $html .= '<tr>
                    <td ' . $table_style . '><strong>' . htmlspecialchars($day) . '</strong></td>
                    <td ' . $table_style . ' align="center">' . htmlspecialchars($kurszeit) . '</td>
                    <td ' . $table_style . ' align="center">' . htmlspecialchars($selbstzeit) . '</td>
                    <td ' . $table_style . ' align="center"></td>
                </tr>';
    }

    $html .= '</table>';

    // Hinweistext
    $html .= '<div style="font-size:8pt">&nbsp;</div>
    <div style="font-size:8.5pt; color: #334155;">' . htmlspecialchars($kb_hinweis) . '</div>
    <div style="font-size:32pt">&nbsp;</div>';

    // Stampiglie & Unterschrift (exakt ausgerichtete Zeilen)
    $html .= '
    <table cellspacing="0" cellpadding="0" style="width: 100%;">
        <tr>
            <td style="width: 46%; vertical-align: bottom;">
                <img src="https://www.x-sieben.at/wp-content/uploads/2021/01/stempel_kurszeiten-e1610995257460.png" width="180px">
            </td>
            <td style="width: 8%;"></td>
            <td style="width: 46%; vertical-align: bottom;">
                &nbsp;
            </td>
        </tr>
        <tr>
            <td style="width: 46%; vertical-align: top;">
                <div style="border-top: 1px solid black; font-size: 2pt;">&nbsp;</div>
                <span style="font-size: 9pt;">' . $kb_sig_institut . '</span>
            </td>
            <td style="width: 8%;"></td>
            <td style="width: 46%; vertical-align: top;">
                <div style="border-top: 1px solid black; font-size: 2pt;">&nbsp;</div>
                <span style="font-size: 9pt;">' . $kb_sig_kunde . '</span>
            </td>
        </tr>
    </table>';

    if (!class_exists('MYPDFA_Kurszeiten')) {
        class MYPDFA_Kurszeiten extends TCPDF
        {
            public $header_content = '';
            public $logo_html = '';

            public function Header()
            {
                // Do not render a header
            }

            public function Footer()
            {
                // Do not render a footer
            }
        }
    }

    $pdfAuthor = 'XSieben Wirtschaftstraining GmbH';

    // TCPDF Objekt erzeugen
    $pdf = new MYPDFA_Kurszeiten(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Kurszeitenbestaetigung');
    $pdf->SetSubject('Kurszeitenbestaetigung');

    // Header und Footer entfernen
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Ränder & 1-Seiten-Garantie
    $pdf->SetMargins(15, 10, 15);
    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetCellPadding(0);

    // Neue Seite
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    // Ordner sicherstellen & PDF speichern
    $save_dir = get_template_directory() . '/angebote/';
    if (!file_exists($save_dir)) {
        wp_mkdir_p($save_dir);
    }
    $save_path = $save_dir . $pdfName;
    $save_path = str_replace('/', DIRECTORY_SEPARATOR, $save_path);
    $pdf->Output($save_path, 'F');

    // URL für Webzugriff
    $pdf_url = get_template_directory_uri() . '/angebote/' . rawurlencode($pdfName);
    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'kurszeitenbestaetigung');
    } else {
        return $pdf_url;
    }
}
