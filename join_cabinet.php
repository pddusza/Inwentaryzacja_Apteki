<?php
// join_cabinet.php
session_start();
require 'config.php';

// 1) Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// 2) Handle search form submission (finding cabinets by admin username)
$message  = "";
$cabinets = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_username'])) {
    $admin_username = trim($_POST['admin_username']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$admin_username]);
    $admin = $stmt->fetch();

    if ($admin) {
        $admin_id = $admin['id'];
        // Find cabinets where that user is an admin
        $stmt = $pdo->prepare("SELECT c.id, c.name FROM cabinets AS c JOIN cabinet_users AS cu ON c.id = cu.cabinet_id WHERE cu.user_id = ? AND cu.is_admin = 1");
        $stmt->execute([$admin_id]);
        $cabinets = $stmt->fetchAll();

        if (!$cabinets) {
            $message = "Użytkownik “" . htmlspecialchars($admin_username) . "” nie posiada żadnej apteczki lub nie jest adminem.";
        }
    } else {
        $message = "Nie znaleziono użytkownika o nazwie “" . htmlspecialchars($admin_username) . "”.";
    }
}

include 'header.php';
?>

<!-- Flash message from send_join_request.php -->
<?php if (!empty($_SESSION['flash_message'])): ?>
  <div class="alert alert-info mt-4">
    <?= htmlspecialchars($_SESSION['flash_message']) ?>
  </div>
  <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<h1>Dołącz do Apteki</h1>

<!-- Search result message -->
<?php if ($message): ?>
  <div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<!-- Formularz wyszukiwania apteczki po admin username -->
<form method="post" class="mb-4">
  <div class="form-group">
    <label>Podaj nazwę użytkownika (admina) Apteki, do której chcesz dołączyć:</label>
    <input
      type="text"
      name="admin_username"
      class="form-control"
      required
      value="<?= isset($admin_username) ? htmlspecialchars($admin_username) : '' ?>"
    >
  </div>
  <button type="submit" class="btn btn-primary">Szukaj</button>
</form>

<?php if (!empty($cabinets)): ?>
  <h2>Znalezione Apteki:</h2>
  <table class="table table-bordered">
    <thead class="thead-light">
      <tr>
        <th>Nazwa Apteki</th>
        <th>Akcja</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($cabinets as $cabinet): ?>
      <tr>
        <td><?= htmlspecialchars($cabinet['name']) ?></td>
        <td>
          <form method="post" action="send_join_request.php">
            <input type="hidden" name="cabinet_id" value="<?= (int)$cabinet['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-sm">
              Wyślij prośbę o dołączenie
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php include 'footer.php'; ?>
