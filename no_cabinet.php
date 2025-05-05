<?php
// no_cabinet.php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Brak Apteki</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-5">
    <h1>Witaj!</h1>
    <p>Nie masz jeszcze przypisanej Apteki. Wybierz jedną z opcji:</p>
    <a href="create_cabinet.php" class="btn btn-primary">Utwórz Aptekę</a>
    <a href="join_cabinet.php" class="btn btn-secondary">Dołącz do Apteki</a>
  </div>
</body>
</html>
