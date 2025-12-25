// js/scripts.js
document.addEventListener('DOMContentLoaded', function() {
    // Set today's date as default for date input
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('date');
    if (dateInput && !dateInput.value) {
        dateInput.value = today;
    }
    
    // Set current time for starting time
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;
    
    const startTimeInput = document.getElementById('startingTime');
    if (startTimeInput && !startTimeInput.value) {
        startTimeInput.value = currentTime;
    }
    
    // Form validation
    const form = document.getElementById('drivingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            validateAndSubmit();
        });
    }
    
    // Initialize charts on summary page
    if (document.getElementById('weatherChart')) {
        loadStatistics();
    }
});

function validateAndSubmit() {
    const date = document.getElementById('date').value;
    const startingTime = document.getElementById('startingTime').value;
    const endingTime = document.getElementById('endingTime').value;
    const kilometers = document.getElementById('km').value;
    const weather = document.getElementById('idWeather').value;
    const traffic = document.getElementById('idTrafficDensity').value;
    const road = document.getElementById('idRoad').value;
    const maneuvers = document.querySelectorAll('input[name="maneuvers[]"]:checked');
    
    // Validation
    if (!date || !startingTime || !endingTime || !kilometers || 
        !weather || !traffic || !road || maneuvers.length === 0) {
        alert('Please fill in all fields before saving.');
        return false;
    }
    
    if (kilometers <= 0 || kilometers > 9999) {
        alert('Please enter a valid distance between 1 and 9999 km.');
        return false;
    }
    
    if (startingTime === endingTime) {
        alert('Starting Time and Ending Time cannot be the same.');
        return false;
    }
    
    // Prepare form data
    const formData = new FormData(document.getElementById('drivingForm'));
    const maneuversArray = Array.from(maneuvers).map(cb => cb.value);
    maneuversArray.forEach(value => {
        formData.append('maneuvers[]', value);
    });
    
    // Submit via AJAX
    fetch('save_experience.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById('drivingForm').reset();
            // Reset to default values
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;
            
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            document.getElementById('startingTime').value = `${hours}:${minutes}`;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving.');
    });
}

function loadStatistics() {
    fetch('get_statistics.php')
        .then(response => response.json())
        .then(data => {
            updateTotalKilometers(data.totalKm);
            createCharts(data);
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

function updateTotalKilometers(totalKm) {
    const totalElement = document.getElementById('totalKilometers');
    if (totalElement) {
        totalElement.textContent = `Total Kilometers Driven: ${totalKm ? totalKm.toFixed(2) : '0'} km`;
    }
}

function createCharts(data) {
    // Create weather chart
    if (data.weatherStats && document.getElementById('weatherChart')) {
        createPieChart('weatherChart', data.weatherStats, 'Weather Conditions', 'Condition', 'count');
    }
    
    // Create traffic chart
    if (data.trafficStats && document.getElementById('trafficChart')) {
        createPieChart('trafficChart', data.trafficStats, 'Traffic Density', 'Density', 'count');
    }
    
    // Create road chart
    if (data.roadStats && document.getElementById('roadChart')) {
        createPieChart('roadChart', data.roadStats, 'Road Types', 'Type', 'count');
    }
}

function createPieChart(canvasId, data, title, labelKey, valueKey) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    const labels = data.map(item => item[labelKey]);
    const values = data.map(item => item[valueKey]);
    
    const colors = generateColors(data.length);
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: title
                }
            }
        }
    });
}

function generateColors(count) {
    const baseColors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];
    
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(baseColors[i % baseColors.length]);
    }
    return colors;
}

function deleteExperience(id) {
    if (confirm('Are you sure you want to delete this experience?')) {
        fetch('delete_experience.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Experience deleted successfully!');
                location.reload();
            } else {
                alert('Error deleting experience.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting.');
        });
    }
}
