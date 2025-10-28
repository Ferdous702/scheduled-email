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
            modal.classList.add("edit-modal");

            let editorHtml;

            if (name === 'scheduled_time') {
                // For date editing, provide a datetime-local input
                const formattedDate = content.replace(' ', 'T').substring(0, 16);
                editorHtml = `<input type="datetime-local" id="modal-input" value="${formattedDate}" style="width: 100%; padding: 8px;">`;
            } else {
                // For other fields, use the existing rich/text editor
                const subjectInput = name === 'edit_content' ?
                    `<input style="width:100%;" type="text" id="email-subject" value="${subject}"/>` :
                    '';
                editorHtml = `
                    ${subjectInput}
                    <button id="toggle-mode">Switch to Text View</button>
                    <textarea id="modal-input-text" class="edit-textarea" style="display:none;">${content}</textarea>
                    <div id="html-editor" contenteditable="true" class="edit-html-view">${content}</div>
                `;
            }

            const fieldName = name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            modal.innerHTML = `
                <div class="edit-modal-content">
                    <h2>Edit ${fieldName}</h2>
                    ${editorHtml}
                    <div class="modal-actions">
                        <button id="save-btn">Save</button>
                        <button id="cancel-btn">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Setup event listeners only if the text/html editor exists
            if (name !== 'scheduled_time') {
                const textArea = document.getElementById("modal-input-text");
                const htmlEditor = document.getElementById("html-editor");
                const toggleBtn = document.getElementById("toggle-mode");

                textArea.style.display = "none";
                htmlEditor.style.display = "block";
                toggleBtn.textContent = "Switch to Text View";

                toggleBtn.addEventListener("click", function () {
                    const isHtmlMode = textArea.style.display === 'none';
                    if (isHtmlMode) {
                        htmlEditor.style.display = "none";
                        textArea.style.display = "block";
                        textArea.value = htmlEditor.innerHTML;
                        toggleBtn.textContent = "Switch to HTML View";
                    } else {
                        textArea.style.display = "none";
                        htmlEditor.style.display = "block";
                        htmlEditor.innerHTML = textArea.value;
                        toggleBtn.textContent = "Switch to Text View";
                    }
                });
            }

            function closeModal() {
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
                    const textArea = document.getElementById("modal-input-text");
                    const isHtmlMode = textArea.style.display === 'none';
                    newValue = isHtmlMode ? document.getElementById("html-editor").innerHTML : textArea.value;
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
        modal.classList.add("custom-modal");
        modal.innerHTML = `
        <div class="custom-modal-content">
            <h2>Replace Content</h2>
            <div id="query-error" class="error-message"></div>
            <input type="text" id="query" placeholder="Order / Product / Variation">
            <div id="content-find-error" class="error-message"></div>
            <textarea id="modal-input-find" placeholder="Find Content"></textarea>
            <div id="content-replace-error" class="error-message"></div>
            <textarea id="modal-input-replace" placeholder="Replace Content"></textarea>
            <div class="modal-actions">
                <button id="save-btn">Submit</button>
                <button id="cancel-btn">Cancel</button>
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
        modal.classList.add("custom-modal");
        modal.innerHTML = `
        <div class="custom-modal-content">
            <h2>Add Email by Order ID</h2>
            <input type="text" id="order_id" placeholder="Order ID"/>
            <div id="error-message" class="error-message"></div>
            <div class="modal-actions">
                <button id="save-btn">Submit</button>
                <button id="cancel-btn">Cancel</button>
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
        modal.classList.add("custom-modal");
        modal.innerHTML = `
        <div class="custom-modal-content">
            <h2>New Email</h2>
            <div id="email-error" class="error-message"></div>
            <input type="email" id="email" placeholder="Email">
            <div id="subject-error" class="error-message"></div>
            <input type="text" id="subject" placeholder="Subject">
            <div id="content-error" class="error-message"></div>
            <div id="sent-time-error" class="error-message"></div>
            <input type="datetime-local" id="sent_time">
            <button id="toggle-mode">Switch to Text View</button>
            <textarea id="modal-input" class="edit-textarea"></textarea>
            <div id="html-editor" contenteditable="true" class="edit-html-view"></div>
            <input type="text" id="order_id" placeholder="Order ID">
            <input type="text" id="product_id" placeholder="Product ID">
            <input type="text" id="variation_id" placeholder="Variation ID">
            <div class="modal-actions">
                <button id="save-btn">Submit</button>
                <button id="cancel-btn">Cancel</button>
            </div>
        </div>
    `;
        document.body.appendChild(modal);
        var textArea = document.getElementById("modal-input");
        var htmlEditor = document.getElementById("html-editor");
        var toggleBtn = document.getElementById("toggle-mode");
        textArea.style.display = "none";
        htmlEditor.style.display = "block";
        toggleBtn.textContent = "Switch to Text View";
        var isHtmlMode = true;
        toggleBtn.addEventListener("click", function () {
            isHtmlMode = !isHtmlMode;
            if (isHtmlMode) {
                textArea.style.display = "none";
                htmlEditor.style.display = "block";
                htmlEditor.innerHTML = textArea.value;
                toggleBtn.textContent = "Switch to Text View";
            } else {
                htmlEditor.style.display = "none";
                textArea.style.display = "block";
                textArea.value = htmlEditor.innerHTML;
                toggleBtn.textContent = "Switch to HTML View";
            }
        });

        function closeModalAddEmail() {
            document.body.removeChild(modal);
        }
        document.getElementById("save-btn").addEventListener("click", function () {
            var subject = document.getElementById("subject");
            var email = document.getElementById("email");
            var content = document.getElementById("modal-input");
            var sentTime = document.getElementById("sent_time");
            var emailError = document.getElementById("email-error");
            var subjectError = document.getElementById("subject-error");
            var contentError = document.getElementById("content-error");
            var sentTimeError = document.getElementById("sent-time-error");
            let isValid = true;
            email.classList.remove("error");
            subject.classList.remove("error");
            content.classList.remove("error");
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
            if (content.value.trim() === "" && isHtmlMode == false) {
                content.classList.add("error");
                contentError.textContent = "Content is required.";
                isValid = false;
            }
            if (sentTime.value.trim() === "") {
                sentTime.classList.add("error");
                sentTimeError.textContent = "Datetime is required.";
                isValid = false;
            }
            if (!isValid) return;
            var newValue = isHtmlMode ? htmlEditor.innerHTML : textArea.value;
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
