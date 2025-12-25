<?php
// classes/Statistics.php
class DrivingStatistics {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getTotalKilometers($filters = []) {
        $whereClause = $this->buildWhereClause($filters);
        $query = "SELECT SUM(TraveledKm) as total FROM Driving_Experience $whereClause";
        
        $stmt = $this->db->prepare($query);
        $this->bindFilterParams($stmt, $filters);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'] ? round($result['total'], 2) : 0;
    }
    
    public function getWeatherDistribution($filters = []) {
        $whereClause = $this->buildWhereClause($filters, 'de');
        $query = "SELECT wc.`Condition`, COUNT(*) as count 
                  FROM Driving_Experience de
                  JOIN Weather_Conditions wc ON de.Id_Weather = wc.Id_Weather
                  $whereClause
                  GROUP BY wc.`Condition`
                  ORDER BY count DESC";
        
        $stmt = $this->db->prepare($query);
        $this->bindFilterParams($stmt, $filters);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getExperiences($filters = [], $limit = null) {
        $whereClause = $this->buildWhereClause($filters);
        $limitClause = $limit ? "LIMIT $limit" : "";
        
        $query = "SELECT * FROM Driving_Experience 
                  $whereClause 
                  ORDER BY DateOfJourney DESC, StartingTime DESC 
                  $limitClause";
        
        $stmt = $this->db->prepare($query);
        $this->bindFilterParams($stmt, $filters);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function buildWhereClause($filters, $alias = '') {
        $conditions = [];
        $prefix = $alias ? "$alias." : "";
        
        if (!empty($filters['dateFrom'])) {
            $conditions[] = "{$prefix}DateOfJourney >= :dateFrom";
        }
        if (!empty($filters['dateTo'])) {
            $conditions[] = "{$prefix}DateOfJourney <= :dateTo";
        }
        if (!empty($filters['weather'])) {
            $conditions[] = "{$prefix}Id_Weather = :weather";
        }
        if (!empty($filters['traffic'])) {
            $conditions[] = "{$prefix}Id_Density = :traffic";
        }
        if (!empty($filters['road'])) {
            $conditions[] = "{$prefix}Id_Road = :road";
        }
        
        return empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }
    
    private function bindFilterParams($stmt, $filters) {
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $stmt->bindValue(":$key", $value);
            }
        }
    }
}
?>