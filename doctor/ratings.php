<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

// Get rating statistics
$rating_stats_stmt = $db->prepare("
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
$rating_stats_stmt->execute([$doctor_id]);
$rating_stats = $rating_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent reviews
$recent_reviews_stmt = $db->prepare("
    SELECT r.*
    FROM doctor_ratings r
    WHERE r.doctor_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recent_reviews_stmt->execute([$doctor_id]);
$recent_reviews = $recent_reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Your Ratings & Reviews</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>
                    <?php echo ($rating_stats['total_ratings'] ?? 0) > 0 ?
                        number_format($rating_stats['average_rating'] ?? 0, 1) : 'N/A'; ?>
                </h3>
                <p>Average Rating</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $rating_stats['total_ratings'] ?? 0; ?></h3>
                <p>Total Ratings</p>
            </div>
        </div>

        <div class="card">
            <h2>Rating Distribution</h2>

            <?php if (($rating_stats['total_ratings'] ?? 0) > 0): ?>
            <div class="rating-bars">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-bar-container">
                    <div class="rating-label">
                        <?php echo $i; ?> ★
                    </div>
                    <div class="rating-bar">
                        <div class="rating-bar-fill"
                             style="width: <?php echo (($rating_stats[$i.'_star'] ?? 0) / ($rating_stats['total_ratings'] ?? 1)) * 100; ?>%">
                        </div>
                    </div>
                    <div class="rating-count">
                        <?php echo $rating_stats[$i.'_star'] ?? 0; ?>
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
                                <h3>Patient</h3> <p class="review-date">
                                    <?php echo date('F j, Y', strtotime($review['created_at'] ?? '')); ?>
                                </p>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="rating-star <?php echo $i <= ($review['rating'] ?? 0) ? 'active' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <?php if (!empty($review['review'])): ?>
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['review'] ?? '')); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-right">
                    <a href="ratings.php" class="btn btn-primary">View All Reviews</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Basic dashboard styling */
.dashboard {
    display: flex;
}

.sidebar {
    background-color: var(--dark);
    color: var(--white);
    padding: var(--space-lg) 0;
  }
  
  .sidebar-nav {
    padding: var(--space-lg) 0;
  }
  
  .sidebar-nav a {
    display: block;
    padding: var(--space-sm) var(--space-lg);
    color: var(--white);
  }
  
  .sidebar-nav a:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
  
  .sidebar-nav a.active {
    background-color: var(--primary);
  }
.main-content {
    flex-grow: 1;
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.stat-card h3 {
    margin-top: 0;
    font-size: 2em;
    color: #007bff;
}

.stat-card p {
    margin-bottom: 0;
    color: #6c757d;
}

.card {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.card h2 {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: var(--space-lg);
    margin-bottom: var(--space-lg);
}

.rating-bars {
    margin-top: 15px;
}

.rating-bar-container {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.rating-label {
    width: 50px;
    margin-right: 10px;
}

.rating-bar {
    background-color: #f0f0f0;
    height: 20px;
    border-radius: 5px;
    flex-grow: 1;
    overflow: hidden;
    margin-right: 10px;
}

.rating-bar-fill {
    background-color: #ffc107;
    height: 100%;
    border-radius: 5px;
}

.rating-count {
    width: 30px;
    text-align: right;
    color: #777;
}

.reviews-list {
    margin-top: 15px;
}

.review-item {
    padding: 15px;
    border: 1px solid #eee;
    margin-bottom: 10px;
    border-radius: 5px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.review-patient h3 {
    margin-top: 0;
    font-size: 1.1em;
}

.review-date {
    color: #777;
    font-size: 0.9em;
}

.review-rating {
    color: gold;
    font-size: 1.2em;
}

.review-content p {
    margin-top: 0;
    color: #555;
}

.text-right {
    text-align: right;
    margin-top: 15px;
}
</style>

<?php include '../includes/footer.php'; ?>