<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];


if(isset($_POST['add_item'])){
    $item_id = !empty($_POST['item_id']) ? $_POST['item_id'] : 'IV' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
    $item_name = $_POST['item_name'];
    $medicine_type = $_POST['medicine_type'];
    $quantity = (int)$_POST['quantity'];
    $supplier = $_POST['supplier'];
    $batch_number = $_POST['batch_number'];
    $expiration_date = $_POST['expiration_date'];

    $stmt = $conn->prepare("INSERT INTO inventory (item_id,item_name,medicine_type,quantity,supplier,batch_number,expiration_date) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("sssisss",$item_id,$item_name,$medicine_type,$quantity,$supplier,$batch_number,$expiration_date);
    $stmt->execute();
    header("Location: inventory.php");
    exit();
}


if(isset($_POST['stock_action'])){
    $item_id = $_POST['item_id'];
    $qty = (int)$_POST['qty'];
    $type = $_POST['type'];
    $notes = $_POST['notes'];
    $supplier = $_POST['supplier'] ?? '';

    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE item_id=?");
    $stmt->bind_param("s",$item_id);
    $stmt->execute();
    $current_qty = $stmt->get_result()->fetch_assoc()['quantity'] ?? 0;

    if($type === "IN"){
        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ?, supplier=? WHERE item_id=?");
        $stmt->bind_param("iss",$qty,$supplier,$item_id);
        $stmt->execute();
    } else {
        if($current_qty >= $qty){
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id=?");
            $stmt->bind_param("is",$qty,$item_id);
            $stmt->execute();
        } else {
            $_SESSION['error'] = "Cannot remove more than current stock!";
        }
    }

    $stmt = $conn->prepare("INSERT INTO stock_transactions (item_id,transaction_type,quantity,notes,performed_by,supplier) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssisss",$item_id,$type,$qty,$notes,$username,$supplier);
    $stmt->execute();

    header("Location: inventory.php");
    exit();
}


if(isset($_POST['dispense'])){
    $item_id = $_POST['item_id'];
    $patient_id = (int)$_POST['patient_id'];
    $qty = (int)$_POST['quantity'];
    $notes = $_POST['notes'];
    $dispensed_by = $username;

    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE item_id=?");
    $stmt->bind_param("s",$item_id);
    $stmt->execute();
    $current_qty = $stmt->get_result()->fetch_assoc()['quantity'] ?? 0;

    if($current_qty >= $qty){
        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id=?");
        $stmt->bind_param("is",$qty,$item_id);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO dispensing_records (item_id,patient_id,quantity,performed_by,notes) VALUES (?,?,?,?,?)");
        $stmt->bind_param("siiss",$item_id,$patient_id,$qty,$dispensed_by,$notes);
        $stmt->execute();
    } else {
        $_SESSION['error'] = "Cannot dispense more than available stock!";
    }

    header("Location: inventory.php");
    exit();
}


$search = $_GET['search'] ?? "";
$filter_type = $_GET['medicine_type'] ?? "";

$query = "SELECT * FROM inventory WHERE 1=1";
$params = [];
$types = "";

if($search !== ""){
    $query .= " AND (item_name LIKE CONCAT('%',?,'%') OR item_id LIKE CONCAT('%',?,'%'))";
    $types .= "ss";
    $params[] = $search;
    $params[] = $search;
}

if($filter_type !== ""){
    $query .= " AND medicine_type=?";
    $types .= "s";
    $params[] = $filter_type;
}

$query .= " ORDER BY item_name ASC";
$stmt = $conn->prepare($query);
if(count($params) > 0){
    $stmt->bind_param($types,...$params);
}
$stmt->execute();
$items = $stmt->get_result();


$total_items = $conn->query("SELECT COUNT(*) as total FROM inventory")->fetch_assoc()['total'] ?? 0;
$total_stock = $conn->query("SELECT SUM(quantity) as total FROM inventory")->fetch_assoc()['total'] ?? 0;
$low_stock = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE quantity < 20")->fetch_assoc()['total'] ?? 0;
$expiring_soon = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?? 0;
$transactions = $conn->query("SELECT * FROM stock_transactions ORDER BY transaction_date DESC");
$dispensing = $conn->query("SELECT d.*, p.name AS patient_name FROM dispensing_records d LEFT JOIN patients p ON d.patient_id = p.patient_id ORDER BY d.dispense_date DESC");


$currentPage = basename($_SERVER['PHP_SELF']);
function navClass($page){
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Inventory - Family Planning System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fa;
}

.header {
    background: #0f8f5f;
    color: white;
    padding: 20px;
    position: relative;
}

.header h1 {
    margin: 0;
}

.header h2,
.header p {
    margin: 5px 0 0;
    font-size: 14px;
}

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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 100;
}

.dropdown a {
    color: black;
    text-decoration: none;
    display: block;
    padding: 10px 15px;
}

.dropdown a:hover {
    background: #f0f0f0;
}

.nav {
    background: white;
    padding: 12px 20px;
    display: flex;
    gap: 25px;
    border-bottom: 1px solid #ddd;
}

.nav a {
    text-decoration: none;
    color: #555;
    border-bottom: 2px solid transparent;
    padding-bottom: 5px;
    transition: all 0.2s;
}

.nav a.active {
    color: #0f8f5f;
    border-bottom: 2px solid #0f8f5f;
    background: transparent;
}

.container {
    padding: 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.search {
    padding: 8px;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    background: #0f8f5f;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.card {
    background: white;
    padding: 15px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.card h4 {
    margin: 0;
    font-size: 14px;
    color: #777;
}

.card h2 {
    margin: 5px 0 0;
    color: #0f8f5f;
    font-size: 28px;
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

.dashboard-cards {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.dashboard-cards .card {
    flex: 1 1 20%;
    min-width: 120px;
}
</style>
</head>
<body>

<div class="header" style="display:flex; align-items:center; gap:10px;">
    <i class="far fa-heart" style="font-size:50px; color:white;"></i>
<div>
    <h1 style="margin:0; font-size:32px; font-weight:bold;">Family Planning Monitoring System</h1>
    <p style="margin:5px 0 0; font-size:14px; font-weight:normal;">Rural Health Unit - Dumingag, Zamboanga del Sur</p>
</div>
<div class="menu" onclick="toggleDropdown()" style="margin-left:auto; font-size:24px; cursor:pointer;">☰</div>
<div class="dropdown" id="dropdownMenu"><a href="logout.php">Logout</a></div>
</div>

<div class="nav">
<a href="index.php" class="<?= navClass('index.php'); ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
<a href="patients.php" class="<?= navClass('patients.php'); ?>"><i class="fas fa-users"></i>Patients</a>
<a href="appointments.php" class="<?= navClass('appointments.php'); ?>"><i class="fas fa-calendar-check"></i>Appointments</a>
<a href="services.php" class="<?= navClass('services.php'); ?>"><i class="fas fa-concierge-bell"></i>Services</a>
<a href="inventory.php" class="<?= navClass('inventory.php'); ?>"><i class="fas fa-capsules"></i>Inventory</a>
<a href="reports.php" class="<?= navClass('reports.php'); ?>"><i class="fas fa-file-alt"></i>Reports</a>
</div>

<div class="d-flex mb-4" style="gap:15px;">
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center" style="flex:1;">
        <div class="icon"><i class="fas fa-boxes"></i></div>
        <h4>Total Items</h4>
        <h2><?= $total_items ?></h2>
    </div>
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center" style="flex:1;">
        <div class="icon"><i class="fas fa-layer-group"></i></div>
        <h4>Total Stock</h4>
        <h2><?= $total_stock ?></h2>
    </div>
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center text-danger" style="flex:1;">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h4>Low Stock</h4>
        <h2><?= $low_stock ?></h2>
    </div>
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center text-warning" style="flex:1;">
        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        <h4>Expiring Soon</h4>
        <h2><?= $expiring_soon ?></h2>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#inventoryTab">Current Inventory</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#transactionsTab">Stock Transactions</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dispenseTab">Dispensing Records</a></li>
</ul>

<div class="tab-content bg-white p-3 shadow-sm">
<div class="tab-pane fade show active" id="inventoryTab">
<div class="top-bar mb-3">
<input type="text" id="searchInput" class="search" placeholder="Search inventory...">
<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">Add New Item</button>
</div>

<table class="table table-bordered table-striped" id="dataTable">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Type</th>
    <th>Qty</th>
    <th>Expiration Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php while($row = $items->fetch_assoc()):
    $today = date('Y-m-d');
    $exp_date = $row['expiration_date'];

    if($row['quantity'] < 20){ 
        $badge="<span class='badge bg-danger'>Low Stock</span>"; 
    } elseif($exp_date < $today){ 
        $badge="<span class='badge bg-danger'>Expired</span>"; 
    } elseif(strtotime($exp_date) <= strtotime("+30 days")){ 
        $badge="<span class='badge bg-warning'>Expiring Soon</span>"; 
    } else { 
        $badge="<span class='text-muted'>OK</span>"; 
    }

    $exp_display = date('m/d/Y', strtotime($exp_date));
?>
<tr>
    <td><?= htmlspecialchars($row['item_id']) ?></td>
    <td><?= htmlspecialchars($row['item_name']) ?></td>
    <td><?= htmlspecialchars($row['medicine_type']) ?></td>
    <td><?= $row['quantity'] ?></td>
    <td><?= $exp_display ?></td>
    <td><?= $badge ?></td>
    <td>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal<?= $row['item_id'] ?>">Stock</button>
        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#dispenseModal<?= $row['item_id'] ?>">Dispense</button>
    </td>
</tr>

<div class="modal fade" id="stockModal<?= $row['item_id'] ?>">
<div class="modal-dialog"><div class="modal-content p-3">
<h5>Stock Action</h5>
<form method="POST">
<input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
<select name="type" class="form-control mb-2">
<option value="IN">Stock In</option>
<option value="OUT">Stock Out</option>
</select>
<input type="number" name="qty" class="form-control mb-2" placeholder="Quantity" required>
<input type="text" name="supplier" class="form-control mb-2" placeholder="Supplier (if stock in)">
<textarea name="notes" class="form-control mb-2" placeholder="Notes"></textarea>
<button name="stock_action" class="btn btn-success">Submit</button>
</form>
</div></div></div>

<div class="modal fade" id="dispenseModal<?= $row['item_id'] ?>">
<div class="modal-dialog"><div class="modal-content p-3">
<h5>Dispense Item</h5>
<form method="POST">
<input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
<div class="mb-2">
<label>Patient</label>
<select name="patient_id" class="form-control" required>
<?php
$patients = $conn->query("SELECT patient_id,name FROM patients ORDER BY name ASC");
while($p = $patients->fetch_assoc()){
    echo "<option value='{$p['patient_id']}'>{$p['name']}</option>";
}
?>
</select>
</div>
<div class="mb-2"><label>Quantity</label><input type="number" name="quantity" class="form-control" required></div>
<div class="mb-2"><label>Notes/Purpose</label><textarea name="notes" class="form-control"></textarea></div>
<button name="dispense" class="btn btn-warning">Dispense</button>
</form>
</div></div></div>

<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="tab-pane fade" id="transactionsTab">
<table class="table table-bordered table-striped">
<thead class="table-dark"><tr><th>Item ID</th><th>Type</th><th>Qty</th><th>Date</th><th>Notes</th></tr></thead>
<tbody>
<?php while($t=$transactions->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($t['item_id']) ?></td>
<td><?= htmlspecialchars($t['transaction_type']) ?></td>
<td><?= $t['quantity'] ?></td>
<td><?= $t['transaction_date'] ?></td>
<td><?= htmlspecialchars($t['notes']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="tab-pane fade" id="dispenseTab">
<table class="table table-bordered table-striped">
<thead class="table-dark"><tr><th>Item ID</th><th>Patient</th><th>Qty</th><th>Date</th><th>Performed By</th><th>Notes</th></tr></thead>
<tbody>
<?php while($d=$dispensing->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($d['item_id']) ?></td>
<td><?= htmlspecialchars($d['patient_name']) ?></td>
<td><?= $d['quantity'] ?></td>
<td><?= $d['dispense_date'] ?></td>
<td><?= htmlspecialchars($d['performed_by']) ?></td>
<td><?= htmlspecialchars($d['notes']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</div>


<div class="modal fade" id="addModal">
<div class="modal-dialog"><div class="modal-content p-3">
<h5>Add New Inventory Item</h5>
<form method="POST">
<div class="mb-2"><label>Item ID</label><input type="text" name="item_id" class="form-control" required></div>
<div class="mb-2"><label>Item Name</label><input type="text" name="item_name" class="form-control" required></div>
<div class="mb-2"><label>Medicine Type</label>
<select name="medicine_type" class="form-control" required>
    <option value="condom">Condom</option>
    <option value="pill">Pill</option>
    <option value="injection">Injection</option>
    <option value="implant">Implant</option>
    <option value="other">Other</option>
</select>
</div>

<div class="mb-2"><label>Quantity</label><input type="number" name="quantity" class="form-control" required></div>
<div class="mb-2"><label>Expiration Date</label><input type="date" name="expiration_date" class="form-control" required></div>
<div class="mb-2"><label>Supplier</label><input type="text" name="supplier" class="form-control"></div>
<div class="mb-2"><label>Batch Number</label><input type="text" name="batch_number" class="form-control"></div>
<button type="submit" name="add_item" class="btn btn-success">Add Item</button>
</form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDropdown(){
    var menu = document.getElementById('dropdownMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

document.getElementById('searchInput').addEventListener('input', function(){
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#dataTable tbody tr');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>