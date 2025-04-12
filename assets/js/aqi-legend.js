// Function to update AQI legend
async function updateAQILegend() {
    try {
        const response = await fetch('api/get_aqi_thresholds.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.success) {
            const legendContainer = document.getElementById('aqi-legend-items');
            if (legendContainer) {
                // Clear loading message
                legendContainer.innerHTML = '';
                
                // Add each threshold to the legend
                data.data.forEach(threshold => {
                    const legendItem = document.createElement('div');
                    legendItem.className = 'legend-item';
                    legendItem.innerHTML = `
                        <span class="color-box" style="background: ${threshold.color}"></span>
                        <div>
                            <strong>${threshold.category}</strong>
                            <div class="text-secondary">${threshold.min_value}-${threshold.max_value} AQI</div>
                            <div class="description-text">${threshold.description}</div>
                        </div>
                    `;
                    legendContainer.appendChild(legendItem);
                });
            }
        }
    } catch (error) {
        console.error('Error fetching AQI thresholds:', error);
        const legendContainer = document.getElementById('aqi-legend-items');
        if (legendContainer) {
            legendContainer.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading AQI thresholds. Please refresh the page.
                </div>
            `;
        }
    }
}

// Update AQI legend on page load
document.addEventListener('DOMContentLoaded', updateAQILegend); 