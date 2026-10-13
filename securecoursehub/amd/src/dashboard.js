/**
 * Secure Course Hub teacher AJAX interface.
 *
 * @module local_securecoursehub/dashboard
 */

/**
 * Show a visible success or error message.
 *
 * @param {HTMLElement} container
 * @param {string} message
 * @param {string} type
 */
const showMessage = (container, message, type) => {
    container.textContent = message;
    container.className = `alert alert-${type}`;
    container.hidden = false;
    container.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
};

/**
 * Initialize the teacher request update forms.
 *
 * @param {Object} config
 */
export const init = (config) => {
    const messageBox = document.querySelector(
        '#sch-ajax-message'
    );

    if (!messageBox) {
        return;
    }

    document.addEventListener('submit', async(event) => {
        const form = event.target.closest(
            '.sch-teacher-form'
        );

        if (!form) {
            return;
        }

        event.preventDefault();

        const row = form.closest('tr');
        const button = form.querySelector(
            'button[type="submit"]'
        );
        const requestIdInput = form.querySelector(
            '[name="requestid"]'
        );

        const statusSelect = row.querySelector(
            '[name="status"]'
        );

        const responseField = row.querySelector(
            '[name="response"]'
        );

        const statusLabel = row.querySelector(
            '.sch-status-label'
        );

        const modifiedCell = row.querySelector(
            '.sch-modified'
        );

        const originalButtonText = button.textContent;

        button.disabled = true;
        button.textContent = config.savingMessage;

        try {
            const serverResponse = await fetch(
                config.endpoint,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        action:
                            'update_teacher_request',
                        courseid: config.courseId,
                        id: Number(
                            requestIdInput.value
                        ),
                        status: statusSelect.value,
                        response: responseField.value,
                        sesskey: M.cfg.sesskey,
                    }),
                }
            );

            let result;

            try {
                result = await serverResponse.json();
            } catch (error) {
                throw new Error(
                    config.invalidResponseMessage
                );
            }

            if (
                !serverResponse.ok ||
                !result.success
            ) {
                throw new Error(
                    result.error ||
                    config.failureMessage
                );
            }

            /*
             * textContent is used instead of innerHTML so that
             * untrusted text cannot execute as markup.
             */
            statusLabel.textContent =
                result.request.statuslabel;

            modifiedCell.textContent =
                result.request.modified;

            showMessage(
                messageBox,
                result.message ||
                    config.successMessage,
                'success'
            );
        } catch (error) {
            showMessage(
                messageBox,
                error.message ||
                    config.failureMessage,
                'danger'
            );
        } finally {
            button.disabled = false;
            button.textContent =
                originalButtonText;
        }
    });
};