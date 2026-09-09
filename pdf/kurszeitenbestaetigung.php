<?php
function xsieben_kurszeitenbestaetigung_pdf($entry_id, $course_id, $output_to_browser=true)
{
    // Model laden
    $course = new CRM_Model($course_id, $entry_id);

    // Variablen aus dem Model
    $nummer           = $course->nummer;
    $file_title       = preg_replace('/[^a-zA-Z0-9_\-äöüÄÖÜß]/u', '-', $course->titel_short);
    $title            = $course->title;
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

    // HTML Aufbau
    $html = '
<div style="font-size:25pt">&nbsp;</div>
<table cellspacing="0" style="width: 100%;">
    <tr>
        <td style="font-size:16px; font-weight: bold; text-align: center;">
            <span style="text-align:center;">Bestätigung Kurszeiten</span>
        </td>
    </tr>
</table>';

    $html .= '
<table cellspacing="0" style="width: 100%;">
    <tr>
        <td><div style="font-size:12pt">&nbsp;</div>
            <span style="text-decoration: underline;"><strong>Kursinstitut</strong></span>
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 2px solid black;">
            <div style="font-size:10pt">&nbsp;</div>
            Name des Kursinstituts: X SIEBEN Wirtschaftstraining GmbH
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 2px solid black;">
            <div style="font-size:10pt">&nbsp;</div>
            Schulungsort (Adresse): Rochusgasse 6, 1030 Wien bzw. online
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 2px solid black;">
            <div style="font-size:10pt">&nbsp;</div>
            Kursbezeichnung: ' . $title . '
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 2px solid black;">
            <div style="font-size:10pt">&nbsp;</div>
            Kurs von-bis: ' . htmlspecialchars($startdatum) . ' bis ' . htmlspecialchars($enddatum) . '
        </td>
    </tr>
</table>';

    $html .= '<div style="font-size:5pt">&nbsp;</div>';

    $html .= '
<table cellspacing="0" style="width: 100%;">
    <tr>
        <td><div style="font-size:12pt">&nbsp;</div>
            <span style="text-decoration: underline;"><strong>KursteilnehmerIn: </strong></span>
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 2px solid black;">
            <div style="font-size:10pt">&nbsp;</div>
            Name: ' . htmlspecialchars($vorname) . ' ' . htmlspecialchars($nachname) . '
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 2px solid black;">
            <div style="font-size:10pt">&nbsp;</div>
            SV-Nummer: ' . htmlspecialchars($svr) . '
        </td>
    </tr>
</table>';

    $html .= '<div style="font-size:20pt">&nbsp;</div>';

    $html .= '<table cellspacing="0" cellpadding="3" style="width: 100%; border: 2px solid black;">
    <tr>
        <td>Kurstyp: Tageskurs ' . $kursart_t . '</td>
        <td>Abendkurs ' .$kursart_a . '</td>
        <td>Wochenendkurs ' . $kursart_we . '</td>
    </tr>
</table>';

    $html .= '<div style="font-size:18pt">&nbsp;</div>';

    /* ==========
        Dynamische Tabelle Kurszeiten
        ========== */
    $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

    $html .= '<table cellspacing="0" cellpadding="5" style="width: 100%; border: 2px solid black;">
        <tr>
            <td ' . $table_style . '><strong>Kurstage</strong></td>
            <td ' . $table_style . ' align="center"><strong>Kurszeit (von - bis)</strong><br><span style="font-size: 8px;">exkl. Mittagspause</span></td>
            <td ' . $table_style . ' align="center"><strong>Tele-/Selbstlernzeit</strong><br><span style="font-size: 8px;">bei und unter Aufsicht</span></td>
            <td ' . $table_style . ' align="center"><strong>Tele-/Selbstlernzeit</strong><br><span style="font-size: 8px;">außerhalb des Kursinstitutes</span></td>
        </tr>';

    foreach ($weekdays as $day) {
        $key      = strtolower($day);
        $kurszeit = isset($kurszeiten[$key]) ? $kurszeiten[$key] : '';
        $selbstzeit = isset($selbststudium[$key]) ? $selbststudium[$key] : '';

        $html .= '<tr>
                    <td ' . $table_style . '>' . htmlspecialchars($day) . '</td>
                    <td ' . $table_style . '>' . htmlspecialchars($kurszeit) . '</td>
                    <td ' . $table_style . '>' . htmlspecialchars($selbstzeit) . '</td>
                    <td ' . $table_style . '></td>
                </tr>';
    }

    $html .= '</table>';

    $html .= '<div style="font-size:2pt">&nbsp;</div>
    Bei unregelmäßigen Kurszeiten ist ein Ablaufplan der einzelnen Kurswochen beizulegen.<br>
    <div style="font-size:10pt">&nbsp;</div>
    <table cellspacing="0" cellpadding="0" style="width: 100%;">
        <tr>
            <td>
                <img src="https://www.x-sieben.at/wp-content/uploads/2021/01/stempel_kurszeiten-e1610995257460.png">
                <div style="border-top: 1px solid black;">&nbsp;</div>
                <span style="font-size: 10px;">Wien, ' . $kurszeiten_datum . '
                <br>Unterschrift, Stampiglie Kursinstitut</span>
            </td>
            <td></td>
            <td>
            <div style="font-size:43pt">&nbsp;</div>
            <div style="border-top: 1px solid black; width: 80%">&nbsp;</div>
                <span style="font-size: 10px;">Ort, Datum, Unterschrift, Kunde/Kundin</span>
            </td>
        </tr>
    </table>';

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

    $pdfAuthor = 'XSieben Wirtschaftstraining GmbH';

    // Instantiate the custom MYPDFA class
    $pdf = new MYPDFA_Kurszeiten(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Kurszeitenbestaetigung');
    $pdf->SetSubject('Kurszeitenbestaetigung');

    // Set header and footer fonts
    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins - adjusted top margin for content
    $pdf->SetMargins(PDF_MARGIN_LEFT, 15, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetCellPadding(0);

    // Add a page and write the HTML content
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    // Ensure target folder exists and save the PDF
    $save_dir = get_template_directory() . '/angebote/';
    if (!file_exists($save_dir)) {
        wp_mkdir_p($save_dir);
    }
    $save_path = $save_dir . $pdfName;
    $pdf->Output($save_path, 'F');

    // Create the correct URL for preview
    $pdf_url = get_template_directory_uri() . '/angebote/' . rawurlencode($pdfName);
    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'kurszeitenbestaetigung');
    }
    else {
        return $pdf_url;
    }
}
