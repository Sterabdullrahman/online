<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

// Get rating statistics
$rating_stats = $db->prepare("
    SELECT 
        COUNT(*) as total_ratings,
        AVG(rating) as average_rating,
        SUM(rating = 5) as five_star,
        SUM(rating = 4) as four_star,
        SUM(rating = 3) as three_star,
        SUM(rating = 2) as two_star,
        SUM(rating = 1) as one_star
    FROM doctor_ratings
    WHERE doctor_id = ?
");
$rating_stats->execute([$doctor_id]);
$rating_stats = $rating_stats->fetch();

// Get recent reviews (modified query - removed join with appointments table)
$recent_reviews = $db->prepare("
    SELECT r.*, u.full_name as patient_name
    FROM doctor_ratings r
    JOIN users u ON r.patient_id = u.user_id
    WHERE r.doctor_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recent_reviews->execute([$doctor_id]);
$recent_reviews = $recent_reviews->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Your Ratings & Reviews</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>
                    <?php echo $rating_stats['total_ratings'] > 0 ? 
                        number_format($rating_stats['average_rating'], 1) : 'N/A'; ?>
                </h3>
                <p>Average Rating</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $rating_stats['total_ratings']; ?></h3>
                <p>Total Ratings</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Rating Distribution</h2>
            
            <?php if ($rating_stats['total_ratings'] > 0): ?>
            <div class="rating-bars">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-bar-container">
                    <div class="rating-label">
                        <?php echo $i; ?> ★
                    </div>
                    <div class="rating-bar">
                        <div class="rating-bar-fill" 
                             style="width: <?php echo ($rating_stats[$i.'_star'] / $rating_stats['total_ratings']) * 100; ?>%">
                        </div>
                    </div>
                    <div class="rating-count">
                        <?php echo $rating_stats[$i.'_star']; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php else: ?>
                <p>No ratings yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Recent Reviews</h2>
            
            <?php if (empty($recent_reviews)): ?>
                <p>No reviews yet.</p>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($recent_reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-patient">
                                <h3><?php echo htmlspecialchars($review['patient_name']); ?></h3>
                                <p class="review-date">
                                    <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                </p>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="rating-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($review['review'])): ?>
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-right">
                    <a href="all_ratings.php" class="btn btn-primary">View All Reviews</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>