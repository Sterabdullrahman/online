<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

$db = (new Database())->connect();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch the user's current password from the database
    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $_SESSION['success'] = "Password updated successfully!";
                header("Location: ../doctor/profile.php");
                exit();
            } else {
                $_SESSION['error'] = "Error updating password. Please try again.";
                header("Location: ../doctor/profile.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "New password and confirm password do not match.";
            header("Location: ../doctor/profile.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Incorrect current password.";
        header("Location: ../doctor/profile.php");
        exit();
    }
} else {
    // If the page is accessed directly without a POST request
    header("Location: ../doctor/profile.php");
    exit();
}
?>