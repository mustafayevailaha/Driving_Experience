<?php
// delete_experience.php
require_once 'includes/config.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if it's a POST request and has ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get and validate ID
        $id = intval($_POST['id']);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid experience ID']);
            exit;
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // 1. First delete from Experience_Maneuvers (child table)
        $deleteManeuvers = "DELETE FROM Experience_Maneuvers WHERE Driving_Id = :id";
        $stmt1 = $db->prepare($deleteManeuvers);
        $stmt1->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt1->execute();
        
        // 2. Then delete from Driving_Experience (parent table)
        $deleteExperience = "DELETE FROM Driving_Experience WHERE Driving_Id = :id";
        $stmt2 = $db->prepare($deleteExperience);
        $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt2->execute()) {
            // Check if any row was actually deleted
            if ($stmt2->rowCount() > 0) {
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Experience deleted successfully']);
            } else {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Experience not found']);
            }
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to delete experience']);
        }
        
    } catch(PDOException $e) {
        // Rollback if in transaction
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing ID']);
}
?>