/**
 * Dashboard Widgets Enhancement Script
 * Simple weather and calendar functionality for CORE II
 */

// Weather update functionality
function updateWeatherDisplay() {
    // Simulate real-time weather updates
    const temperatures = [26, 27, 28, 29, 30];
    const conditions = ['Sunny', 'Partly Cloudy', 'Cloudy', 'Light Rain'];
    const icons = ['bi-sun', 'bi-cloud-sun', 'bi-cloud', 'bi-cloud-rain'];
    
    const randomTemp = temperatures[Math.floor(Math.random() * temperatures.length)];
    const randomCondition = conditions[Math.floor(Math.random() * conditions.length)];
    const randomIcon = icons[Math.floor(Math.random() * icons.length)];
    
    // Update temperature
    const tempElements = document.querySelectorAll('.weather-temp');
    tempElements.forEach(el => el.textContent = `${randomTemp}°C`);
    
    // Update condition
    const conditionElements = document.querySelectorAll('.weather-condition');
    conditionElements.forEach(el => el.textContent = randomCondition);
    
    // Update icons
    const iconElements = document.querySelectorAll('.weather-icon i');
    iconElements.forEach(el => {
        el.className = randomIcon;
    });
    
    console.log('Weather updated:', { temp: randomTemp, condition: randomCondition });
}

// Calendar update functionality
function updateCalendarDisplay() {
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const todayString = today.toLocaleDateString('en-US', options);
    
    // Update date displays
    const dateElements = document.querySelectorAll('.calendar-date');
    dateElements.forEach(el => el.textContent = todayString);
    
    // Update day number
    const dayElements = document.querySelectorAll('.calendar-day-number');
    dayElements.forEach(el => el.textContent = today.getDate());
    
    console.log('Calendar updated:', todayString);
}

// Initialize widgets
function initializeWidgets() {
    console.log('Initializing dashboard widgets...');
    
    // Initial update
    updateWeatherDisplay();
    updateCalendarDisplay();
    
    // Set up periodic updates
    setInterval(updateWeatherDisplay, 300000); // Update weather every 5 minutes
    setInterval(updateCalendarDisplay, 60000);  // Update calendar every minute
    
    // Add click handlers for refresh buttons
    const refreshButtons = document.querySelectorAll('.refresh-widget');
    refreshButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateWeatherDisplay();
            updateCalendarDisplay();
        });
    });
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeWidgets);
} else {
    initializeWidgets();
}
