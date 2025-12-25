<?php
// classes/Experience.php
class DrivingExperience {
    private $id;
    private $date;
    private $startTime;
    private $endTime;
    private $kilometers;
    private $weatherId;
    private $trafficId;
    private $roadId;
    private $maneuvers = [];
    
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Getters and setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setDate($date) { $this->date = $date; return $this; }
    public function setStartTime($time) { $this->startTime = $time; return $this; }
    public function setEndTime($time) { $this->endTime = $time; return $this; }
    public function setKilometers($km) { $this->kilometers = $km; return $this; }
    public function setWeatherId($id) { $this->weatherId = $id; return $this; }
    public function setTrafficId($id) { $this->trafficId = $id; return $this; }
    public function setRoadId($id) { $this->roadId = $id; return $this; }
    public function setManeuvers($maneuvers) { $this->maneuvers = $maneuvers; return $this; }
    
    public function save() {
        try {
            $this->db->beginTransaction();
            
            // Save main experience
            $query = "INSERT INTO Driving_Experience 
                     (DateOfJourney, StartingTime, EndingTime, TraveledKm, Id_Weather, Id_Density, Id_Road) 
                     VALUES (:date, :start, :end, :km, :weather, :traffic, :road)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':date' => $this->date,
                ':start' => $this->startTime,
                ':end' => $this->endTime,
                ':km' => $this->kilometers,
                ':weather' => $this->weatherId,
                ':traffic' => $this->trafficId,
                ':road' => $this->roadId
            ]);
            
            $this->id = $this->db->lastInsertId();
            
            // Save maneuvers
            foreach ($this->maneuvers as $maneuverId) {
                $query = "INSERT INTO Experience_Maneuvers (Driving_Id, Id_Maneuver) 
                          VALUES (:expId, :maneuverId)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':expId' => $this->id,
                    ':maneuverId' => $maneuverId
                ]);
            }
            
            $this->db->commit();
            return $this->id;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public static function findById($db, $id) {
        $query = "SELECT * FROM Driving_Experience WHERE Driving_Id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exp = new self($db);
            $exp->setId($data['Driving_Id'])
                ->setDate($data['DateOfJourney'])
                ->setStartTime($data['StartingTime'])
                ->setEndTime($data['EndingTime'])
                ->setKilometers($data['TraveledKm'])
                ->setWeatherId($data['Id_Weather'])
                ->setTrafficId($data['Id_Density'])
                ->setRoadId($data['Id_Road']);
            
            // Get maneuvers
            $maneuverQuery = "SELECT Id_Maneuver FROM Experience_Maneuvers 
                             WHERE Driving_Id = :id";
            $maneuverStmt = $db->prepare($maneuverQuery);
            $maneuverStmt->execute([':id' => $id]);
            $maneuvers = $maneuverStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $exp->setManeuvers($maneuvers);
            
            return $exp;
        }
        
        return null;
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'kilometers' => $this->kilometers,
            'weatherId' => $this->weatherId,
            'trafficId' => $this->trafficId,
            'roadId' => $this->roadId,
            'maneuvers' => $this->maneuvers
        ];
    }
}
?>
