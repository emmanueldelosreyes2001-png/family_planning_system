<?php include 'config.php'; ?>

<?php
if(isset($_POST['save_patient'])){
    $check = $conn->prepare("SELECT COUNT(*) as count FROM patients WHERE patient_id = ?");
    $check->bind_param("s", $_POST['patient_id']);
    $check->execute();
    $checkResult = $check->get_result()->fetch_assoc();

    if($checkResult['count'] > 0){
        echo "<script>alert('Patient with this ID already exists. Cannot proceed.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO patients 
            (patient_id,name,age,sex,address,current_method,status) 
            VALUES (?,?,?,?,?,?,?)");

        $stmt->bind_param("ssissss",
            $_POST['patient_id'],
            $_POST['name'],
            $_POST['age'],
            $_POST['sex'],
            $_POST['address'],
            $_POST['method'],
            $_POST['status']
        );

        $stmt->execute();
        echo "<script>alert('Patient registered successfully!'); window.location='patients.php';</script>";
    }
}


if(isset($_GET['delete'])){
    $patient_id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id=?");
    $stmt->bind_param("s", $patient_id);
    $stmt->execute();

    header("Location: patients.php");
    exit;
}

if(isset($_POST['update_patient'])){
    $stmt = $conn->prepare("UPDATE patients SET 
        name=?, age=?, sex=?, address=?, current_method=?, status=?
        WHERE patient_id=?");

    $stmt->bind_param("sisssss",
        $_POST['name'],
        $_POST['age'],
        $_POST['sex'],
        $_POST['address'],
        $_POST['method'],
        $_POST['status'],
        $_POST['patient_id']
    );

    $stmt->execute();
    header("Location: patients.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Patients - Family Planning System</title>

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

.container { padding:20px; }
.top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.search { padding:8px; width:250px; border:1px solid #ccc; border-radius:6px; }

button { background:#0f8f5f; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer; }

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

.active { background: #22c55e; }     
.inactive { background: #ef4444; } 


.table-box { background:white; border-radius:10px; padding:10px; margin-top:25px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; }
th { background:#f9fafb; }
.badge { padding:5px 10px; border-radius:6px; font-size:12px; color:white; }
.scheduled { background:#3b82f6; }
.completed { background:#22c55e; }
.cancelled { background:#ef4444; }


.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); justify-content:center; align-items:center; }
.modal-content { background:white; padding:30px; border-radius:10px; width:400px; }
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

<h2>Patient Records</h2>
<p>Manage family planning patient information</p>

<div class="top-bar">
<input type="text" id="searchInput" class="search" placeholder="Search...">
<button onclick="openModal()">+ Register Patient</button>
</div>

<?php
$total = $conn->query("SELECT COUNT(*) as t FROM patients")->fetch_assoc()['t'];
$active = $conn->query("SELECT COUNT(*) as t FROM patients WHERE status='Active'")->fetch_assoc()['t'];
$inactive = $conn->query("SELECT COUNT(*) as t FROM patients WHERE status='Inactive'")->fetch_assoc()['t'];
?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<div class="cards">
  <div class="card">
    <div class="icon"><i class="fas fa-chart-pie"></i></div>
    <div>
      <h4>Total</h4>
      <h2><?php echo $total; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-user-check"></i></div>
    <div>
      <h4>Active</h4>
      <h2><?php echo $active; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-user-times"></i></div>
    <div>
      <h4>Inactive</h4>
      <h2><?php echo $inactive; ?></h2>
    </div>
  </div>
</div>

<div class="table-box">
<table id="dataTable">
<tr>
<th>ID</th>
<th>Name</th>
<th>Age</th>
<th>Sex</th>
<th>Address</th>
<th>Method</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php
$result = $conn->query("SELECT * FROM patients");
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo $row['patient_id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['age']; ?></td>
<td><?php echo $row['sex']; ?></td>
<td><?php echo $row['address']; ?></td>
<td><?php echo $row['current_method']; ?></td>

<td>
    <span class="badge <?php echo strtolower($row['status']); ?>">
        <?php echo $row['status']; ?>
    </span>
</td>

<td>
<button onclick='viewPatient(<?php echo json_encode($row); ?>)'><i class="fas fa-eye"></i></button>
<button onclick='editPatient(<?php echo json_encode($row); ?>)'><i class="fas fa-pencil-alt"></i></button>
<a href="?delete=<?php echo $row['patient_id']; ?>" onclick="return confirm('Delete this patient?')">
    <button style="background:#ef4444;"><i class="fas fa-trash-alt"></i></button>
</a>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>


<div class="modal" id="patientModal">
<div class="modal-content">
<h3 style="text-align:center;">Register New Patient</h3>
<form method="POST" style="display:flex; flex-direction:column; gap:12px;">

  <div style="display:flex; gap:10px;">
    <input name="patient_id" id="patient_id" placeholder="Patient ID" required style="flex:1;">
    <input name="name" id="name" placeholder="Full Name" required style="flex:2;">
  </div>


  <div style="display:flex; gap:10px;">
    <input type="number" name="age" id="age" placeholder="Age" required style="flex:1;">
    <select name="sex" id="sex" style="flex:1;">
      <option>Female</option>
      <option>Male</option>
    </select>
  </div>


  <input name="address" id="address" placeholder="Address" style="width:95%;">


  <div style="display:flex; gap:10px;">
    <input name="method" id="method" placeholder="Method" style="flex:1;">
    <select name="status" id="status" style="flex:1;">
      <option>Active</option>
      <option>Inactive</option>
    </select>
  </div>

  <div class="modal-actions" style="justify-content:flex-end; gap:10px; margin-top:10px;">
    <button type="button" onclick="closeModal()" style="background:#ccc;color:black;">Cancel</button>
    <button name="save_patient" style="background:#0f8f5f;">Register</button>
  </div>
</form>
</div>
</div>


<div class="modal" id="viewModal">
<div class="modal-content">
<h3>Patient Details</h3>
<div id="patientDetails">
<p><strong>Patient ID:</strong> <span id="v_patient_id"></span></p>
<p><strong>Name:</strong> <span id="v_name"></span></p>
<p><strong>Age:</strong> <span id="v_age"></span></p>
<p><strong>Sex:</strong> <span id="v_sex"></span></p>
<p><strong>Address:</strong> <span id="v_address"></span></p>
<p><strong>Current Method:</strong> <span id="v_method"></span></p>
<p><strong>Status:</strong> <span id="v_status"></span></p>
</div>
<div class="modal-actions">
<button type="button" onclick="closeView()">Close</button>
</div>
</div>
</div>


<div class="modal" id="editModal">
<div class="modal-content">
<h3 style="text-align:center;">Edit Patient</h3>
<form method="POST" id="editForm" style="display:flex; flex-direction:column; gap:12px;">


  <div style="display:flex; gap:10px;">
    <input type="hidden" name="patient_id" id="edit_patient_id">
    <input name="name" id="edit_name" placeholder="Full Name" required style="flex:2;">
  </div>


  <div style="display:flex; gap:10px;">
    <input type="number" name="age" id="edit_age" placeholder="Age" required style="flex:1;">
    <select name="sex" id="edit_sex" style="flex:1;">
      <option>Female</option>
      <option>Male</option>
    </select>
  </div>


  <input name="address" id="edit_address" placeholder="Address" style="width:95%;">


  <div style="display:flex; gap:10px;">
    <input name="method" id="edit_method" placeholder="Method" style="flex:1;">
    <select name="status" id="edit_status" style="flex:1;">
      <option>Active</option>
      <option>Inactive</option>
    </select>
  </div>


  <div class="modal-actions" style="justify-content:flex-end; gap:10px; margin-top:10px;">
    <button type="button" onclick="closeEdit()" style="background:#ccc;color:black;">Close</button>
    <button name="update_patient" style="background:#0f8f5f;">Save</button>
  </div>

</form>
</div>
</div>

<script>
function openModal(){ document.getElementById("patientModal").style.display="flex"; }
function closeModal(){ document.getElementById("patientModal").style.display="none"; }

function viewPatient(data){
    document.getElementById("v_patient_id").innerText = data.patient_id;
    document.getElementById("v_name").innerText = data.name;
    document.getElementById("v_age").innerText = data.age;
    document.getElementById("v_sex").innerText = data.sex;
    document.getElementById("v_address").innerText = data.address;
    document.getElementById("v_method").innerText = data.current_method;
    document.getElementById("v_status").innerText = data.status;
    document.getElementById("viewModal").style.display = "flex";
}
function closeView(){ document.getElementById("viewModal").style.display="none"; }

function editPatient(data){
    document.getElementById("editModal").style.display = "flex";
    document.getElementById("edit_patient_id").value = data.patient_id;
    document.getElementById("edit_name").value = data.name;
    document.getElementById("edit_age").value = data.age;
    document.getElementById("edit_sex").value = data.sex;
    document.getElementById("edit_address").value = data.address;
    document.getElementById("edit_method").value = data.current_method;
    document.getElementById("edit_status").value = data.status;
}
function closeEdit(){ document.getElementById("editModal").style.display="none"; }

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


function allowNumbersOnly(e){ e.target.value = e.target.value.replace(/[^0-9]/g,''); }
function allowLettersOnly(e){ e.target.value = e.target.value.replace(/[^A-Za-z\s]/g,''); }
function allowAddressChars(e){ e.target.value = e.target.value.replace(/[^A-Za-z0-9\s,.-]/g,''); }

document.getElementById('patient_id').addEventListener('input', allowNumbersOnly);
document.getElementById('edit_patient_id').addEventListener('input', allowNumbersOnly);

document.getElementById('age').addEventListener('input', allowNumbersOnly);
document.getElementById('edit_age').addEventListener('input', allowNumbersOnly);

document.getElementById('name').addEventListener('input', allowLettersOnly);
document.getElementById('edit_name').addEventListener('input', allowLettersOnly);

document.getElementById('method').addEventListener('input', allowLettersOnly);
document.getElementById('edit_method').addEventListener('input', allowLettersOnly);

document.getElementById('address').addEventListener('input', allowAddressChars);
document.getElementById('edit_address').addEventListener('input', allowAddressChars);
</script>

</body>
</html>