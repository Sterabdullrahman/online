<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Redirect logged-in users to their respective dashboards
if ($auth->isLoggedIn()) {
    switch ($_SESSION['user_type']) {
        case 'patient':
            header("Location: patient/dashboard.php");
            break;
        case 'doctor':
            header("Location: doctor/dashboard.php");
            break;
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'super_admin':
            header("Location: superadmin/dashboard.php");
            break;
        default:
            header("Location: auth/login.php");
    }
    exit();
}

include 'includes/header.php';
?>

<style>
/* Base Styles */
:root {
  --primary: #3498db;
  --primary-dark: #2980b9;
  --secondary: #f1c40f;
  --dark: #2c3e50;
  --light: #ecf0f1;
  --gray: #95a5a6;
  --success: #2ecc71;
  --danger: #e74c3c;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background-color: #f9f9f9;
  color: #333;
  line-height: 1.6;
}

.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Buttons */
.btn {
  display: inline-block;
  padding: 10px 20px;
  border-radius: 5px;
  font-weight: 500;
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background-color: var(--primary);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-outline {
  background-color: transparent;
  color: var(--primary);
  border: 1px solid var(--primary);
}

.btn-outline:hover {
  background-color: rgba(52, 152, 219, 0.1);
}

.btn-lg {
  padding: 12px 24px;
  font-size: 1.1rem;
}

/* Header */
.landing-header {
  background-color: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
}

.navbar-brand h1 {
  color: var(--primary);
  font-size: 1.8rem;
}

.navbar-actions {
  display: flex;
  gap: 15px;
}

/* Hero Section */
.hero-section {
  padding: 80px 0;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.hero-content {
  max-width: 600px;
}

.hero-content h2 {
  font-size: 2.5rem;
  margin-bottom: 20px;
  color: var(--dark);
}

.hero-content p {
  font-size: 1.1rem;
  color: var(--gray);
  margin-bottom: 30px;
}

.hero-actions {
  display: flex;
  gap: 15px;
}

.hero-image img {
  max-width: 100%;
  height: auto;
  border-radius: 10px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

/* Features Section */
.features-section {
  padding: 80px 0;
  background-color: white;
}

.section-title {
  text-align: center;
  margin-bottom: 50px;
  font-size: 2rem;
  color: var(--dark);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
}

.feature-card {
  background-color: white;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-5px);
}

.feature-icon {
  font-size: 2.5rem;
  color: var(--primary);
  margin-bottom: 20px;
}

.feature-card h3 {
  margin-bottom: 15px;
  font-size: 1.3rem;
}

.feature-card p {
  color: var(--gray);
}

/* Testimonials */
.testimonials-section {
  padding: 80px 0;
  background-color: #f5f7fa;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
}

.testimonial-card {
  background-color: white;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.testimonial-content {
  font-style: italic;
  margin-bottom: 20px;
  color: var(--gray);
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 15px;
}

.author-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: var(--light);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary);
  font-size: 1.2rem;
}

.author-info h4 {
  margin-bottom: 5px;
}

.author-info p {
  color: var(--gray);
  font-size: 0.9rem;
}

/* Footer */
.landing-footer {
  background-color: var(--dark);
  color: white;
  padding: 50px 0 20px;
}

.footer-content {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  margin-bottom: 30px;
}

.footer-brand {
  flex: 1;
  min-width: 300px;
}

.footer-brand h3 {
  margin-bottom: 15px;
  color: white;
}

.footer-links {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
}

.link-group {
  min-width: 150px;
}

.link-group h4 {
  margin-bottom: 15px;
  color: white;
}

.link-group ul {
  list-style: none;
}

.link-group li {
  margin-bottom: 10px;
}

.link-group a {
  color: var(--gray);
  transition: color 0.3s ease;
}

.link-group a:hover {
  color: white;
}

.copyright {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--gray);
  font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-section .container {
    flex-direction: column;
    text-align: center;
  }
  
  .hero-actions {
    justify-content: center;
  }
  
  .navbar {
    flex-direction: column;
    gap: 20px;
  }
  
  .section-title {
    font-size: 1.8rem;
  }
}

@media (max-width: 480px) {
  .hero-content h2 {
    font-size: 2rem;
  }
  
  .hero-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>

<div class="landing-container">
    <header class="landing-header">
        <div class="container">
            <nav class="navbar">
                <div class="navbar-brand">
                    <h1>Hospital Management System</h1>
                </div>
                <div class="navbar-actions">
                    <a href="auth/login.php" class="btn btn-outline">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Register as Patient</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="landing-main">
        <section class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h2>Online Doctors Book Appointments</h2>
                    <p class="lead">Streamline your hospital operations, patient care, and appointment scheduling with our comprehensive management system.</p>
                    <div class="hero-actions">
                        <a href="auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
                        <a href="#features" class="btn btn-outline btn-lg">Learn More</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="features-section">
            <div class="container">
                <h2 class="section-title">Key Features</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>Online Appointments</h3>
                        <p>Patients can easily book appointments with doctors based on availability.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h3>Doctor Management</h3>
                        <p>Manage doctor schedules, specialties, and availability efficiently.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h3>Hospital Administration</h3>
                        <p>Comprehensive tools for managing hospitals, departments, and staff.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Rating System</h3>
                        <p>Patients can rate their experience to help improve healthcare quality.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <h3>Medical Records</h3>
                        <p>Secure digital storage for patient medical history and records.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Analytics Dashboard</h3>
                        <p>Track performance metrics and hospital operations statistics.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="testimonials-section">
            <div class="container">
                <h2 class="section-title">What Our Users Say</h2>
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"This system has transformed how we manage our hospital. Appointment scheduling is now 50% more efficient!"</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="author-info">
                                <h4>Dr. Sarah Johnson</h4>
                                <p>Chief Medical Officer</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"As a patient, I love being able to book appointments online and see my medical history in one place."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="author-info">
                                <h4>Michael Thompson</h4>
                                <p>Patient</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"The admin tools make managing our hospital departments and staff incredibly straightforward."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <div class="author-info">
                                <h4>Lisa Chen</h4>
                                <p>Hospital Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h3>Hospital Management System</h3>
                    <p>Comprehensive healthcare management solution for modern hospitals</p>
                </div>
                <div class="footer-links">
                    <div class="link-group">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="auth/login.php">Login</a></li>
                            <li><a href="auth/register.php">Register</a></li>
                            <li><a href="#features">Features</a></li>
                        </ul>
                    </div>
                    <div class="link-group">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>© <?php echo date('Y'); ?> Hospital Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>
</div>

<?php include 'includes/footer.php'; ?>