<?php
// switch_cabinet.php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// — if we got ?cabinet_id=, flip session and go back to dashboard
if (isset($_GET['cabinet_id'])) {
    $_SESSION['cabinet_id'] = intval($_GET['cabinet_id']);
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// Pobierz wszystkie Lokalizacje powiązane z użytkownikiem
$stmt = $pdo->prepare("SELECT cu.cabinet_id, c.name FROM cabinet_users cu JOIN cabinets c ON cu.cabinet_id = c.id WHERE cu.user_id = ?");
$stmt->execute([$user_id]);
$cabinets = $stmt->fetchAll();

include 'header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Wybierz Lokalizację</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($cabinets)): ?>
                        <ul class="list-group">
                            <?php foreach ($cabinets as $cab): ?>
                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($cab['name']); ?></span>
                                    <a href="switch_cabinet.php?cabinet_id=<?php echo $cab['cabinet_id']; ?>" class="btn btn-sm btn-outline-primary">Wybierz</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Nie jesteś przypisany do żadnej Lokalizacji.</p>
                    <?php endif; ?>

                    <div class="mt-4 text-center">
                        <a href="create_cabinet.php" class="btn btn-success">Utwórz nową Lokalizację</a>
                        <a href="join_cabinet.php" class="btn btn-secondary ml-2">Dołącz do Lokalizacji</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
