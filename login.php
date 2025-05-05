<?php
// login.php
session_start();
require 'config.php';

// Clear out any leftover cabinet selection
unset($_SESSION['cabinet_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        // 1) remember who you are
        $_SESSION['user_id'] = $user['id'];

        // 2) pick a default cabinet (if any) and stash it
        $stmt2 = $pdo->prepare("
            SELECT cabinet_id
            FROM cabinet_users
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt2->execute([$user['id']]);
        if ($row = $stmt2->fetch()) {
            $_SESSION['cabinet_id'] = $row['cabinet_id'];
            header("Location: dashboard.php");
            exit;
        } else {
            // no cabinet yet → send to no_cabinet.php to create or join
            header("Location: no_cabinet.php");
            exit;
        }
    } else {
        $error = "Nieprawidłowe dane logowania!";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Logowanie</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
  <h1>Zaloguj się</h1>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="form-group">
      <label>Username:</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="form-group">
      <label>Password:</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <input type="submit" value="Zaloguj" class="btn btn-primary">
  </form>
  <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
</div>
</body>
</html>
