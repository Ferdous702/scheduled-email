function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00'); // Ensure it's parsed as date
    const day = date.getDate();
    const month = date.toLocaleString('en-US', { month: 'long' });
    const year = date.getFullYear();
    const ordinal = (d) => {
        if (d > 3 && d < 21) return d + 'th';
        switch (d % 10) {
            case 1: return d + 'st';
            case 2: return d + 'nd';
            case 3: return d + 'rd';
            default: return d + 'th';
        }
    };
    return ordinal(day) + ' ' + month + ', ' + year;
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".editable").forEach(function (cell) {
        cell.addEventListener("dblclick", function () {
            const originalContent = this.innerHTML;
            const id = this.getAttribute("data-id");
            const name = this.getAttribute("data-name");
            const subject = this.getAttribute("data-subject");
            const content = this.getAttribute("data-content") || originalContent;

            // Create modal container
            const modal = document.createElement("div");
            modal.classList.add("se-modal-overlay");

            let editorHtml;
            let usesTinyMCE = false;
            const editorId = 'se-tinymce-editor-' + Date.now();

            if (name === 'scheduled_time') {
                // For date editing, provide a datetime-local input
                const formattedDate = content.replace(' ', 'T').substring(0, 16);
                editorHtml = `
                    <div class="se-form-group">
                        <label class="se-label">Date & Time</label>
                        <input type="datetime-local" id="modal-input" value="${formattedDate}">
                    </div>`;
            } else {
                usesTinyMCE = true;
                // For other fields, use TinyMCE editor
                const subjectInput = name === 'edit_content' ?
                    `<div class="se-form-group">
                        <label class="se-label">Subject</label>
                        <input type="text" id="email-subject" value="${subject}"/>
                    </div>` :
                    '';
                editorHtml = `
                    ${subjectInput}
                    <div class="se-form-group">
                        <label class="se-label">Content</label>
                        <textarea id="${editorId}" class="se-tinymce-textarea">${content}</textarea>
                    </div>
                `;
            }

            const fieldName = name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            modal.innerHTML = `
                <div class="se-modal">
                    <div class="se-modal-header">
                        <h2>Edit ${fieldName}</h2>
                    </div>
                    <div class="se-modal-body">
                        ${editorHtml}
                    </div>
                    <div class="se-modal-footer">
                        <button id="cancel-btn" class="se-btn-secondary">Cancel</button>
                        <button id="save-btn" class="se-btn-primary">Save Changes</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Initialize TinyMCE if this modal uses it
            if (usesTinyMCE && typeof wp !== 'undefined' && wp.editor) {
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern',
                        toolbar1: 'formatselect bold italic underline | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | wp_adv',
                        toolbar2: 'strikethrough hr forecolor | pastetext removeformat | charmap | outdent indent | undo redo | wp_help',
                        height: 300,
                        menubar: false,
                        relative_urls: false,
                        remove_script_host: false,
                        convert_urls: false
                    },
                    quicktags: true,
                    mediaButtons: true
                });
            }

            function closeModal() {
                // Destroy TinyMCE instance before removing modal
                if (usesTinyMCE && typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.remove(editorId);
                }
                document.body.removeChild(modal);
            }

            document.getElementById("save-btn").addEventListener("click", function () {
                let newValue;
                let emailSubject = "";

                if (name === 'scheduled_time') {
                    newValue = document.getElementById("modal-input").value;
                    if (!newValue) {
                        alert('Please select a valid date and time.');
                        return;
                    }
                } else {
                    if (name === 'edit_content') {
                        emailSubject = document.getElementById("email-subject").value;
                    }
                    // Get content from TinyMCE
                    if (usesTinyMCE && typeof wp !== 'undefined' && wp.editor) {
                        // Sync the visual editor content to textarea
                        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                            tinymce.get(editorId).save();
                        }
                        newValue = document.getElementById(editorId).value;
                    } else {
                        newValue = document.getElementById(editorId).value;
                    }
                }
                
                if (newValue === content) {
                    closeModal();
                    return;
                }

                fetch(scheduledAjax.ajaxUrl, {
                        method: "POST",
                        credentials: "same-origin",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                            "Accept": "application/json"
                        },
                        body: new URLSearchParams({
                            action: "update_content",
                            security: scheduledAjax.nonce,
                            id: id,
                            content: newValue,
                            name_info: name,
                            subject_info: emailSubject,
                        }),
                    })
                    .then(async (response) => {
                        // Try to parse JSON; fall back to text for error visibility
                        const contentType = response.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            return response.json();
                        }
                        const text = await response.text();
                        // If WP nonce fails, admin-ajax may return "-1" or HTML; surface it
                        throw new Error(text || 'Non-JSON response from server');
                    })
                    .then((data) => {
                        if (data.success) {
                            // This is the new, pasted code
                            if (data.data.content_preview) {
                                cell.innerHTML = data.data.content_preview;
                            } else {
                                // This line was added to update the date field visually
                                cell.innerHTML = newValue.replace('T', ' ') + ':00';
                            }
                            // Update Course_Date in content if scheduled_time was changed
                            if (name === 'scheduled_time') {
                                const row = cell.closest('tr');
                                const contentCell = row.querySelector('td[data-name="edit_content"]');
                                if (contentCell) {
                                    let content = contentCell.getAttribute('data-content');
                                    try {
                                        let parsed = JSON.parse(content);
                                        if (parsed.properties && parsed.properties.Course_Date) {
                                            const selectedDate = new Date(newValue.split('T')[0]);
                                            selectedDate.setDate(selectedDate.getDate() + 1);
                                            const newDate = selectedDate.toISOString().split('T')[0];
                                            parsed.properties.Course_Date = formatDate(newDate);
                                            const updatedContent = JSON.stringify(parsed);
                                            // Send fetch to update content
                                            fetch(scheduledAjax.ajaxUrl, {
                                                method: "POST",
                                                credentials: "same-origin",
                                                headers: {
                                                    "Content-Type": "application/x-www-form-urlencoded",
                                                    "Accept": "application/json"
                                                },
                                                body: new URLSearchParams({
                                                    action: "update_content",
                                                    security: scheduledAjax.nonce,
                                                    id: id,
                                                    content: updatedContent,
                                                    name_info: 'edit_content',
                                                    subject_info: contentCell.getAttribute('data-subject') || ''
                                                }),
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    contentCell.setAttribute('data-content', updatedContent);
                                                    contentCell.innerHTML = data.data.content_preview || (contentCell.getAttribute('data-subject') + '<br>' + updatedContent);
                                                }
                                            })
                                            .catch(error => console.error("Content update failed:", error));
                                        }
                                    } catch (e) {
                                        // Not JSON
                                    }
                                }
                            }
                        } else {
                            alert(data.data.message || "Error updating content.");
                        }
                        closeModal();
                    })
                    .catch((error) => {
                        console.error("Update failed:", error);
                        const msg = (error && error.message) ? error.message : "An unexpected error occurred.";
                        // Show a clearer message if nonce or HTML error returned
                        alert(msg);
                        closeModal();
                    });
            });

            document.getElementById("cancel-btn").addEventListener("click", closeModal);
        });
    });

    document.getElementById("replaceContent").addEventListener("click", function () {
        replaceContentModal();
    });

    function replaceContentModal() {
        var modal = document.createElement("div");
        modal.classList.add("se-modal-overlay");
        modal.innerHTML = `
        <div class="se-modal">
            <div class="se-modal-header">
                <h2>Replace Content</h2>
            </div>
            <div class="se-modal-body">
                <div class="se-form-group">
                    <label class="se-label">Order / Product / Variation ID</label>
                    <div id="query-error" class="error-message"></div>
                    <input type="text" id="query" placeholder="Enter ID to filter by...">
                </div>
                <div class="se-form-group">
                    <label class="se-label">Find Content</label>
                    <div id="content-find-error" class="error-message"></div>
                    <textarea id="modal-input-find" placeholder="Text to find..."></textarea>
                </div>
                <div class="se-form-group">
                    <label class="se-label">Replace With</label>
                    <div id="content-replace-error" class="error-message"></div>
                    <textarea id="modal-input-replace" placeholder="Replacement text..."></textarea>
                </div>
            </div>
            <div class="se-modal-footer">
                <button id="cancel-btn" class="se-btn-secondary">Cancel</button>
                <button id="save-btn" class="se-btn-primary">Replace</button>
            </div>
        </div>
    `;
        document.body.appendChild(modal);

        function closeModalReplaceEmail() {
            document.body.removeChild(modal);
        }
        document.getElementById("save-btn").addEventListener("click", function () {
            var query = document.getElementById("query");
            var contentFind = document.getElementById("modal-input-find");
            var contentReplace = document.getElementById("modal-input-replace");
            var queryError = document.getElementById("query-error");
            var contentFindError = document.getElementById("content-find-error");
            var contentReplaceError = document.getElementById("content-replace-error");
            let isValid = true;
            query.classList.remove("error");
            contentFind.classList.remove("error");
            contentReplace.classList.remove("error");
            queryError.textContent = "";
            contentFindError.textContent = "";
            contentReplaceError.textContent = "";
            if (query.value.trim() === "") {
                query.classList.add("error");
                queryError.textContent = "Query is required.";
                isValid = false;
            }
            if (contentFind.value.trim() === "") {
                contentFind.classList.add("error");
                contentFindError.textContent = "Content Find is required.";
                isValid = false;
            }
            if (contentReplace.value.trim() === "") {
                contentReplace.classList.add("error");
                contentReplaceError.textContent = "Content Replace is required.";
                isValid = false;
            }
            if (!isValid) return;
            var formData = {
                content_find: contentFind.value,
                content_replace: contentReplace.value,
                query: query.value,
            };
            fetch(scheduledAjax.ajaxUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        action: "replace_email_content",
                        security: scheduledAjax.nonce,
                        info: JSON.stringify(formData)
                    }),
                })
                .then((response) => response.json())
                .then((data) => {
                    alert(data.data.updated_rows + " Content changed");
                    closeModalReplaceEmail();
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("An unexpected error occurred.");
                    closeModalReplaceEmail();
                });
        });
        document.getElementById("cancel-btn").addEventListener("click", closeModalReplaceEmail);
    }
    document.getElementById("addByOrderId").addEventListener("click", function () {
        addByOrderIdModal();
    });

    function addByOrderIdModal() {
        var modal = document.createElement("div");
        modal.classList.add("se-modal-overlay");
        modal.innerHTML = `
        <div class="se-modal" style="max-width: 400px;">
            <div class="se-modal-header">
                <h2>Add Email by Order ID</h2>
            </div>
            <div class="se-modal-body">
                <div class="se-form-group">
                    <label class="se-label">Order ID</label>
                    <input type="text" id="order_id" placeholder="Enter order ID..."/>
                    <div id="error-message" class="error-message"></div>
                </div>
            </div>
            <div class="se-modal-footer">
                <button id="cancel-btn" class="se-btn-secondary">Cancel</button>
                <button id="save-btn" class="se-btn-primary">Add Emails</button>
            </div>
        </div>`;
        document.body.appendChild(modal);
        var orderIdInput = document.getElementById("order_id");
        var errorMessage = document.getElementById("error-message");
        document.getElementById("save-btn").addEventListener("click", function () {
            var orderId = orderIdInput.value;
            if (!orderId) {
                errorMessage.textContent = "Order ID is required.";
                return;
            }
            fetch(scheduledAjax.ajaxUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        action: "add_email_by_order_id",
                        security: scheduledAjax.nonce,
                        order_id: orderId,
                    }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.body.removeChild(modal);
                    } else {
                        alert("Error adding Email by Order ID.");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("An unexpected error occurred.");
                });
        });
        document.getElementById("cancel-btn").addEventListener("click", function () {
            document.body.removeChild(modal);
        });
    }
    document.getElementById("openModalBtn").addEventListener("click", function () {
        openModal();
    });

    function openModal() {
        var modal = document.createElement("div");
        modal.classList.add("se-modal-overlay");
        var newEmailEditorId = 'se-new-email-editor-' + Date.now();
        modal.innerHTML = `
        <div class="se-modal" style="max-width: 700px;">
            <div class="se-modal-header">
                <h2>Create New Email</h2>
            </div>
            <div class="se-modal-body">
                <div class="se-form-group">
                    <label class="se-label">Email Address *</label>
                    <input type="email" id="email" placeholder="recipient@example.com">
                    <div id="email-error" class="error-message"></div>
                </div>
                <div class="se-form-group">
                    <label class="se-label">Subject *</label>
                    <input type="text" id="subject" placeholder="Email subject...">
                    <div id="subject-error" class="error-message"></div>
                </div>
                <div class="se-form-group">
                    <label class="se-label">Scheduled Time *</label>
                    <input type="datetime-local" id="sent_time">
                    <div id="sent-time-error" class="error-message"></div>
                </div>
                <div class="se-form-group">
                    <label class="se-label">Content</label>
                    <textarea id="${newEmailEditorId}" class="se-tinymce-textarea"></textarea>
                    <div id="content-error" class="error-message"></div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <div class="se-form-group" style="flex: 1;">
                        <label class="se-label">Order ID</label>
                        <input type="text" id="order_id" placeholder="Optional">
                    </div>
                    <div class="se-form-group" style="flex: 1;">
                        <label class="se-label">Product ID</label>
                        <input type="text" id="product_id" placeholder="Optional">
                    </div>
                    <div class="se-form-group" style="flex: 1;">
                        <label class="se-label">Variation ID</label>
                        <input type="text" id="variation_id" placeholder="Optional">
                    </div>
                </div>
            </div>
            <div class="se-modal-footer">
                <button id="cancel-btn" class="se-btn-secondary">Cancel</button>
                <button id="save-btn" class="se-btn-primary">Create Email</button>
            </div>
        </div>
    `;
        document.body.appendChild(modal);

        // Initialize TinyMCE for new email content
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.initialize(newEmailEditorId, {
                tinymce: {
                    wpautop: true,
                    plugins: 'charmap colorpicker hr lists paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern',
                    toolbar1: 'formatselect bold italic underline | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | wp_adv',
                    toolbar2: 'strikethrough hr forecolor | pastetext removeformat | charmap | outdent indent | undo redo | wp_help',
                    height: 300,
                    menubar: false,
                    relative_urls: false,
                    remove_script_host: false,
                    convert_urls: false
                },
                quicktags: true,
                mediaButtons: true
            });
        }

        function closeModalAddEmail() {
            // Destroy TinyMCE instance before removing modal
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.remove(newEmailEditorId);
            }
            document.body.removeChild(modal);
        }
        document.getElementById("save-btn").addEventListener("click", function () {
            var subject = document.getElementById("subject");
            var email = document.getElementById("email");
            var contentTextarea = document.getElementById(newEmailEditorId);
            var sentTime = document.getElementById("sent_time");
            var emailError = document.getElementById("email-error");
            var subjectError = document.getElementById("subject-error");
            var contentError = document.getElementById("content-error");
            var sentTimeError = document.getElementById("sent-time-error");
            let isValid = true;
            email.classList.remove("error");
            subject.classList.remove("error");
            sentTime.classList.remove("error");
            emailError.textContent = "";
            subjectError.textContent = "";
            contentError.textContent = "";
            sentTimeError.textContent = "";
            if (email.value.trim() === "") {
                email.classList.add("error");
                emailError.textContent = "Email is required.";
                isValid = false;
            }
            if (subject.value.trim() === "") {
                subject.classList.add("error");
                subjectError.textContent = "Subject is required.";
                isValid = false;
            }
            if (sentTime.value.trim() === "") {
                sentTime.classList.add("error");
                sentTimeError.textContent = "Datetime is required.";
                isValid = false;
            }
            if (!isValid) return;

            // Get content from TinyMCE
            var newValue = '';
            if (typeof wp !== 'undefined' && wp.editor && typeof tinymce !== 'undefined' && tinymce.get(newEmailEditorId)) {
                tinymce.get(newEmailEditorId).save();
                newValue = contentTextarea.value;
            } else {
                newValue = contentTextarea.value;
            }

            var formData = {
                subject: subject.value,
                content: newValue,
                email: document.getElementById("email").value,
                sent_time: sentTime.value,
                order_id: document.getElementById("order_id").value,
                product_id: document.getElementById("product_id").value,
                variation_id: document.getElementById("variation_id").value,
            };
            fetch(scheduledAjax.ajaxUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        action: "add_email_content",
                        security: scheduledAjax.nonce,
                        info: JSON.stringify(formData)
                    }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        alert("Error creating new Email.");
                    }
                    closeModalAddEmail();
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("An unexpected error occurred.");
                    closeModalAddEmail();
                });
        });
        document.getElementById("cancel-btn").addEventListener("click", closeModalAddEmail);
    }
    jQuery(document).ready(function ($) {
        $('#course').change(function () {
            var course_id = $(this).val().split('~')[0];
            $('#date > option').hide();
            $('#date > option').filter('[data-course=' + course_id + ']').show();
            $('#date > option:first').show().prop('selected', true);
        });
        $('#date').click(function () {
            var course_id = $('#course').val().split('~')[0];
            if (course_id) {
                $('#date > option').hide();
                $('#date > option').filter('[data-course=' + course_id + ']').show();
                $('#date > option:first').show();
            }
        });
    });
});
