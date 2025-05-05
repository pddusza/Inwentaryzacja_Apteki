<?php
// join_requests.php
require 'header.php';   // session_start(), config, sets $cabinet_id and $userPerms

// 1) Only admins may manage join requests
if (empty($userPerms['is_admin'])) {
    die("Brak uprawnień do zarządzania prośbami.");
}

// 2) Handle “reject” form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    if ($_POST['action'] === 'reject') {
        $stmt = $pdo->prepare("UPDATE cabinet_join_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$request_id]);
    }
}

// 3) Fetch pending join requests for *this* cabinet
$stmt = $pdo->prepare("SELECT r.id, u.username, r.request_date FROM cabinet_join_requests AS r JOIN users AS u ON r.requester_user_id = u.id WHERE r.cabinet_id = ? AND r.status = 'pending'");
$stmt->execute([$cabinet_id]);
$requests = $stmt->fetchAll();

?>
<!-- header.php has already printed your <nav> and opened <div class="container"> -->
<h1>Prośby o dołączenie do apteczki</h1>

<?php if (count($requests) > 0): ?>
    <table class="table table-bordered">
        <thead class="thead-light">
            <tr>
                <th>Username</th>
                <th>Data prośby</th>
                <th>Akcja</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><?php echo htmlspecialchars($req['username']); ?></td>
                <td><?php echo $req['request_date']; ?></td>
                <td>
                    <a href="accept_join_request.php?request_id=<?php echo $req['id']; ?>" 
                       class="btn btn-success btn-sm mr-2">Akceptuj</a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                            Odrzuć
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Brak oczekujących prośb.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
