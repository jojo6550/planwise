<?php
/**
 * Email Share Modal Component
 * Displays a modal for sharing lesson plans via email
 */

// Check if user is authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$lessonId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<!-- Email Share Modal -->
<div class="modal fade" id="emailShareModal" tabindex="-1" aria-labelledby="emailShareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailShareModalLabel">Share Lesson Plan via Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emailShareForm">
                <div class="modal-body">
                    <input type="hidden" id="lessonId" value="<?php echo htmlspecialchars($lessonId); ?>">

                    <!-- Single Recipient Tab -->
                    <ul class="nav nav-tabs mb-3" id="emailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single-recipient" type="button" role="tab" aria-controls="single-recipient" aria-selected="true">Single Recipient</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="multiple-tab" data-bs-toggle="tab" data-bs-target="#multiple-recipients" type="button" role="tab" aria-controls="multiple-recipients" aria-selected="false">Multiple Recipients</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="emailTabContent">
                        <!-- Single Recipient Tab -->
                        <div class="tab-pane fade show active" id="single-recipient" role="tabpanel" aria-labelledby="single-tab">
                            <div class="mb-3">
                                <label for="recipientEmail" class="form-label">Recipient Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="recipientEmail" name="recipient_email" placeholder="colleague@example.com" required>
                                <small class="form-text text-muted">Enter the email address of the person you want to share with.</small>
                            </div>

                            <div class="mb-3">
                                <label for="recipientName" class="form-label">Recipient Name</label>
                                <input type="text" class="form-control" id="recipientName" name="recipient_name" placeholder="e.g., John Doe">
                                <small class="form-text text-muted">Optional: The recipient's name for a personalized greeting.</small>
                            </div>
                        </div>

                        <!-- Multiple Recipients Tab -->
                        <div class="tab-pane fade" id="multiple-recipients" role="tabpanel" aria-labelledby="multiple-tab">
                            <div class="mb-3">
                                <label for="recipientsList" class="form-label">Recipients <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="recipientsList" name="recipients_list" rows="4" placeholder="Enter one recipient per line&#10;Email,Name&#10;john@example.com,John Doe&#10;jane@example.com,Jane Smith"></textarea>
                                <small class="form-text text-muted">Enter recipients in the format: email@example.com or email@example.com,Full Name (one per line)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label for="shareMessage" class="form-label">Personal Message (Optional)</label>
                        <textarea class="form-control" id="shareMessage" name="message" rows="3" placeholder="Add a personal message to include in the email..."></textarea>
                        <small class="form-text text-muted">Max 500 characters</small>
                    </div>

                    <!-- Error Alert -->
                    <div id="errorAlert" class="alert alert-danger alert-dismissible fade hide" role="alert" style="display: none;">
                        <span id="errorMessage"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <!-- Success Alert -->
                    <div id="successAlert" class="alert alert-success alert-dismissible fade hide" role="alert" style="display: none;">
                        <span id="successMessage"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sendBtn">
                        <span id="sendBtnText">Send Email</span>
                        <span id="sendBtnSpinner" class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('emailShareForm');
    const lessonId = document.getElementById('lessonId').value;
    const sendBtn = document.getElementById('sendBtn');
    const sendBtnText = document.getElementById('sendBtnText');
    const sendBtnSpinner = document.getElementById('sendBtnSpinner');
    const errorAlert = document.getElementById('errorAlert');
    const successAlert = document.getElementById('successAlert');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Determine which tab is active
        const activeTab = document.querySelector('.nav-link.active');
        const isSingleRecipient = activeTab.id === 'single-tab';

        try {
            // Disable button and show spinner
            sendBtn.disabled = true;
            sendBtnText.style.display = 'none';
            sendBtnSpinner.style.display = 'inline-block';

            let requestData;

            if (isSingleRecipient) {
                // Single recipient request
                const email = document.getElementById('recipientEmail').value.trim();
                const name = document.getElementById('recipientName').value.trim();
                const message = document.getElementById('shareMessage').value.trim();

                if (!email) {
                    throw new Error('Recipient email is required');
                }

                if (!isValidEmail(email)) {
                    throw new Error('Invalid email address');
                }

                requestData = {
                    lesson_id: parseInt(lessonId),
                    recipient_email: email,
                    recipient_name: name || 'Colleague',
                    message: message
                };

                // Send single email
                const response = await fetch('/planwise/controllers/LessonPlanController.php?action=emailLesson', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to send email');
                }

                showSuccess('Lesson plan sent successfully to ' + email);
                form.reset();

            } else {
                // Multiple recipients request
                const recipientsList = document.getElementById('recipientsList').value.trim();
                const message = document.getElementById('shareMessage').value.trim();

                if (!recipientsList) {
                    throw new Error('Please enter at least one recipient');
                }

                // Parse recipients
                const recipients = [];
                const lines = recipientsList.split('\n');

                for (const line of lines) {
                    const parts = line.split(',');
                    const email = parts[0].trim();
                    const name = parts[1] ? parts[1].trim() : '';

                    if (email) {
                        if (!isValidEmail(email)) {
                            throw new Error('Invalid email: ' + email);
                        }
                        recipients.push({
                            email: email,
                            name: name || 'Colleague'
                        });
                    }
                }

                if (recipients.length === 0) {
                    throw new Error('No valid recipients found');
                }

                requestData = {
                    lesson_id: parseInt(lessonId),
                    recipients: recipients,
                    message: message
                };

                // Send to multiple recipients
                const response = await fetch('/planwise/controllers/LessonPlanController.php?action=emailLessonMultiple', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to send emails');
                }

                showSuccess(`Lesson plan sent successfully to ${result.success_count} recipient(s)` + 
                           (result.failure_count > 0 ? ` (${result.failure_count} failed)` : ''));
                form.reset();
            }

        } catch (error) {
            console.error('Error:', error);
            showError(error.message);
        } finally {
            // Re-enable button and hide spinner
            sendBtn.disabled = false;
            sendBtnText.style.display = 'inline';
            sendBtnSpinner.style.display = 'none';
        }
    });

    function showError(message) {
        errorAlert.style.display = 'block';
        document.getElementById('errorMessage').textContent = message;
        successAlert.style.display = 'none';
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showSuccess(message) {
        successAlert.style.display = 'block';
        document.getElementById('successMessage').textContent = message;
        errorAlert.style.display = 'none';
        successAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>
