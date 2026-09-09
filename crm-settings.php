<?php
// Main function to render the CRM settings page
function render_crm_settings_page() {
    // Check for a full settings save
    if (isset($_POST['crm_settings_nonce']) && wp_verify_nonce($_POST['crm_settings_nonce'], 'save_crm_settings')) {
        if (isset($_POST['crm_test_email'])) {
            update_option('crm_test_email', sanitize_email($_POST['crm_test_email']));
        }
        $fields = [];
        if (!empty($_POST['crm_fields']) && is_array($_POST['crm_fields'])) {
            foreach ($_POST['crm_fields'] as $field) {
                $fields[] = [
                    'title'   => sanitize_text_field($field['title'] ?? ''),
                    'content' => wp_kses_post($field['content'] ?? ''),
                ];
            }
        }
        update_option('crm_custom_fields', $fields);
        echo '<div class="updated"><p>' . esc_html__('All settings saved.', 'custom-crm') . '</p></div>';
    }

    // Load settings
    $fields = get_option('crm_custom_fields', []);

    // Sort the fields based on a custom rule
    usort($fields, function($a, $b) {
        $a_is_email = (substr(strtolower($a['title']), 0, 6) === 'e-mail');
        $b_is_email = (substr(strtolower($b['title']), 0, 6) === 'e-mail');

        // If one is an 'E-Mail' field and the other isn't, sort the 'E-Mail' field first.
        if ($a_is_email && !$b_is_email) {
            return -1;
        }
        if (!$a_is_email && $b_is_email) {
            return 1;
        }

        // If both are 'E-Mail' fields or both are not, sort them alphabetically.
        return strcmp($a['title'], $b['title']);
    });
    ?>
    <div class="wrap">
        <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span><?php esc_html_e('CRM Settings', 'custom-crm'); ?></span>
            <span class="crm-version-badge" style="font-size:12px; font-weight:600; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:3px 10px; border-radius:12px; display:inline-flex; align-items:center; gap:5px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                <span class="dashicons dashicons-tag" style="font-size:14px; width:14px; height:14px; line-height:14px;"></span>
                Version <?php echo esc_html(defined('CRM_VERSION') ? CRM_VERSION : '2.1.0'); ?>
            </span>
        </h1>
        <form method="post" id="crm-main-form">
            <?php wp_nonce_field('save_crm_settings', 'crm_settings_nonce'); ?>

            <?php $current_test_email = get_option('crm_test_email', wp_get_current_user()->user_email); ?>
            <div class="card" style="max-width: 800px; padding: 15px 20px; margin-bottom: 25px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top: 5px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-email-alt" style="font-size: 24px;"></span>
                    <?php esc_html_e('Test-E-Mail Einstellungen', 'custom-crm'); ?>
                </h2>
                <p><?php esc_html_e('Definieren Sie eine Standard-Test-E-Mail-Adresse. Im CRM-Mailer kann jede E-Mail (inklusive generierter PDF-Anlagen und Signaturen) wahlweise an diese Test-Adresse gesendet werden.', 'custom-crm'); ?></p>
                <table class="form-table" role="presentation" style="margin-top: 0;">
                    <tr>
                        <th scope="row"><label for="crm_test_email"><?php esc_html_e('Standard Test-Empfänger', 'custom-crm'); ?></label></th>
                        <td>
                            <input name="crm_test_email" type="email" id="crm_test_email" value="<?php echo esc_attr($current_test_email); ?>" class="regular-text" style="width: 100%; max-width: 420px;" placeholder="test@ihredomain.at" />
                            <p class="description"><?php esc_html_e('Wird im Mailer automatisch vorausgewählt, wenn der Test-Modus aktiviert ist.', 'custom-crm'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="crm-fields-wrapper">
                <?php
                if (!empty($fields)) {
                    $has_email_fields = false;
                    foreach ($fields as $index => $field) {
                        $is_email = (substr(strtolower($field['title']), 0, 6) === 'e-mail');
                        
                        // Check if we need to add the break and heading
                        if (!$has_email_fields && !$is_email) {
                            $has_email_fields = true; // Mark that we've passed the email fields
                            echo '<hr />';
                            echo '<h2>' . esc_html__('Snippets', 'custom-crm') . '</h2>';
                        }
                        
                        crm_render_editor_field($index, $field['title'], $field['content']);
                    }
                } else {
                    crm_render_editor_field(0, '', '');
                }
                ?>
            </div>

            <p>
                <button type="button" class="button" id="add-crm-field"><?php esc_html_e('Add New Field', 'custom-crm'); ?></button>
            </p>

            <?php submit_button(__('Save All Settings', 'custom-crm')); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let fieldCount = <?php echo !empty($fields) ? count($fields) : 1; ?>;

        // Toggle accordion for each field
        $(document).on('click', '.crm-field-header', function() {
            $(this).next('.crm-field-content').slideToggle();
            $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-right');
        });

        // Add new field via AJAX
        $('#add-crm-field').on('click', function(e) {
            e.preventDefault();
            let newIndex = fieldCount++;
            let ajaxData = {
                action: 'crm_add_field_editor',
                index: newIndex
            };
            $.post(ajaxurl, ajaxData, function(response) {
                $('#crm-fields-wrapper').append(response);
            });
        });

        // Remove field
        $(document).on('click', '.remove-crm-field', function(e) {
            e.preventDefault();
            $(this).closest('.crm-field-block').remove();
        });

        // Individual field save via AJAX
        $(document).on('click', '.crm-save-field', function(e) {
            e.preventDefault();
            const fieldBlock = $(this).closest('.crm-field-block');
            const index = fieldBlock.data('index');
            const title = fieldBlock.find('input[name="crm_fields[' + index + '][title]"]').val();
            const content = tinymce.get('crm_fields_' + index + '_content').getContent();
            const nonce = '<?php echo wp_create_nonce('save_crm_field_individual'); ?>';

            const ajaxData = {
                action: 'crm_save_field_individual',
                nonce: nonce,
                index: index,
                title: title,
                content: content,
            };

            $.post(ajaxurl, ajaxData, function(response) {
                if(response.success) {
                    fieldBlock.find('.save-status').text('Saved!').fadeIn().delay(2000).fadeOut();
                } else {
                    fieldBlock.find('.save-status').text('Error saving.').fadeIn().delay(2000).fadeOut();
                }
            });
        });
    });
    </script>
    <style>
        .crm-field-header {
            cursor: pointer;
            background: #f5f5f5;
            padding: 10px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .crm-field-content {
            padding: 10px;
            border: 1px solid #ccc;
            border-top: none;
            display: none; /* Initially collapsed */
        }
        .crm-field-block {
            margin-bottom: 15px;
        }
        .save-status {
            color: green;
            font-style: italic;
            margin-left: 10px;
            display: none;
        }
    </style>
    <?php
}

// Renders a single field with title + editor inside an accordion
function crm_render_editor_field($index, $title, $content) {
    ?>
    <div class="crm-field-block" data-index="<?php echo esc_attr($index); ?>">
        <div class="crm-field-header">
            <h3>
                <span class="dashicons dashicons-arrow-right"></span>
                <?php echo $title ? esc_html($title) : esc_html__('New Field', 'custom-crm'); ?>
            </h3>
            <div>
                <button type="button" class="button crm-save-field"><?php esc_html_e('Save Field', 'custom-crm'); ?></button>
                <button type="button" class="button remove-crm-field"><?php esc_html_e('Remove', 'custom-crm'); ?></button>
                <span class="save-status"></span>
            </div>
        </div>
        <div class="crm-field-content">
            <p>
                <label><?php esc_html_e('Field Title', 'custom-crm'); ?>:</label><br>
                <input type="text" name="crm_fields[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($title); ?>" class="regular-text widefat">
            </p>
            <?php
            wp_editor(
                $content,
                "crm_fields_{$index}_content",
                [
                    'textarea_name' => "crm_fields[{$index}][content]",
                    'textarea_rows' => 5,
                    'media_buttons' => true,
                    'tinymce'       => true,
                    'quicktags'     => true,
                ]
            );
            ?>
        </div>
    </div>
    <?php
}

// AJAX handler to add a new blank field
add_action('wp_ajax_crm_add_field_editor', 'crm_add_field_editor_ajax_handler');
function crm_add_field_editor_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_die();
    }
    $index = intval($_POST['index']);
    crm_render_editor_field($index, '', '');
    wp_die();
}

// AJAX handler to save a single field
add_action('wp_ajax_crm_save_field_individual', 'crm_save_field_individual_ajax_handler');
function crm_save_field_individual_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'save_crm_field_individual')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    $index = intval($_POST['index']);
    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);

    // Get the existing fields
    $fields = get_option('crm_custom_fields', []);

    // Create a temporary array to hold the new or updated field
    $new_field = [
        'title'   => $title,
        'content' => $content,
    ];

    // Update the specific field using its index.
    // This will either update an existing index or create a new one.
    $fields[$index] = $new_field;
    
    // Sort the array by key to maintain the correct order
    ksort($fields);

    // Save the fields back to the database.
    // DO NOT re-index with array_values() as this is what breaks the future individual saves.
    update_option('crm_custom_fields', $fields);

    wp_send_json_success(['message' => 'Field saved successfully.']);
}