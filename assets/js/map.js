// Initialize the map centered on Colombo
const map = L.map('map').setView([6.9271, 79.8612], 12);

// Add OpenStreetMap tile layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Store markers in a layer group
const markersLayer = L.layerGroup().addTo(map);

// Function to get AQI info based on value
function getAQIInfo(value) {
    if (value <= 50) return {
        category: 'Good',
        color: '#66BB6A',
        emoji: '😊',
        backgroundColor: '#e6f4ea',
        description: 'Air quality is satisfactory'
    };
    if (value <= 100) return {
        category: 'Moderate',
        color: '#FDD835',
        emoji: '🙂',
        backgroundColor: '#fff8e1',
        description: 'Acceptable air quality'
    };
    if (value <= 150) return {
        category: 'Unhealthy for Sensitive Groups',
        color: '#FB8C00',
        emoji: '😐',
        backgroundColor: '#fff3e0',
        description: 'Members of sensitive groups may experience health effects'
    };
    if (value <= 200) return {
        category: 'Unhealthy',
        color: '#E53935',
        emoji: '😷',
        backgroundColor: '#ffebee',
        description: 'Everyone may begin to experience health effects'
    };
    if (value <= 300) return {
        category: 'Very Unhealthy',
        color: '#8E24AA',
        emoji: '🤢',
        backgroundColor: '#f3e5f5',
        description: 'Health warnings of emergency conditions'
    };
    return {
        category: 'Hazardous',
        color: '#B71C1C',
        emoji: '⚠️',
        backgroundColor: '#ffebee',
        description: 'Health alert: everyone may experience serious health effects'
    };
}

// Function to get cute message based on AQI
function getCuteMessage(value) {
    if (value <= 50) return "The air is fresh and clean! 🌿✨";
    if (value <= 100) return "Pretty good air today! 🌤️";
    if (value <= 150) return "Let's take care of our air! 🌱";
    if (value <= 200) return "Time to be a bit careful! 🏃‍♂️";
    if (value <= 300) return "Oh no! Air needs our help! 🏠";
    return "Stay safe indoors today! 🏡💕";
}

// Function to create custom marker icon
function createCustomIcon(aqiInfo) {
    return L.divIcon({
        className: 'custom-marker-icon',
        html: `
            <div class="marker-container" style="background-color: ${aqiInfo.color}">
                <div class="sensor-icon">
                    <div class="satellite-dish">
                        <div class="base"></div>
                        <div class="dish"></div>
                        <div class="signal-waves">
                            <div class="wave wave1"></div>
                            <div class="wave wave2"></div>
                            <div class="wave wave3"></div>
                        </div>
                    </div>
                </div>
                <div class="pulse-ring"></div>
            </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
    });
}

// Function to create animated marker
function createMarker(sensor) {
    const aqiInfo = getAQIInfo(sensor.aqi_value);
    
    // Create a marker with custom icon
    const marker = L.marker([sensor.latitude, sensor.longitude], {
        icon: createCustomIcon(aqiInfo)
    });

    // Create the popup content
    const popupContent = document.createElement('div');
    popupContent.className = 'custom-popup';
    popupContent.style.backgroundColor = aqiInfo.backgroundColor;
    popupContent.innerHTML = `
        <div class="popup-header" style="background-color: ${aqiInfo.color}">
            <div class="header-content">
                <span class="emoji animated">${aqiInfo.emoji}</span>
                <div class="header-text">
                    <div class="aqi-value">${sensor.aqi_value}</div>
                    <div class="aqi-category">${aqiInfo.category}</div>
                </div>
            </div>
            <button class="close-btn">×</button>
        </div>
        <div class="popup-content">
            <div class="cute-message">
                <div class="message-bubble">
                    ${getCuteMessage(sensor.aqi_value)}
                </div>
            </div>
            <div class="location">
                <div class="location-icon">
                    <div class="signal-dot"></div>
                    <div class="signal-wave"></div>
                </div>
                <strong>${sensor.location_name}</strong>
                            </div>
            <div class="timestamp">
                <span class="clock-icon">🕐</span>
                updated ${getTimeAgo(sensor.timestamp)}
                        </div>
            <div class="readings-container">
                <div class="reading-card">
                    <div class="reading-icon">😷</div>
                    <div class="reading-details">
                        <div class="reading-label">PM<sub>2.5</sub></div>
                        <div class="reading-value">${Math.round(sensor.aqi_value * 0.8)} µg/m³</div>
                    </div>
                    <div class="reading-bar">
                        <div class="bar-fill" style="width: ${Math.min(100, sensor.aqi_value)}%; background-color: ${aqiInfo.color}">
                            <div class="sparkles"></div>
                        </div>
                    </div>
                        </div>
                <div class="reading-card">
                    <div class="reading-icon">🌫️</div>
                    <div class="reading-details">
                        <div class="reading-label">PM<sub>10</sub></div>
                        <div class="reading-value">${Math.round(sensor.aqi_value * 0.6)} µg/m³</div>
                    </div>
                    <div class="reading-bar">
                        <div class="bar-fill" style="width: ${Math.min(100, sensor.aqi_value * 0.8)}%; background-color: ${aqiInfo.color}">
                            <div class="sparkles"></div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="cute-button" onclick="showDetails('${sensor.sensor_id}')">
                <span class="button-icon">📊</span>
                <span class="button-text">See More Details</span>
                <span class="button-stars">
                    <span class="star">⭐</span>
                </span>
            </button>
        </div>
    `;

    // Add popup
    const popup = L.popup({
        closeButton: false,
        className: 'custom-popup-container'
    }).setContent(popupContent);

    marker.bindPopup(popup);

    // Add popup events
    marker.on('popupopen', function() {
        // Add close button handler
        const closeBtn = document.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.onclick = () => marker.closePopup();
        }

        // Create historical chart
        createHistoricalChart(sensor.sensor_id);
    });

    return marker;
}

// Function to create historical chart
function createHistoricalChart(sensorId) {
    const chartContainer = document.getElementById(`chart-${sensorId}`);
    if (!chartContainer) return;

    // Sample historical data (replace with actual API call)
    const data = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        values: [65, 72, 88, 55, 42, 70]
    };

    const chartHtml = `
        <div class="chart-header">
            <h4>Historical Data</h4>
            <div class="chart-legend">
                <span class="legend-item">
                    <span class="legend-color" style="background: #4CAF50"></span>
                    PM2.5
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #2196F3"></span>
                    PM10
                </span>
            </div>
        </div>
        <div class="chart-grid">
            ${data.values.map((value, index) => `
                <div class="chart-column">
                    <div class="chart-bar-vertical" style="height: ${value}%"></div>
                    <div class="chart-label">${data.labels[index]}</div>
                </div>
            `).join('')}
        </div>
    `;

    chartContainer.innerHTML = chartHtml;
}

// Helper function to get time ago string
function getTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    }
    if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    }
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} day${days > 1 ? 's' : ''} ago`;
}

// Function to show sensor details
function showDetails(sensorId) {
    const infoPanel = document.getElementById('sensor-info');
    infoPanel.innerHTML = `
        <div class="info-header">
            <h3>📡 Sensor Information</h3>
            <div class="loading-spinner"></div>
        </div>
    `;
    infoPanel.classList.add('loading');

    fetch(`api/get_sensor_details.php?sensor_id=${sensorId}`)
        .then(response => response.json())
        .then(data => {
            const sensor = data.sensor;
            const readings = data.recent_readings;
    const aqiInfo = getAQIInfo(sensor.aqi_value);
    
            infoPanel.innerHTML = `
                <div class="info-header">
                    <h3>📡 Sensor Information</h3>
                    <div class="sensor-status ${sensor.status}">
                        <span class="status-dot"></span>
                        ${sensor.status}
                    </div>
                </div>
                <div class="info-content">
                    <div class="info-card main-info" style="border-color: ${aqiInfo.color}">
                        <div class="card-header" style="background-color: ${aqiInfo.color}">
                            <div class="header-content">
                                <span class="emoji animated">${aqiInfo.emoji}</span>
                                <div class="header-text">
                                    <div class="aqi-value">${sensor.aqi_value}</div>
                                    <div class="aqi-category">${aqiInfo.category}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <span class="info-label">📍 Location:</span>
                                <span class="info-value">${sensor.location_name}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">🌐 Coordinates:</span>
                                <span class="info-value">${sensor.latitude}, ${sensor.longitude}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">🕒 Last Update:</span>
                                <span class="info-value">${getTimeAgo(sensor.last_update)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card stats">
                        <h4>📊 Statistics</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">${Math.round(sensor.avg_aqi)}</div>
                                <div class="stat-label">Average AQI</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${sensor.total_readings}</div>
                                <div class="stat-label">Total Readings</div>
                            </div>
                </div>
            </div>

                    <div class="info-card chart">
                        <h4>📈 24 Hour Trend</h4>
                        <div class="trend-chart" id="trend-chart-${sensorId}"></div>
                    </div>
                </div>
            `;

            // Create trend chart
            createTrendChart(sensorId, readings);
            infoPanel.classList.remove('loading');
        })
        .catch(error => {
            console.error('Error:', error);
            infoPanel.innerHTML = `
                <div class="info-header">
                    <h3>📡 Sensor Information</h3>
            </div>
                <div class="info-content error">
                    <p>⚠️ Error loading sensor information. Please try again.</p>
                </div>
            `;
            infoPanel.classList.remove('loading');
        });
}

// Function to create trend chart
function createTrendChart(sensorId, readings) {
    const chartContainer = document.getElementById(`trend-chart-${sensorId}`);
    if (!chartContainer || !readings.length) return;

    const chartData = readings.reverse().map(reading => ({
        value: reading.aqi_value,
        time: new Date(reading.timestamp)
    }));

    const width = chartContainer.offsetWidth;
    const height = 200;
    const margin = { top: 20, right: 20, bottom: 30, left: 40 };
    const innerWidth = width - margin.left - margin.right;
    const innerHeight = height - margin.top - margin.bottom;

    // Create SVG
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', width);
    svg.setAttribute('height', height);
    svg.style.overflow = 'visible';

    // Create scales
    const xScale = d3.scaleTime()
        .domain(d3.extent(chartData, d => d.time))
        .range([0, innerWidth]);

    const yScale = d3.scaleLinear()
        .domain([0, d3.max(chartData, d => d.value)])
        .range([innerHeight, 0])
        .nice();

    // Create line generator
    const line = d3.line()
        .x(d => xScale(d.time))
        .y(d => yScale(d.value))
        .curve(d3.curveMonotoneX);

    // Create group element
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('transform', `translate(${margin.left},${margin.top})`);

    // Add path
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', line(chartData));
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke', '#4CAF50');
    path.setAttribute('stroke-width', '2');
    g.appendChild(path);

    // Add axes
    const xAxis = d3.axisBottom(xScale)
        .ticks(5)
        .tickFormat(d3.timeFormat('%H:%M'));
    
    const yAxis = d3.axisLeft(yScale)
        .ticks(5);

    const xAxisG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    xAxisG.setAttribute('transform', `translate(0,${innerHeight})`);
    xAxisG.setAttribute('class', 'x-axis');
    g.appendChild(xAxisG);

    const yAxisG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    yAxisG.setAttribute('class', 'y-axis');
    g.appendChild(yAxisG);

    svg.appendChild(g);
    chartContainer.appendChild(svg);

    // Add axes using D3
    d3.select(xAxisG).call(xAxis);
    d3.select(yAxisG).call(yAxis);
}

// Function to load and display sensors
async function loadSensors() {
    try {
        const response = await fetch('api/get_sensor_data.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.error) {
            console.error('Error loading sensors:', data.message);
            showError('Unable to load sensor data. Please try again later.');
            return;
        }

        // Clear existing markers
        markersLayer.clearLayers();
        
        // Update sensor count in UI if element exists
        const countElement = document.getElementById('sensor-count');
        if (countElement) {
            countElement.textContent = `${data.count} Active Sensors`;
        }
        
        // Add markers for each active sensor
        data.sensors.forEach(sensor => {
            const marker = createMarker(sensor);
            
            // Add click event to show sensor details
            marker.on('click', () => {
                // Update sensor information panel
                updateSensorInfo(sensor.sensor_id);
                
                // Highlight selected sensor
                marker.getElement().classList.add('selected');
            });
            
            // Remove highlight when popup closes
            marker.on('popupclose', () => {
                marker.getElement().classList.remove('selected');
            });
            
            markersLayer.addLayer(marker);
        });

        // Update last refresh time if element exists
        const refreshElement = document.getElementById('last-refresh');
        if (refreshElement) {
            refreshElement.textContent = `Last updated: ${new Date(data.timestamp).toLocaleString()}`;
        }

    } catch (error) {
        console.error('Error loading sensors:', error);
        showError('Unable to load sensor data. Please try again later.');
    }
}

// Function to show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `
        <div class="error-content">
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        </div>
    `;

    // Remove any existing error message
    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    document.body.appendChild(errorDiv);
    
    // Remove error message after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Load sensors initially
loadSensors();

// Refresh sensor data every 5 minutes
setInterval(loadSensors, 5 * 60 * 1000);

// Add CSS for selected marker
const style = document.createElement('style');
style.textContent = `
    .custom-marker-icon.selected .marker-container {
        transform: scale(1.2);
        box-shadow: 0 0 15px rgba(0,0,0,0.3);
    }
    .error-message {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff5252;
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }
    .error-content {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);
