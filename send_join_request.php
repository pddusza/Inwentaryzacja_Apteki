<?php
// send_join_request.php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cabinet_id'])) {
    $cabinet_id = intval($_POST['cabinet_id']);

    // Sprawdź, czy już nie wysłano żądania
    $stmt = $pdo->prepare("
        SELECT id
        FROM cabinet_join_requests
        WHERE cabinet_id = ? AND requester_user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$cabinet_id, $user_id]);

    if ($stmt->fetch()) {
        $_SESSION['flash_message'] = "Masz już wysłaną prośbę o dołączenie do tej apteczki.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO cabinet_join_requests (cabinet_id, requester_user_id)
            VALUES (?, ?)
        ");
        if ($stmt->execute([$cabinet_id, $user_id])) {
            $_SESSION['flash_message'] = "Prośba o dołączenie została wysłana. Czekaj na akceptację.";
        } else {
            $_SESSION['flash_message'] = "Wystąpił błąd podczas wysyłania prośby.";
        }
    }
} else {
    $_SESSION['flash_message'] = "Nieprawidłowe żądanie.";
}

// Redirect back to the form page
header("Location: join_cabinet.php");
exit;
