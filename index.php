<?php
require_once 'includes/config.php';

$weatherConditions = [];
$trafficDensity    = [];
$roadTypes          = [];
$drivingManeuvers   = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // WEATHER (Condition is a reserved word → backticks + alias)
    $weatherStmt = $db->query("
        SELECT 
            Id_Weather,
            `Condition` AS WeatherCondition
        FROM Weather_Conditions
        ORDER BY `Condition`
    ");
    $weatherConditions = $weatherStmt->fetchAll(PDO::FETCH_ASSOC);

    // TRAFFIC
    $trafficStmt = $db->query("
        SELECT Id_Density, Density 
        FROM Traffic_Density
        ORDER BY Id_Density
    ");
    $trafficDensity = $trafficStmt->fetchAll(PDO::FETCH_ASSOC);

    // ROAD TYPES
    $roadStmt = $db->query("
        SELECT Id_Road, Type 
        FROM Road_Type
        ORDER BY Type
    ");
    $roadTypes = $roadStmt->fetchAll(PDO::FETCH_ASSOC);

    // MANEUVERS
    $maneuversStmt = $db->query("
        SELECT Id_Maneuver, Maneuver 
        FROM Driving_Maneuvers
        ORDER BY Maneuver
    ");
    $drivingManeuvers = $maneuversStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driving Experience Assistant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
<div class="container">

    <header>
        <h1><i class="fas fa-car"></i> Driving Experience Assistant</h1>
    </header>

    <main>
        <section class="form-container">

            <h2><i class="fas fa-clipboard-list"></i> Record Your Driving Experience</h2>

            <form id="drivingForm" method="POST" action="save_experience.php">

                <!-- DATE -->
                <label for="date">
                    <i class="fas fa-calendar-alt"></i> Date of Journey:
                </label>
                <input type="date" id="date" name="date" required
                       value="<?php echo date('Y-m-d'); ?>">

                <!-- START TIME -->
                <label for="startingTime">
                    <i class="fas fa-clock"></i> Starting Time:
                </label>
                <input type="time" id="startingTime" name="startingTime" required
                       value="<?php echo date('H:i'); ?>">

                <!-- END TIME -->
                <label for="endingTime">
                    <i class="fas fa-clock"></i> Ending Time:
                </label>
                <input type="time" id="endingTime" name="endingTime" required>

                <!-- KM -->
                <label for="km">
                    <i class="fas fa-road"></i> Kilometers Travelled:
                </label>
                <input type="number" id="km" name="km"
                       min="0.1" step="0.1" required
                       placeholder="Enter distance in km">

                <!-- WEATHER -->
                <label for="idWeather">
                    <i class="fas fa-cloud-sun"></i> Weather Condition:
                </label>
                <select id="idWeather" name="idWeather" required>
                    <option value="">-- Choose --</option>
                    <?php foreach ($weatherConditions as $weather): ?>
                        <option value="<?php echo htmlspecialchars($weather['Id_Weather']); ?>">
                            <?php echo htmlspecialchars($weather['WeatherCondition']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- TRAFFIC -->
                <label for="idTrafficDensity">
                    <i class="fas fa-traffic-light"></i> Traffic Density:
                </label>
                <select id="idTrafficDensity" name="idTrafficDensity" required>
                    <option value="">-- Choose --</option>
                    <?php foreach ($trafficDensity as $traffic): ?>
                        <option value="<?php echo htmlspecialchars($traffic['Id_Density']); ?>">
                            <?php echo htmlspecialchars($traffic['Density']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- ROAD -->
                <label for="idRoad">
                    <i class="fas fa-road"></i> Road Type:
                </label>
                <select id="idRoad" name="idRoad" required>
                    <option value="">-- Choose --</option>
                    <?php foreach ($roadTypes as $road): ?>
                        <option value="<?php echo htmlspecialchars($road['Id_Road']); ?>">
                            <?php echo htmlspecialchars($road['Type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- MANEUVERS -->
                <label>
                    <i class="fas fa-exchange-alt"></i> Driving Maneuvers:
                </label>

                <div class="checkbox-group">
                    <?php foreach ($drivingManeuvers as $maneuver): ?>
                        <div class="checkbox-item">
                            <input type="checkbox"
                                   name="maneuvers[]"
                                   id="maneuver_<?php echo $maneuver['Id_Maneuver']; ?>"
                                   value="<?php echo $maneuver['Id_Maneuver']; ?>">
                            <label for="maneuver_<?php echo $maneuver['Id_Maneuver']; ?>">
                                <?php echo htmlspecialchars($maneuver['Maneuver']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit">
                    <i class="fas fa-save"></i> Save Experience
                </button>

            </form>

            <a href="summaryreport.php" class="back-link">
                <i class="fas fa-chart-bar"></i> View Driving Experience Summary
            </a>

        </section>
    </main>

    <footer>
        <p>&copy; 2025 Driving Experience Assistant by Mustafayeva Ilaha.</p>
    </footer>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('drivingForm');

    // Default ending time = +1 hour
    const now = new Date();
    now.setHours(now.getHours() + 1);
    form.endingTime.value =
        now.getHours().toString().padStart(2, '0') + ':' +
        now.getMinutes().toString().padStart(2, '0');

    // Require at least one maneuver
    form.addEventListener('submit', (e) => {
        if (document.querySelectorAll('input[name="maneuvers[]"]:checked').length === 0) {
            alert('Please select at least one driving maneuver.');
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>
