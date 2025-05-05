<?php
// dashboard.php
require 'header.php';

// 1) Fetch the currently active cabinet’s details
$stmt = $pdo->prepare("SELECT * FROM cabinets WHERE id = ?");
$stmt->execute([$cabinet_id]);
$cabinet = $stmt->fetch();

// 2) Fetch this cabinet’s meds (sorted by name & expiration)
$stmt = $pdo->prepare("SELECT cm.*, m.name AS med_name, cm.expiration_date FROM cabinet_medications AS cm JOIN medications AS m ON cm.medication_id = m.id WHERE cm.cabinet_id = ? AND cm.quantity > 0 ORDER BY m.name ASC, cm.expiration_date ASC");
$stmt->execute([$cabinet_id]);
$medications = $stmt->fetchAll();

// 3) Fetch expired meds for this cabinet
$expStmt = $pdo->prepare("SELECT m.name, cm.expiration_date FROM cabinet_medications AS cm JOIN medications AS m ON cm.medication_id = m.id WHERE cm.cabinet_id = ? AND cm.expiration_date < CURDATE() AND cm.quantity > 0");
$expStmt->execute([$cabinet_id]);
$expired = $expStmt->fetchAll();
?>

    <h1>System Inwentaryzacji: <?php echo htmlspecialchars($cabinet['name']); ?></h1>

    <!-- Wyszukiwarka leków -->
    <div class="form-group mt-3">
        <input type="text" id="search" class="form-control" placeholder="Szukaj leku...">
    </div>

    <!-- Tabela leków -->
    <table id="med-table" class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th>Nazwa leku</th>
                <th>Data ważności</th>
                <th>Ilość</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($medications): ?>
            <?php foreach ($medications as $med): ?>
            <tr>
                <td><?php echo htmlspecialchars($med['med_name']); ?></td>
                <td><?php echo htmlspecialchars($med['expiration_date']); ?></td>
                <td><?php echo (int)$med['quantity']; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center">Brak dostępnych leków na stanie.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Przeterminowane leki -->
    <h2 class="mt-5">Leki przeterminowane</h2>
    <?php if ($expired): ?>
        <ul class="list-group">
            <?php foreach ($expired as $e): ?>
            <li class="list-group-item">
                <?php echo htmlspecialchars($e['name']) . ' (wygasł: ' . htmlspecialchars($e['expiration_date']) . ')'; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted">Brak przeterminowanych leków.</p>
    <?php endif; ?>

<?php
// Close container & include footer (which closes </body></html>)
include 'footer.php';
?>

<!-- Skrypt filtrowania -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('search');
    const rows  = document.querySelectorAll('#med-table tbody tr');
    input.addEventListener('input', function() {
        const filter = this.value.toLowerCase().trim();
        rows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });
});
</script>
