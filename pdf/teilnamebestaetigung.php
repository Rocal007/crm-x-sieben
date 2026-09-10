<?php
function xsieben_teilnahmebestaetigung_pdf($entry_id, $course_id, $output_to_browser = true)
{
    // Lade Kurs- und Adressdaten
    $course = new CRM_Model($course_id, $entry_id);

    $pdfAuthor = 'X- Sieben Wirtschaftstraining GmbH';

    // Sauberen Dateinamen erzeugen (nur erlaubte Zeichen)
    $safe_title = preg_replace('/[^a-zA-Z0-9_\-äöüÄÖÜß]/u', '-', $course->titel_short);
    $pdf_name = "TB_" . $safe_title . "_" . $course->vorname . "_" . $course->nachname . ".pdf";

    $clean_text = function ($val) {
        return trim(strip_tags($val ?? ''));
    };

    // Dynamische Texte aus dem CRM Model (PDF Editor) mit Default-Fallback
    $default_tb_betrieb_name = !empty($course->company_name) ? $course->company_name : 'X SIEBEN Wirtschaftstraining GmbH';
    $default_tb_betrieb_str  = !empty($course->company_street) ? $course->company_street : 'Kurzegasse 7';
    $default_tb_betrieb_plz  = !empty($course->company_zip) ? $course->company_zip : '2493';
    $default_tb_betrieb_ort  = !empty($course->company_city) ? $course->company_city : 'Lichtenwörth';
    $default_tb_ort_str      = !empty($course->location_wien_street) ? ($course->location_wien_street . ' bzw. online') : 'Rochusgasse 6 bzw. online';
    $default_tb_ort_plz      = !empty($course->location_wien_zip) ? $course->location_wien_zip : '1030';
    $default_tb_ort_ort      = !empty($course->location_wien_city) ? $course->location_wien_city : 'Wien';

    $tb_title        = $clean_text($course->get_crm_field_with_default('TB - Titel', 'Teilnahmebestätigung'));
    $tb_einleitung   = $clean_text($course->get_crm_field_with_default('TB - Einleitung', 'Wir bestätigen, dass'));
    $tb_betrieb_name = $clean_text($course->get_crm_field_with_default('TB - Betrieb Name', $default_tb_betrieb_name));
    $tb_betrieb_str  = $clean_text($course->get_crm_field_with_default('TB - Betrieb Strasse', $default_tb_betrieb_str));
    $tb_betrieb_plz  = $clean_text($course->get_crm_field_with_default('TB - Betrieb PLZ', $default_tb_betrieb_plz));
    $tb_betrieb_ort  = $clean_text($course->get_crm_field_with_default('TB - Betrieb Ort', $default_tb_betrieb_ort));
    $tb_ort_str      = $clean_text($course->get_crm_field_with_default('TB - Schulungsort Strasse', $default_tb_ort_str));
    $tb_ort_plz      = $clean_text($course->get_crm_field_with_default('TB - Schulungsort PLZ', $default_tb_ort_plz));
    $tb_ort_ort      = $clean_text($course->get_crm_field_with_default('TB - Schulungsort Ort', $default_tb_ort_ort));

    $tb_teilnahme_raw = $course->get_crm_field_with_default('TB - Teilnahme Text', 'an der Ausbildung: <strong>"' . $course->title . '"</strong> (' . intval($course->anzahl_le) . ' LE) teilgenommen hat.');
    $tb_teilnahme = preg_replace('/^\s*<p[^>]*>/iu', '', $tb_teilnahme_raw);
    $tb_teilnahme = preg_replace('/<\/p>\s*$/iu', '', $tb_teilnahme);
    $tb_teilnahme = trim($tb_teilnahme);

    $tb_datum        = $clean_text($course->get_crm_field_with_default('TB - Datum', 'Datum: ' . date('d.m.Y')));
    $tb_unterschrift = $clean_text($course->get_crm_field_with_default('TB - Unterschrift Label', 'Unterschrift:'));

    // Teilnehmerdaten
    $tn_name    = trim($course->vorname . ' ' . $course->nachname);
    $tn_svr     = trim((string)$course->svr);
    $tn_adresse = trim($course->street . ' ' . $course->house_number);
    $tn_plz     = trim((string)$course->zip_code);
    $tn_ort     = trim((string)$course->city);

    $html = '
    <div style="font-size:4pt">&nbsp;</div>
    <table cellspacing="0" cellpadding="0" style="width: 100%;">
        <tr>
            <td style="font-size:14pt; font-weight: bold; line-height:1; text-align: center;">
                ' . htmlspecialchars($tb_title) . '
            </td>
        </tr>
    </table>
    <div style="font-size:6pt">&nbsp;</div>
    <table cellspacing="0" cellpadding="0" style="width: 100%;">
        <tr>
            <td style="font-size:9.5pt; line-height: 1; text-align: left;">
                <span>' . htmlspecialchars($tb_einleitung) . '</span>
            </td>
        </tr>
    </table>
    <div style="font-size:5pt">&nbsp;</div>';

    // Box 1: Kursteilnehmer
    $html .= '<div style="width: 100%; border: 2px solid black;">
        <div style="font-size:3pt">&nbsp;</div>
        <table cellpadding="2" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 68%">
                    <span style="font-size:8pt;">Vor- und Familien- /Nachname</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tn_name) ? htmlspecialchars($tn_name) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
                <td style="width: 26%">
                    <span style="font-size:8pt;">SV-Nummer</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tn_svr) ? htmlspecialchars($tn_svr) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
            <tr>
                <td style="width: 2%"></td>
                <td colspan="3">
                    <span style="font-size:8pt;">Wohnadresse (Straße, Hausnummer, Stiege, Türnummer)</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tn_adresse) ? htmlspecialchars($tn_adresse) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 26%">
                    <span style="font-size:8pt;">Postleitzahl</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tn_plz) ? htmlspecialchars($tn_plz) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
                <td style="width: 68%">
                    <span style="font-size:8pt;">Ort</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tn_ort) ? htmlspecialchars($tn_ort) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
        </table>
        <div style="font-size:3pt">&nbsp;</div>
    </div>
    <div style="font-size:6pt">&nbsp;</div>';

    // Zeitraum
    $html .= '<table cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td style="width: 8%; vertical-align: middle; font-size:9.5pt;">vom </td>
            <td style="width: 26%;">
                <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5;">
                    <span> ' . htmlspecialchars($course->start_datum ?: date('d.m.Y')) . '</span>
                </div>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 6%; vertical-align: middle; font-size:9.5pt;">bis </td>
            <td style="width: 26%;">
                <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5;">
                    <span> ' . htmlspecialchars($course->end_datum ?: date('d.m.Y')) . '</span>
                </div>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 28%; vertical-align: middle; font-size:9.5pt;">bei</td>
        </tr>
    </table>
    <div style="font-size:6pt">&nbsp;</div>';

    // Box 2: Ausbildungsstätte & Schulungsort
    $html .= '<div style="width: 100%; border: 2px solid black;">
        <div style="font-size:3pt">&nbsp;</div>
        <table cellpadding="2" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 96%">
                    <span style="font-size:8pt;">Bezeichnung des Betriebes/der Ausbildungseinrichtung</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_betrieb_name) ? htmlspecialchars($tb_betrieb_name) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 96%">
                    <span style="font-size:8pt;">Adresse des Betriebes (Straße, Hausnummer, Stiege, Türnummer)</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_betrieb_str) ? htmlspecialchars($tb_betrieb_str) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
        </table>
        <table cellpadding="2" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 26%">
                    <span style="font-size:8pt;">Postleitzahl</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_betrieb_plz) ? htmlspecialchars($tb_betrieb_plz) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
                <td style="width: 68%">
                    <span style="font-size:8pt;">Ort</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_betrieb_ort) ? htmlspecialchars($tb_betrieb_ort) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
        </table>
        <table cellpadding="2" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 96%">
                    <span style="font-size:8pt;">Adresse des Schulungsortes (Straße, Hausnummer, Stiege, Türnummer)</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_ort_str) ? htmlspecialchars($tb_ort_str) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
        </table>
        <table cellpadding="2" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 2%"></td>
                <td style="width: 26%">
                    <span style="font-size:8pt;">Postleitzahl</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_ort_plz) ? htmlspecialchars($tb_ort_plz) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
                <td style="width: 68%">
                    <span style="font-size:8pt;">Ort</span>
                    <div style="font-size:9.5pt; border: 1px solid black; line-height: 1.5">
                        <span> ' . (!empty($tb_ort_ort) ? htmlspecialchars($tb_ort_ort) : '&nbsp;') . '</span>
                    </div>
                </td>
                <td style="width: 2%"></td>
            </tr>
        </table>
        <div style="font-size:3pt">&nbsp;</div>
    </div>
    <div style="font-size:8pt">&nbsp;</div>';

    // Teilnahme Text
    $html .= '<table style="width: 100%;">
        <tr>
            <td style="font-size: 9.5pt; line-height: 1.35;">' . $tb_teilnahme . '</td>
        </tr>
    </table>
    <div style="font-size:10pt">&nbsp;</div>';

    // Datum & Unterschrift (wie auf dem Angebot)
    $html .= '<table cellspacing="0" cellpadding="0" style="width: 100%;">
        <tr>
            <td style="width: 45%; vertical-align: top; font-size: 9.5pt;">
                ' . htmlspecialchars($tb_datum) . '
            </td>
            <td style="width: 55%; vertical-align: top;">
                ' . (!empty($tb_unterschrift) ? '<span style="font-size: 9.5pt;">' . htmlspecialchars($tb_unterschrift) . '</span><br>' : '') . '
                ' . $course->signatur . '
            </td>
        </tr>
    </table>';

    if (!class_exists('MYPDFA_teilnahme')) {
        class MYPDFA_teilnahme extends TCPDF
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

    // TCPDF Objekt erzeugen
    $pdf = new MYPDFA_teilnahme(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Dokumentinformationen setzen
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Teilnahmebestätigung');
    $pdf->SetSubject('Teilnahmebestätigung PDF');

    // Kopf- und Fußzeilen entfernen
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Margins & AutoPageBreak: Exakt 1 Seite garantiert
    $pdf->SetMargins(15, 8, 15);
    $pdf->SetAutoPageBreak(false);

    // Schriftart
    $pdf->SetFont('dejavusans', '', 10);

    // Neue Seite
    $pdf->AddPage();

    // HTML ausgeben
    $pdf->writeHTML($html, true, false, true, false, '');

    // PDF speichern
    $save_dir = get_template_directory() . '/angebote/';
    if (!file_exists($save_dir)) {
        wp_mkdir_p($save_dir);
    }
    $save_path = $save_dir . $pdf_name;
    $save_path = str_replace('/', DIRECTORY_SEPARATOR, $save_path);

    $pdf->Output($save_path, 'F');

    // URL zum PDF für Webzugriff
    $pdf_url = get_template_directory_uri() . '/angebote/' . rawurlencode($pdf_name);
    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'teilnahmebestaetigung');
    } else {
        return $pdf_url;
    }
}
