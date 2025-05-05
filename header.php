<?php
// header.php
session_start();
require 'config.php';

// What page am I on?
$current = basename($_SERVER['PHP_SELF']);

// These pages must be allowed even if there's no $_SESSION['cabinet_id'] yet:
$noCabinetPages = [
    'login.php',
    'register.php',
    'no_cabinet.php',
    'create_cabinet.php',
    'join_cabinet.php'
];

// 1) You must be logged in (unless you’re literally on login or register)
if (!isset($_SESSION['user_id']) &&
    !in_array($current, ['login.php','register.php'], true)) {
    header("Location: login.php");
    exit;
}

// 2) If you’re *not* on one of the “no‐cabinet” pages, enforce having a cabinet
if (!in_array($current, $noCabinetPages, true)) {
    if (empty($_SESSION['cabinet_id'])) {
        header("Location: no_cabinet.php");
        exit;
    }
}

$cabinet_id = $_SESSION['cabinet_id'];
$user_id    = $_SESSION['user_id'];

// Fetch permissions for this user + cabinet
$stmt = $pdo->prepare("SELECT can_add_med, can_usage, can_reports, is_admin FROM cabinet_users WHERE user_id = ? AND cabinet_id = ? LIMIT 1
");
$stmt->execute([ $user_id, $cabinet_id ]);
$userPerms = $stmt->fetch();

$can_add_med = $userPerms['can_add_med'];
$can_usage   = $userPerms['can_usage'];
$can_reports = $userPerms['can_reports'];
$is_admin    = $userPerms['is_admin'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Apteka Cyanowa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="dashboard.php">Apteka Cyanowa</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mr-auto">
          <li class="nav-item">
              <a class="nav-link" href="dashboard.php">Dashboard</a>
          </li>
          <?php if (isset($cabinet_id)): ?>
              <?php if ($can_add_med || $is_admin): ?>
                  <li class="nav-item"><a class="nav-link" href="add_medication.php">Dodaj Lek</a></li>
              <?php endif; ?>
              <?php if ($can_usage || $is_admin): ?>
                  <li class="nav-item"><a class="nav-link" href="record_usage.php">Zużycie/Utylizacja</a></li>
              <?php endif; ?>
              <?php if ($can_reports || $is_admin): ?>
                  <li class="nav-item"><a class="nav-link" href="reports_index.php">Raporty</a></li>
              <?php endif; ?>
              <?php if ($is_admin): ?>
                  <li class="nav-item"><a class="nav-link" href="assign_role.php">Zarządzaj Uprawnieniami</a></li>
                  <li class="nav-item"><a class="nav-link" href="join_requests.php">Prośby Dołączenia</a></li>
              <?php endif; ?>
          <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="create_cabinet.php">Utwórz Apteczkę</a></li>
              <li class="nav-item"><a class="nav-link" href="join_cabinet.php">Dołącz do Apteczki</a></li>
          <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
          <?php if (isset($cabinet_id)): ?>
              <li class="nav-item">
                  <a class="nav-link btn btn-secondary text-white ml-2" href="switch_cabinet.php">Zmień Lokalizację</a>
              </li>
          <?php endif; ?>
          <li class="nav-item">
              <a class="nav-link btn btn-danger text-white ml-2" href="logout.php">Wyloguj</a>
          </li>
      </ul>
  </div>
</nav>

<div class="container mt-4">
