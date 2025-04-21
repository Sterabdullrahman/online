<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['admin']);

$db = (new Database())->connect();
$admin_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get hospital info
$hospital = $db->query("SELECT * FROM hospitals WHERE hospital_id = $hospital_id")->fetch();

// Get departments count
$departments_count = $db->query("SELECT COUNT(*) FROM departments WHERE hospital_id = $hospital_id")->fetchColumn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $stmt = $db->prepare("
        UPDATE hospitals 
        SET name = ?, address = ?, city = ?, phone = ?, email = ?
        WHERE hospital_id = ?
    ");
    
    if ($stmt->execute([$name, $address, $city, $phone, $email, $hospital_id])) {
        $_SESSION['success'] = "Hospital information updated successfully!";
        header("Location: hospitals.php");
        exit();
    } else {
        $error = "Failed to update hospital information.";
    }
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Hospital Information</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $departments_count; ?></h3>
                <p>Departments</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Edit Hospital Details</h2>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="name">Hospital Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($hospital['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($hospital['address']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" 
                               value="<?php echo htmlspecialchars($hospital['city']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($hospital['phone']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($hospital['email']); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Information</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Departments</h2>
            <a href="departments.php" class="btn btn-primary">Manage Departments</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>