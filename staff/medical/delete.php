<?php
require_once '../../config/init.php';
requireStaff();

$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$record_id) {
    header('Location: index.php');
    exit();
}

// Fetch record information
try {
    $stmt = $pdo->prepare("SELECT prescription FROM medical_records WHERE record_id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        $_SESSION['error_message'] = 'Medical record not found.';
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Error fetching medical record.';
    header('Location: index.php');
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $del = $pdo->prepare("DELETE FROM medical_records WHERE record_id = ?");
        $del->execute([$record_id]);
        // Remove prescription photo if exists
        if (!empty($record['prescription']) && file_exists($record['prescription'])) {
            unlink($record['prescription']);
        }
        $pdo->commit();
        $_SESSION['success_message'] = 'Medical record deleted successfully.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Failed to delete medical record.';
    }
    header('Location: index.php');
    exit();
}

$pageTitle = 'Delete Medical Record - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <?php include '../../includes/favicon.php'; ?>
    <style>
        body {background-color: var(--light-color); margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
        .main-content {margin-left:250px; padding:2rem;}
        .card {background:white; padding:2rem; border-radius:12px; box-shadow:0 2px 4px rgba(0,0,0,0.05);}        
        .actions {display:flex; gap:1rem; margin-top:1rem;}
        .btn {padding:0.75rem 1.5rem; border:none; border-radius:8px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block;}
        .btn-danger {background:#dc3545; color:white;}
        .btn-secondary {background:var(--gray-light); color:var(--text-dark);}
    </style>
</head>
<body>
<?php include '../../includes/sidebar-staff.php'; ?>
<?php include '../../includes/navbar.php'; ?>
<div class="main-content">
    <div class="card">
        <h2>Delete Medical Record</h2>
        <p>Are you sure you want to delete this medical record?</p>
        <form method="POST" class="actions">
            <button type="submit" class="btn btn-danger">Delete</button>
            <a href="view.php?id=<?php echo $record_id; ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>