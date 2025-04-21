<?php
// ... (existing functions)

/**
 * Get doctor's average rating
 */
function getDoctorAverageRating($db, $doctor_id) {
    $stmt = $db->prepare("
        SELECT AVG(rating) as average_rating 
        FROM doctor_ratings 
        WHERE doctor_id = ?
    ");
    $stmt->execute([$doctor_id]);
    $result = $stmt->fetch();
    return $result['average_rating'] ? round($result['average_rating'], 1) : null;
}

/**
 * Get doctor's rating distribution
 */
function getDoctorRatingDistribution($db, $doctor_id) {
    $stmt = $db->prepare("
        SELECT 
            SUM(rating = 5) as five_star,
            SUM(rating = 4) as four_star,
            SUM(rating = 3) as three_star,
            SUM(rating = 2) as two_star,
            SUM(rating = 1) as one_star
        FROM doctor_ratings
        WHERE doctor_id = ?
    ");
    $stmt->execute([$doctor_id]);
    return $stmt->fetch();
}

/**
 * Check if appointment can be rated by patient
 */
function canRateAppointment($db, $appointment_id, $patient_id) {
    $stmt = $db->prepare("
        SELECT 1 FROM appointments 
        WHERE appointment_id = ? 
        AND patient_id = ?
        AND status = 'completed'
        AND NOT EXISTS (
            SELECT 1 FROM doctor_ratings 
            WHERE appointment_id = ?
        )
    ");
    $stmt->execute([$appointment_id, $patient_id, $appointment_id]);
    return (bool) $stmt->fetch();
}