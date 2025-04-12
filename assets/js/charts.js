// Function to create historical data chart
function createChart(data) {
    const ctx = document.getElementById('sensorChart').getContext('2d');
    
    // Prepare data
    const labels = data.map(reading => new Date(reading.timestamp).toLocaleTimeString());
    const values = data.map(reading => reading.aqi_value);

    // Create chart
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'AQI Value',
                data: values,
                borderColor: '#3498db',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
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
            },
            plugins: {
                title: {
                    display: true,
                    text: '24-Hour AQI Trend'
                }
            }
        }
    });
}
