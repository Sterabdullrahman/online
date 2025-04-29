<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } else {
        // Check if username or email already exists
        $db = (new Database())->connect();
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = "Username or email already exists";
        } else {
            // Create new patient account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users 
                (username, email, password, phone, full_name, user_type, created_at)
                VALUES (?, ?, ?, ?, ?, 'patient', NOW())
            ");
            
            if ($stmt->execute([$username, $email, $hashed_password, $phone, $full_name])) {
                $success = "Registration successful! You can now login.";
                $_POST = array(); // Clear form
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fas fa-hospital-alt"></i>
            <h1>Hospital Management</h1>
        </div>
        
        <h2 class="auth-title">Patient Registration</h2>
        
        <?php if ($error): ?>
        <div class="notification error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="notification success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="full_name" name="full_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" placeholder="Your full name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Choose a username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Your email address">
                </div>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-phone"></i></span>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="Your phone number (optional)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Create password (min 8 characters)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                           placeholder="Confirm your password">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>