<?php
// create_cabinet.php
session_start();
require 'config.php';

// 1) Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2) Handle the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['cabinet_name'] ?? '');
    if ($name === '') {
        $error = "Podaj nazwę apteczki.";
    } else {
        try {
            // Start transaction so both inserts happen together
            $pdo->beginTransaction();

            // a) Create the cabinet
            $stmt = $pdo->prepare("INSERT INTO cabinets (name) VALUES (?)");
            $stmt->execute([$name]);
            $newCabinetId = $pdo->lastInsertId();

            // b) Grant the creator full rights
            $stmt2 = $pdo->prepare("
                INSERT INTO cabinet_users
                  (cabinet_id, user_id, can_add_med, can_usage, can_reports, is_admin)
                VALUES (?, ?, 1, 1, 1, 1)
            ");
            $stmt2->execute([
                $newCabinetId,
                $_SESSION['user_id']
            ]);

            $pdo->commit();

            // c) Remember this as their active cabinet
            $_SESSION['cabinet_id'] = $newCabinetId;

            // d) Go to dashboard
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Błąd podczas tworzenia apteczki: " . $e->getMessage();
        }
    }
}

// 3) Render the form (no header.php here, since that would redirect us back)
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Utwórz Apteczkę</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
  <h1>Utwórz nową apteczkę</h1>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="form-group">
      <label for="cabinet_name">Nazwa apteczki:</label>
      <input
        type="text"
        id="cabinet_name"
        name="cabinet_name"
        class="form-control"
        required
        value="<?= isset($name) ? htmlspecialchars($name) : '' ?>"
      >
    </div>
    <button type="submit" class="btn btn-primary">Utwórz</button>
    <a href="no_cabinet.php" class="btn btn-secondary ml-2">Anuluj</a>
  </form>
</div>
</body>
</html>
