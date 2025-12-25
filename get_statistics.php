<?php
// get_statistics.php
require_once 'includes/config.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get total kilometers
    $totalKmQuery = "SELECT SUM(TraveledKm) as totalKm FROM Driving_Experience";
    $totalStmt = $db->prepare($totalKmQuery);
    $totalStmt->execute();
    $totalKm = $totalStmt->fetch();
    
    // Get statistics by weather
    $weatherStatsQuery = "SELECT wc.Condition, COUNT(*) as count 
                          FROM Driving_Experience de
                          JOIN Weather_Conditions wc ON de.Id_Weather = wc.Id_Weather
                          GROUP BY wc.Condition
                          ORDER BY count DESC";
    $weatherStmt = $db->prepare($weatherStatsQuery);
    $weatherStmt->execute();
    $weatherStats = $weatherStmt->fetchAll();
    
    // Get statistics by traffic
    $trafficStatsQuery = "SELECT td.Density, COUNT(*) as count 
                          FROM Driving_Experience de
                          JOIN Traffic_Density td ON de.Id_Density = td.Id_Density
                          GROUP BY td.Density
                          ORDER BY FIELD(td.Density, 'No Traffic', 'Low', 'Medium', 'High', 'Extremely High')";
    $trafficStmt = $db->prepare($trafficStatsQuery);
    $trafficStmt->execute();
    $trafficStats = $trafficStmt->fetchAll();
    
    // Get statistics by road type
    $roadStatsQuery = "SELECT rt.Type, COUNT(*) as count 
                       FROM Driving_Experience de
                       JOIN Road_Type rt ON de.Id_Road = rt.Id_Road
                       GROUP BY rt.Type
                       ORDER BY count DESC";
    $roadStmt = $db->prepare($roadStatsQuery);
    $roadStmt->execute();
    $roadStats = $roadStmt->fetchAll();
    
    // Get average distance per journey
    $avgQuery = "SELECT AVG(TraveledKm) as avgDistance FROM Driving_Experience";
    $avgStmt = $db->prepare($avgQuery);
    $avgStmt->execute();
    $avgDistance = $avgStmt->fetch();
    
    $stats = [
        'totalKm' => $totalKm['totalKm'] ? round($totalKm['totalKm'], 2) : 0,
        'avgDistance' => $avgDistance['avgDistance'] ? round($avgDistance['avgDistance'], 2) : 0,
        'weatherStats' => $weatherStats,
        'trafficStats' => $trafficStats,
        'roadStats' => $roadStats,
        'totalJourneys' => count($weatherStats) // Approximate count
    ];
    
    echo json_encode($stats);
    
} catch(PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage(),
        'totalKm' => 0,
        'avgDistance' => 0,
        'weatherStats' => [],
        'trafficStats' => [],
        'roadStats' => [],
        'totalJourneys' => 0
    ]);
}
?>