// crm-admin.js
document.addEventListener("DOMContentLoaded", function () {
    // --- Variables ---
    const table = document.querySelector(".js-sort-table");
    const searchInput = document.getElementById("courseTableSearch");
    const ajaxUrl = crmData.ajaxUrl;
    const nonce = crmData.nonce;
    const noticeContainer = document.getElementById('crm-ajax-notice-container');
    const detailsContainer = document.getElementById('crm-entry-details-container');
    const listView = document.getElementById('crm-list-view');
    const editorView = document.getElementById('crm-editor-view');
    const editorClientName = document.getElementById('crm-editor-client-name-val');
    const editorCourseTitle = document.getElementById('crm-editor-course-title-val');
    const editorEntryId = document.getElementById('crm-editor-entry-id-val');
    let activeEntryId = null;

    /**
     * Switch from Screen 1 (List) to Screen 2 (Editor / Workspace)
     */
    function openEditorView(row, actionKey) {
        if (!editorView || !listView) return;
        activeEntryId = row ? row.dataset.entryId : null;

        const clientName = row ? (row.dataset.clientName || 'Kunde') : 'Kunde';
        const courseTitle = row ? (row.dataset.courseTitle || 'Kurs') : 'Kurs';
        const entryId = row ? row.dataset.entryId : '---';

        if (editorClientName) editorClientName.textContent = clientName;
        if (editorCourseTitle) editorCourseTitle.textContent = courseTitle;
        if (editorEntryId) editorEntryId.textContent = entryId;

        // Hide List, Show Editor
        listView.style.display = 'none';
        editorView.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Switch from Screen 2 (Editor / Workspace) back to Screen 1 (List)
     */
    function closeEditorView() {
        if (!editorView || !listView) return;

        editorView.style.display = 'none';
        listView.style.display = 'block';

        // Scroll back to active row and give a subtle visual pulse
        if (activeEntryId) {
            const targetRow = document.querySelector('tr.crm-entry-row[data-entry-id="' + activeEntryId + '"]');
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetRow.classList.add('crm-row-highlight');
                setTimeout(() => targetRow.classList.remove('crm-row-highlight'), 2000);
            }
        }
    }

    // Expose globally
    window.crmOpenEditorView = openEditorView;
    window.crmCloseEditorView = closeEditorView;

    // Return to list on any back button click
    document.addEventListener('click', function (e) {
        if (e.target.closest('.crm-back-to-list-btn')) {
            e.preventDefault();
            closeEditorView();
        }
    });

    // Escape key handling
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modalBackdrop = document.getElementById('crm-history-modal-backdrop');
            if (modalBackdrop && modalBackdrop.style.display === 'flex') {
                return;
            }
            if (editorView && editorView.style.display !== 'none') {
                const activeEl = document.activeElement;
                const isTyping = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable);
                if (!isTyping) {
                    closeEditorView();
                }
            }
        }
    });

    if (table) {
        // --- Client-side Search & Status Filter ---
        const tableRows = table.querySelectorAll("tbody tr");
        const statusFilter = document.getElementById("crmStatusFilter");

        const updatePlaceholder = () => {
            const visibleRows = Array.from(tableRows).filter(row => row.style.display !== "none").length;
            if (searchInput) {
                searchInput.placeholder = `Search (${visibleRows} / ${tableRows.length})...`;
            }
        };

        const filterRows = () => {
            const searchVal = (searchInput ? searchInput.value : "").toLowerCase().trim();
            const statusVal = (statusFilter ? statusFilter.value : "").toLowerCase().trim();

            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const matchesSearch = !searchVal || text.includes(searchVal);
                const statusCell = row.querySelector('.crm-status-cell');
                const statusText = statusCell ? statusCell.innerText.toLowerCase() : '';
                const matchesStatus = !statusVal || statusText.includes(statusVal);

                row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
            });
            updatePlaceholder();
        };

        if (searchInput) {
            searchInput.addEventListener("input", filterRows);
        }
        if (statusFilter) {
            statusFilter.addEventListener("change", filterRows);
        }
        updatePlaceholder();

        // --- Client-side Sorting ---
        const getCellValue = (tr, idx) => {
            const cell = tr.children[idx];
            if (!cell) return '';
            return cell.dataset.sort || cell.innerText.trim();
        };
        const comparer = (idx, asc) => (a, b) =>
            getCellValue(a, idx).localeCompare(getCellValue(b, idx), 'de', { numeric: true }) * (asc ? 1 : -1);

        table.querySelectorAll("th").forEach((th, idx) => {
            if (idx >= table.querySelector("th").length - 1) return;

            const indicator = document.createElement("span");
            indicator.className = "sort-indicator";
            indicator.textContent = " ⇅";
            th.appendChild(indicator);
            th.style.cursor = 'pointer';

            th.addEventListener("click", () => {
                const tbody = table.querySelector("tbody");
                const asc = th.dataset.sort !== "asc";
                th.dataset.sort = asc ? "asc" : "desc";

                table.querySelectorAll("th").forEach(otherTh => {
                    const otherIndicator = otherTh.querySelector('.sort-indicator');
                    if (otherIndicator) {
                        otherIndicator.textContent = otherTh === th ? (asc ? " ↑" : " ↓") : " ⇅";
                    }
                });

                Array.from(tbody.querySelectorAll("tr"))
                    .sort(comparer(idx, asc))
                    .forEach(tr => tbody.appendChild(tr));
            });
        });

        // --- AJAX Notices Helper ---
        const displayNotice = (message, type = 'success') => {
            const notice = document.createElement('div');
            notice.className = `notice notice-${type} is-dismissible`;
            notice.innerHTML = `<p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>`;
            noticeContainer.innerHTML = '';
            noticeContainer.appendChild(notice);

            notice.querySelector('.notice-dismiss').addEventListener('click', () => notice.remove());
        };

        // --- CRM Table AJAX Actions & More Actions Menu ---
        table.addEventListener('click', function (e) {
            // Toggle "more actions" dropdown
            const toggleBtn = e.target.closest('.crm-more-toggle-btn');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                const dropdown = toggleBtn.closest('.crm-more-actions-dropdown');
                if (dropdown) {
                    const isOpen = dropdown.classList.contains('is-open');
                    document.querySelectorAll('.crm-more-actions-dropdown.is-open').forEach(d => {
                        if (d !== dropdown) d.classList.remove('is-open');
                    });
                    dropdown.classList.toggle('is-open', !isOpen);
                }
                return;
            }

            const button = e.target.closest('.crm-action-btn');
            if (!button) return;

            // Close more actions menu if clicked an item inside
            const parentDropdown = button.closest('.crm-more-actions-dropdown');
            if (parentDropdown) {
                parentDropdown.classList.remove('is-open');
            }

            const actionKey = button.dataset.action;
            const entryId = button.dataset.entryId;
            const courseId = button.dataset.courseId;
            const context = button.dataset.context; // Get the context from the button
            const row = button.closest('tr.crm-entry-row');

            // Open Screen 2 (Editor View)
            openEditorView(row, actionKey);

            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = '...';

            detailsContainer.innerHTML = '<div style="padding:40px 20px; text-align:center; color:#64748b;"><span class="dashicons dashicons-update spin" style="font-size:32px; width:32px; height:32px; margin-bottom:12px;"></span><br><strong style="font-size:15px; color:#1e293b;">Dokument wird vorbereitet...</strong><p style="margin-top:6px; font-size:13px; color:#64748b;">PDF-Vorschau und Optionen werden geladen.</p></div>';
            detailsContainer.style.display = 'block';

            const formData = new FormData();
            formData.append('action', 'crm_entry_action');
            formData.append('nonce', nonce);
            formData.append('action_key', actionKey);
            formData.append('entry_id', entryId);
            formData.append('course_id', courseId);
            formData.append('context', context); // Add the context to the form data

            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.output) {
                            detailsContainer.innerHTML = data.data.output;
                            detailsContainer.style.display = 'block';
                        }
                        displayNotice(data.data.message, 'success');
                    } else {
                        displayNotice(data.data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('CRM Action Error:', error);
                    displayNotice('An unexpected error occurred. Please check the console.', 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = originalText;
                });
        });

        // Close more actions on outside click
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.crm-more-actions-dropdown')) {
                document.querySelectorAll('.crm-more-actions-dropdown.is-open').forEach(d => d.classList.remove('is-open'));
            }
        });

        // --- CRM Status Quick Change via Dropdown ---
        table.addEventListener('change', function (e) {
            if (!e.target.classList.contains('crm-status-dropdown')) return;

            const select = e.target;
            const entryId = select.dataset.entryId;
            const newStatus = select.value;
            const newLabel = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : newStatus;
            const row = select.closest('tr');
            const pill = select.closest('.crm-status-pill');
            const labelEl = pill ? pill.querySelector('.crm-status-label') : null;
            const badgeWrap = row ? row.querySelector('.crm-status-badge-wrap') : null;
            const dateVal = row ? row.querySelector('.crm-status-date-val') : null;

            // Immediate visual feedback (<1ms)
            if (labelEl) {
                labelEl.textContent = newLabel;
            }
            if (pill) {
                pill.className = pill.className.replace(/\bcrm-status-[a-z0-9_-]+\b/g, '').trim();
                pill.classList.add('crm-status-' + newStatus);
                pill.classList.add('crm-status-loading');
                pill.setAttribute('data-status', newStatus);
            }

            select.disabled = true;

            const formData = new FormData();
            formData.append('action', 'crm_update_entry_status');
            formData.append('nonce', nonce);
            formData.append('entry_id', entryId);
            formData.append('course_id', row ? (row.dataset.courseId || 0) : 0);
            formData.append('status_key', newStatus);

            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (pill && data.data.status_key) {
                            pill.className = pill.className.replace(/\bcrm-status-[a-z0-9_-]+\b/g, '').trim();
                            pill.classList.add('crm-status-' + data.data.status_key);
                            pill.setAttribute('data-status', data.data.status_key);
                        }
                        if (labelEl && data.data.status_label) {
                            labelEl.textContent = data.data.status_label;
                        }
                        if (badgeWrap && data.data.badge_html) {
                            badgeWrap.innerHTML = data.data.badge_html;
                        }
                        if (dateVal && data.data.date_formatted) {
                            dateVal.textContent = data.data.date_formatted;
                        }
                        if (data.data.actions_html) {
                            const actionsCell = row ? row.querySelector('.crm-actions') : null;
                            if (actionsCell) {
                                actionsCell.innerHTML = data.data.actions_html;
                            }
                        }
                        displayNotice(data.data.message, 'success');
                    } else {
                        displayNotice((data.data && data.data.message) ? data.data.message : 'Fehler beim Ändern des Status.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Status Update Error:', err);
                    displayNotice('Status konnte nicht aktualisiert werden.', 'error');
                })
                .finally(() => {
                    select.disabled = false;
                    if (pill) {
                        pill.classList.remove('crm-status-loading');
                    }
                });
        });

        // --- CRM Status History Timeline Modal ---
        table.addEventListener('click', function (e) {
            const histBtn = e.target.closest('.crm-history-btn');
            if (!histBtn) return;

            const entryId = histBtn.dataset.entryId;
            const modalBackdrop = document.getElementById('crm-history-modal-backdrop');
            const modalContent = document.getElementById('crm-history-modal-content');

            if (!modalBackdrop || !modalContent) return;

            modalContent.innerHTML = '<p style="text-align:center; padding:20px; color:#64748b;">⏳ Verlauf wird geladen...</p>';
            modalBackdrop.style.display = 'flex';

            const formData = new FormData();
            formData.append('action', 'crm_get_entry_history');
            formData.append('nonce', nonce);
            formData.append('entry_id', entryId);

            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.html) {
                        modalContent.innerHTML = data.data.html;
                        const closeBtn = modalContent.querySelector('.crm-close-modal');
                        if (closeBtn) {
                            closeBtn.addEventListener('click', () => {
                                modalBackdrop.style.display = 'none';
                            });
                        }
                    } else {
                        modalContent.innerHTML = '<p style="color:#d63638; padding:15px;">' + (data.data?.message || 'Verlauf konnte nicht geladen werden.') + '</p>';
                    }
                })
                .catch(err => {
                    console.error('History Fetch Error:', err);
                    modalContent.innerHTML = '<p style="color:#d63638; padding:15px;">Verlauf konnte nicht geladen werden.</p>';
                });
        });

        // Close modal on backdrop click or Escape
        const modalBackdrop = document.getElementById('crm-history-modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function (e) {
                if (e.target === modalBackdrop) {
                    modalBackdrop.style.display = 'none';
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modalBackdrop.style.display === 'flex') {
                    modalBackdrop.style.display = 'none';
                }
            });
        }

    }

}); // End DOMContentLoaded
jQuery(document).ready(function ($) {
    // Handler for clicking the "E-Mail versenden" or "Test-Mail vorbereiten" button
    $('body').on('click', '.x-sieben-email-btn', function (e) {
        e.preventDefault();

        const pdfUrl = $(this).data('pdf');
        const courseId = $(this).data('course');
        const entryId = $(this).data('entry');
        const context = $(this).data('context');
        const focusTest = $(this).data('focus-test');

        // Remove any existing editor instance before loading new content
        if (typeof tinymce !== 'undefined' && tinymce.get('x_sieben_body')) {
            tinymce.get('x_sieben_body').remove();
        }

        $.ajax({
            url: xSiebenAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'x_sieben_load_mailer',
                pdf_url: pdfUrl,
                course_id: courseId,
                entry_id: entryId,
                context: context,
                security: xSiebenAjax.nonce
            },
            beforeSend: function () {
                $('#crm-entry-details-container').html('<p style="padding:15px; color:#64748b;">⏳ Mailer wird geladen...</p>');
            },
            success: function (response) {
                $('#crm-entry-details-container').html(response);

                // Initialize the TinyMCE editor with absolute URL settings
                if (typeof tinymce !== 'undefined') {
                    tinymce.init({
                        selector: '#x_sieben_body',
                        relative_urls: false,
                        remove_script_host: false,
                        convert_urls: false,
                        menubar: false,
                        toolbar: "undo redo | bold italic underline | bullist numlist | link unlink | code",
                        branding: false
                    });
                }

                // Re-initialize Quicktags (Text tab)
                if (typeof quicktags !== 'undefined') {
                    quicktags({ id: 'x_sieben_body' });
                }

                // If test button was clicked in preview, scroll to and highlight the test box
                if (focusTest) {
                    setTimeout(function () {
                        const testBox = document.getElementById('crm-test-mail-box');
                        if (testBox) {
                            testBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            testBox.style.transition = 'box-shadow 0.3s ease';
                            testBox.style.boxShadow = '0 0 0 3px #0284c7';
                            setTimeout(function () { testBox.style.boxShadow = ''; }, 1500);
                            const testInput = document.getElementById('x_sieben_test_recipient');
                            if (testInput) testInput.focus();
                        }
                    }, 350);
                }
            },
            error: function () {
                $('#crm-entry-details-container').html('<p style="color:#d63638; padding:15px;">Fehler beim Laden des Mailers. Bitte versuchen Sie es erneut.</p>');
            }
        });
    });

    // Helper to send either Live Mail or Test Mail
    function executeSendMail(isTestMode, triggerButton) {
        const $btn = $(triggerButton);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text(isTestMode ? '🧪 Wird gesendet...' : 'Wird gesendet...');

        const recipient = $('#x_sieben_recipient').val();
        const subject = $('#x_sieben_subject').val();
        const pdf_url = $('#x_sieben_pdf_url').val();
        const course_id = $('#x_sieben_course_id').val();
        const entry_id = $('#x_sieben_entry_id').val();
        const context = $('#x_sieben_context').val();

        const testRecipient = $('#x_sieben_test_recipient').val();
        const testModeType = $('input[name="x_sieben_test_mode_type"]:checked').val() || 'only_test';
        const prefixSubject = $('#x_sieben_test_prefix_subject').is(':checked') ? 1 : 0;

        if (isTestMode && (!testRecipient || !testRecipient.includes('@'))) {
            alert('Bitte geben Sie eine gültige Test-E-Mail-Adresse ein.');
            $btn.prop('disabled', false).text(originalText);
            $('#x_sieben_test_recipient').focus();
            return;
        }

        if (!isTestMode && (!recipient || !recipient.includes('@'))) {
            alert('Bitte geben Sie eine gültige Kunden-E-Mail-Adresse ein.');
            $btn.prop('disabled', false).text(originalText);
            $('#x_sieben_recipient').focus();
            return;
        }

        let body;
        if (typeof tinymce !== 'undefined' && tinymce.get('x_sieben_body')) {
            body = tinymce.get('x_sieben_body').getContent();
        } else {
            body = $('#x_sieben_body').val();
        }

        $.ajax({
            url: xSiebenAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'x_sieben_send_mail',
                security: xSiebenAjax.nonce,
                x_sieben_recipient: recipient,
                x_sieben_subject: subject,
                x_sieben_body: body,
                x_sieben_pdf_url: pdf_url,
                course_id: course_id,
                entry_id: entry_id,
                context: context,
                is_test_mode: isTestMode ? 1 : 0,
                test_recipient: testRecipient,
                test_mode_type: testModeType,
                prefix_subject: prefixSubject
            },
            success: function (response) {
                if (response.success) {
                    const msg = (typeof response.data === 'object' && response.data.message) ? response.data.message : response.data;
                    const isTest = (typeof response.data === 'object' && response.data.is_test) || isTestMode;

                    // Build in-page success notice
                    const nowStr = new Date().toLocaleTimeString('de-AT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    const noticeHtml = `
                        <div class="notice notice-success is-dismissible crm-action-notice" style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#ecfdf5; border:1px solid #a7f3d0; border-left:5px solid #10b981; border-radius:6px; margin:12px 0 16px 0; box-shadow:0 2px 5px rgba(0,0,0,0.04); animation:crmFadeIn 0.3s ease-out;">
                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                <span class="dashicons dashicons-yes-alt" style="color:#10b981; font-size:24px; width:24px; height:24px; margin-top:2px;"></span>
                                <div>
                                    <div style="color:#065f46; font-size:14px; font-weight:700;">
                                        ${isTest ? '🧪 Test-E-Mail erfolgreich versendet!' : '✉️ E-Mail erfolgreich an Kunden versendet!'}
                                    </div>
                                    <div style="color:#047857; font-size:12.5px; margin-top:3px; line-height:1.4;">
                                        ${msg}
                                    </div>
                                    <div style="color:#0f766e; font-size:11px; margin-top:4px;">
                                        Sendezeit: <strong>${nowStr}</strong>
                                    </div>
                                    ${!isTest ? `
                                    <div style="margin-top:10px;">
                                        <button type="button" class="button button-primary crm-back-to-list-btn" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                                            <span class="dashicons dashicons-arrow-left-alt" style="line-height:22px; font-size:16px;"></span>
                                            Zurück zur Anfragen-Übersicht
                                        </button>
                                    </div>` : ''}
                                </div>
                            </div>
                            <button type="button" class="crm-notice-close-btn" style="background:transparent; border:none; color:#047857; font-size:22px; line-height:1; cursor:pointer; padding:0 4px;" title="Hinweis schließen">&times;</button>
                        </div>
                    `;

                    // Inject notice right above the test box (if test) or mailer top (if live)
                    if (isTest) {
                        const testNotice = document.getElementById('crm-test-mail-notice');
                        if (testNotice) {
                            testNotice.innerHTML = noticeHtml;
                            testNotice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    } else {
                        const mailerNotice = document.getElementById('crm-mailer-notice');
                        if (mailerNotice) {
                            mailerNotice.innerHTML = noticeHtml;
                            mailerNotice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }

                    // Also show in top global notice container
                    const globalNotice = document.getElementById('crm-ajax-notice-container');
                    if (globalNotice) {
                        globalNotice.innerHTML = noticeHtml;
                    }

                    // Real-time table row update for status & timestamp
                    if (typeof response.data === 'object' && response.data.entry_id && !response.data.is_test) {
                        const targetRow = document.querySelector('tr.crm-entry-row[data-entry-id="' + response.data.entry_id + '"]');
                        if (targetRow) {
                            const pill = targetRow.querySelector('.crm-status-pill');
                            const labelEl = targetRow.querySelector('.crm-status-label');
                            const dropdown = targetRow.querySelector('.crm-status-dropdown');
                            const dateVal = targetRow.querySelector('.crm-status-date-val');
                            const badgeWrap = targetRow.querySelector('.crm-status-badge-wrap');

                            if (response.data.status_key) {
                                if (pill) {
                                    pill.className = pill.className.replace(/\bcrm-status-[a-z0-9_-]+\b/g, '').trim();
                                    pill.classList.add('crm-status-' + response.data.status_key);
                                    pill.setAttribute('data-status', response.data.status_key);
                                }
                                if (dropdown) dropdown.value = response.data.status_key;
                            }
                            if (labelEl && response.data.status_label) {
                                labelEl.textContent = response.data.status_label;
                            }
                            if (badgeWrap && response.data.badge_html) {
                                badgeWrap.innerHTML = response.data.badge_html;
                            }
                            if (dateVal && response.data.date_formatted) {
                                dateVal.textContent = response.data.date_formatted;
                            }
                            if (response.data.actions_html) {
                                const actionsCell = targetRow.querySelector('.crm-actions');
                                if (actionsCell) {
                                    actionsCell.innerHTML = response.data.actions_html;
                                }
                            }
                        }
                    }
                } else {
                    const errMsg = (typeof response.data === 'object' && response.data.message) ? response.data.message : response.data;
                    const errHtml = `
                        <div class="notice notice-error is-dismissible crm-action-notice" style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#fef2f2; border:1px solid #fecaca; border-left:5px solid #ef4444; border-radius:6px; margin:12px 0 16px 0;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="dashicons dashicons-dismiss" style="color:#ef4444; font-size:24px; width:24px; height:24px;"></span>
                                <div style="color:#991b1b; font-size:13px;"><strong>Fehler:</strong> ${errMsg}</div>
                            </div>
                            <button type="button" class="crm-notice-close-btn" style="background:transparent; border:none; color:#991b1b; font-size:22px; line-height:1; cursor:pointer; padding:0 4px;" title="Hinweis schließen">&times;</button>
                        </div>
                    `;
                    const targetContainer = isTestMode ? document.getElementById('crm-test-mail-notice') : document.getElementById('crm-mailer-notice');
                    if (targetContainer) {
                        targetContainer.innerHTML = errHtml;
                        targetContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        alert('Fehler: ' + errMsg);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', xhr.responseText);
                alert('AJAX request failed. See console.');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    // Dismiss custom action notices
    $(document).on('click', '.crm-notice-close-btn', function () {
        $(this).closest('.crm-action-notice').fadeOut(200, function () { $(this).remove(); });
    });

    // Handler for clicking the "E-Mail an Kunden senden" button
    $(document).on('click', '#send-mail-btn', function (e) {
        e.preventDefault();
        executeSendMail(false, this);
    });

    // Handler for clicking the Test Mail buttons
    $(document).on('click', '#send-test-mail-btn, #send-test-mail-btn-bottom', function (e) {
        e.preventDefault();
        executeSendMail(true, this);
    });
});
jQuery(document).ready(function ($) {

    // Umschalten zwischen Visuell (TinyMCE) und Text (Quicktags)
    $('body').on('click', '.wp-switch-editor', function () {
        const textareaId = 'x_sieben_body';

        if ($(this).hasClass('switch-tmce')) {
            // TinyMCE aktivieren
            if (typeof tinymce !== 'undefined' && !tinymce.get(textareaId)) {
                tinymce.execCommand('mceAddEditor', true, textareaId);
            }
            $('#x-sieben-mail-editor')
                .removeClass('html-active')
                .addClass('tmce-active');
        } else {
            // TinyMCE deaktivieren → Quicktags aktiv
            if (typeof tinymce !== 'undefined' && tinymce.get(textareaId)) {
                tinymce.execCommand('mceRemoveEditor', true, textareaId);
            }
            $('#x-sieben-mail-editor')
                .removeClass('tmce-active')
                .addClass('html-active');
        }
    });

});

