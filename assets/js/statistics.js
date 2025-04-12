// Function to update statistics
async function updateStatistics() {
    try {
        const response = await fetch('api/get_statistics.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.success) {
            // Update active sensors count
            const activeSensorsElement = document.getElementById('activeSensors');
            if (activeSensorsElement) {
                activeSensorsElement.textContent = data.data.active_sensors;
            }

            // Update average AQI
            const averageAQIElement = document.getElementById('averageAQI');
            if (averageAQIElement) {
                averageAQIElement.textContent = data.data.average_aqi;
                
                // Update color based on AQI value
                const aqi = parseFloat(data.data.average_aqi);
                let color = '#66BB6A'; // Good (default)
                
                if (aqi > 300) color = '#B71C1C'; // Hazardous
                else if (aqi > 200) color = '#8E24AA'; // Very Unhealthy
                else if (aqi > 150) color = '#E53935'; // Unhealthy
                else if (aqi > 100) color = '#FB8C00'; // Unhealthy for Sensitive Groups
                else if (aqi > 50) color = '#FDD835'; // Moderate
                
                averageAQIElement.style.color = color;
            }
        }
    } catch (error) {
        console.error('Error fetching statistics:', error);
        document.getElementById('activeSensors').textContent = 'Error';
        document.getElementById('averageAQI').textContent = 'Error';
    }
}

// Update statistics every 5 minutes
updateStatistics(); // Initial update
setInterval(updateStatistics, 5 * 60 * 1000); // Update every 5 minutes 