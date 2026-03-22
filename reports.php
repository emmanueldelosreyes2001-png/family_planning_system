<?php 
include 'config.php'; 
?>
<!DOCTYPE html>
<html>
<head>
<title>Reports - Family Planning System</title>
<style>
body { margin:0; font-family:Arial; background:#f5f7fa; }


.header { 
    background:#0f8f5f; 
    color:white; 
    padding:20px; 
    position: relative; 
}
.header h1 { margin:0; }
.header h2, .header p { margin:5px 0 0; font-size:14px; }


.menu {
    position: absolute;
    right: 20px;
    top: 25px;
    font-size: 24px;
    cursor: pointer;
    user-select: none;
}
.dropdown {
    display: none;
    position: absolute;
    right: 20px;
    top: 55px;
    background: white;
    color: black;
    min-width: 120px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 100;
}
.dropdown a {
    color: black;
    text-decoration: none;
    display: block;
    padding: 10px 15px;
}
.dropdown a:hover { background:#f0f0f0; }


.nav {
    background:white;
    padding:12px 20px;
    display:flex;
    gap:25px;
    border-bottom:1px solid #ddd;
}
.nav a {
    text-decoration:none;
    color:#555;
    padding-bottom:5px;
    border-bottom:2px solid transparent;
    transition: all 0.2s;
}
.nav a.active { color:#0f8f5f; border-bottom:2px solid #0f8f5f; }

.container { padding:20px; }
.top-bar { display:flex; justify-content:flex-end; margin-bottom:15px; }
button { background:#0f8f5f; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer; }

.cards {
  display: flex;
  gap: 20px;
  flex-wrap: wrap; 
}

.card {
  background: white;
  padding: 15px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 15px;
  flex: 1;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.card h4 {
  margin: 0;
  font-size: 14px;
  color: #777;
}

.card h2 {
  margin: 5px 0 0;
  font-size: 28px;
  color: #0f8f5f;
}

.card .icon {
  font-size: 32px;
  color: #0f8f5f;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #e0f7f1; 
}

.chart-row {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.chart-box {
    width: 30%; 
}

.chart-container {
    width: 100%;
    height: 200px; 
}

.section { background:white; padding:15px; border-radius:10px; margin-top:20px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { padding:10px; border-bottom:1px solid #eee; text-align:center; }
th { background:#f9fafb; }


.chart-container { width: 100%; max-width: 600px; height: 250px; margin: 15px auto; }
canvas { width:100% !important; height:100% !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="header" style="display:flex; align-items:center; gap:15px;">
    <i class="far fa-heart" style="font-size:50px; color:white;"></i>
    
    <div>
        <h1 style="margin:0;">Family Planning Monitoring System</h1>
        <p style="margin:5px 0 0; font-size:14px;">Rural Health Unit - Dumingag, Zamboanga del Sur</p>
    </div>
    
    <div class="menu" onclick="toggleDropdown()" style="margin-left:auto; font-size:24px; cursor:pointer;">☰</div>
    <div class="dropdown" id="dropdownMenu">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="nav">
<?php
$currentPage = basename($_SERVER['PHP_SELF']); 
function navClass($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<a href="index.php" class="<?php echo navClass('index.php'); ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
<a href="patients.php" class="<?php echo navClass('patients.php'); ?>"><i class="fas fa-users"></i>Patients</a>
<a href="appointments.php" class="<?php echo navClass('appointments.php'); ?>"><i class="fas fa-calendar-check"></i>Appointments</a>
<a href="services.php" class="<?php echo navClass('services.php'); ?>"><i class="fas fa-concierge-bell"></i>Services</a>
<a href="inventory.php" class="<?= navClass('inventory.php'); ?>"><i class="fas fa-capsules"></i>Inventory</a>
<a href="reports.php" class="<?php echo navClass('reports.php'); ?>"><i class="fas fa-file-alt"></i>Reports</a>
</div>

<div class="container">

<h2>Reports & Analytics</h2>
<p>Generate and view family planning program reports</p>

<div class="top-bar">
    <a href="export_reports.php"><button>Export Report</button></a>
</div>

<?php
$totalClients = $conn->query("SELECT COUNT(*) as total FROM patients")->fetch_assoc()['total'] ?? 0;
$activeClients = $conn->query("SELECT COUNT(*) as total FROM patients WHERE status='Active'")->fetch_assoc()['total'] ?? 0;
$servicesThisMonth = $conn->query("
    SELECT COUNT(*) as total 
    FROM service_records 
    WHERE MONTH(service_date)=MONTH(CURDATE()) AND YEAR(service_date)=YEAR(CURDATE())
")->fetch_assoc()['total'] ?? 0;
$coverage = $totalClients > 0 ? round(($activeClients / $totalClients) * 100) : 0;


$methodData = [];
$methodsList = [];
$methodQuery = $conn->query("SELECT current_method, COUNT(*) as total FROM patients GROUP BY current_method");
while($row = $methodQuery->fetch_assoc()) {
    $methodsList[] = $row['current_method'];
    $methodData[] = (int)$row['total'];
}


$serviceLabels = [];
$serviceData = [];
$serviceQuery = $conn->query("SELECT service_type, COUNT(*) as total FROM service_records GROUP BY service_type");
while($row = $serviceQuery->fetch_assoc()) {
    $serviceLabels[] = $row['service_type'];
    $serviceData[] = (int)$row['total'];
}


$barangays = [];
$barangayData = [];
$barangayQuery = $conn->query("SELECT address, COUNT(*) as total FROM patients GROUP BY address");
while($row = $barangayQuery->fetch_assoc()) {
    $barangays[] = $row['address'];
    $barangayData[] = (int)$row['total'];
}
?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<div class="cards">
    <div class="card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div>
            <h4>Total Clients</h4>
            <h2><?php echo $totalClients; ?></h2>
        </div>
    </div>
    <div class="card">
        <div class="icon"><i class="fas fa-user-check"></i></div>
        <div>
            <h4>Active Clients</h4>
            <h2><?php echo $activeClients; ?></h2>
        </div>
    </div>
    <div class="card">
        <div class="icon"><i class="fas fa-concierge-bell"></i></div>
        <div>
            <h4>Services This Month</h4>
            <h2><?php echo $servicesThisMonth; ?></h2>
        </div>
    </div>
    <div class="card">
        <div class="icon"><i class="fas fa-percent"></i></div>
        <div>
            <h4>Coverage Rate</h4>
            <h2><?php echo $coverage; ?>%</h2>
        </div>
    </div>
</div>

<div class="chart-row">
    <div class="section chart-box">
        <h3>Family Planning Method Distribution</h3>
        <div class="chart-container"><canvas id="methodChart"></canvas></div>
    </div>

    <div class="section chart-box">
        <h3>Service Type Distribution</h3>
        <div class="chart-container"><canvas id="serviceChart"></canvas></div>
    </div>

    <div class="section chart-box">
        <h3>Client Distribution by Barangay</h3>
        <div class="chart-container"><canvas id="barangayChart"></canvas></div>
    </div>
</div>

<div class="section">
<h3>Method Summary Table</h3>
<table>
<tr><th>Method</th><th>Clients</th><th>Percentage</th></tr>
<?php
$totalMethodsClients = array_sum($methodData);
$percentages = [];
$rounded = [];


foreach($methodsList as $i => $m) {
    $percentages[$i] = $totalMethodsClients > 0 ? ($methodData[$i] / $totalMethodsClients) * 100 : 0;
    $rounded[$i] = floor($percentages[$i] * 10) / 10; 
}


$sumRounded = array_sum($rounded);
$diff = round(100 - $sumRounded, 1);

if($diff != 0){
    $maxIndex = array_keys($rounded, max($rounded))[0];
    $rounded[$maxIndex] += $diff;
}


foreach($methodsList as $i => $m):
?>


<tr>
    <td><?php echo $m; ?></td>
    <td><?php echo $methodData[$i]; ?></td>
    <td><?php echo $rounded[$i]; ?>%</td>
</tr>
<?php endforeach; ?>
<tr>
    <td><b>Total</b></td>
    <td><b><?php echo $totalMethodsClients; ?></b></td>
    <td><b>100%</b></td>
</tr>
</table>
</div>

<div class="section">
    <h3>Recent Inventory Items</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Expiration Date</th>
                <th>Supplier</th>
                <th>Batch Number</th>
            </tr>
        </thead>
        <tbody>
        <?php

        $inventoryItems = $conn->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 10");
        while($item = $inventoryItems->fetch_assoc()):
        ?>
            <tr>
                <td><?= htmlspecialchars($item['item_id']) ?></td>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= htmlspecialchars($item['medicine_type']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= !empty($item['expiration_date']) && $item['expiration_date'] != '0000-00-00' ? date('m/d/Y', strtotime($item['expiration_date'])) : '-' ?></td>
                <td><?= htmlspecialchars($item['supplier']) ?></td>
                <td><?= htmlspecialchars($item['batch_number']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function toggleDropdown() {
    var dropdown = document.getElementById("dropdownMenu");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}
window.onclick = function(event) {
  if (!event.target.matches('.menu')) {
    var dropdown = document.getElementById("dropdownMenu");
    if (dropdown.style.display === "block") { dropdown.style.display = "none"; }
  }
}

const chartOptions = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };

new Chart(document.getElementById('methodChart'), {
    type: 'bar',
    data: { labels: <?php echo json_encode($methodsList); ?>, datasets: [{ label:'Clients', data:<?php echo json_encode($methodData); ?>, backgroundColor:'#0f8f5f' }] },
    options: chartOptions
});

new Chart(document.getElementById('serviceChart'), {
    type: 'pie',
    data: { 
        labels: <?php echo json_encode($serviceLabels); ?>, 
        datasets: [{ 
            data: <?php echo json_encode($serviceData); ?>, 
            backgroundColor: ['#3b82f6','#22c55e','#ef4444','#f59e0b','#8b5cf6','#ec4899'] 
        }] 
    },
    options: chartOptions
});

new Chart(document.getElementById('barangayChart'), {
    type: 'bar',
    data: { labels: <?php echo json_encode($barangays); ?>, datasets: [{ label:'Clients', data:<?php echo json_encode($barangayData); ?>, backgroundColor:'#0f8f5f' }] },
    options: chartOptions
});
</script>

</body>
</html>