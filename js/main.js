/**
 * Placement Preparation Portal - Main JavaScript Utility File
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Auto-dismiss Bootstrap alert messages after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // 2. Initialize Bootstrap Tooltips if any exist
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Utility: Copy text to clipboard
 */
function copyToClipboard(text, successMsg) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            alert(successMsg || "Copied to clipboard!");
        });
    }
}
