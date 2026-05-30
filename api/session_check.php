<?php
// ============================================================
// GrooveKut — Session / Guest Gate Check
// File: api/session_check.php
// Returns whether user can play, increments guest play count
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';
header('Content-Type: application/json');

if (is_logged_in()) {
    echo json_encode(['success' => true, 'logged_in' => true]);
    exit;
}

// Guest — track plays in session
$plays = $_SESSION['guest_plays'] ?? 0;

if ($plays >= 1) {
    // Gate triggered
    echo json_encode([
        'success'    => false,
        'logged_in'  => false,
        'gate'       => true,
        'message'    => 'Sign up free to keep listening.'
    ]);
    exit;
}

$_SESSION['guest_plays'] = $plays + 1;
echo json_encode(['success' => true, 'logged_in' => false, 'gate' => false]);
