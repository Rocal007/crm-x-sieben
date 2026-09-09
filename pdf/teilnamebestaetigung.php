<?php
function xsieben_teilnahmebestaetigung_pdf($entry_id, $course_id, $output_to_browser = true)
{
    // Lade Kurs- und Adressdaten
    $course = new CRM_Model($course_id, $entry_id);
    $entry_data = $course->entry_data;
    //var_dump($entry_data);

    $pdfAuthor = 'X- Sieben Wirtschaftstraining GmbH';

    // Sauberen Dateinamen erzeugen (nur erlaubte Zeichen)
    $safe_title = preg_replace('/[^a-zA-Z0-9_\-äöüÄÖÜß]/u', '-', $course->titel_short);
    $pdf_name = "TB_" . $safe_title . "_" . $course->vorname . "_" . $course->nachname . ".pdf";

    $html = '
<div style="font-size:12pt">&nbsp;</div>
<table cellspacing="0" style="width: 100%;">
    <tr>
        <td style="font-size:14px; font-weight: bold; line-height:1; text-align: center;">
            Teilnahmebestätigung
            <br>
        </td>
    </tr>
</table>';

    $html .= '<table style="width: 100%;">
                <tr>
                    <td style="font-size:10pt; line-height: 1; text-align: left;">
                        <span style="text-align:left;">Wir bestätigen, dass</span>
                    </td>
                </tr>
            </table>
            <br>';

    $html .= '<div style="width: 100%; border: 2px solid black;">
                <br>
                <table>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 68%">
                            Vor- und Familien- /Nachname
                            <div style="font-size:10pt; border: 1px solid black;line-height: 1.8">
                                <span> ' . htmlspecialchars($course->vorname) . ' ' . htmlspecialchars($course->nachname) . '</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                        <td style="width: 26%">
                            SV-Nummer
                            <div style="font-size:10pt; border: 1px solid black;line-height: 1.8">
                                <span> ' . htmlspecialchars($course->svr) . '</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                    <tr>
                        <td style="width: 2%"></td>
                        <td colspan="3">
                            Wohnadresse (Straße, Hausnummer, Stiege, Türnummer)
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> ' . htmlspecialchars($course->street) . ' ' . htmlspecialchars($course->house_number) . '</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 26%">
                            Postleitzahl
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> ' . htmlspecialchars($course->zip_code) . '</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                        <td style="width: 68%">
                            Ort
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> ' . htmlspecialchars($course->city) . '</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                </table>
            </div>
            <br>';

    $html .= '<table>
                <tr>
                    <td style="width: 10%">vom </td>
                    <td style="width: 25%">
                        <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                            <span> ' . htmlspecialchars($course->start_datum ?: '2025-06-01') . '</span>
                        </div>
                    </td>
                    <td style="width: 2%"></td>
                    <td style="width: 5%">bis </td>
                    <td style="width: 25%">
                        <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                            <span> ' . htmlspecialchars($course->end_datum) . '</span>
                        </div>
                    </td>
                    <td style="width: 2%"></td>
                    <td>bei</td>
                </tr>
            </table>';

    $html .= '<div style="width: 100%; border: 2px solid black;">
                <br>
                <table>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 96%">
                            Bezeichnung des Betriebes/der Ausbildungseinrichtung
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> X SIEBEN Wirtschaftstraining GmbH </span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 96%;">
                            Adresse des Betriebes (Straße, Hausnummer, Stiege, Türnummer)
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> Kurzegasse 7</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 26%">
                            Postleitzahl
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> 2493</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                        <td style="width: 68%">
                            Ort
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> Lichtenwörth</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 96%;">
                            Adresse des Schulungsortes (Straße, Hausnummer, Stiege, Türnummer)
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> Rochusgasse 6 bzw. online</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="width: 2%"></td>
                        <td style="width: 26%">
                            Postleitzahl
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> 1030</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                        <td style="width: 68%">
                            Ort
                            <div style="font-size:10pt; border: 1px solid black; line-height: 1.8">
                                <span> Wien</span>
                            </div>
                        </td>
                        <td style="width: 2%"></td>
                    </tr>
                </table>
            </div>
            <br>';

    $html .= '<table>
                <tr>
                    <td>an der Ausbildung: <strong>"' . $course->title . '"</strong> (' . intval($course->anzahl_le) . ' LE) teilgenommen hat.</td>
                </tr>
            </table><br><br>';

    $html .= '<table>
                <tr>
                    <td>Datum: ' . date('d.m.Y') . '</td>
                </tr>
                <tr>
                    <td>Unterschrift:</td>
                </tr>
            </table>';

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

    // Schriftart
    $pdf->SetFont('dejavusans', '', 10);

    // Neue Seite
    $pdf->AddPage();

    // HTML ausgeben
    $pdf->writeHTML($html, true, false, true, false, '');

    // PDF speichern (Ordner muss existieren und Schreibrechte haben!)
    $save_path = get_template_directory() . '/angebote/' . $pdf_name;
    $save_path = str_replace('/', DIRECTORY_SEPARATOR, $save_path); // Für Windows Pfad anpassen

    $pdf->Output(get_template_directory() . '/angebote/' . $pdf_name, 'F');

    // URL zum PDF für Webzugriff (Pfad ggf. anpassen)
    $pdf_url = get_template_directory_uri() . '/angebote/' . $pdf_name;
    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'teilnahmebestaetigung');
    } else {
        return $pdf_url;
    }
}
