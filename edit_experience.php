<?php
// edit_experience.php - FIXED VERSION
require_once 'includes/config.php';

// Get experience ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: summaryreport.php');
    exit;
}

// Initialize variables
$experience = null;
$selectedManeuvers = [];
$weatherConditions = [];
$trafficDensity = [];
$roadTypes = [];
$drivingManeuvers = [];
$error = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the experience to edit
    $query = "
        SELECT 
            de.*,
            wc.`Condition` as weather_name,
            td.Density as traffic_name,
            rt.Type as road_name
        FROM Driving_Experience de
        LEFT JOIN Weather_Conditions wc ON de.Id_Weather = wc.Id_Weather
        LEFT JOIN Traffic_Density td ON de.Id_Density = td.Id_Density
        LEFT JOIN Road_Type rt ON de.Id_Road = rt.Id_Road
        WHERE de.Driving_Id = :id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $experience = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$experience) {
        header('Location: summaryreport.php?error=not_found');
        exit;
    }
    
    // Get selected maneuvers for this experience
    $maneuverQuery = "SELECT Id_Maneuver FROM Experience_Maneuvers WHERE Driving_Id = :id";
    $maneuverStmt = $db->prepare($maneuverQuery);
    $maneuverStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $maneuverStmt->execute();
    $selectedManeuvers = $maneuverStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Get all dropdown options with FIX for reserved word
    $weatherStmt = $db->query("SELECT Id_Weather, `Condition` FROM Weather_Conditions ORDER BY `Condition`");
    $weatherConditions = $weatherStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $trafficStmt = $db->query("SELECT Id_Density, Density FROM Traffic_Density ORDER BY Id_Density");
    $trafficDensity = $trafficStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $roadStmt = $db->query("SELECT Id_Road, Type FROM Road_Type ORDER BY Type");
    $roadTypes = $roadStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maneuversStmt = $db->query("SELECT Id_Maneuver, Maneuver FROM Driving_Maneuvers ORDER BY Maneuver");
    $drivingManeuvers = $maneuversStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data with proper validation
        $date = trim($_POST['date'] ?? '');
        $startingTime = trim($_POST['startingTime'] ?? '');
        $endingTime = trim($_POST['endingTime'] ?? '');
        $kilometers = floatval($_POST['km'] ?? 0);
        $weather = intval($_POST['idWeather'] ?? 0);
        $traffic = intval($_POST['idTrafficDensity'] ?? 0);
        $road = intval($_POST['idRoad'] ?? 0);
        $maneuvers = $_POST['maneuvers'] ?? [];
        
        // Validation
        $errors = [];
        
        if (empty($date)) $errors[] = 'Date is required';
        if (empty($startingTime)) $errors[] = 'Starting time is required';
        if (empty($endingTime)) $errors[] = 'Ending time is required';
        if ($kilometers <= 0) $errors[] = 'Kilometers must be greater than 0';
        if ($weather <= 0) $errors[] = 'Weather condition is required';
        if ($traffic <= 0) $errors[] = 'Traffic density is required';
        if ($road <= 0) $errors[] = 'Road type is required';
        if (empty($maneuvers)) $errors[] = 'At least one maneuver is required';
        
        if ($startingTime === $endingTime) {
            $errors[] = 'Starting and ending times cannot be the same';
        }
        
        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Update driving experience
        $updateQuery = "
            UPDATE Driving_Experience 
            SET DateOfJourney = :date,
                StartingTime = :startingTime,
                EndingTime = :endingTime,
                TraveledKm = :kilometers,
                Id_Weather = :weather,
                Id_Density = :traffic,
                Id_Road = :road
            WHERE Driving_Id = :id
        ";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':date', $date);
        $updateStmt->bindParam(':startingTime', $startingTime);
        $updateStmt->bindParam(':endingTime', $endingTime);
        $updateStmt->bindParam(':kilometers', $kilometers);
        $updateStmt->bindParam(':weather', $weather, PDO::PARAM_INT);
        $updateStmt->bindParam(':traffic', $traffic, PDO::PARAM_INT);
        $updateStmt->bindParam(':road', $road, PDO::PARAM_INT);
        $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update driving experience');
        }
        
        // Delete old maneuvers
        $deleteManeuvers = "DELETE FROM Experience_Maneuvers WHERE Driving_Id = :id";
        $deleteStmt = $db->prepare($deleteManeuvers);
        $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $deleteStmt->execute();
        
        // Insert new maneuvers using prepared statement for each
        if (!empty($maneuvers)) {
            $insertQuery = "INSERT INTO Experience_Maneuvers (Driving_Id, Id_Maneuver) VALUES (:id, :maneuver)";
            $insertStmt = $db->prepare($insertQuery);
            
            foreach ($maneuvers as $maneuverId) {
                $maneuverId = intval($maneuverId);
                if ($maneuverId > 0) {
                    $insertStmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $insertStmt->bindParam(':maneuver', $maneuverId, PDO::PARAM_INT);
                    if (!$insertStmt->execute()) {
                        throw new Exception('Failed to insert maneuver: ' . $maneuverId);
                    }
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // Redirect to summary with success message
        header('Location: summaryreport.php?message=updated');
        exit;
        
    } catch(Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Driving Experience</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .edit-header {
            background: linear-gradient(to right, #6a1b9a, #9b4dff);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }
        
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
        }
        
        .btn-save:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.2s ease;
        }
        
        .checkbox-item:hover {
            border-color: #9b4dff;
            background: #f9f5ff;
        }
        
        .checkbox-item input[type="checkbox"] {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <header class="edit-header">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Driving Experience
            </h1>
            <p>ID: <?php echo $id; ?> | Date: <?php echo htmlspecialchars($experience['DateOfJourney'] ?? ''); ?></p>
        </header>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                Experience updated successfully!
            </div>
        <?php endif; ?>
        
        <?php if ($experience): ?>
            <form method="POST" class="edit-form">
                <!-- Date -->
                <label for="date">
                    <i class="fas fa-calendar-alt"></i> Date of Journey:
                </label>
                <input type="date" id="date" name="date" required
                       value="<?php echo htmlspecialchars($experience['DateOfJourney']); ?>">
                
                <!-- Start Time -->
                <label for="startingTime">
                    <i class="fas fa-clock"></i> Starting Time:
                </label>
                <input type="time" id="startingTime" name="startingTime" required
                       value="<?php echo htmlspecialchars($experience['StartingTime']); ?>">
                
                <!-- End Time -->
                <label for="endingTime">
                    <i class="fas fa-clock"></i> Ending Time:
                </label>
                <input type="time" id="endingTime" name="endingTime" required
                       value="<?php echo htmlspecialchars($experience['EndingTime']); ?>">
                
                <!-- Kilometers -->
                <label for="km">
                    <i class="fas fa-road"></i> Kilometers Travelled:
                </label>
                <input type="number" id="km" name="km" min="0.1" step="0.1" required
                       value="<?php echo htmlspecialchars($experience['TraveledKm']); ?>"
                       placeholder="Enter distance in km">
                
                <!-- Weather -->
                <label for="idWeather">
                    <i class="fas fa-cloud-sun"></i> Weather Condition:
                </label>
                <select id="idWeather" name="idWeather" required>
                    <option value="">-- Choose --</option>
                    <?php foreach ($weatherConditions as $weather): ?>
                        <option value="<?php echo $weather['Id_Weather']; ?>"
                            <?php echo ($weather['Id_Weather'] == $experience['Id_Weather']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($weather['Condition']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Traffic -->
                <label for="idTrafficDensity">
                    <i class="fas fa-traffic-light"></i> Traffic Density:
                </label>
                <select id="idTrafficDensity" name="idTrafficDensity" required>
                    <option value="">-- Choose --</option>
                    <?php foreach ($trafficDensity as $traffic): ?>
                        <option value="<?php echo $traffic['Id_Density']; ?>"
                            <?php echo ($traffic['Id_Density'] == $experience['Id_Density']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($traffic['Density']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Road Type -->
                <label for="idRoad">
                    <i class="fas fa-road"></i> Road Type:
                </label>
                <select id="idRoad" name="idRoad" required>
                    <option value="">-- Choose --</option>
                    <?php foreach ($roadTypes as $road): ?>
                        <option value="<?php echo $road['Id_Road']; ?>"
                            <?php echo ($road['Id_Road'] == $experience['Id_Road']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($road['Type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Maneuvers -->
                <label>
                    <i class="fas fa-exchange-alt"></i> Driving Maneuvers:
                </label>
                <div class="checkbox-group">
                    <?php foreach ($drivingManeuvers as $maneuver): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" 
                                   name="maneuvers[]" 
                                   value="<?php echo $maneuver['Id_Maneuver']; ?>"
                                   id="maneuver_<?php echo $maneuver['Id_Maneuver']; ?>"
                                   <?php echo in_array($maneuver['Id_Maneuver'], $selectedManeuvers) ? 'checked' : ''; ?>>
                            <label for="maneuver_<?php echo $maneuver['Id_Maneuver']; ?>">
                                <?php echo htmlspecialchars($maneuver['Maneuver']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save"></i> Update Experience
                    </button>
                    <a href="summaryreport.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Experience not found!
            </div>
            <a href="summaryreport.php" class="btn btn-cancel" style="margin-top: 20px;">
                <i class="fas fa-arrow-left"></i> Back to Summary
            </a>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const maneuvers = document.querySelectorAll('input[name="maneuvers[]"]:checked');
                const kilometers = document.getElementById('km').value;
                const startingTime = document.getElementById('startingTime').value;
                const endingTime = document.getElementById('endingTime').value;
                
                // Check at least one maneuver
                if (maneuvers.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one driving maneuver.');
                    return false;
                }
                
                // Check kilometers
                if (kilometers <= 0 || kilometers > 9999) {
                    e.preventDefault();
                    alert('Please enter a valid distance between 0.1 and 9999 km.');
                    return false;
                }
                
                // Check times
                if (startingTime === endingTime) {
                    e.preventDefault();
                    alert('Starting Time and Ending Time cannot be the same.');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>