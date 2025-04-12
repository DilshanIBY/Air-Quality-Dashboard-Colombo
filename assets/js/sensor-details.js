let sensorChart = null;

// Function to format date
function formatDate(dateString) {
    if (!dateString) return 'Not Available';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Function to update sensor information
async function updateSensorInfo(sensorId) {
    try {
        const response = await fetch(`api/get_sensor_details.php?sensor_id=${sensorId}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.success) {
            const sensor = data.data.sensor;
            
            // Show sensor readings div and hide initial message
            document.getElementById('sensor-info').style.display = 'none';
            document.getElementById('sensor-readings').classList.remove('hidden');
            
            // Update sensor details
            document.getElementById('sensor-location').textContent = sensor.location_name;
            document.getElementById('sensor-id').textContent = sensor.sensor_id;
            document.getElementById('installation-date').textContent = formatDate(sensor.installation_date);
            document.getElementById('last-maintenance').textContent = formatDate(sensor.last_maintenance);
            document.getElementById('maintenance-due').textContent = formatDate(sensor.maintenance_due);
            
            // Update status badge
            const statusBadge = document.getElementById('sensor-status');
            statusBadge.textContent = sensor.status.toUpperCase();
            statusBadge.className = `status-badge status-${sensor.status.toLowerCase()}`;
            
            // Update current readings
            document.getElementById('current-aqi').textContent = sensor.aqi_value || '-';
            document.getElementById('current-pm25').textContent = sensor.pm25_value ? `${sensor.pm25_value} µg/m³` : '-';
            document.getElementById('current-pm10').textContent = sensor.pm10_value ? `${sensor.pm10_value} µg/m³` : '-';
            document.getElementById('current-temp').textContent = sensor.temperature ? `${sensor.temperature}°C` : '-';
            document.getElementById('current-humidity').textContent = sensor.humidity ? `${sensor.humidity}%` : '-';
            
            // Update chart with historical data
            updateSensorChart(data.data.historical);
        }
    } catch (error) {
        console.error('Error fetching sensor details:', error);
        document.getElementById('sensor-info').innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Error loading sensor information. Please try again.
            </div>
        `;
    }
}

// Function to update sensor chart
function updateSensorChart(historicalData) {
    const ctx = document.getElementById('sensor-chart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (sensorChart) {
        sensorChart.destroy();
    }
    
    // Prepare data for chart
    const labels = historicalData.map(reading => new Date(reading.timestamp).toLocaleTimeString());
    const aqiData = historicalData.map(reading => reading.aqi_value);
    
    // Create new chart
    sensorChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'AQI Value',
                data: aqiData,
                borderColor: '#4CAF50',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '24 Hour AQI History'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'AQI Value'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                }
            }
        }
    });
}

// Function to show sensor details when clicked on map
window.showSensorDetails = function(sensorId) {
    updateSensorInfo(sensorId);
}; 