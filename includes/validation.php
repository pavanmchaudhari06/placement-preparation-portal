<?php
/**
 * validation.php – helper functions for input validation & sanitisation.
 * All functions return an empty string on success or an error message.
 */

// Regular expression patterns
const REGEX_NAME   = '/^[A-Za-z]+(?:\s[A-Za-z]+){0,29}$/'; // 1‑30 letters, single spaces
const REGEX_PHONE  = '/^\d{10}$/';
const REGEX_PASS   = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
const REGEX_EMAIL  = '/^[^@\s]{1,50}@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/';

/** Validate a full name (3‑30 chars, letters + single spaces). */
function validate_name(string $name): string {
    $name = trim($name);
    return preg_match(REGEX_NAME, $name) ? '' : 'Name can contain only letters and single spaces (3‑30 characters).';
}
/** Validate e‑mail format and uniqueness (max 50 chars). */
function validate_email(string $email, mysqli $conn, ?int $ignoreId = null): string {
    $email = trim($email);
    if (!preg_match(REGEX_EMAIL, $email)) {
        return 'Enter a valid e‑mail address (max 50 characters).';
    }
    // uniqueness check using prepared stmt
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    if (!$stmt) return 'Database error.';
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($foundId);
    $exists = $stmt->fetch();
    $stmt->close();
    if ($exists && ($ignoreId === null || $foundId != $ignoreId)) {
        return 'E‑mail already in use.';
    }
    return '';
}
/** Validate password strength. */
function validate_password(string $pwd): string {
    return preg_match(REGEX_PASS, $pwd) ? '' : 'Password must be at least 8 characters, contain upper‑case, lower‑case, number and special character.';
}
/** Validate phone number (exactly 10 digits). */
function validate_phone(string $phone): string {
    return preg_match(REGEX_PHONE, $phone) ? '' : 'Phone number must be exactly 10 digits.';
}
/** Sanitize free‑text fields: strip HTML tags and enforce max length. */
function sanitize_text(string $txt, int $maxLen): string {
    $txt = strip_tags($txt);
    $txt = trim($txt);
    return substr($txt, 0, $maxLen);
}
?>
