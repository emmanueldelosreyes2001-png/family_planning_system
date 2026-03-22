<?php
include 'config.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=family_planning_report.xls");

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

function spacingRow($cols){ echo "<tr><td colspan='$cols'>&nbsp;</td></tr>"; }

echo "<h2>Summary</h2>";
echo "<table border='1'>";
echo "<tr><th>Total Clients</th><th>Active Clients</th><th>Services This Month</th><th>Coverage Rate</th></tr>";
echo "<tr><td>$totalClients</td><td>$activeClients</td><td>$servicesThisMonth</td><td>$coverage%</td></tr>";
echo "</table>";
spacingRow(4);

$methodChartConfig = ['type'=>'bar','data'=>['labels'=>$methodsList,'datasets'=>[['label'=>'Clients','data'=>$methodData,'backgroundColor'=>'#0f8f5f']]]];
$methodChartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($methodChartConfig));
echo "<h3>Family Planning Method Distribution</h3>";
echo "<img src='$methodChartUrl' width='600' height='300' />";
spacingRow(1);

$serviceChartConfig = ['type'=>'pie','data'=>['labels'=>$serviceLabels,'datasets'=>[['data'=>$serviceData,'backgroundColor'=>['#3b82f6','#22c55e','#ef4444','#f59e0b','#8b5cf6','#ec4899']]]]];
$serviceChartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($serviceChartConfig));
echo "<h3>Service Distribution</h3>";
echo "<img src='$serviceChartUrl' width='600' height='300' />";
spacingRow(1);

$barangayChartConfig = ['type'=>'bar','data'=>['labels'=>$barangays,'datasets'=>[['label'=>'Clients','data'=>$barangayData,'backgroundColor'=>'#0f8f5f']]]];
$barangayChartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($barangayChartConfig));
echo "<h3>Clients by Barangay</h3>";
echo "<img src='$barangayChartUrl' width='600' height='300' />";
spacingRow(1);

$totalMethodsClients = array_sum($methodData);
$percentages = [];
$rounded = [];
foreach($methodsList as $i => $m) {
    $percentages[$i] = $totalMethodsClients > 0 ? ($methodData[$i]/$totalMethodsClients)*100 : 0;
    $rounded[$i] = floor($percentages[$i]*10)/10; 
}
$sumRounded = array_sum($rounded);
$diff = round(100-$sumRounded,1);
if($diff!=0){ $maxIndex = array_keys($rounded,max($rounded))[0]; $rounded[$maxIndex]+=$diff; }

echo "<h2>Method Summary Table</h2>";
echo "<table border='1'><tr><th>Method</th><th>Clients</th><th>Percentage</th></tr>";
foreach($methodsList as $i => $m){
    echo "<tr><td>$m</td><td>{$methodData[$i]}</td><td>{$rounded[$i]}%</td></tr>";
}
echo "<tr><td><b>Total</b></td><td><b>$totalMethodsClients</b></td><td><b>100%</b></td></tr>";
echo "</table>";
spacingRow(3);

echo "<h2>Service Distribution Table</h2>";
echo "<table border='1'><tr><th>Service</th><th>Clients</th></tr>";
foreach($serviceLabels as $i => $s){
    echo "<tr><td>$s</td><td>{$serviceData[$i]}</td></tr>";
}
echo "</table>";
spacingRow(3);

echo "<h2>Clients by Barangay Table</h2>";
echo "<table border='1'><tr><th>Barangay</th><th>Clients</th></tr>";
foreach($barangays as $i => $b){
    echo "<tr><td>$b</td><td>{$barangayData[$i]}</td></tr>";
}
echo "</table>";
spacingRow(3);

echo "<h2>All Patients</h2>";
echo "<table border='1'><tr><th>Patient ID</th><th>Name</th><th>Age</th><th>Sex</th><th>Address</th><th>Current Method</th><th>Status</th></tr>";
$result = $conn->query("SELECT patient_id,name,age,sex,address,current_method,status FROM patients");
while($row = $result->fetch_assoc()){
    echo "<tr>";
    foreach($row as $val){ echo "<td>$val</td>"; }
    echo "</tr>";
}
echo "</table>";
spacingRow(3);

echo "<h2>Recent Inventory Items</h2>";
echo "<table border='1'>";
echo "<tr><th>Item ID</th><th>Name</th><th>Type</th><th>Quantity</th><th>Expiration Date</th><th>Supplier</th><th>Batch Number</th></tr>";
$inventoryItems = $conn->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 10");
while($item = $inventoryItems->fetch_assoc()){
    $expDate = (!empty($item['expiration_date']) && $item['expiration_date'] != '0000-00-00') 
               ? date('m/d/Y', strtotime($item['expiration_date'])) 
               : '-';
    echo "<tr>";
    echo "<td>{$item['item_id']}</td>";
    echo "<td>{$item['item_name']}</td>";
    echo "<td>{$item['medicine_type']}</td>";
    echo "<td>{$item['quantity']}</td>";
    echo "<td>{$expDate}</td>";
    echo "<td>{$item['supplier']}</td>";
    echo "<td>{$item['batch_number']}</td>";
    echo "</tr>";
}
echo "</table>";
spacingRow(3);
?>