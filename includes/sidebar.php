<?php
if (!isset($_SESSION['user_type'])) {
    return;
}

$userType = $_SESSION['user_type'];
$activePage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>Hospital Management</h3>
        <p><?php echo ucfirst($userType); ?> Panel</p>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($userType === 'patient'): ?>
            <a href="dashboard.php" class="<?php echo $activePage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="appointments.php" class="<?php echo $activePage === 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a href="book_appointment.php" class="<?php echo $activePage === 'book_appointment.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Book Appointment
            </a>
            <a href="ratings.php" class="<?php echo $activePage === 'ratings.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> My Ratings
            </a>
            <a href="profile.php" class="<?php echo $activePage === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            
        <?php elseif ($userType === 'doctor'): ?>
            <a href="dashboard.php" class="<?php echo $activePage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="appointments.php" class="<?php echo $activePage === 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a href="schedule.php" class="<?php echo $activePage === 'schedule.php' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> My Schedule
            </a>
            <a href="ratings.php" class="<?php echo $activePage === 'ratings.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> My Ratings
            </a>
            <a href="profile.php" class="<?php echo $activePage === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            
        <?php elseif ($userType === 'admin'): ?>
            <a href="dashboard.php" class="<?php echo $activePage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="doctors.php" class="<?php echo $activePage === 'doctors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Doctors
            </a>
            <a href="appointments.php" class="<?php echo $activePage === 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a href="hospitals.php" class="<?php echo $activePage === 'hospitals.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i> Hospital Info
            </a>
            
        <?php elseif ($userType === 'super_admin'): ?>
            <a href="dashboard.php" class="<?php echo $activePage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="hospitals.php" class="<?php echo $activePage === 'hospitals.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i> Hospitals
            </a>
            <a href="admins.php" class="<?php echo $activePage === 'admins.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Admins
            </a>
        <?php endif; ?>
        
        <a href="../auth/logout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

