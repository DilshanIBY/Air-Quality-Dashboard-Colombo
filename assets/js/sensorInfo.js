// Function to fetch sensor details
async function fetchSensorDetails(sensorId) {
    try {
        const response = await fetch(`api/get_sensor_details.php?sensor_id=${sensorId}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return await response.json();
    } catch (error) {
        console.error('Error fetching sensor details:', error);
        return null;
    }
}

// Function to get AQI category and info
function getAQIInfo(value) {
    if (value <= 50) return {
        category: 'Good',
        color: '#66BB6A',
        emoji: '😊',
        message: 'Fresh and clean air!'
    };
    if (value <= 100) return {
        category: 'Moderate',
        color: '#FDD835',
        emoji: '🙂',
        message: 'Air is okay!'
    };
    if (value <= 150) return {
        category: 'Unhealthy for Sensitive Groups',
        color: '#FB8C00',
        emoji: '😐',
        message: 'Take care if sensitive!'
    };
    if (value <= 200) return {
        category: 'Unhealthy',
        color: '#E53935',
        emoji: '😷',
        message: 'Better stay inside!'
    };
    if (value <= 300) return {
        category: 'Very Unhealthy',
        color: '#8E24AA',
        emoji: '🤢',
        message: 'Not good for health!'
    };
    return {
        category: 'Hazardous',
        color: '#B71C1C',
        emoji: '⚠️',
        message: 'Stay indoors!'
    };
}

// Function to format time ago
function getTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes}m ago`;
    }
    if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours}h ago`;
    }
    const days = Math.floor(diffInSeconds / 86400);
    return `${days}d ago`;
}

// Function to update sensor information panel
async function updateSensorInfo(sensorId) {
    const infoPanel = document.getElementById('sensor-info');
    
    if (!sensorId) {
        infoPanel.innerHTML = `
            <div class="info-header">
                <h3>
                    <i class="fas fa-satellite"></i>
                    <span>Sensor Information</span>
                </h3>
            </div>
            <div class="info-content">
                <div class="select-prompt">
                    <i class="fas fa-info-circle"></i>
                    <p>Select a sensor on the map to view detailed information</p>
                </div>
            </div>
        `;
        return;
    }
    
    try {
        // Show loading state
        infoPanel.innerHTML = `
            <div class="info-header">
                <h3>
                    <i class="fas fa-satellite"></i>
                    <span>Sensor Information</span>
                </h3>
            </div>
            <div class="info-content">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>Loading sensor data...</p>
                </div>
            </div>
        `;

        // Fetch sensor data
        const response = await fetch(`api/get_sensor_details.php?sensor_id=${sensorId}`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to fetch sensor data');
        }

        if (!data.sensor) {
            throw new Error('Sensor not found');
        }

        const { sensor } = data;
        const aqiInfo = getAQIInfo(sensor.aqi_value || 0);

        // Update the panel with sensor information
        infoPanel.innerHTML = `
            <div class="info-header">
                <h3>
                    <i class="fas fa-satellite"></i>
                    <span>Sensor Information</span>
                </h3>
                <div class="sensor-status ${sensor.status || 'inactive'}">
                    <span class="status-dot ${sensor.status || 'inactive'}"></span>
                    ${sensor.status || 'Unknown'}
                </div>
            </div>
            <div class="info-content">
                <div class="sensor-info-card">
                    <div class="sensor-info-header">
                        <h3>
                            <i class="fas fa-map-marker-alt"></i>
                            ${sensor.location_name || 'Unknown Location'}
                        </h3>
                    </div>
                    <div class="sensor-info-content">
                        <div class="aqi-display" style="color: ${aqiInfo.color}">
                            <div class="aqi-value-large">
                                ${Math.round(sensor.aqi_value || 0)}
                                <span class="aqi-emoji">${aqiInfo.emoji}</span>
                            </div>
                            <div class="aqi-category">
                                ${aqiInfo.category}
                            </div>
                            <div class="aqi-message">
                                ${aqiInfo.message}
                            </div>
                        </div>
                        
                        <div class="quick-stats">
                            <div class="stat-pill">
                                <i class="fas fa-wind"></i>
                                PM2.5: ${Math.round(sensor.pm25_value || 0)} µg/m³
                            </div>
                            <div class="stat-pill">
                                <i class="fas fa-cloud"></i>
                                PM10: ${Math.round(sensor.pm10_value || 0)} µg/m³
                            </div>
                        </div>
                        
                        <div class="location-badge">
                            <i class="fas fa-map-pin"></i>
                            ${parseFloat(sensor.latitude || 0).toFixed(4)}°N, 
                            ${parseFloat(sensor.longitude || 0).toFixed(4)}°E
                        </div>
                        
                        <div class="status-indicator">
                            <i class="fas fa-clock"></i>
                            ${sensor.last_update ? getTimeAgo(sensor.last_update) : 'No updates yet'}
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">
                                    <i class="fas fa-chart-line"></i>
                                    ${Math.round(sensor.avg_aqi || 0)}
                                </div>
                                <div class="stat-label">Average AQI</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">
                                    <i class="fas fa-database"></i>
                                    ${sensor.total_readings || 0}
                                </div>
                                <div class="stat-label">Total Readings</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
        infoPanel.innerHTML = `
            <div class="info-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Error</span>
                </h3>
            </div>
            <div class="info-content">
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>${error.message || 'Error loading sensor information. Please try again.'}</p>
                </div>
            </div>
        `;
    }
}

// Export the function to be used in map.js
window.updateSensorInfo = updateSensorInfo; 