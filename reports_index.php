<?php
// reports_index.php
session_start();
require 'config.php';

// Ustal typ raportu – domyślnie profit_cost
$report_type = $_GET['report'] ?? 'profit_cost';

include 'header.php';
?>

<h1 class="mb-4">Raporty</h1>
<!-- Menu raportów -->
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo ($report_type == 'integrity') ? 'active' : ''; ?>" href="reports_index.php?report=integrity">Integralności</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo ($report_type == 'usage') ? 'active' : ''; ?>" href="reports_index.php?report=usage">Zużycia</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo ($report_type == 'monthly') ? 'active' : ''; ?>" href="reports_index.php?report=monthly">Miesięczny</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo ($report_type == 'profit_cost') ? 'active' : ''; ?>" href="reports_index.php?report=profit_cost">Profit/Kosztowy</a>
  </li>
</ul>

<div class="mt-4">
<?php
// Tylko admin i edytor mają dostęp do raportów
$stmt = $pdo->prepare("SELECT can_reports, is_admin FROM cabinet_users WHERE cabinet_id = ? AND user_id = ?");
$stmt->execute([$cabinet_id, $user_id]);
$perm = $stmt->fetch();
if (!($perm['can_reports'] || $perm['is_admin'])) {
    echo "<div class='alert alert-danger'>Brak uprawnień do raportów.</div>";
    include 'footer.php';
    exit;
}

switch ($report_type) {
    case 'integrity':
        $stmt = $pdo->prepare("SELECT cm.id, m.name, cm.quantity, 
                   (SELECT IFNULL(SUM(mov.quantity), 0) FROM movements mov WHERE mov.cabinet_medication_id = cm.id AND mov.movement_type = 'przychod') AS total_in,
                   (SELECT IFNULL(SUM(mov.quantity), 0) FROM movements mov WHERE mov.cabinet_medication_id = cm.id AND mov.movement_type IN ('rozchod','utylizacja')) AS total_out
            FROM cabinet_medications cm JOIN medications m ON cm.medication_id = m.id WHERE cabinet_id = ?
        ");
        $stmt->execute([$cabinet_id]);
        $inventory = $stmt->fetchAll();
        echo "<h2>Raport integralności apteczki</h2>";
        echo "<p>Liczba różnych specyfików: " . count($inventory) . "</p>";
        echo "<table class='table table-bordered'>";
        echo "<thead class='thead-light'><tr>
                <th>Nazwa leku</th>
                <th>Ilość</th>
                <th>Przychody</th>
                <th>Rozchody</th>
                <th>Status</th>
              </tr></thead><tbody>";
        foreach ($inventory as $item) {
            $calculated = $item['total_in'] - $item['total_out'];
            $status = ($calculated == $item['quantity']) ? 'OK' : 'Błąd';
            echo "<tr>
                    <td>" . htmlspecialchars($item['name']) . "</td>
                    <td>" . $item['quantity'] . "</td>
                    <td>" . $item['total_in'] . "</td>
                    <td>" . $item['total_out'] . "</td>
                    <td>" . $status . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
        break;
    
    case 'usage':
        $stmt = $pdo->prepare("SELECT mov.*, m.name AS med_name, u.username FROM movements mov JOIN cabinet_medications cm ON mov.cabinet_medication_id = cm.id JOIN medications m ON cm.medication_id = m.id LEFT JOIN users u ON mov.action_user_id = u.id WHERE mov.movement_type = 'rozchod' AND cabinet_id = ? ORDER BY mov.movement_date DESC");
        $stmt->execute([$cabinet_id]);
        $usage = $stmt->fetchAll();
        echo "<h2>Raport zużycia leków</h2>";
        echo "<table class='table table-bordered'>";
        echo "<thead class='thead-light'><tr>
                <th>Nazwa leku</th>
                <th>Ilość</th>
                <th>Data operacji</th>
                <th>Użytkownik</th>
              </tr></thead><tbody>";
        foreach ($usage as $row) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['med_name']) . "</td>
                    <td>" . $row['quantity'] . "</td>
                    <td>" . $row['movement_date'] . "</td>
                    <td>" . htmlspecialchars($row['username']) . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
        break;
    
    case 'monthly':
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(movement_date, '%Y-%m') AS month, movement_type, SUM(quantity) AS total_quantity FROM movements
            WHERE cabinet_medication_id IN (SELECT id FROM cabinet_medications WHERE cabinet_id = ?) GROUP BY month, movement_type ORDER BY month ASC");
        $stmt->execute([$cabinet_id]);
        $monthlyData = $stmt->fetchAll();
        $report = [];
        foreach ($monthlyData as $row) {
            $month = $row['month'];
            if (!isset($report[$month])) {
                $report[$month] = ['przychod' => 0, 'rozchod' => 0, 'utylizacja' => 0];
            }
            $report[$month][$row['movement_type']] = $row['total_quantity'];
        }
        echo "<h2>Raport miesięczny</h2>";
        echo "<table class='table table-bordered'>";
        echo "<thead class='thead-light'><tr>
                <th>Miesiąc</th>
                <th>Przychody</th>
                <th>Rozchody</th>
                <th>Utylizacja</th>
              </tr></thead><tbody>";
        foreach ($report as $month => $data) {
            echo "<tr>
                    <td>$month</td>
                    <td>" . $data['przychod'] . "</td>
                    <td>" . $data['rozchod'] . "</td>
                    <td>" . $data['utylizacja'] . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
        break;
    
    case 'profit_cost':
        echo "<h2>Raport Profit/Kosztowy</h2>";
        // Ustaw domyślne daty na bieżący miesiąc, jeśli nie podano
        if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
            $firstDay = date("Y-m-01");
            $lastDay = date("Y-m-t");
            $start_date = $firstDay;
            $end_date = $lastDay;
        } else {
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];
        }
        echo "<form method='get' class='form-inline mb-3'>
                <input type='hidden' name='cabinet_id' value='$cabinet_id'>
                <input type='hidden' name='report' value='profit_cost'>
                <div class='form-group mr-2'>
                    <label>Od: </label>
                    <input type='date' name='start_date' class='form-control ml-2' value='" . htmlspecialchars($start_date) . "' required>
                </div>
                <div class='form-group mr-2'>
                    <label>Do: </label>
                    <input type='date' name='end_date' class='form-control ml-2' value='" . htmlspecialchars($end_date) . "' required>
                </div>
                <input type='submit' value='Pokaż raport' class='btn btn-primary'>
              </form>";
        if ($start_date && $end_date) {
            // Sekcja kosztowa
            $stmt = $pdo->prepare("
                SELECT mov.movement_type, SUM(mov.quantity * cm.price) AS total_cost
                FROM movements mov
                JOIN cabinet_medications cm ON mov.cabinet_medication_id = cm.id
                WHERE cm.cabinet_id = ? AND mov.movement_date BETWEEN ? AND ?
                GROUP BY mov.movement_type
            ");
            $stmt->execute([$cabinet_id, $start_date, $end_date]);
            $rows = $stmt->fetchAll();
            $costs = ['przychod' => 0, 'rozchod' => 0, 'utylizacja' => 0];
            foreach ($rows as $row) {
                $costs[$row['movement_type']] = $row['total_cost'];
            }
            echo "<h3>Raport kosztowy</h3>";
            echo "<table class='table table-bordered'>
                    <thead class='thead-light'>
                        <tr>
                            <th>Operacja</th>
                            <th>Łączny koszt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Zakupione (przychod)</td>
                            <td>" . number_format($costs['przychod'], 2) . "</td>
                        </tr>
                        <tr>
                            <td>Zużyte (rozchod)</td>
                            <td>" . number_format($costs['rozchod'], 2) . "</td>
                        </tr>
                        <tr>
                            <td>Utylizowane (utylizacja)</td>
                            <td>" . number_format($costs['utylizacja'], 2) . "</td>
                        </tr>
                    </tbody>
                  </table>";
            echo "<canvas id='costChart' width='400' height='200'></canvas>";
            echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
            echo "<script>
                var ctx = document.getElementById('costChart').getContext('2d');
                var costChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Zakupione', 'Zużyte', 'Utylizowane'],
                        datasets: [{
                            label: 'Łączny koszt',
                            data: [". number_format($costs['przychod'], 2, '.', '') .", ". number_format($costs['rozchod'], 2, '.', '') .", ". number_format($costs['utylizacja'], 2, '.', '') ."],
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(255, 206, 86, 0.2)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(255, 206, 86, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            </script>";
            
            // Sekcja profit: tylko operacje sprzedaży (rozchod z sale_price podanym)
            $stmtProfit = $pdo->prepare("SELECT mov.*, cm.price AS purchase_price, m.name AS med_name FROM movements mov
                JOIN cabinet_medications cm ON mov.cabinet_medication_id = cm.id
                JOIN medications m ON cm.medication_id = m.id
                WHERE cm.cabinet_id = ? AND mov.sale_price IS NOT NULL AND mov.movement_date BETWEEN ? AND ?
                ORDER BY mov.movement_date DESC
            ");
            $stmtProfit->execute([$cabinet_id, $start_date, $end_date]);
            $profitData = $stmtProfit->fetchAll();
            echo "<h3>Raport zysku, marży i profitu (sprzedaż)</h3>";
            echo "<table class='table table-bordered'>";
            echo "<thead class='thead-light'><tr>
                    <th>Nazwa leku</th>
                    <th>Data operacji</th>
                    <th>Ilość</th>
                    <th>Cena zakupu</th>
                    <th>Cena sprzedaży</th>
                    <th>Zysk (na jednostkę)</th>
                    <th>Łączny zysk</th>
                    <th>Marża (%)</th>
                  </tr></thead><tbody>";
            $totalProfit = 0;
            foreach ($profitData as $row) {
                $purchase_price = $row['purchase_price'];
                $sale_price = $row['sale_price'];
                $quantity = $row['quantity'];
                $profitPerUnit = $sale_price - $purchase_price;
                $total = $profitPerUnit * $quantity;
                $totalProfit += $total;
                $marginPercentage = ($sale_price > 0) ? ($profitPerUnit / $sale_price) * 100 : 0;
                echo "<tr>
                        <td>" . htmlspecialchars($row['med_name']) . "</td>
                        <td>" . $row['movement_date'] . "</td>
                        <td>" . $quantity . "</td>
                        <td>" . number_format($purchase_price, 2) . "</td>
                        <td>" . number_format($sale_price, 2) . "</td>
                        <td>" . number_format($profitPerUnit, 2) . "</td>
                        <td>" . number_format($total, 2) . "</td>
                        <td>" . number_format($marginPercentage, 2) . "%</td>
                      </tr>";
            }
            echo "</tbody></table>";
            // Net profit = profit sprzedaży minus koszty zakupu (przychod)
            $netProfit = $totalProfit - $costs['przychod'];
            echo "<h3>Net Profit (Profit sprzedaży - Koszty zakupu): " . number_format($netProfit, 2) . "</h3>";
        }
        break;
    
    default:
        echo "<p>Wybierz raport z powyższego menu.</p>";
        break;
}
?>
</div>

<?php include 'footer.php'; ?>
