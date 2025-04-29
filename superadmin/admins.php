<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['super_admin']);

$db = (new Database())->connect();

// Get hospital filter
$hospital_id = $_GET['hospital_id'] ?? null;
if ($hospital_id && !is_numeric($hospital_id)) {
    header("Location: admins.php");
    exit();
}

// Get all hospitals for dropdown
$hospitals = $db->query("SELECT * FROM hospitals ORDER BY name")->fetchAll();

// Get admins based on filter
$sql = "
    SELECT u.*, h.name as hospital_name
    FROM users u
    LEFT JOIN hospitals h ON u.hospital_id = h.hospital_id
    WHERE u.user_type = 'admin'
";

if ($hospital_id) {
    $sql .= " AND u.hospital_id = :hospital_id";
}

$sql .= " ORDER BY u.full_name";

$stmt = $db->prepare($sql);

if ($hospital_id) {
    $stmt->bindParam(':hospital_id', $hospital_id);
}

$stmt->execute();
$admins = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $hospital_id = $_POST['hospital_id'];
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = password_hash('admin123', PASSWORD_DEFAULT); // Default password
        
        $stmt = $db->prepare("
            INSERT INTO users 
            (username, email, password, phone, full_name, user_type, hospital_id, created_at)
            VALUES (?, ?, ?, ?, ?, 'admin', ?, NOW())
        ");
        
        if ($stmt->execute([$username, $email, $password, $phone, $full_name, $hospital_id])) {
            $_SESSION['success'] = "Admin added successfully! Default password: admin123";
        } else {
            $error = "Failed to add admin.";
        }
    } elseif (isset($_POST['toggle_status'])) {
        $admin_id = $_POST['admin_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE user_id = ? AND user_type = 'admin'");
        if ($stmt->execute([$new_status, $admin_id])) {
            $_SESSION['success'] = "Admin status updated successfully!";
        } else {
            $error = "Failed to update admin status.";
        }
    } elseif (isset($_POST['reset_password'])) {
        $admin_id = $_POST['admin_id'];
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ? AND user_type = 'admin'");
        if ($stmt->execute([$password, $admin_id])) {
            $_SESSION['success'] = "Password reset successfully! New password: admin123";
        } else {
            $error = "Failed to reset password.";
        }
    }
    
    header("Location: admins.php" . ($hospital_id ? "?hospital_id=$hospital_id" : ""));
    exit();
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Manage Hospital Admins</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="filter-bar">
                <form method="get" action="">
                    <div class="form-group">
                        <label for="hospital_id">Filter by Hospital:</label>
                        <select id="hospital_id" name="hospital_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Hospitals</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value="<?php echo $hospital['hospital_id']; ?>" <?php echo $hospital_id == $hospital['hospital_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hospital['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <h2>Add New Admin</h2>
            
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="hospital_id">Hospital</label>
                        <select id="hospital_id" name="hospital_id" class="form-control" required>
                            <option value="">-- Select Hospital --</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value="<?php echo $hospital['hospital_id']; ?>" <?php echo $hospital_id == $hospital['hospital_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hospital['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control">
                </div>
                
                <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Admin List</h2>
            
            <?php if (empty($admins)): ?>
                <p>No admins found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Hospital</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['hospital_name'] ?? 'Unassigned'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($admin['email']); ?><br>
                                <?php echo htmlspecialchars($admin['phone']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $admin['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['user_id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $admin['is_active']; ?>">
                                    <button type="submit" name="toggle_status" class="btn <?php echo $admin['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                        <?php echo $admin['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['user_id']; ?>">
                                    <button type="submit" name="reset_password" class="btn btn-info btn-sm" 
                                            onclick="return confirm('Reset password to default (admin123)?');">
                                        Reset Password
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>