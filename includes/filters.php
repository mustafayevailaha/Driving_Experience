<?php
// includes/filters.php
function generateFilterOptions() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get all filter options with backticks for reserved word
        $weatherStmt = $db->prepare("SELECT Id_Weather, `Condition` FROM Weather_Conditions ORDER BY `Condition`");
        $weatherStmt->execute();
        $weatherOptions = $weatherStmt->fetchAll();
        
        $trafficStmt = $db->prepare("SELECT Id_Density, Density FROM Traffic_Density ORDER BY Id_Density");
        $trafficStmt->execute();
        $trafficOptions = $trafficStmt->fetchAll();
        
        $roadStmt = $db->prepare("SELECT Id_Road, Type FROM Road_Type ORDER BY Type");
        $roadStmt->execute();
        $roadOptions = $roadStmt->fetchAll();
        
        return [
            'weather' => $weatherOptions,
            'traffic' => $trafficOptions,
            'road' => $roadOptions
        ];
        
    } catch(PDOException $e) {
        return [
            'error' => "Error loading filter options: " . $e->getMessage(),
            'weather' => [],
            'traffic' => [],
            'road' => []
        ];
    }
}
?>