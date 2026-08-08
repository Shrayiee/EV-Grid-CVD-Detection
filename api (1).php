<?php
// ============================================
//  AD LAB — CVD Detection System
//  MySQL Connection & API (PHP Backend)
//  File: api.php
// ============================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");

// ============================================
//  DATABASE CONFIG — Change these values!
// ============================================
define('DB_HOST',     'localhost');
define('DB_USER',     'root');        // your MySQL username
define('DB_PASS',     '');            // your MySQL password
define('DB_NAME',     'adlab_cvd');

// ============================================
//  CONNECT TO MYSQL
// ============================================
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
        exit();
    }
    return $conn;
}

// ============================================
//  ROUTE HANDLER
// ============================================
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'save':       saveAssessment();   break;
    case 'getAll':     getAllAssessments(); break;
    case 'getStats':   getStats();         break;
    case 'delete':     deleteAssessment(); break;
    default:
        echo json_encode(["message" => "AD LAB API is running ✅", "version" => "1.0"]);
}

// ============================================
//  SAVE PATIENT ASSESSMENT
//  POST /api.php?action=save
// ============================================
function saveAssessment() {
    $conn = getConnection();
    $data = json_decode(file_get_contents("php://input"), true);

    // Sanitize inputs
    $name      = $conn->real_escape_string($data['patient_name'] ?? 'Anonymous');
    $age       = (int)($data['age'] ?? 0);
    $gender    = $conn->real_escape_string($data['gender'] ?? 'Male');
    $height    = (float)($data['height_cm'] ?? 0);
    $weight    = (float)($data['weight_kg'] ?? 0);
    $sbp       = (int)($data['systolic_bp'] ?? 0);
    $dbp       = (int)($data['diastolic_bp'] ?? 0);
    $chol      = $conn->real_escape_string($data['cholesterol'] ?? 'Normal');
    $gluc      = $conn->real_escape_string($data['glucose'] ?? 'Normal');
    $smoke     = (int)($data['smoke'] ?? 0);
    $alcohol   = (int)($data['alcohol'] ?? 0);
    $active    = (int)($data['active'] ?? 1);
    $riskScore = (int)($data['risk_score'] ?? 0);
    $riskLevel = $conn->real_escape_string($data['risk_level'] ?? 'Low');
    $cvdPred   = (int)($data['cvd_prediction'] ?? 0);

    $sql = "INSERT INTO patient_assessments 
            (patient_name, age, gender, height_cm, weight_kg,
             systolic_bp, diastolic_bp, cholesterol, glucose,
             smoke, alcohol, active, risk_score, risk_level, cvd_prediction)
            VALUES 
            ('$name', $age, '$gender', $height, $weight,
             $sbp, $dbp, '$chol', '$gluc',
             $smoke, $alcohol, $active, $riskScore, '$riskLevel', $cvdPred)";

    if ($conn->query($sql)) {
        echo json_encode([
            "success" => true,
            "message" => "Assessment saved successfully",
            "id"      => $conn->insert_id
        ]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    $conn->close();
}

// ============================================
//  GET ALL ASSESSMENTS
//  GET /api.php?action=getAll
// ============================================
function getAllAssessments() {
    $conn   = getConnection();
    $result = $conn->query("SELECT * FROM assessment_summary LIMIT 100");
    $rows   = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode(["success" => true, "data" => $rows, "count" => count($rows)]);
    $conn->close();
}

// ============================================
//  GET STATS / DASHBOARD NUMBERS
//  GET /api.php?action=getStats
// ============================================
function getStats() {
    $conn = getConnection();

    $total    = $conn->query("SELECT COUNT(*) AS c FROM patient_assessments")->fetch_assoc()['c'];
    $high     = $conn->query("SELECT COUNT(*) AS c FROM patient_assessments WHERE risk_level='High'")->fetch_assoc()['c'];
    $moderate = $conn->query("SELECT COUNT(*) AS c FROM patient_assessments WHERE risk_level='Moderate'")->fetch_assoc()['c'];
    $low      = $conn->query("SELECT COUNT(*) AS c FROM patient_assessments WHERE risk_level='Low'")->fetch_assoc()['c'];
    $cvdPos   = $conn->query("SELECT COUNT(*) AS c FROM patient_assessments WHERE cvd_prediction=1")->fetch_assoc()['c'];
    $avgBmi   = $conn->query("SELECT ROUND(AVG(bmi),1) AS b FROM patient_assessments")->fetch_assoc()['b'];

    echo json_encode([
        "success"       => true,
        "total"         => (int)$total,
        "high_risk"     => (int)$high,
        "moderate_risk" => (int)$moderate,
        "low_risk"      => (int)$low,
        "cvd_positive"  => (int)$cvdPos,
        "avg_bmi"       => (float)$avgBmi
    ]);
    $conn->close();
}

// ============================================
//  DELETE ASSESSMENT
//  GET /api.php?action=delete&id=5
// ============================================
function deleteAssessment() {
    $conn = getConnection();
    $id   = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["success" => false, "error" => "Invalid ID"]);
        return;
    }
    if ($conn->query("DELETE FROM patient_assessments WHERE id=$id")) {
        echo json_encode(["success" => true, "message" => "Record deleted"]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    $conn->close();
}
?>
