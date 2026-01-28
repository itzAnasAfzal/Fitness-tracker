<?php
// app/config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION["user"]);
}

function require_login(): void {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// function require_role(string $role): void {
//     require_login();
//     if ($_SESSION["user"]["role"] !== $role) {
//         http_response_code(403);
//         echo "403 Forbidden (role required: " . htmlspecialchars($role) . ")";
//         exit;
//     }
// }

function require_role($role) {
    if (!is_logged_in() || $_SESSION["user"]["role"] !== $role) {
        // redirect to public login page instead of non-existent admin/login.php
        header("Location: /fitness-tracker/public/login.php");
        exit;
    }
}
