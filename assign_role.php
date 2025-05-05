<?php
// assign_role.php
require 'header.php';   // starts session, sets $cabinet_id & $userPerms, renders <nav> and opens <div class="container">

// 1) Only admins may change permissions
if (empty($userPerms['is_admin'])) {
    die("Brak uprawnień do zmiany uprawnień w tej apteczce.");
}

// 2) Fetch all users in this cabinet
$stmt = $pdo->prepare("SELECT cu.user_id, u.username, cu.can_add_med, cu.can_usage, cu.can_reports, cu.is_admin FROM cabinet_users AS cu JOIN users AS u ON cu.user_id = u.id WHERE cu.cabinet_id = ?");
$stmt->execute([$cabinet_id]);
$users = $stmt->fetchAll();

// count current admins
$adminCount = 0;
foreach ($users as $u) {
    if ($u['is_admin']) {
        $adminCount++;
    }
}

$error   = '';
$message = '';

// 3) Handle removal requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user_id'])) {
    $removeId = (int)$_POST['remove_user_id'];

    foreach ($users as $u) {
        if ($u['user_id'] === $removeId) {
            if ($u['is_admin'] && $adminCount <= 1) {
                $error = "Nie można usunąć ostatniego administratora.";
            } else {
                $del = $pdo->prepare("DELETE FROM cabinet_users WHERE cabinet_id = ? AND user_id = ?");
                $del->execute([$cabinet_id, $removeId]);
                $message = "Użytkownik \"" . htmlspecialchars($u['username']) . "\" został usunięty.";
            }
            break;
        }
    }

    // refetch users & adminCount
    $stmt->execute([$cabinet_id]);
    $users = $stmt->fetchAll();
    $adminCount = 0;
    foreach ($users as $u) {
        if ($u['is_admin']) { $adminCount++; }
    }
}

// 4) Handle permissions update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permissions']) && !$error) {
    // count how many will be admins
    $desiredAdmins = 0;
    foreach ($users as $u) {
        $uid = $u['user_id'];
        if (!empty($_POST['permissions'][$uid]['is_admin'])) {
            $desiredAdmins++;
        }
    }
    if ($desiredAdmins < 1) {
        $error = "Musisz pozostawić co najmniej jednego administratora.";
    } else {
        foreach ($users as $u) {
            $uid = $u['user_id'];
            $perms = $_POST['permissions'][$uid] ?? [];

            $can_add_med  = isset($perms['can_add_med']) ? 1 : 0;
            $can_usage    = isset($perms['can_usage'])   ? 1 : 0;
            $can_reports  = isset($perms['can_reports']) ? 1 : 0;
            $is_admin_new = isset($perms['is_admin'])    ? 1 : 0;

            $upd = $pdo->prepare("UPDATE cabinet_users SET can_add_med = ?, can_usage = ?, can_reports = ?, is_admin = ? WHERE cabinet_id = ? AND user_id = ?");
            $upd->execute([$can_add_med, $can_usage, $can_reports, $is_admin_new, $cabinet_id, $uid]);
        }
        $message = "Uprawnienia zaktualizowane pomyślnie.";

        // refetch
        $stmt->execute([$cabinet_id]);
        $users = $stmt->fetchAll();
    }
}
?>

<h1>Zarządzaj uprawnieniami dla apteczki</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($message): ?>
  <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" id="permissionsForm">
  <table class="table table-bordered">
    <thead class="thead-light">
      <tr>
        <th>Username</th>
        <th>Dodaj Lek</th>
        <th>Zużycie</th>
        <th>Raporty</th>
        <th>Admin</th>
        <th style="width:50px;">Usuń</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): 
        $uid = (int)$user['user_id'];
        $isOnlyAdmin = ($user['is_admin'] && $adminCount <= 1);
      ?>
      <tr>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td>
          <input type="checkbox"
                 name="permissions[<?= $uid ?>][can_add_med]"
                 <?= $user['can_add_med'] ? 'checked' : '' ?>>
        </td>
        <td>
          <input type="checkbox"
                 name="permissions[<?= $uid ?>][can_usage]"
                 <?= $user['can_usage'] ? 'checked' : '' ?>>
        </td>
        <td>
          <input type="checkbox"
                 name="permissions[<?= $uid ?>][can_reports]"
                 <?= $user['can_reports'] ? 'checked' : '' ?>>
        </td>
        <td>
          <input type="checkbox"
                 name="permissions[<?= $uid ?>][is_admin]"
                 <?= $user['is_admin'] ? 'checked' : '' ?>>
        </td>
        <td class="text-center" style="width:50px;">
          <button type="button"
                  class="btn btn-sm btn-outline-danger remove-btn"
                  data-user-id="<?= $uid ?>"
                  data-username="<?= htmlspecialchars($user['username']) ?>"
                  <?= $isOnlyAdmin ? 'disabled' : '' ?>>
            &times;
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <button type="submit" class="btn btn-primary">Zaktualizuj uprawnienia</button>
</form>

<!-- Hidden removal form -->
<form method="post" id="removeForm" style="display:none;">
  <input type="hidden" name="remove_user_id" id="remove_user_id">
</form>

<script>
  document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const userId   = btn.getAttribute('data-user-id');
      const username = btn.getAttribute('data-username');
      if (confirm(`Czy na pewno chcesz usunąć użytkownika “${username}”?`)) {
        document.getElementById('remove_user_id').value = userId;
        document.getElementById('removeForm').submit();
      }
    });
  });
</script>

<?php include 'footer.php'; ?>
