<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

// Get current schedule - Fixed the column name to day_of_week
$stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$doctor_id]);
$schedules = $stmt->fetchAll();

// Group by day
$schedule_by_day = [];
foreach ($schedules as $schedule) {
    $schedule_by_day[$schedule['day_of_week']][] = $schedule;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Delete all existing schedules
        $stmt = $db->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        
        // Insert new schedules from table
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            if (isset($_POST['active_'.$day]) && $_POST['active_'.$day] == '1') {
                $stmt = $db->prepare("
                    INSERT INTO doctor_schedules 
                    (doctor_id, day_of_week, start_time, end_time, duration_per_patient, max_patients, valid_from, valid_to, is_recurring)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $doctor_id, 
                    $day, 
                    $_POST['start_'.$day],
                    $_POST['end_'.$day],
                    $_POST['duration_'.$day] ?? 15,
                    $_POST['max_'.$day] ?? 10,
                    $_POST['valid_from_'.$day] ?? date('Y-m-d'),
                    !empty($_POST['valid_to_'.$day]) ? $_POST['valid_to_'.$day] : null
                ]);
            }
        }
        
        $db->commit();
        $_SESSION['success'] = "Schedule updated successfully!";
        header("Location: schedule.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Failed to update schedule: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Manage Schedule</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Weekly Schedule</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Day</th>
                                    <th>Available</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Duration (min)</th>
                                    <th>Max Patients</th>
                                    <th>Valid From</th>
                                    <th>Valid To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                <?php 
                                    $has_schedule = isset($schedule_by_day[$day]);
                                    $schedule = $has_schedule ? $schedule_by_day[$day][0] : null;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($day) ?></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input day-toggle" type="checkbox" 
                                                   name="active_<?= $day ?>" value="1"
                                                   <?= $has_schedule ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control" name="start_<?= $day ?>"
                                               value="<?= $has_schedule ? substr($schedule['start_time'], 0, 5) : '09:00' ?>"
                                               <?= !$has_schedule ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control" name="end_<?= $day ?>"
                                               value="<?= $has_schedule ? substr($schedule['end_time'], 0, 5) : '17:00' ?>"
                                               <?= !$has_schedule ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="duration_<?= $day ?>"
                                               value="<?= $has_schedule ? $schedule['duration_per_patient'] : 15 ?>"
                                               min="5" max="120" <?= !$has_schedule ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="max_<?= $day ?>"
                                               value="<?= $has_schedule ? $schedule['max_patients'] : 10 ?>"
                                               min="1" <?= !$has_schedule ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="date" class="form-control" name="valid_from_<?= $day ?>"
                                               value="<?= $has_schedule ? $schedule['valid_from'] : date('Y-m-d') ?>"
                                               <?= !$has_schedule ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="date" class="form-control" name="valid_to_<?= $day ?>"
                                               value="<?= $has_schedule ? $schedule['valid_to'] : '' ?>"
                                               <?= !$has_schedule ? 'disabled' : '' ?>>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" name="update_schedule" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable/disable row inputs when toggling availability
    document.querySelectorAll('.day-toggle').forEach(toggle => {
        // Initialize on page load
        const row = toggle.closest('tr');
        const inputs = row.querySelectorAll('input:not(.day-toggle)');
        inputs.forEach(input => {
            input.disabled = !toggle.checked;
        });

        // Add change event listener
        toggle.addEventListener('change', function() {
            const row = this.closest('tr');
            const inputs = row.querySelectorAll('input:not(.day-toggle)');
            inputs.forEach(input => {
                input.disabled = !this.checked;
                if (this.checked && input.type === 'time' && !input.value) {
                    // Set default time if empty
                    if (input.name.includes('start_')) {
                        input.value = '09:00';
                    } else if (input.name.includes('end_')) {
                        input.value = '17:00';
                    }
                }
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>