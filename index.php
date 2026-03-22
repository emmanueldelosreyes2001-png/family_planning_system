<?php
include 'config.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard - Family Planning System</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
    display:flex;
    align-items:center;
    gap:6px;
    transition: all 0.2s;
}
.nav a.active {
    color:#0f8f5f;
    border-bottom:2px solid #0f8f5f;
}
.nav a i { margin-right:6px; }


.container { padding:20px; }
.subtext { color:#777; }


.cards { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-top:20px; }
.card { background:white; padding:15px; border-radius:12px; display:flex; align-items:center; gap:15px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
.card h4 { margin:0; font-size:14px; color:#777; }
.card h2 { margin:5px 0 0; color:#0f8f5f; }
.card .icon {
    font-size:32px;
    color:#0f8f5f;
    display:flex;
    align-items:center;
    justify-content:center;
    width:50px;
    height:50px;
    border-radius:50%;
    background:#e0f7f1;
}


.table-box { background:white; border-radius:10px; padding:10px; margin-top:25px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; }
th { background:#f9fafb; }
.badge { padding:5px 10px; border-radius:6px; font-size:12px; color:white; }
.scheduled { background:#3b82f6; }
.completed { background:#22c55e; }
.cancelled { background:#ef4444; }

.chart-row {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.chart-box {
    width: 30%;
    min-width: 280px;
}

.chart-container {
    width: 100%;
    height: 200px;
}

.scheduled { background:#3b82f6; }
.completed { background:#22c55e; }
.cancelled { background:#ef4444; }
.no-show { background:#f59e0b; }

.chart-section { background:white; padding:15px; border-radius:10px; margin-top:25px; }
.chart-container { width:100%; max-width:600px; height:250px; margin:15px auto; }
canvas { width:100% !important; height:100% !important; }
</style>
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
function navClass($page) { global $currentPage; return $currentPage === $page ? 'active' : ''; }
?>
<a href="index.php" class="<?php echo navClass('index.php'); ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
<a href="patients.php" class="<?php echo navClass('patients.php'); ?>"><i class="fas fa-users"></i>Patients</a>
<a href="appointments.php" class="<?php echo navClass('appointments.php'); ?>"><i class="fas fa-calendar-check"></i>Appointments</a>
<a href="services.php" class="<?php echo navClass('services.php'); ?>"><i class="fas fa-concierge-bell"></i>Services</a>
<a href="inventory.php" class="<?php echo navClass('inventory.php'); ?>"><i class="fas fa-capsules"></i>Inventory</a>
<a href="reports.php" class="<?php echo navClass('reports.php'); ?>"><i class="fas fa-file-alt"></i>Reports</a>
</div>

<div class="container">
<h2>Dashboard Overview</h2>
<p class="subtext">Welcome back! Here's what's happening today.</p>

<?php
$patients = $conn->query("SELECT COUNT(*) as total FROM patients")->fetch_assoc()['total'] ?? 0;
$appointments = $conn->query("SELECT COUNT(*) as total FROM appointments")->fetch_assoc()['total'] ?? 0;
$completed = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='Completed'")->fetch_assoc()['total'] ?? 0;
$missed = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='Cancelled' OR status='No Show'")->fetch_assoc()['total'] ?? 0;


$methodLabels = $methodData = [];
$methodQuery = $conn->query("SELECT current_method, COUNT(*) as total FROM patients GROUP BY current_method");
while($row = $methodQuery->fetch_assoc()){ $methodLabels[] = $row['current_method']; $methodData[] = (int)$row['total']; }

$serviceLabels = $serviceData = [];
$serviceQuery = $conn->query("SELECT service_type, COUNT(*) as total FROM service_records GROUP BY service_type");
while($row = $serviceQuery->fetch_assoc()){ $serviceLabels[] = $row['service_type']; $serviceData[] = (int)$row['total']; }

$barangayLabels = $barangayData = [];
$barangayQuery = $conn->query("SELECT address, COUNT(*) as total FROM patients GROUP BY address");
while($row = $barangayQuery->fetch_assoc()){ $barangayLabels[] = $row['address']; $barangayData[] = (int)$row['total']; }
?>

<div class="cards">
    <div class="card"><div class="icon"><i class="fas fa-users"></i></div><div><h4>Total Patients</h4><h2><?php echo $patients; ?></h2></div></div>
    <div class="card"><div class="icon"><i class="fas fa-calendar-alt"></i></div><div><h4>Appointments</h4><h2><?php echo $appointments; ?></h2></div></div>
    <div class="card"><div class="icon"><i class="fas fa-check-circle"></i></div><div><h4>Completed</h4><h2><?php echo $completed; ?></h2></div></div>
    <div class="card"><div class="icon"><i class="fas fa-times-circle"></i></div><div><h4>Missed</h4><h2><?php echo $missed; ?></h2></div></div>
</div>


<div class="table-box">
<h3>Recent Appointments</h3>
<table>
<tr>
<th>ID</th><th>Patient</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th>
</tr>
<?php
$result = $conn->query("SELECT * FROM appointments ORDER BY date ASC LIMIT 5");
while($row = $result->fetch_assoc()):
?>
<tr>
    <td><?php echo $row['appointment_id']; ?></td>
    <td><?php echo $row['patient_id']; ?></td>
    <td><?php echo date("m/d/Y", strtotime($row['date'])); ?></td>
    <td><?php echo date("h:i A", strtotime($row['time'])); ?></td>
    <td><?php echo $row['purpose']; ?></td>
    <td>
        <span class="badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
            <?php echo $row['status']; ?>
        </span>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="chart-row">
    <div class="chart-section chart-box">
        <h3>Family Planning Method Distribution</h3>
        <div class="chart-container"><canvas id="methodsChart"></canvas></div>
    </div>

    <div class="chart-section chart-box">
        <h3>Service Type Distribution</h3>
        <div class="chart-container"><canvas id="servicesChart"></canvas></div>
    </div>

    <div class="chart-section chart-box">
        <h3>Client Distribution by Barangay</h3>
        <div class="chart-container"><canvas id="barangayChart"></canvas></div>
    </div>
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
const chartOptions = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } };


const ctxMethods = document.getElementById('methodsChart').getContext('2d');
new Chart(ctxMethods, {
    type: 'bar',
    data: { labels: <?php echo json_encode($methodLabels); ?>, datasets: [{ label:'Clients', data:<?php echo json_encode($methodData); ?>, backgroundColor:'#0f8f5f' }] },
    options: chartOptions
});


const ctxServices = document.getElementById('servicesChart').getContext('2d');
new Chart(ctxServices, {
    type: 'pie',
    data: { labels: <?php echo json_encode($serviceLabels); ?>, datasets: [{ data:<?php echo json_encode($serviceData); ?>, backgroundColor:['#3b82f6','#22c55e','#ef4444','#f59e0b','#8b5cf6','#ec4899'] }] },
    options: chartOptions
});


const ctxBarangay = document.getElementById('barangayChart').getContext('2d');
new Chart(ctxBarangay, {
    type: 'bar',
    data: { labels: <?php echo json_encode($barangayLabels); ?>, datasets: [{ label:'Clients', data:<?php echo json_encode($barangayData); ?>, backgroundColor:'#0f8f5f' }] },
    options: chartOptions
});


function toggleDropdown() {
    var dropdown = document.getElementById("dropdownMenu");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

window.onclick = function(event) {
  if (!event.target.matches('.menu')) {
    var dropdown = document.getElementById("dropdownMenu");
    if (dropdown.style.display === "block") { dropdown.style.display = "none"; }
  }
};
</script>

</div>
</body>
</html>