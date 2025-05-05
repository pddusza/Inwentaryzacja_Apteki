<?php
// accept_join_request.php
require 'header.php';   // session_start(), config, sets $cabinet_id & $userPerms, renders nav and container

// 1) Only admins may accept join requests
if (empty($userPerms['is_admin'])) {
    die("Brak uprawnień do akceptacji prośb.");
}

// 2) Make sure we got a request_id
if (!isset($_GET['request_id'])) {
    die("Nieprawidłowe żądanie.");
}
$request_id = intval($_GET['request_id']);

// 3) Look up the pending request in this cabinet
$stmt = $pdo->prepare("
    SELECT requester_user_id 
    FROM cabinet_join_requests
    WHERE id = ? 
      AND cabinet_id = ? 
      AND status = 'pending'
");
$stmt->execute([$request_id, $cabinet_id]);
$requester_user_id = $stmt->fetchColumn();
if (!$requester_user_id) {
    die("Nie znaleziono prośby lub prośba już przetworzona.");
}

// 4) If they just submitted the form, process acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_role = $_POST['new_role'];
    if (!in_array($selected_role, ['dashboard','add_med','usage','reports','admin'], true)) {
        die("Nieprawidłowa rola.");
    }

    // initialize flags
    $can_add_med = $can_usage = $can_reports = $is_admin = 0;
    switch ($selected_role) {
        case 'add_med':  $can_add_med = 1; break;
        case 'usage':    $can_usage   = 1; break;
        case 'reports':  $can_reports = 1; break;
        case 'admin':
            $can_add_med = $can_usage = $can_reports = $is_admin = 1;
            break;
        // dashboard = no flags
    }

    // a) mark the request accepted
    $upd = $pdo->prepare("UPDATE cabinet_join_requests SET status = 'accepted' WHERE id = ?");
    $upd->execute([$request_id]);

    // b) add the user into cabinet_users
    $ins = $pdo->prepare("
        INSERT INTO cabinet_users
          (cabinet_id, user_id, can_add_med, can_usage, can_reports, is_admin)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([
        $cabinet_id,
        $requester_user_id,
        $can_add_med,
        $can_usage,
        $can_reports,
        $is_admin
    ]);

    // c) back to the list
    header("Location: join_requests.php");
    exit;
}
?>

<h1>Akceptuj prośbę o dołączenie</h1>

<p>Wybierz poziom uprawnień dla użytkownika (ID: <?php echo htmlspecialchars($requester_user_id); ?>):</p>

<form method="post">
    <div class="form-group">
        <label>Wybierz rolę:</label>
        <select name="new_role" class="form-control" required>
            <option value="dashboard">Dashboard (podstawowy dostęp)</option>
            <option value="add_med">Dodaj Lek</option>
            <option value="usage">Zużycie/Utylizacja</option>
            <option value="reports">Raporty</option>
            <option value="admin">Admin (pełne uprawnienia)</option>
        </select>
    </div>
    <input type="submit" value="Akceptuj prośbę" class="btn btn-success">
</form>

<?php include 'footer.php'; ?>
