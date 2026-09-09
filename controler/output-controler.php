<?php

require_once dirname(__DIR__) . '/helpers/crm-status.php';
require_once dirname(__DIR__) . '/helpers/normalize.php';
require_once dirname(__DIR__) . '/repositories/CrmStatusRepository.php';
require_once dirname(__DIR__) . '/services/StatusTransitionService.php';

/**
 * Display a PDF preview with action buttons in WP Admin, or switch to the email mailer.
 */
function x_sieben_pdf_preview($pdf_url, $course_id, $entry_id = 0, $context = 'xsieben_angebot')
{ ?>
  <div id="x-sieben-container" class="wp-clearfix" style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">

    <div id="x-sieben-pdf-preview" style="flex: 0 0 65%; min-width: 300px;">
      <h2 class="title">Vorschau</h2>
      <embed src="<?php echo esc_url($pdf_url); ?>" type="application/pdf" width="100%" height="400px" />
    </div>

    <div id="x-sieben-button-row" style="flex: 0 0 30%; display:flex; flex-direction:column; gap:10px; margin-top:70px;">

      <ul style="list-style: none; margin: 0; padding: 0;">
        <li style="margin-bottom: 10px;">
          <a href="<?php echo esc_url($pdf_url); ?>" download>
            <span class="dashicons dashicons-download"></span>
            PDF herunterladen
          </a>
        </li>
        <li style="margin-bottom: 10px;">
          <a href="#" class="x-sieben-email-btn"
            data-pdf="<?php echo esc_url($pdf_url); ?>"
            data-course="<?php echo absint($course_id); ?>"
            data-entry="<?php echo absint($entry_id); ?>"
            data-context="<?php echo esc_attr($context); ?>">
            <span class="dashicons dashicons-email"></span>
            E-Mail bearbeiten & senden
          </a>
        </li>
        <li style="margin-bottom: 10px;">
          <a href="#" class="x-sieben-email-btn"
            data-pdf="<?php echo esc_url($pdf_url); ?>"
            data-course="<?php echo absint($course_id); ?>"
            data-entry="<?php echo absint($entry_id); ?>"
            data-context="<?php echo esc_attr($context); ?>"
            data-focus-test="1"
            style="color:#0284c7; font-weight:600;">
            <span class="dashicons dashicons-email-alt" style="color:#0284c7;"></span>
            🧪 Test-Mail vorbereiten
          </a>
        </li>
        <?php if ($entry_id) : ?>
          <li style="margin-bottom: 10px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpforms-entries&view=edit&entry_id=' . absint($entry_id))); ?>">
              <span class="dashicons dashicons-edit"></span>
              Kundendaten bearbeiten
            </a>
          </li>
        <?php endif; ?>
        <?php if ($course_id) : ?>
          <li style="margin-bottom: 10px;">
            <a href="<?php echo esc_url(get_edit_post_link($course_id)); ?>">
              <span class="dashicons dashicons-admin-page"></span>
              Kurs bearbeiten
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <div style="margin-top: 10px; font-style: italic; font-size: 0.9em;">
        Kundendaten bearbeiten, um Zertifizierungen auszuwählen
      </div>

    </div>

  </div>
<?php
}

/**
 * Generates an email editor interface with a downloadable PDF attachment.
 * The email body changes based on the context provided.
 *
 * @param string $pdf_url The URL of the PDF to attach.
 * @param int $course_id The ID of the associated course.
 * @param int $entry_id The ID of the associated WPForms entry.
 * @param string $context The context that determines the email body ('anfrage', 'bestellung', etc.).
 */
function x_sieben_pdf_mailer($pdf_url, $course_id, $entry_id, $context)
{
  // Initialize data model
  $data = new CRM_Model($course_id, $entry_id);

  // Set default recipient and subject
  $recipient = !empty($data->email) ? $data->email : '';
  $subject = '';
  $body = '';

  // Context switch
  switch ($context) {
    case 'anmeldung':
      $subject = !empty($data->title) ? 'Ihre Anfrage für: ' . $data->title : 'Anfrage für Ihren Kurs';
      $body    = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;"><tr><td>' . $data->anmeldung_email . '</td></tr></table>';
      break;

    case 'xsieben_angebot':
      $subject = !empty($data->title) ? 'Angebot für: ' . $data->title : 'Angebot X SIEBEN';
      $body    = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;"><tr><td>' . $data->angebot_email . '</td></tr></table>';
      break;

    case 'xsieben_diplom':
      $subject = !empty($data->title) ? 'Diplom ' . $data->title : 'Diplom X SIEBEN';
      $body    = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;"><tr><td>' . $data->diplom_email . '</td></tr></table>';
      break;

    case 'teilnahmebestaetigung':
      $subject = !empty($data->title) ? 'Ihre Teilnahmebestätigung für: ' . $data->title : 'Teilnahmebestätigung';
      $body    = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;"><tr><td>' . $data->teilnahmebestaetigung_email . '</td></tr></table>';
      break;

    case 'xsieben_angebot_kurszeiten':
    case 'kurszeitenbestaetigung':
      $subject = !empty($data->title) ? 'Angebot für: ' . $data->title : 'Angebot & Kurszeiten X SIEBEN';
      $body    = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;"><tr><td>' . $data->angebot_email . '</td></tr></table>';
      break;

    default:
      $subject = !empty($data->title) ? 'Anfrage zu: ' . $data->title : 'Ihre Anfrage';
      $body    = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;"><tr><td>'
               . $data->salutation . ' ' . $data->titel . ' ' . $data->vorname . ' ' . $data->nachname . ',<br><br>'
               . 'vielen Dank für Ihre Nachricht. Wir werden uns in Kürze bei Ihnen melden.<br><br>'
               . $data->signatur_email
               . '</td></tr></table>'
               . $data->email_footer;
      break;
  }

  // Default test email from settings or current WP user
  $current_user = wp_get_current_user();
  $default_test_email = get_option('crm_test_email');
  if (empty($default_test_email) && !empty($current_user->user_email)) {
    $default_test_email = $current_user->user_email;
  }

  // Output the HTML form for the email editor in the backend
  echo '<div id="x-sieben-container" class="wp-clearfix" style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">';

  // Left: Email editor
  echo '<div id="x-sieben-email-editor" style="flex:0 0 80%;">';
  echo '<h2 class="title">E-Mail verfassen</h2>';
  echo '<div id="crm-mailer-notice"></div>';
  echo '<p><label for="x_sieben_recipient">Empfänger-E-Mail (Kunde):</label>';
  echo '<input type="email" id="x_sieben_recipient" class="regular-text" style="width:100%;" value="' . esc_attr($recipient) . '" required></p>';
  echo '<p><label for="x_sieben_subject">Betreff:</label>';
  echo '<input type="text" id="x_sieben_subject" class="regular-text" style="width:100%;" value="' . esc_attr($subject) . '" required></p>';

  // --- Test-E-Mail Box ---
  echo '<div id="crm-test-mail-box" style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:6px; padding:12px 16px; margin:15px 0 20px 0; box-shadow:0 1px 2px rgba(0,0,0,0.03);">';
  echo '  <div id="crm-test-mail-notice"></div>';
  echo '  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1px solid #e0f2fe; padding-bottom:8px;">';
  echo '    <strong style="color:#0369a1; font-size:13px; display:inline-flex; align-items:center; gap:6px;">';
  echo '      <span class="dashicons dashicons-email-alt" style="color:#0284c7;"></span> ' . esc_html__('Test-E-Mail Versand', 'custom-crm');
  echo '    </strong>';
  echo '    <button type="button" id="send-test-mail-btn" class="button button-secondary" style="background:#0284c7; color:#fff; border-color:#0284c7; font-weight:600; cursor:pointer;">';
  echo '      🧪 ' . esc_html__('Test-E-Mail jetzt senden', 'custom-crm');
  echo '    </button>';
  echo '  </div>';

  echo '  <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-start;">';
  echo '    <div style="flex:1; min-width:260px;">';
  echo '      <label for="x_sieben_test_recipient" style="font-weight:600; font-size:12px; color:#334155; display:block; margin-bottom:4px;">' . esc_html__('Test-Empfänger (E-Mail):', 'custom-crm') . '</label>';
  echo '      <input type="email" id="x_sieben_test_recipient" class="regular-text" style="width:100%; height:32px; font-size:13px;" value="' . esc_attr($default_test_email) . '" placeholder="ihre-adresse@domain.at">';
  echo '    </div>';

  echo '    <div style="flex:1; min-width:280px; font-size:12px; color:#334155;">';
  echo '      <label style="display:block; margin-bottom:5px; font-weight:600;">' . esc_html__('Versand-Option:', 'custom-crm') . '</label>';
  echo '      <label style="margin-right:15px; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">';
  echo '        <input type="radio" name="x_sieben_test_mode_type" value="only_test" checked="checked"> ' . esc_html__('Nur an Test-Empfänger', 'custom-crm');
  echo '      </label>';
  echo '      <label style="cursor:pointer; display:inline-flex; align-items:center; gap:4px;">';
  echo '        <input type="radio" name="x_sieben_test_mode_type" value="both"> ' . esc_html__('An Test & Kunde gleichzeitig', 'custom-crm');
  echo '      </label>';
  echo '    </div>';
  echo '  </div>';

  echo '  <div style="margin-top:10px; font-size:12px; color:#64748b; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">';
  echo '    <label style="cursor:pointer; display:inline-flex; align-items:center; gap:4px;">';
  echo '      <input type="checkbox" id="x_sieben_test_prefix_subject" value="1" checked="checked"> ' . esc_html__('[TEST] im Betreff voranstellen', 'custom-crm');
  echo '    </label>';
  echo '    <span style="font-size:11px; color:#0369a1; font-style:italic;">' . esc_html__('Tipp: Bei "Nur an Test-Empfänger" bleibt der Kundenstatus erhalten (wird im Verlauf protokolliert).', 'custom-crm') . '</span>';
  echo '  </div>';
  echo '</div>';

  // Hier der Editor mit Tabs (Visuell / Text)
  echo '<p><label for="x_sieben_body">Nachricht:</label></p>';
  echo '<div id="x-sieben-mail-editor" class="wp-editor-wrap tmce-active">';
  echo '  <div class="wp-editor-tabs">';
  echo '    <button type="button" id="x_sieben_body-tmce" class="wp-switch-editor switch-tmce">Visuell</button>';
  echo '    <button type="button" id="x_sieben_body-html" class="wp-switch-editor switch-html">Text</button>';
  echo '  </div>';
  echo '  <div class="wp-editor-container">';
  $editor_initial_body = function_exists('crm_prepare_email_html_for_sending') ? crm_prepare_email_html_for_sending($body) : $body;
  echo '    <textarea id="x_sieben_body" name="x_sieben_body" rows="10" class="wp-editor-area">' . esc_textarea($editor_initial_body) . '</textarea>';
  echo '  </div>';
  echo '</div>';

  echo '<input type="hidden" id="x_sieben_pdf_url" value="' . esc_url($pdf_url) . '">';
  echo '<input type="hidden" id="x_sieben_course_id" value="' . esc_attr($course_id) . '">';
  echo '<input type="hidden" id="x_sieben_entry_id" value="' . esc_attr($entry_id) . '">';
  echo '<input type="hidden" id="x_sieben_context" value="' . esc_attr($context) . '">';
  echo '<p style="text-align:right; display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-top:15px;">';
  echo '  <button type="button" id="send-test-mail-btn-bottom" class="button" style="background:#0284c7; color:#fff; border-color:#0284c7; font-weight:600;">🧪 ' . esc_html__('Test-E-Mail senden', 'custom-crm') . '</button>';
  echo '  <button type="button" id="send-mail-btn" class="button button-primary" style="font-weight:600;">' . esc_html__('E-Mail an Kunden senden', 'custom-crm') . '</button>';
  echo '</p>';
  echo '</div>'; // end email editor

  // Right: Action buttons
  echo '<div id="x-sieben-button-row" style="flex:1; display:flex; flex-direction:column; gap:10px; align-items:flex-end; margin-top:70px;">';
  echo '<ul style="list-style:none; margin:0; padding:0;">';
  echo '  <li style="margin-bottom:10px;"><a href="' . esc_url($pdf_url) . '" download><span class="dashicons dashicons-download"></span> PDF herunterladen</a></li>';
  if ($entry_id) {
    echo '  <li style="margin-bottom:10px;"><a href="' . esc_url(admin_url('admin.php?page=wpforms-entries&view=edit&entry_id=' . absint($entry_id))) . '"><span class="dashicons dashicons-edit"></span> Kundendaten bearbeiten</a></li>';
  }
  if ($course_id) {
    echo '  <li style="margin-bottom:10px;"><a href="' . esc_url(get_edit_post_link($course_id)) . '"><span class="dashicons dashicons-admin-page"></span> Kurs bearbeiten</a></li>';
  }
  echo '</ul>';
  echo '</div>'; // end buttons
  echo '</div>'; // end container
}


/**
 * AJAX loading mailer
 */
add_action('wp_ajax_x_sieben_load_mailer', function () {
  check_ajax_referer('x_sieben_mailer_nonce', 'security');

  $pdf_url   = isset($_POST['pdf_url']) ? esc_url_raw($_POST['pdf_url']) : '';
  $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
  $entry_id  = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
  $context   = isset($_POST['context']) ? sanitize_key($_POST['context']) : 'default'; // 👈 Get the context here

  if (!$pdf_url) {
    wp_send_json_error('PDF URL missing');
  }

  // Pass the context to the mailer function
  x_sieben_pdf_mailer($pdf_url, $course_id, $entry_id, $context); // 👈 Pass it on
  wp_die();
});

/**
 * AJAX handler for mailer (Supports Live Send & Test Send modes)
 */
add_action('wp_ajax_x_sieben_send_mail', function () {
  check_ajax_referer('x_sieben_mailer_nonce', 'security');

  $recipient      = isset($_POST['x_sieben_recipient']) ? sanitize_email($_POST['x_sieben_recipient']) : '';
  $subject        = isset($_POST['x_sieben_subject']) ? sanitize_text_field($_POST['x_sieben_subject']) : '';
  $body           = isset($_POST['x_sieben_body']) ? wp_unslash($_POST['x_sieben_body']) : '';
  $entry_id       = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
  $context        = isset($_POST['context']) ? sanitize_key($_POST['context']) : '';

  // Test mode parameters
  $is_test_mode   = !empty($_POST['is_test_mode']) && in_array(strval($_POST['is_test_mode']), ['1', 'true'], true);
  $test_recipient = isset($_POST['test_recipient']) ? sanitize_email($_POST['test_recipient']) : '';
  $test_mode_type = isset($_POST['test_mode_type']) ? sanitize_key($_POST['test_mode_type']) : 'only_test';
  $prefix_subject = !empty($_POST['prefix_subject']) && in_array(strval($_POST['prefix_subject']), ['1', 'true'], true);

  // Validation
  if ($is_test_mode) {
    if (empty($test_recipient) || !is_email($test_recipient)) {
      wp_send_json_error('Bitte geben Sie eine gültige Test-E-Mail-Adresse an.');
    }
    if ($test_mode_type === 'both' && (empty($recipient) || !is_email($recipient))) {
      wp_send_json_error('Für den Versand an Kunde & Test wird auch eine gültige Kunden-E-Mail-Adresse benötigt.');
    }
  } else {
    if (empty($recipient) || !is_email($recipient)) {
      wp_send_json_error('Bitte geben Sie eine gültige Kunden-E-Mail-Adresse an.');
    }
  }

  // Determine recipients
  $recipients_to_send = [];
  if ($is_test_mode) {
    if ($test_mode_type === 'both') {
      $recipients_to_send = array_unique(array_filter([$test_recipient, $recipient]));
    } else {
      $recipients_to_send = [$test_recipient];
    }
  } else {
    $recipients_to_send = [$recipient];
  }

  // Format subject for test mode
  if ($is_test_mode && $prefix_subject) {
    if (strpos($subject, '[TEST]') !== 0) {
      $subject = '[TEST] ' . $subject;
    }
  }

  // Format body for test mode (inject clear test banner)
  if ($is_test_mode) {
    $mode_label = ($test_mode_type === 'both') ? 'An Test & Kunde versendet' : 'Nur an Test-Empfänger (Kunde erhält nichts)';
    $test_banner = '<div style="background-color:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:12px 16px; margin-bottom:20px; font-family:sans-serif; font-size:13px; border-radius:4px; line-height:1.5;">'
      . '<strong>🧪 TEST-MODUS (X SIEBEN CRM)</strong><br>'
      . 'Dies ist eine interne Test-Zustellung aus dem CRM.<br>'
      . '<strong>Kunden-Empfänger:</strong> ' . esc_html($recipient ?: 'Keine angegeben') . '<br>'
      . '<strong>WPForms Eintrag-ID:</strong> #' . esc_html($entry_id) . '<br>'
      . '<strong>Modus:</strong> ' . esc_html($mode_label) . '<br>'
      . '<strong>Zeitstempel:</strong> ' . esc_html(current_time('d.m.Y H:i:s'))
      . '</div>';
    $body = $test_banner . $body;
  }

  // Attachments
  $attachments = [];
  if (!empty($_POST['x_sieben_pdf_url'])) {
    $attachments_path = str_replace(home_url('/'), ABSPATH, esc_url_raw($_POST['x_sieben_pdf_url']));
    if (file_exists($attachments_path)) {
      $attachments[] = $attachments_path;
    }
  }

  // Set email headers
  $headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: X SIEBEN Wirtschaftstraining <office@x-sieben.at>'
  ];

  // Normalize HTML for email delivery:
  // 1. Force public absolute HTTPS URLs for all images & links (prevents relative path breakages in Gmail/Outlook).
  // 2. Strip destructive cookie banner attributes (e.g. consent-original-src-_).
  // 3. Remove webmail/TinyMCE artifacts.
  if (function_exists('crm_prepare_email_html_for_sending')) {
    $body = crm_prepare_email_html_for_sending($body);
  }

  // Send mail to all designated targets
  $sent_targets = [];
  foreach ($recipients_to_send as $target_email) {
    if (wp_mail($target_email, $subject, $body, $headers, $attachments)) {
      $sent_targets[] = $target_email;
    }
  }

  if (!empty($sent_targets)) {
    $now_mysql = current_time('mysql');

    // Case 1: ONLY TEST MODE (Audit log only, customer status remains unchanged)
    if ($is_test_mode && $test_mode_type === 'only_test') {
      if ($entry_id && function_exists('crm_add_entry_status_history')) {
        $note = sprintf(
          '🧪 Test-E-Mail gesendet an: %s (Kunde: %s). Betreff: %s',
          implode(', ', $sent_targets),
          $recipient ?: '-',
          $subject
        );
        crm_add_entry_status_history($entry_id, 'test_mail_gesendet', '🧪 Test-Mail gesendet', $note);
      }

      wp_send_json_success([
        'message'        => '🧪 Test-E-Mail erfolgreich gesendet an: ' . implode(', ', $sent_targets),
        'is_test'        => true,
        'entry_id'       => $entry_id,
      ]);
    }

    // Case 2: LIVE EMAIL OR BOTH TEST & CUSTOMER
    $statusRepo = new CrmStatusRepository();
    $transitionService = new StatusTransitionService();
    $current_status_key = $entry_id ? $statusRepo->getCurrentStatusKey($entry_id) : '';

    $nextStatus = $transitionService->resolveNextStatus($current_status_key, $context);
    $status_key = $nextStatus['key'];
    $status_label = $nextStatus['label'];

    if ($entry_id && function_exists('crm_set_entry_status')) {
      if ($is_test_mode && $test_mode_type === 'both') {
        $note = sprintf(
          'E-Mail an Kunden (%s) UND Test-Empfänger (%s) gesendet. Betreff: %s',
          $recipient,
          $test_recipient,
          $subject
        );
      } else {
        $note = sprintf('E-Mail an Kunden (%s) gesendet. Betreff: %s', $recipient, $subject);
      }
      crm_set_entry_status($entry_id, $status_key, $note, $now_mysql);
    }

    wp_send_json_success([
      'message'        => 'E-Mail erfolgreich gesendet an: ' . implode(', ', $sent_targets),
      'is_test'        => false,
      'entry_id'       => $entry_id,
      'status_key'     => $status_key,
      'status_label'   => $status_label,
      'badge_html'     => function_exists('crm_render_status_badge') ? crm_render_status_badge($status_key, $status_label, $now_mysql) : '',
      'date_formatted' => date_i18n('d.m.Y, H:i', strtotime($now_mysql)),
      'actions_html'   => function_exists('crm_render_entry_actions') ? crm_render_entry_actions($entry_id, $course_id, $status_key) : '',
    ]);
  } else {
    wp_send_json_error('Fehler beim Senden der E-Mail. Bitte Mail-Konfiguration prüfen.');
  }
});
