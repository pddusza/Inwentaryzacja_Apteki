<?php
// record_usage.php
require 'header.php';  // session_start(), config, sets $cabinet_id and $userPerms

// 1) Permission check
if (empty($userPerms['can_usage'])) {
    die("Nie masz uprawnień do rejestrowania operacji w tej apteczce.");
}

$error   = '';
$success = '';

// 2) Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cabMedId = (int)($_POST['cabinet_medication_id'] ?? 0);
    $qty      = (int)($_POST['quantity'] ?? 0);
    $type     = in_array($_POST['movement_type'], ['rozchod','utylizacja']) ? $_POST['movement_type'] : 'rozchod';
    $price    = $type === 'utylizacja' ? 0 : (isset($_POST['price']) ? (float)$_POST['price'] : null);

    try {
        $pdo->beginTransaction();

        // Lock + fetch current quantity
        $lock = $pdo->prepare("SELECT quantity FROM cabinet_medications WHERE id = ? AND cabinet_id = ? FOR UPDATE ");
        $lock->execute([$cabMedId, $cabinet_id]);
        $med = $lock->fetch();
        if (!$med) {
            throw new Exception("Wybrany lek nie istnieje w tej apteczce.");
        }

        $newQty = $med['quantity'] - $qty;
        if ($newQty < 0) {
            throw new Exception("Nie ma wystarczającej ilości leku na stanie.");
        }

        // Always update quantity
        $upd = $pdo->prepare("UPDATE cabinet_medications SET quantity = ? WHERE id = ? ");
        $upd->execute([$newQty, $cabMedId]);

        // Insert movement record
        $ins = $pdo->prepare("INSERT INTO movements (cabinet_medication_id, movement_type, quantity, sale_price, action_user_id) VALUES (?, ?, ?, ?, ?) ");
        $ins->execute([$cabMedId, $type, $qty, $price, $_SESSION['user_id'] ]);

        $pdo->commit();
        $success = "Operacja została zarejestrowana.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Błąd: " . $e->getMessage();
    }
}

// 3) Fetch in-stock meds for autocomplete, including expiration_date
$stmt = $pdo->prepare("SELECT cm.id, m.name, cm.quantity, cm.expiration_date FROM cabinet_medications AS cm JOIN medications AS m ON cm.medication_id = m.id WHERE cm.cabinet_id = ? AND cm.quantity > 0 ORDER BY m.name ");
$stmt->execute([$cabinet_id]);
$meds = $stmt->fetchAll();

// Prepare JS array
$medsJson = json_encode($meds, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Rejestracja Sprzedaży / Utylizacji</title>
  <link rel="stylesheet" href="path/to/bootstrap.css">
  <style>
    .autocomplete-container { position: relative; }
    .autocomplete-suggestions {
      position: absolute;
      top: 100%; left: 0; right: 0;
      border: 1px solid #ccc;
      background: #fff;
      max-height: 200px; overflow-y: auto;
      z-index: 1000;
    }
    .autocomplete-suggestion {
      display: flex;
      justify-content: space-between;
      padding: 8px;
      cursor: pointer;
    }
    .autocomplete-suggestion .med    { text-align: left; margin-right: 1em; }
    .autocomplete-suggestion .expiry { text-align: right; }
    .autocomplete-suggestion:hover { background-color: #f0f0f0; }
  </style>
</head>
<body>
  <div class="container mt-4">
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h2>Rejestracja Sprzedaży / Utylizacji</h2>
    <form method="post">
      <div class="form-group">
        <label for="cab_med_search">Lek:</label>
        <div class="autocomplete-container">
          <input
            type="text"
            id="cab_med_search"
            class="form-control"
            placeholder="Wpisz nazwę leku..."
            autocomplete="off"
            required
          >
          <div id="cab_med_suggestions" class="autocomplete-suggestions"></div>
        </div>
        <input type="hidden" name="cabinet_medication_id" id="cab_med_id">
      </div>

      <div class="form-group">
        <label for="movement_type">Typ operacji:</label>
        <select name="movement_type" id="movement_type" class="form-control" required>
          <option value="rozchod">Sprzedaż</option>
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

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script>
    const meds = <?= $medsJson ?>;
    const searchInput = document.getElementById('cab_med_search');
    const suggestionsBox = document.getElementById('cab_med_suggestions');
    const hiddenId = document.getElementById('cab_med_id');

    function showSuggestions(query) {
      const q = query.toLowerCase();
      const matches = meds
        .filter(m => m.name.toLowerCase().includes(q))
        .slice(0, 50);

      suggestionsBox.innerHTML = '';
      for (let m of matches) {
        const div = document.createElement('div');
        div.classList.add('autocomplete-suggestion');

        const spanMed = document.createElement('span');
        spanMed.classList.add('med');
        spanMed.textContent = `${m.name} (aktualnie ${m.quantity})`;

        const spanExp = document.createElement('span');
        spanExp.classList.add('expiry');
        spanExp.textContent = `Wygasa: ${m.expiration_date}`;

        div.appendChild(spanMed);
        div.appendChild(spanExp);

        div.addEventListener('click', () => {
          searchInput.value = m.name;
          hiddenId.value    = m.id;
          suggestionsBox.innerHTML = '';
        });

        suggestionsBox.appendChild(div);
      }
    }

    searchInput.addEventListener('input', () => {
      hiddenId.value = '';
      showSuggestions(searchInput.value);
    });
    searchInput.addEventListener('focus', () => showSuggestions(searchInput.value));
    document.addEventListener('click', e => {
      if (!searchInput.parentElement.contains(e.target)) {
        suggestionsBox.innerHTML = '';
      }
    });

    function togglePrice() {
      $('#price_group').toggle($('#movement_type').val() !== 'utylizacja');
    }
    $(function(){
      togglePrice();
      $('#movement_type').on('change', togglePrice);
    });
  </script>
</body>
</html>
