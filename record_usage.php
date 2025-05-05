<?php
// record_usage.php
require 'header.php';  // session_start(), config, sets $cabinet_id and $userPerms

// 1) Permission check
if (empty($userPerms['can_usage'])) {
    die("Nie masz uprawnień do rejestrowania zużyć w tej apteczce.");
}

// 2) Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cabMedId = (int)($_POST['cabinet_medication_id'] ?? 0);
    $qty      = (int)($_POST['quantity'] ?? 0);
    $type     = in_array($_POST['movement_type'], ['przychod','rozchod','utylizacja'])
                ? $_POST['movement_type']
                : 'rozchod';

    // Jeśli to utylizacja, wymuś sprzedaż = 0
    if ($type === 'utylizacja') {
        $price = 0;
    } else {
        $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
    }

    // (opcjonalnie) transakcja i walidacja ilości...
    $ins = $pdo->prepare("INSERT INTO movements (cabinet_medication_id, movement_type, quantity, sale_price, action_user_id) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([ $cabMedId, $type, $qty, $price, $_SESSION['user_id']]);

    header("Location: record_usage.php?success=1");
    exit;
}

// 3) Fetch current meds in this cabinet
$stmt = $pdo->prepare("SELECT cm.id, m.name, cm.quantity FROM cabinet_medications AS cm JOIN medications AS m ON cm.medication_id = m.id WHERE cm.cabinet_id = ?");
$stmt->execute([$cabinet_id]);
$meds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Rejestracja Zużyć</title>
  <link rel="stylesheet" href="path/to/bootstrap.css">
</head>
<body>
  <!-- header.php already rendered your navbar and opened <div class="container"> -->
  <div class="container mt-4">
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success">Zapisano ruch magazynowy.</div>
    <?php endif; ?>

    <h2>Rejestracja Zużycia / Przychodu / Utylizacji</h2>
    <form method="post">
      <div class="form-group">
        <label for="cabinet_medication_id">Lek:</label>
        <select name="cabinet_medication_id" id="cabinet_medication_id" class="form-control" required>
          <?php foreach($meds as $m): ?>
            <option value="<?= $m['id'] ?>">
              <?= htmlspecialchars($m['name']) ?> (aktualnie <?= $m['quantity'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="movement_type">Typ ruchu:</label>
        <select name="movement_type" id="movement_type" class="form-control" required>
          <option value="rozchod">Zużycie (rozchód)</option>
          <option value="przychod">Przychód</option>
          <option value="utylizacja">Utylizacja</option>
        </select>
      </div>

      <div class="form-group">
        <label for="quantity">Ilość:</label>
        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
      </div>

      <div class="form-group" id="price_group">
        <label for="price">Cena sprzedaży:</label>
        <input type="number" name="price" id="price" class="form-control" step="0.01">
      </div>

      <button type="submit" class="btn btn-primary">Zapisz</button>
    </form>
  </div>

  <!-- script to hide/show sale price -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script>
    $(function(){
      function togglePrice() {
        if ($('#movement_type').val() === 'utylizacja') {
          $('#price_group').hide();
        } else {
          $('#price_group').show();
        }
      }
      togglePrice();
      $('#movement_type').on('change', togglePrice);
    });
  </script>
</body>
</html>
