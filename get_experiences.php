<?php
// get_experiences.php
require_once 'includes/config.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check for filter parameters
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : null;
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : null;
    $weather = isset($_GET['weather']) ? intval($_GET['weather']) : null;
    $traffic = isset($_GET['traffic']) ? intval($_GET['traffic']) : null;
    $road = isset($_GET['road']) ? intval($_GET['road']) : null;
    
    // Build query with filters
    $query = "SELECT 
                de.Driving_Id,
                de.DateOfJourney,
                de.StartingTime,
                de.EndingTime,
                de.TraveledKm,
                wc.Condition as weather,
                wc.Id_Weather as weather_id,
                td.Density as traffic,
                td.Id_Density as traffic_id,
                rt.Type as road,
                rt.Id_Road as road_id,
                TIME_FORMAT(TIMEDIFF(de.EndingTime, de.StartingTime), '%H:%i') as duration
              FROM Driving_Experience de
              LEFT JOIN Weather_Conditions wc ON de.Id_Weather = wc.Id_Weather
              LEFT JOIN Traffic_Density td ON de.Id_Density = td.Id_Density
              LEFT JOIN Road_Type rt ON de.Id_Road = rt.Id_Road
              WHERE 1=1";
    
    $params = [];
    
    if ($dateFrom) {
        $query .= " AND de.DateOfJourney >= :dateFrom";
        $params[':dateFrom'] = $dateFrom;
    }
    
    if ($dateTo) {
        $query .= " AND de.DateOfJourney <= :dateTo";
        $params[':dateTo'] = $dateTo;
    }
    
    if ($weather) {
        $query .= " AND de.Id_Weather = :weather";
        $params[':weather'] = $weather;
    }
    
    if ($traffic) {
        $query .= " AND de.Id_Density = :traffic";
        $params[':traffic'] = $traffic;
    }
    
    if ($road) {
        $query .= " AND de.Id_Road = :road";
        $params[':road'] = $road;
    }
    
    $query .= " ORDER BY de.DateOfJourney DESC, de.StartingTime DESC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        if (is_int($value)) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $experiences = $stmt->fetchAll();
    
    // Get maneuvers for each experience
    foreach ($experiences as &$exp) {
        $maneuverQuery = "SELECT dm.Maneuver 
                          FROM Experience_Maneuvers em
                          JOIN Driving_Maneuvers dm ON em.Id_Maneuver = dm.Id_Maneuver
                          WHERE em.Driving_Id = :id";
        $maneuverStmt = $db->prepare($maneuverQuery);
        $maneuverStmt->bindParam(':id', $exp['Driving_Id'], PDO::PARAM_INT);
        $maneuverStmt->execute();
        $exp['maneuvers'] = $maneuverStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $experiences,
        'count' => count($experiences)
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => [],
        'count' => 0
    ]);
}
?>