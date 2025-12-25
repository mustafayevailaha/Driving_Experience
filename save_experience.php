<?php
// save_experience.php
require_once 'includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get form data
        $date = $_POST['date'] ?? '';
        $startingTime = $_POST['startingTime'] ?? '';
        $endingTime = $_POST['endingTime'] ?? '';
        $kilometers = $_POST['km'] ?? '';
        $weather = $_POST['idWeather'] ?? '';
        $traffic = $_POST['idTrafficDensity'] ?? '';
        $road = $_POST['idRoad'] ?? '';
        $maneuvers = $_POST['maneuvers'] ?? [];
        
        // Validate required fields
        if (empty($date) || empty($startingTime) || empty($endingTime) || 
            empty($kilometers) || empty($weather) || empty($traffic) || empty($road) || 
            empty($maneuvers)) {
            die("Error: All fields are required.");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Insert into Driving_Experience table
        $query = "INSERT INTO Driving_Experience 
                  (DateOfJourney, StartingTime, EndingTime, TraveledKm, Id_Weather, Id_Density, Id_Road) 
                  VALUES (:date, :startingTime, :endingTime, :kilometers, :weather, :traffic, :road)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':startingTime', $startingTime);
        $stmt->bindParam(':endingTime', $endingTime);
        $stmt->bindParam(':kilometers', $kilometers);
        $stmt->bindParam(':weather', $weather);
        $stmt->bindParam(':traffic', $traffic);
        $stmt->bindParam(':road', $road);
        
        if ($stmt->execute()) {
            $drivingId = $db->lastInsertId();
            
            // Insert maneuvers into Experience_Maneuvers
            if (!empty($maneuvers)) {
                foreach ($maneuvers as $maneuverId) {
                    $maneuverQuery = "INSERT INTO Experience_Maneuvers (Driving_Id, Id_Maneuver) 
                                      VALUES (:drivingId, :maneuverId)";
                    $maneuverStmt = $db->prepare($maneuverQuery);
                    $maneuverStmt->bindParam(':drivingId', $drivingId);
                    $maneuverStmt->bindParam(':maneuverId', $maneuverId);
                    $maneuverStmt->execute();
                }
            }
            
            // Commit transaction
            $db->commit();
            
            // Redirect to summary page instead of showing message
            header('Location: summaryreport.php?message=saved');
            exit();
            
        } else {
            $db->rollBack();
            die("Error: Could not save experience to database.");
        }
        
    } catch(PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    // If not POST request, redirect to form
    header('Location: index.php');
    exit();
}
?>