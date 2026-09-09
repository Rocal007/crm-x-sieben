<?php 
function x_sieben_handle_email_submission() {
    // Check if the form has been submitted and the nonce is valid for security.
    if (isset($_POST['x_sieben_send_email']) && wp_verify_nonce($_POST['x_sieben_email_nonce'], 'send_pdf_email')) {
        
        // Sanitize and retrieve the form data
        $to = sanitize_email($_POST['x_sieben_recipient']);
        $subject = sanitize_text_field($_POST['x_sieben_subject']);
        $body = wp_kses_post($_POST['x_sieben_body']);
        $pdf_url = esc_url_raw($_POST['x_sieben_pdf_url']);

        // Check if the file exists on the server to be attached
        $upload_dir = wp_upload_dir();
        $pdf_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $pdf_url);

        if (file_exists($pdf_path)) {
            $attachments = [$pdf_path];
        } else {
            // Handle case where file is not found
            // You might want to log this or provide an error message
            $attachments = [];
        }

        // Use wp_mail to send the email with the attachment
        $sent = wp_mail($to, $subject, $body, [], $attachments);

        if ($sent) {
            // Display a success message to the user
            echo '<div class="notice notice-success is-dismissible"><p>E-Mail erfolgreich versendet!</p></div>';
        } else {
            // Display an error message
            echo '<div class="notice notice-error is-dismissible"><p>E-Mail konnte nicht versendet werden. Bitte versuchen Sie es erneut.</p></div>';
        }
    }
}
add_action('admin_notices', 'x_sieben_handle_email_submission');