<?php
function xsieben_invoice_pdf($entry_id, $course_id, $output_to_browser = true)
{
    // CRM_Model-Instanz laden (hier Reihenfolge der Parameter prüfen!)
    $course = new CRM_Model($course_id, $entry_id);

    $pdfAuthor = 'X-Sieben Wirtschaftstraining GmbH';

    // Sauberen Dateinamen erzeugen
    $safe_title = preg_replace('/[^a-zA-Z0-9_\-äöüÄÖÜß]/u', '-', $course->titel_short);
    $pdf_name = "HN_" . $course_id . "_" . $safe_title . "_" . $course->vorname . "_" . $course->nachname . ".pdf";

    // Dynamische Texte aus dem CRM Model (PDF Editor) mit Default-Fallback
    $hn_title      = $course->get_crm_field_with_default('Honorarnote - Titel', 'Honorarnote');
    $hn_einleitung = $course->get_crm_field_with_default('Honorarnote - Einleitung', '<p>Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</p>');
    $hn_zahlung    = $course->get_crm_field_with_default('Honorarnote - Zahlungsanweisung', 'Bitte überweisen Sie den Betrag bis zum [Datum] auf das Konto von X SIEBEN Wirtschaftstraining GmbH.<br>IBAN: AT29 3293 7001 0012 5260 | BIC: RLNWATWWWRN');

    // HTML-Inhalt für das PDF (beispielhaft, anpassen wie du willst)
    $html = '
    <div style="font-size: 12pt; font-weight: bold; text-align: center;">
        ' . $hn_title . '
    </div>
    <div style="margin-top: 20px; font-size: 10pt;">
        <strong>Rechnungsnummer:</strong> HN_' . $course_id . '<br>
        <strong>Name:</strong> ' . htmlspecialchars($course->vorname) . ' ' . htmlspecialchars($course->nachname) . '<br>
        <strong>Adresse:</strong> ' . htmlspecialchars($course->street) . ' ' . htmlspecialchars($course->house_number) . ', ' . htmlspecialchars($course->zip_code) . ' ' . htmlspecialchars($course->city) . '<br>
        <strong>Veranstaltung:</strong> ' . htmlspecialchars($course->title) . '<br>
        <strong>Datum:</strong> ' . date('d.m.Y') . '
    </div>
    <hr style="margin-top:20px; margin-bottom:20px;">
    <div style="font-size: 10pt;">
        ' . $hn_einleitung . '
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th>Leistung</th>
                    <th>Menge</th>
                    <th>Preis pro Einheit</th>
                    <th>Gesamt</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>' . htmlspecialchars($course->title) . '</td>
                    <td>' . intval($course->anzahl_le) . '</td>
                    <td>XX,XX €</td>
                    <td>XX,XX €</td>
                </tr>
            </tbody>
        </table>
        <p><strong>Gesamtbetrag:</strong> XX,XX €</p>
    </div>
    <div style="margin-top: 30px; font-size: 9pt;">
        ' . $hn_zahlung . '
    </div>';

    // TCPDF-Objekt erstellen
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Honorarnote');
    $pdf->SetSubject('Honorarnote PDF');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->SetFont('dejavusans', '', 10);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    // Speicherordner vorbereiten
    $save_dir = get_template_directory() . '/angebote/';
    if (!is_dir($save_dir)) {
        mkdir($save_dir, 0777, true);
    }
    $save_path = $save_dir . $pdf_name;

    // PDF speichern
    $pdf->Output($save_path, 'F');

    // URL zum PDF für Webzugriff (Pfad ggf. anpassen)
    $pdf_url = get_template_directory_uri() . '/angebote/' . $pdf_name;

    if ($output_to_browser) {
        x_sieben_pdf_preview($pdf_url, $course_id, $entry_id, 'invoice');
    } else {
        return $pdf_url;
    }
}
