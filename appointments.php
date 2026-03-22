<?php include 'config.php'; ?>

<?php
if(isset($_POST['save_appointment'])){
    $stmt = $conn->prepare("INSERT INTO appointments 
    (appointment_id,patient_id,date,time,purpose,status) 
    VALUES (?,?,?,?,?,?)");

    $stmt->bind_param("ssssss",
        $_POST['appointment_id'],
        $_POST['patient_id'],
        $_POST['date'],
        $_POST['time'],
        $_POST['purpose'],
        $_POST['status']
    );

    $stmt->execute();
}


if(isset($_GET['update_status'])){
    $id = $_GET['id'];
    $status = $_GET['update_status'];

    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE appointment_id=?");
    $stmt->bind_param("ss", $status, $id);
    $stmt->execute();

    header("Location: appointments.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Appointments - Family Planning System</title>

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
.top-bar { display:flex; justify-content:space-between; margin-bottom:20px; }
.search { padding:8px; width:250px; border:1px solid #ccc; border-radius:6px; }

button { background:#0f8f5f; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer; }

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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


.table-box { background:white; border-radius:10px; padding:10px; margin-top:25px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; }
th { background:#f9fafb; }
.badge { padding:5px 10px; border-radius:6px; font-size:12px; color:white; }
.scheduled { background:#3b82f6; }
.completed { background:#22c55e; }
.cancelled { background:#ef4444; }



.Scheduled { background:#3b82f6; }
.Completed { background:#22c55e; }
.Cancelled { background:#ef4444; }
.NoShow { background:#f59e0b; }


.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); justify-content:center; align-items:center; }
.modal-content { background:white; padding:20px; border-radius:10px; width:400px; }
input, select { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:6px; }
.modal-actions { display:flex; justify-content:flex-end; gap:10px; }
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

<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="nav">
    <a href="index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i>Dashboard
    </a>
    <a href="patients.php" class="<?= $currentPage == 'patients.php' ? 'active' : '' ?>">
        <i class="fas fa-users"></i>Patients
    </a>
    <a href="appointments.php" class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i>Appointments
    </a>
    <a href="services.php" class="<?= $currentPage == 'services.php' ? 'active' : '' ?>">
        <i class="fas fa-concierge-bell"></i>Services
    </a>
    <a href="inventory.php" class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-capsules"></i>Inventory
    </a>
    <a href="reports.php" class="<?= $currentPage == 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-file-alt"></i>Reports
    </a>
</div>

<div class="container">

<h2>Appointments</h2>
<p>Schedule and manage patient appointments</p>

<div class="top-bar">
<input type="text" id="searchInput" class="search" placeholder="Search appointments...">
<button onclick="openModal()">+ Schedule Appointment</button>
</div>

<?php
$scheduled = $conn->query("SELECT COUNT(*) as t FROM appointments WHERE status='Scheduled'")->fetch_assoc()['t'];
$completed = $conn->query("SELECT COUNT(*) as t FROM appointments WHERE status='Completed'")->fetch_assoc()['t'];
$cancelled = $conn->query("SELECT COUNT(*) as t FROM appointments WHERE status='Cancelled'")->fetch_assoc()['t'];
$noshow = $conn->query("SELECT COUNT(*) as t FROM appointments WHERE status='No Show'")->fetch_assoc()['t'];
?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<div class="cards">
  <div class="card">
    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
    <div>
      <h4>Scheduled</h4>
      <h2><?php echo $scheduled; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-check-circle"></i></div>
    <div>
      <h4>Completed</h4>
      <h2><?php echo $completed; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-times-circle"></i></div>
    <div>
      <h4>Cancelled</h4>
      <h2><?php echo $cancelled; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-user-slash"></i></div>
    <div>
      <h4>No Show</h4>
      <h2><?php echo $noshow; ?></h2>
    </div>
  </div>
</div>

<div class="table-box">
<table id="dataTable">
<tr>
<th>ID</th>
<th>Patient</th>
<th>Date</th>
<th>Time</th>
<th>Purpose</th>
<th>Status</th>
<th>Actions</th>
</tr>

<?php
$result = $conn->query("SELECT a.*, p.name as patient_name 
FROM appointments a 
JOIN patients p ON a.patient_id=p.patient_id");

while($row = $result->fetch_assoc()):
$statusClass = str_replace(' ', '', $row['status']);
?>
<tr>
<td><?php echo $row['appointment_id']; ?></td>
<td><?php echo $row['patient_name']; ?></td>
<td><?php echo date("m/d/Y", strtotime($row['date'])); ?></td>
<td><?php echo date("h:i A", strtotime($row['time'])); ?></td>
<td><?php echo $row['purpose']; ?></td>
<td>
<span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span>
</td>
<td>
<form method="GET">
<input type="hidden" name="id" value="<?php echo $row['appointment_id']; ?>">
<select name="update_status" onchange="this.form.submit()">
<option value="Scheduled">Scheduled</option>
<option value="Completed">Completed</option>
<option value="Cancelled">Cancelled</option>
<option value="No Show">No Show</option>
</select>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>


<div class="modal" id="appointmentModal">
<div class="modal-content">
<h3 style="text-align:center;">Schedule Appointment</h3>
<form method="POST" style="display:flex; flex-direction:column; gap:12px;">


  <div style="display:flex; gap:10px;">
    <input name="appointment_id" id="appointment_id" placeholder="Appointment ID" required style="flex:1;">
    <select name="patient_id" id="patient_id" required style="flex:2;">
      <option value="">Select Patient</option>
      <?php
      $p = $conn->query("SELECT patient_id,name FROM patients");
      while($row=$p->fetch_assoc()){
          echo "<option value='{$row['patient_id']}'>{$row['name']}</option>";
      }
      ?>
    </select>
  </div>


  <div style="display:flex; gap:10px;">
    <input type="date" name="date" id="date" required style="flex:1;">
    <input type="time" name="time" id="time" required style="flex:1;">
  </div>


  <div style="display:flex; gap:10px;">
    <input name="purpose" id="purpose" placeholder="Purpose" required style="flex:2;">
    <select name="status" id="status" style="flex:1;">
      <option>Scheduled</option>
      <option>Completed</option>
      <option>Cancelled</option>
      <option>No Show</option>
    </select>
  </div>


  <div class="modal-actions" style="justify-content:flex-end; gap:10px; margin-top:10px;">
    <button type="button" onclick="closeModal()" style="background:#ccc;color:black;">Cancel</button>
    <button name="save_appointment" style="background:#0f8f5f;">Save</button>
  </div>

</form>
</div>
</div>

<script>
function openModal(){ document.getElementById("appointmentModal").style.display="flex"; }
function closeModal(){ document.getElementById("appointmentModal").style.display="none"; }


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


document.getElementById('searchInput').addEventListener('keyup', function() {
    var filter = this.value.toLowerCase();
    var table = document.getElementById('dataTable');
    var tr = table.getElementsByTagName('tr');
    for (var i = 1; i < tr.length; i++) { 
        var rowText = tr[i].textContent.toLowerCase();
        tr[i].style.display = rowText.indexOf(filter) > -1 ? '' : 'none';
    }
});
</script>

</body>
</html>