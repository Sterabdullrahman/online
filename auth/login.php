<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if ($auth->login($username, $password)) {
        // Redirect based on user type
        switch ($_SESSION['user_type']) {
            case 'patient':
                header("Location: ../patient/dashboard.php");
                break;
            case 'doctor':
                header("Location: ../doctor/dashboard.php");
                break;
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;
            case 'super_admin':
                header("Location: ../superadmin/dashboard.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
    } else {
        $error = "Invalid username or password";
    }
}

include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Hospital Login</h1>
        
        <?php if ($error): ?>
        <div class="notification error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Register as Patient</a></p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>