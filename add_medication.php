<?php
// add_medication.php
session_start();
require 'config.php';

if (!isset($_GET['cabinet_id']) && isset($_SESSION['cabinet_id'])) {
    $_GET['cabinet_id'] = (int)$_SESSION['cabinet_id'];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

if (isset($_GET['cabinet_id'])) {
    $cabinet_id = intval($_GET['cabinet_id']);
    $stmt = $pdo->prepare("SELECT can_add_med, is_admin FROM cabinet_users WHERE cabinet_id = ? AND user_id = ?");
    $stmt->execute([$cabinet_id, $user_id]);
    $perm = $stmt->fetch();
    if (!$perm || (!($perm['can_add_med'] || $perm['is_admin']))) {
        die("Brak uprawnień do dodawania leków.");
    }
} else {
    die("Brak cabinet_id.");
}

// Pobierz listę leków ze słownika
$stmt = $pdo->query("SELECT id, name FROM medications ORDER BY name");
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Przygotuj tablicę nazw leków do JS
$medNames = array_column($medications, 'name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_new       = isset($_POST['new_med']) && $_POST['new_med'] === '1';
    $quantity     = intval($_POST['quantity']);
    $expiration   = $_POST['expiration_date'];
    $price        = floatval($_POST['price']);

    if ($is_new) {
        $name = trim($_POST['new_med_name']);
        $stmt = $pdo->prepare("INSERT INTO medications (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $stmt->execute([$name]);
        $medication_id = $pdo->lastInsertId();
    } else {
        $name = trim($_POST['med_name']);
        $stmt = $pdo->prepare("SELECT id FROM medications WHERE name = ?");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            die("Nie znaleziono leku w bazie.");
        }
        $medication_id = $row['id'];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO cabinet_medications (cabinet_id, medication_id, quantity, expiration_date, purchase_date, price)
             VALUES (?, ?, ?, ?, CURDATE(), ?)"
        );
        $stmt->execute([$cabinet_id, $medication_id, $quantity, $expiration, $price]);

        $cab_med_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare(
            "INSERT INTO movements (cabinet_medication_id, movement_type, quantity, action_user_id)
             VALUES (?, 'przychod', ?, ?)"
        );
        $stmt->execute([$cab_med_id, $quantity, $user_id]);

        $pdo->commit();
        $success = "Lek został dodany.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Błąd: " . $e->getMessage();
    }
}

include 'header.php';
?>

<style>
.autocomplete-container {
    position: relative;
    width: 100%;
}
.autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    border: 1px solid #ccc;
    background: #fff;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
}
.autocomplete-suggestion {
    padding: 8px;
    cursor: pointer;
}
.autocomplete-suggestion:hover {
    background-color: #f0f0f0;
}
</style>

<h1>Dodaj Lek</h1>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
<?php elseif (!empty($error)): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<form method="post">
    <div class="form-group">
        <label>
            <input type="checkbox" id="new_med" name="new_med" value="1"> Nowy lek
        </label>
    </div>

    <div class="form-group" id="existing_med_div">
        <label>Wybierz lek (zacznij pisać nazwę):</label>
        <div class="autocomplete-container">
            <input type="text" id="med_name" name="med_name" class="form-control" autocomplete="off" placeholder="Wpisz nazwę leku">
            <div id="med_suggestions" class="autocomplete-suggestions"></div>
        </div>
    </div>

    <div class="form-group" id="new_med_div" style="display:none;">
        <label>Nazwa nowego leku:</label>
        <input type="text" name="new_med_name" class="form-control" placeholder="Wpisz nazwę nowego leku">
    </div>

    <div class="form-group">
        <label>Ilość:</label>
        <input type="number" name="quantity" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Termin ważności:</label>
        <input type="date" name="expiration_date" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Cena zakupu:</label>
        <input type="text" name="price" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Dodaj</button>
</form>

<script>
    const medNames = <?= json_encode($medNames, JSON_UNESCAPED_UNICODE) ?>;
    const input = document.getElementById('med_name');
    const suggestionsBox = document.getElementById('med_suggestions');
    const existingDiv = document.getElementById('existing_med_div');

    input.addEventListener('focus', () => showSuggestions(''));
    input.addEventListener('input', () => showSuggestions(input.value));

    document.addEventListener('click', (e) => {
        if (!existingDiv.contains(e.target)) {
            suggestionsBox.innerHTML = '';
        }
    });

    function showSuggestions(query) {
        const q = query.toLowerCase();
        let matches = medNames.filter(name => name.toLowerCase().includes(q));
        // pokaż maksymalnie 50
        matches = matches.slice(0, 50);
        suggestionsBox.innerHTML = '';
        for (let name of matches) {
            const div = document.createElement('div');
            div.classList.add('autocomplete-suggestion');
            div.textContent = name;
            div.addEventListener('click', () => {
                input.value = name;
                suggestionsBox.innerHTML = '';
            });
            suggestionsBox.appendChild(div);
        }
    }

    document.getElementById('new_med').addEventListener('change', function() {
        document.getElementById('new_med_div').style.display = this.checked ? 'block' : 'none';
        document.getElementById('existing_med_div').style.display = this.checked ? 'none' : 'block';
    });
</script>

<?php include 'footer.php'; ?>
