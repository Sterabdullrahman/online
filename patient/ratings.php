<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['patient']);

$db = (new Database())->connect();
$patient_id = $_SESSION['user_id'];

// Get completed appointments that haven't been rated yet
$unrated_appointments = $db->prepare("
    SELECT a.appointment_id, a.doctor_id, a.appointment_date, u.full_name as doctor_name
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    WHERE a.patient_id = :patient_id 
    AND a.status = 'completed'
    AND NOT EXISTS (
        SELECT 1 FROM doctor_ratings r 
        WHERE r.doctor_id = a.doctor_id 
        AND r.patient_id = a.patient_id
    )
    ORDER BY a.appointment_date DESC
");
$unrated_appointments->bindParam(':patient_id', $patient_id);
$unrated_appointments->execute();
$unrated_appointments = $unrated_appointments->fetchAll();

// Get previous ratings
$previous_ratings = $db->prepare("
    SELECT r.*, u.full_name as doctor_name, a.appointment_date
    FROM doctor_ratings r
    JOIN users u ON r.doctor_id = u.user_id
    JOIN appointments a ON a.doctor_id = r.doctor_id AND a.patient_id = r.patient_id
    WHERE r.patient_id = :patient_id
    ORDER BY r.created_at DESC
");
$previous_ratings->bindParam(':patient_id', $patient_id);
$previous_ratings->execute();
$previous_ratings = $previous_ratings->fetchAll();

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $appointment_id = $_POST['appointment_id'];
    $doctor_id = $_POST['doctor_id'];
    $rating = $_POST['rating'];
    $review = trim($_POST['review'] ?? '');
    
    // Validate appointment belongs to patient
    $stmt = $db->prepare("
        SELECT 1 FROM appointments 
        WHERE appointment_id = :appointment_id 
        AND patient_id = :patient_id
        AND status = 'completed'
    ");
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("
            INSERT INTO doctor_ratings 
            (doctor_id, patient_id, rating, review, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$doctor_id, $patient_id, $rating, $review])) {
            $_SESSION['success'] = "Thank you for your rating!";
            header("Location: ratings.php");
            exit();
        } else {
            $error = "Failed to submit rating. Please try again.";
        }
    } else {
        $error = "Invalid appointment.";
    }
}

include '../includes/header.php';
?>

<!-- The rest of your HTML remains the same -->
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Rate Your Appointments</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($unrated_appointments)): ?>
        <div class="card">
            <h2>Appointments Waiting for Your Rating</h2>
            
            <?php foreach ($unrated_appointments as $appt): ?>
            <div class="rating-form-container">
                <h3>Appointment with Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></h3>
                <p>Date: <?php echo date('F j, Y', strtotime($appt['appointment_date'])); ?></p>
                
                <form method="post" action="">
                    <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                    <input type="hidden" name="doctor_id" value="<?php echo $appt['doctor_id']; ?>">
                    
                    <div class="form-group">
                        <label>Your Rating:</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="rating-<?php echo $appt['appointment_id']; ?>-<?php echo $i; ?>" 
                                       name="rating" value="<?php echo $i; ?>" required>
                                <label for="rating-<?php echo $appt['appointment_id']; ?>-<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review-<?php echo $appt['appointment_id']; ?>">Review (Optional)</label>
                        <textarea id="review-<?php echo $appt['appointment_id']; ?>" name="review" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="submit_rating" class="btn btn-primary">Submit Rating</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Your Previous Ratings</h2>
            
            <?php if (empty($previous_ratings)): ?>
                <p>You haven't rated any appointments yet.</p>
            <?php else: ?>
                <div class="ratings-list">
                    <?php foreach ($previous_ratings as $rating): ?>
                    <div class="rating-item">
                        <div class="rating-header">
                            <h3>Dr. <?php echo htmlspecialchars($rating['doctor_name']); ?></h3>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="rating-star <?php echo $i <= $rating['rating'] ? 'active' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($rating['appointment_date'])): ?>
                        <p class="rating-date">Appointment Date: <?php echo date('F j, Y', strtotime($rating['appointment_date'])); ?></p>
                        <?php endif; ?>
                        
                        <p class="rating-date">Rated on: <?php echo date('F j, Y', strtotime($rating['created_at'])); ?></p>
                        
                        <?php if (!empty($rating['review'])): ?>
                        <div class="rating-review">
                            <p><?php echo nl2br(htmlspecialchars($rating['review'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>