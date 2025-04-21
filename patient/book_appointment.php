<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['patient']);

$db = (new Database())->connect();
$patient_id = $_SESSION['user_id'];

// Step 1: Show all hospitals
$hospitals_stmt = $db->query("SELECT * FROM hospitals");
$hospitals = $hospitals_stmt->fetchAll();

// Step 2: If hospital selected, show its doctors
$selected_hospital_id = $_GET['hospital_id'] ?? null;
$doctors = [];
$hospital_info = null;
if ($selected_hospital_id) {
    // Get hospital info
    $stmt = $db->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
    $stmt->execute([$selected_hospital_id]);
    $hospital_info = $stmt->fetch();

    // Get doctors with their ratings, applying search filter if present
    $search_term = $_GET['search_doctor'] ?? '';
    $search_condition = '';
    if (!empty($search_term)) {
        $search_condition = "AND u.full_name LIKE :search_term";
    }

    $stmt_doctors = $db->prepare("
        SELECT u.user_id, u.full_name, dp.specialization, dp.qualification,
        AVG(dr.rating) as avg_rating, COUNT(dr.rating_id) as review_count
        FROM users u
        JOIN doctor_profiles dp ON u.user_id = dp.doctor_id
        LEFT JOIN doctor_ratings dr ON u.user_id = dr.doctor_id
        JOIN departments d ON dp.dept_id = d.dept_id
        WHERE d.hospital_id = :hospital_id AND u.user_type = 'doctor' AND u.is_active = 1
        $search_condition
        GROUP BY u.user_id
    ");
    $stmt_doctors->bindParam(':hospital_id', $selected_hospital_id, PDO::PARAM_INT);
    if (!empty($search_term)) {
        $stmt_doctors->bindValue(':search_term', '%' . $search_term . '%', PDO::PARAM_STR);
    }
    $stmt_doctors->execute();
    $doctors = $stmt_doctors->fetchAll();
}

// Step 3: If doctor selected, show booking form
$selected_doctor_id = $_GET['doctor_id'] ?? null;
$doctor_info = null;
$schedule_data = [];

if ($selected_doctor_id) {
    // Get doctor info with rating and hospital name
    $stmt = $db->prepare("
        SELECT u.*, dp.*, h.name as hospital_name,
               AVG(dr.rating) as avg_rating, COUNT(dr.rating_id) as review_count
        FROM users u
        JOIN doctor_profiles dp ON u.user_id = dp.doctor_id
        JOIN departments d ON dp.dept_id = d.dept_id
        JOIN hospitals h ON d.hospital_id = h.hospital_id
        LEFT JOIN doctor_ratings dr ON u.user_id = dr.doctor_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->execute([$selected_doctor_id]);
    $doctor_info = $stmt->fetch();

    // Get schedule for next 14 days
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+14 days'));

    // Get doctor's weekly schedule
    $weekly_schedule_stmt = $db->prepare("
        SELECT * FROM doctor_schedules
        WHERE doctor_id = ?
        AND (valid_to IS NULL OR valid_to >= CURDATE())
        ORDER BY day_of_week, start_time
    ");
    $weekly_schedule_stmt->execute([$selected_doctor_id]);
    $weekly_schedule = $weekly_schedule_stmt->fetchAll();

    // Get booked appointments
    $booked_slots_stmt = $db->prepare("
        SELECT a.appointment_date, a.start_time
        FROM appointments a
        WHERE a.doctor_id = ?
        AND a.appointment_date BETWEEN ? AND ?
        AND a.status IN ('pending', 'confirmed')
    ");
    $booked_slots_stmt->execute([$selected_doctor_id, $start_date, $end_date]);
    $booked_slots = $booked_slots_stmt->fetchAll();

    // Organize booked slots by date
    $booked_slots_by_date = [];
    foreach ($booked_slots as $slot) {
        $booked_slots_by_date[$slot['appointment_date']][] = $slot['start_time'];
    }


    // Generate schedule data
    for ($i = 0; $i < 14; $i++) {
        $current_date = date('Y-m-d', strtotime("+$i days"));
        $day_of_week = date('l', strtotime($current_date));

        // Find schedule for this day
        $day_schedule = null;
        foreach ($weekly_schedule as $schedule) {
            if ($schedule['day_of_week'] === $day_of_week) {
                $day_schedule = $schedule;
                break;
            }
        }

        if ($day_schedule) {
            $start = new DateTime($day_schedule['start_time']);
            $end = new DateTime($day_schedule['end_time']);
            $duration = $day_schedule['duration_per_patient'];

            $slots = [];
            while ($start < $end) {
                $slot_time = $start->format('H:i:s');
                $display_time = $start->format('h:i A');

                $is_available = !isset($booked_slots_by_date[$current_date]) ||
                              !in_array($slot_time, $booked_slots_by_date[$current_date]);

                $slots[] = [
                    'time' => $slot_time,
                    'display' => $display_time,
                    'available' => $is_available
                ];

                $start->add(new DateInterval("PT{$duration}M"));
            }

            $schedule_data[$current_date] = [
                'day_name' => $day_of_week,
                'date' => $current_date,
                'display_date' => date('M j, Y', strtotime($current_date)),
                'slots' => $slots
            ];
        }
    }
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['selected_date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'] ?? '';

    // Get the schedule ID for the selected doctor, date, and time
    $schedule_stmt = $db->prepare("
        SELECT schedule_id
        FROM doctor_schedules ds
        WHERE ds.doctor_id = ?
        AND ds.day_of_week = DAYNAME(?)
        AND ? BETWEEN ds.start_time AND ds.end_time
    ");
    $schedule_stmt->execute([$doctor_id, $date, $time]);
    $schedule_id = $schedule_stmt->fetchColumn();

    if ($schedule_id) {
        // Get the end time based on the selected time and doctor's schedule duration
        $duration_stmt = $db->prepare("
            SELECT duration_per_patient
            FROM doctor_schedules
            WHERE schedule_id = ?
        ");
        $duration_stmt->execute([$schedule_id]);
        $duration = $duration_stmt->fetchColumn();

        if ($duration) {
            $start_time = new DateTime($time);
            $end_time = clone $start_time;
            $end_time->add(new DateInterval("PT{$duration}M"));

            // Insert appointment
            $stmt = $db->prepare("
                INSERT INTO appointments
                (patient_id, doctor_id, schedule_id, appointment_date, start_time, end_time, status, reason, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");

            if ($stmt->execute([
                $patient_id, $doctor_id, $schedule_id, $date,
                $start_time->format('H:i:s'), $end_time->format('H:i:s'), $reason
            ])) {
                $_SESSION['success'] = "Appointment booked successfully!";
                header("Location: appointments.php");
                exit();
            } else {
                $error = "Failed to book appointment. Please try again.";
            }
        } else {
            $error = "Could not determine appointment duration.";
        }
    } else {
        $error = "Selected time slot is not available.";
    }
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$selected_hospital_id ): ?>
            <h1>Select a Hospital</h1>
            <div class="hospital-grid">
                <?php foreach ($hospitals as $hospital): ?>
                    <div class="hospital-card">
                        <div class="hospital-image">
                            <img src="../uploads/hospitals/<?php echo htmlspecialchars($hospital['logo_url'] ?? 'default.jpg'); ?>"
                                 alt="<?php echo htmlspecialchars($hospital['name']); ?>">
                        </div>
                        <div class="hospital-info">
                            <h3><?php echo htmlspecialchars($hospital['name']); ?></h3>
                            <p><?php echo htmlspecialchars($hospital['address']); ?></p>
                            <p><?php echo htmlspecialchars($hospital['phone']); ?></p>
                            <a href="?hospital_id=<?php echo $hospital['hospital_id']; ?>" class="btn btn-primary">
                                View Doctors
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($selected_hospital_id && !$selected_doctor_id): ?>
            <div class="hospital-header">
                <h1><?php echo htmlspecialchars($hospital_info['name']); ?></h1>
                <p><?php echo htmlspecialchars($hospital_info['address']); ?></p>
                <a href="book_appointment.php" class="btn btn-secondary">Back to Hospitals</a>
            </div>

            <div class="search-doctors">
                <form method="get" action="">
                    <input type="hidden" name="hospital_id" value="<?php echo $selected_hospital_id; ?>">
                    <input type="text" name="search_doctor" placeholder="Search Doctor Name">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <h2>Available Doctors</h2>
            <div class="doctors-table-container">
                <?php if (empty($doctors)): ?>
                    <p>No doctors found in this hospital<?php if (!empty($_GET['search_doctor'])): ?> matching your search criteria<?php endif; ?>.</p>
                <?php else: ?>
                    <table class="doctors-table">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Qualification</th>
                                <th>Rating</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td>
                                        <div class="doctor-info">
                                            <strong>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['qualification']); ?></td>
                                    <td>
                                        <div class="rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= round($doctor['avg_rating']) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                            <span>(<?php echo $doctor['review_count']; ?>)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="?hospital_id=<?php echo $selected_hospital_id; ?>&doctor_id=<?php echo $doctor['user_id']; ?>"
                                           class="btn btn-primary">
                                            Book Appointment
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php elseif ($selected_doctor_id): ?>
            <div class="booking-header">
                <a href="?hospital_id=<?php echo $selected_hospital_id; ?>" class="btn btn-secondary">
                    ← Back to Doctors
                </a>
                <h1>Book Appointment</h1>
                <div class="doctor-profile">
                    <div class="doctor-image">
                        <img src="../uploads/doctors/<?php echo htmlspecialchars($doctor_info['profile_pic'] ?? 'default.jpg'); ?>"
                             alt="Dr. <?php echo htmlspecialchars($doctor_info['full_name']); ?>">
                    </div>
                    <div class="doctor-details">
                        <h2>Dr. <?php echo htmlspecialchars($doctor_info['full_name']); ?></h2>
                        <p class="specialization"><?php echo htmlspecialchars($doctor_info['specialization']); ?></p>
                        <p class="hospital"><?php echo htmlspecialchars($doctor_info['hospital_name']); ?></p>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= round($doctor_info['avg_rating']) ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                            <span>(<?php echo $doctor_info['review_count']; ?> reviews)</span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" action="" class="booking-form">
            <input type="hidden" name="selected_date" id="selected_date">
                <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor_id; ?>">
                <h3>Select Date & Time</h3>
                <div class="schedule-table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Available Time Slots</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedule_data)): ?>
                                <tr><td colspan="3">No available slots for the next 14 days.</td></tr>
                            <?php else: ?>
                                <?php foreach ($schedule_data as $date => $day_data): ?>
                                    <tr>
                                        <td><?php echo $day_data['display_date']; ?></td>
                                        <td><?php echo $day_data['day_name']; ?></td>
                                        <td>
                                            <?php if (!empty($day_data['slots'])): ?>
                                                <div class="time-slots">
                                                    <?php foreach ($day_data['slots'] as $slot): ?>
                                                        <?php if ($slot['available']): ?>
                                                            <label class="time-slot available">
                                                                <input type="radio" name="time" value="<?php echo $slot['time']; ?>" required>
                                                                <input type="hidden" name="date" value="<?php echo $date; ?>">
                                                                <span><?php echo $slot['display']; ?></span>
                                                            </label>
                                                        <?php else: ?>
                                                            <span class="time-slot booked"><?php echo $slot['display']; ?></span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="no-slots">Not available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Appointment (Optional)</label>
                    <textarea id="reason" name="reason" class="form-control" rows="3"
                              placeholder="Briefly describe the reason for your visit"></textarea>
                </div>

                <button type="submit" name="book_appointment" class="btn btn-primary btn-block">
                    Confirm Appointment
                </button>
            
            </form>
            <script>
    const timeSlots = document.querySelectorAll('.time-slot.available input[type="radio"]');
    const selectedDateInput = document.getElementById('selected_date');
    const scheduleTable = document.querySelector('.schedule-table');

    scheduleTable.addEventListener('click', function(event) {
        const clickedCell = event.target.closest('tr');
        if (clickedCell) {
            const dateCell = clickedCell.querySelector('td:first-child');
            if (dateCell) {
                // Extract the date in YYYY-MM-DD format
                const displayDate = dateCell.textContent;
                const year = displayDate.split(', ')[1];
                const monthDay = displayDate.split(', ')[0];
                const monthStr = monthDay.split(' ')[0];
                const day = monthDay.split(' ')[1];

                const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const monthIndex = monthNames.indexOf(monthStr);
                const month = (monthIndex + 1).toString().padStart(2, '0');
                const formattedDay = day.padStart(2, '0');

                const selectedDateValue = `${year}-${month}-${formattedDay}`;
                selectedDateInput.value = selectedDateValue;
            }
        }
    });

    // Optional: You might want to visually indicate the selected date in the table
    // (e.g., by adding a class to the selected row).
</script>
        <?php endif; ?>
    </div>
</div>

<style>
/* Existing styles remain the same */
.search-doctors {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-doctors input[type="text"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    flex-grow: 1;
}

.search-doctors button {
    padding: 10px 15px;
    border: none;
    background-color: #007bff;
    color: white;
    }
  
/* Existing styles remain the same */
.hospital-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.hospital-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.hospital-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.hospital-image img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.hospital-info {
    padding: 15px;
}

.hospital-info h3 {
    margin-top: 0;
    color: #2c3e50;
}

.doctors-table-container {
    margin-top: 20px;
    overflow-x: auto;
}

.doctors-table {
    width: 100%;
    border-collapse: collapse;
}

.doctors-table th,
.doctors-table td {
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    text-align: left;
}

.doctors-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.doctors-table tr:hover {
    background-color: #f8f9fa;
}

.rating {
    color: #f39c12;
    display: flex;
    align-items: center;
}

.rating .star {
    margin-right: 2px;
}

.rating .star.filled {
    color: #f39c12;
}

.booking-header {
    margin-bottom: 30px;
}

.doctor-profile {
    display: flex;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.doctor-image {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 20px;
}

.doctor-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.doctor-details h2 {
    margin: 0 0 5px 0;
}

.specialization {
    color: #7f8c8d;
    margin: 0 0 5px 0;
    font-weight: 500;
}

.hospital {
    color: #34495e;
    margin: 0;
}

.schedule-table-container {
    margin: 20px 0;
    overflow-x: auto;
}

.schedule-table {
    width: 100%;
    border-collapse: collapse;
}

.schedule-table th,
.schedule-table td {
    padding: 15px;
    border: 1px solid #e0e0e0;
    text-align: left;
}

.schedule-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.time-slots {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.time-slot {
    padding: 8px 15px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.time-slot.available {
    background-color: #e8f5e9;
    border: 1px solid #a5d6a7;
    color: #2e7d32;
}

.time-slot.available:hover {
    background-color: #c8e6c9;
}

.time-slot input[type="radio"]:checked + span {
    background-color: #4caf50;
    color: white;
}

.time-slot.booked {
    background-color: #ffebee;
    border: 1px solid #ef9a9a;
    color: #c62828;
    cursor: not-allowed;
}

.time-slot input[type="radio"] {
    display: none;
}

.no-slots {
    color: #95a5a6;
    font-style: italic;
}

.btn-block {
    display: block;
    width: 100%;
    padding: 12px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .hospital-grid {
        grid-template-columns: 1fr;
    }

    .doctor-profile {
        flex-direction: column;
        text-align: center;
    }

    .doctor-image {
        margin-right: 0;
        margin-bottom: 15px;
    }

    .time-slots {
        flex-direction: column;
    }

    .time-slot {
        width: 100%;
        text-align: center;
    }
}

/* Styles for the search section */
.search-doctors {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-doctors input[type="text"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    flex-grow: 1;
}

.search-doctors button {
    padding: 10px 15px;
    border: none;
    background-color: #007bff;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.search-doctors button:hover {
    background-color: #0056b3;
}
</style>