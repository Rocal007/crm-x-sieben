# X-SIEBEN CRM & Kurs-Spezialist (Agenten-Handbuch)

Dieses Dokument definiert die Architektur, Konventionen und Arbeitsregeln für das X-SIEBEN CRM-Modul.

## 1. Identität & Fokus
- **Spezialisierung:** Kursmanagement (`courses`), WPForms-Einträge (`Form-ID 60468`), PDF-Generierung, E-Mail-Versand und Kundenstatus-Tracking.
- **Organisation:** X SIEBEN Wirtschaftstraining GmbH (Wien / Wr. Neustadt).
- **Kurse:** IPMA®/pma Projektmanagement (Level D, C, B), Agile Methoden (Scrum, Kanban, Agile Coach), Führung, Personenzertifizierungen nach ISO 17024 (FachtrainerIn, Business Coach).
- **Zertifizierungspartner:** pma/IPMA, SystemCERT, TÜV Austria, wba, CERT NÖ, AMS.

## 2. Autarkie-Architektur
Das Modul `inc/core/crm/` ist 100 % unabhängig vom Rest des Themes:
- **`crm-admin.php`**: Hauptseite, Menüeintrag, Asset-Fallback-Registration, Versionsanzeige (`CRM_VERSION = '2.9.5'`).
- **`crm-model.php`**: Zentrales Datenmodell `CRM_Model`. Liest WPForms-Felder und Kursdaten aus. E-Mail-Vorlagen werden via `wpautop()` + `do_shortcode()` + `crm_prepare_email_html_for_sending()` geladen.
- **`helpers/normalize.php`**: E-Mail-HTML-Normalisierer `crm_prepare_email_html_for_sending()`. Garantiert absolute HTTPS-URLs (`https://x-sieben.at/...`), bereinigt Cookie-Banner-Attribute und repariert Smileys.
- **`helpers/crm-status.php`**: Datenbanktabellen `wp_crm_entry_status` und `wp_crm_entry_status_history`, Status-Badges, Zeitstempel, Modal-Historie.
- **`controler/output-controler.php`**: AJAX-Handler `x_sieben_send_mail` mit Test-Modus (`only_test` vs `both`) und PDF-Anhängen.
- **`assets/crm-admin.js`**: Interaktive Steuerung (Inline-Erfolgsmeldung, Status-Dropdown-Wechsel, Historien-Modal).

## 3. LINGUA-LOCA AT (Österreich-Standard)
- Regionale Begrifflichkeiten: "Teilnahmebestätigung", "Kurszeiten", "Lehrgang", "Backoffice / Kanzleiteam".
- Keine bundesdeutschen Floskeln.
- Radikale Objektivität (RO): Höchste Faktenpräzision, transparente Abläufe, keine Marketing-Floskeln.
