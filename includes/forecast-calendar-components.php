<?php
// Shared Forecast and Calendar Components for CORE II Dashboards
// This file contains reusable weather forecast and calendar widgets

/**
 * Generate Weather Forecast Widget HTML
 */
function generateForecastWidget($size = 'default') {
    $widgetClass = $size === 'small' ? 'forecast-widget-small' : 'forecast-widget';
    
    return '
    <div class="' . $widgetClass . ' card">
        <div class="widget-header">
            <h4><i class="bi bi-cloud-sun"></i> Weather Forecast</h4>
            <button class="btn btn-link btn-sm refresh-weather" onclick="refreshWeather()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
        <div class="weather-content" id="weatherContent">
            <div class="current-weather">
                <div class="weather-main">
                    <div class="temperature" id="currentTemp">--°</div>
                    <div class="weather-icon" id="weatherIcon">
                        <i class="bi bi-cloud-sun"></i>
                    </div>
                </div>
                <div class="weather-details">
                    <div class="location" id="weatherLocation">Loading...</div>
                    <div class="description" id="weatherDescription">--</div>
                    <div class="feels-like">Feels like <span id="feelsLike">--°</span></div>
                </div>
            </div>
            <div class="weather-stats">
                <div class="stat-item">
                    <i class="bi bi-droplet"></i>
                    <span>Humidity</span>
                    <span id="humidity">--%</span>
                </div>
                <div class="stat-item">
                    <i class="bi bi-wind"></i>
                    <span>Wind</span>
                    <span id="windSpeed">-- km/h</span>
                </div>
                <div class="stat-item">
                    <i class="bi bi-eye"></i>
                    <span>Visibility</span>
                    <span id="visibility">-- km</span>
                </div>
            </div>
            <div class="hourly-forecast" id="hourlyForecast">
                <!-- Hourly forecast will be populated here -->
            </div>
        </div>
        <div class="weather-loading" id="weatherLoading" style="display: none;">
            <div class="loading-spinner-small"></div>
            <span>Updating weather...</span>
        </div>
    </div>';
}

/**
 * Generate Calendar Widget HTML
 */
function generateCalendarWidget($size = 'default') {
    $widgetClass = $size === 'small' ? 'calendar-widget-small' : 'calendar-widget';
    
    return '
    <div class="' . $widgetClass . ' card">
        <div class="widget-header">
            <h4><i class="bi bi-calendar3"></i> Calendar</h4>
            <div class="calendar-controls">
                <button class="btn btn-link btn-sm" onclick="previousMonth()">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span id="currentMonthYear" class="month-year"></span>
                <button class="btn btn-link btn-sm" onclick="nextMonth()">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="calendar-content">
            <div class="calendar-header">
                <div class="day-name">Sun</div>
                <div class="day-name">Mon</div>
                <div class="day-name">Tue</div>
                <div class="day-name">Wed</div>
                <div class="day-name">Thu</div>
                <div class="day-name">Fri</div>
                <div class="day-name">Sat</div>
            </div>
            <div class="calendar-body" id="calendarBody">
                <!-- Calendar days will be populated here -->
            </div>
        </div>
        <div class="calendar-footer">
            <div class="today-info">
                <span class="today-date" id="todayDate"></span>
                <span class="today-events" id="todayEvents">No events today</span>
            </div>
        </div>
    </div>';
}

/**
 * Generate Combined Widget (Forecast + Calendar in compact form)
 */
function generateCombinedWidget() {
    return '
    <div class="combined-widget card">
        <div class="widget-tabs">
            <button class="tab-btn active" onclick="switchTab(\'weather\')">
                <i class="bi bi-cloud-sun"></i> Weather
            </button>
            <button class="tab-btn" onclick="switchTab(\'calendar\')">
                <i class="bi bi-calendar3"></i> Calendar
            </button>
        </div>
        <div class="tab-content">
            <div class="tab-pane active" id="weather-tab">
                ' . generateForecastWidget('small') . '
            </div>
            <div class="tab-pane" id="calendar-tab">
                ' . generateCalendarWidget('small') . '
            </div>
        </div>
    </div>';
}

/**
 * Generate the CSS styles for the widgets
 */
function generateWidgetStyles() {
    return '
    <style>
    /* Weather Forecast Widget Styles */
    .forecast-widget, .forecast-widget-small {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: var(--border-radius);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        padding: 1.5rem;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .forecast-widget::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    }

    .dark-mode .forecast-widget,
    .dark-mode .forecast-widget-small {
        background: rgba(44, 62, 80, 0.9);
        color: var(--text-light);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .widget-header {
        border-bottom-color: rgba(255,255,255,0.1);
    }

    .widget-header h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dark-mode .widget-header h4 {
        color: var(--text-light);
    }

    .refresh-weather {
        color: #6c757d;
        transition: all 0.3s;
    }

    .refresh-weather:hover {
        color: #0984e3;
        transform: rotate(180deg);
    }

    .current-weather {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .weather-main {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .temperature {
        font-size: 3rem;
        font-weight: 800;
        color: #0984e3;
        line-height: 1;
    }

    .weather-icon {
        font-size: 2.5rem;
        color: #74b9ff;
    }

    .weather-details {
        text-align: right;
    }

    .location {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .description {
        color: #6c757d;
        margin-bottom: 0.25rem;
        text-transform: capitalize;
    }

    .feels-like {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .weather-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: rgba(0,0,0,0.05);
        border-radius: 0.5rem;
    }

    .dark-mode .weather-stats {
        background: rgba(255,255,255,0.05);
    }

    .stat-item {
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .stat-item i {
        font-size: 1.2rem;
        color: #74b9ff;
        margin-bottom: 0.25rem;
    }

    .stat-item span:first-of-type {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-item span:last-of-type {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .hourly-forecast {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding: 0.5rem 0;
    }

    .hour-item {
        flex: 0 0 auto;
        text-align: center;
        padding: 0.75rem;
        background: rgba(116, 185, 255, 0.1);
        border-radius: 0.5rem;
        min-width: 80px;
    }

    .hour-time {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }

    .hour-icon {
        font-size: 1.5rem;
        color: #74b9ff;
        margin-bottom: 0.5rem;
    }

    .hour-temp {
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* Calendar Widget Styles */
    .calendar-widget, .calendar-widget-small {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: var(--border-radius);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        padding: 1.5rem;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .calendar-widget::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    }

    .dark-mode .calendar-widget,
    .dark-mode .calendar-widget-small {
        background: rgba(44, 62, 80, 0.9);
        color: var(--text-light);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .calendar-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .month-year {
        font-weight: 600;
        min-width: 140px;
        text-align: center;
    }

    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .day-name {
        text-align: center;
        font-size: 0.8rem;
        font-weight: 600;
        color: #6c757d;
        padding: 0.5rem 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .calendar-body {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.25rem;
    }

    .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        font-size: 0.9rem;
    }

    .calendar-day:hover {
        background: rgba(0, 184, 148, 0.1);
    }

    .calendar-day.other-month {
        color: #bbb;
    }

    .calendar-day.today {
        background: #00b894;
        color: white;
        font-weight: 600;
    }

    .calendar-day.has-event::after {
        content: "";
        position: absolute;
        bottom: 2px;
        left: 50%;
        transform: translateX(-50%);
        width: 4px;
        height: 4px;
        background: #e17055;
        border-radius: 50%;
    }

    .calendar-day.today.has-event::after {
        background: rgba(255,255,255,0.8);
    }

    .calendar-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .calendar-footer {
        border-top-color: rgba(255,255,255,0.1);
    }

    .today-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .today-date {
        font-weight: 600;
        color: #00b894;
    }

    .today-events {
        font-size: 0.9rem;
        color: #6c757d;
    }

    /* Combined Widget Styles */
    .combined-widget {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: var(--border-radius);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        transition: all 0.3s;
        overflow: hidden;
    }

    .dark-mode .combined-widget {
        background: rgba(44, 62, 80, 0.9);
        color: var(--text-light);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .widget-tabs {
        display: flex;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .widget-tabs {
        border-bottom-color: rgba(255,255,255,0.1);
    }

    .tab-btn {
        flex: 1;
        padding: 1rem;
        border: none;
        background: none;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 500;
    }

    .tab-btn:hover {
        background: rgba(0,0,0,0.05);
    }

    .dark-mode .tab-btn:hover {
        background: rgba(255,255,255,0.05);
    }

    .tab-btn.active {
        background: rgba(116, 185, 255, 0.1);
        color: #0984e3;
        border-bottom: 2px solid #0984e3;
    }

    .tab-content {
        padding: 0;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .tab-pane .card {
        border: none;
        box-shadow: none;
        margin: 0;
    }

    /* Loading Spinner */
    .weather-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 2rem;
        color: #6c757d;
    }

    .loading-spinner-small {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(116, 185, 255, 0.2);
        border-top: 2px solid #74b9ff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .weather-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .current-weather {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .weather-details {
            text-align: center;
        }

        .hourly-forecast {
            justify-content: center;
        }

        .calendar-day {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 480px) {
        .weather-stats {
            grid-template-columns: 1fr;
        }

        .widget-header h4 {
            font-size: 1rem;
        }

        .temperature {
            font-size: 2.5rem;
        }

        .weather-icon {
            font-size: 2rem;
        }
    }
    </style>';
}

/**
 * Generate the JavaScript for widget functionality
 */
function generateWidgetScripts() {
    return '
    <script>
    // Weather and Calendar Widget JavaScript

    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let weatherUpdateInterval;

    // Weather functionality
    async function refreshWeather() {
        const weatherContent = document.getElementById("weatherContent");
        const weatherLoading = document.getElementById("weatherLoading");
        
        if (weatherContent) weatherContent.style.display = "none";
        if (weatherLoading) weatherLoading.style.display = "flex";

        try {
            // Get user location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(async (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    await fetchWeatherData(lat, lon);
                }, () => {
                    // Fallback to default location (Manila)
                    fetchWeatherData(14.5995, 120.9842);
                });
            } else {
                // Fallback to default location
                await fetchWeatherData(14.5995, 120.9842);
            }
        } catch (error) {
            console.error("Error fetching weather:", error);
            showWeatherError();
        }
    }

    async function fetchWeatherData(lat, lon) {
        try {
            // Use our local weather API endpoint
            const response = await fetch(`api/weather.php?lat=${lat}&lon=${lon}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const weatherData = await response.json();
            
            if (weatherData.success) {
                updateWeatherDisplayFromAPI(weatherData);
            } else {
                throw new Error(weatherData.error || 'Failed to fetch weather data');
            }

        } catch (error) {
            console.error("Weather API error:", error);
            showWeatherError();
        } finally {
            const weatherContent = document.getElementById("weatherContent");
            const weatherLoading = document.getElementById("weatherLoading");
            
            if (weatherLoading) weatherLoading.style.display = "none";
            if (weatherContent) weatherContent.style.display = "block";
        }
    }

    function updateWeatherDisplay(data) {
        const elements = {
            currentTemp: document.getElementById("currentTemp"),
            weatherLocation: document.getElementById("weatherLocation"),
            weatherDescription: document.getElementById("weatherDescription"),
            feelsLike: document.getElementById("feelsLike"),
            humidity: document.getElementById("humidity"),
            windSpeed: document.getElementById("windSpeed"),
            visibility: document.getElementById("visibility"),
            weatherIcon: document.getElementById("weatherIcon")
        };

        if (elements.currentTemp) elements.currentTemp.textContent = `${data.temperature}°C`;
        if (elements.weatherLocation) elements.weatherLocation.textContent = data.location;
        if (elements.weatherDescription) elements.weatherDescription.textContent = data.description;
        if (elements.feelsLike) elements.feelsLike.textContent = `${data.feelsLike}°C`;
        if (elements.humidity) elements.humidity.textContent = `${data.humidity}%`;
        if (elements.windSpeed) elements.windSpeed.textContent = `${data.windSpeed} km/h`;
        if (elements.visibility) elements.visibility.textContent = `${data.visibility} km`;
        
        if (elements.weatherIcon) {
            elements.weatherIcon.innerHTML = `<i class="${data.icon}"></i>`;
        }
    }

    function updateWeatherDisplayFromAPI(data) {
        const elements = {
            currentTemp: document.getElementById("currentTemp"),
            weatherLocation: document.getElementById("weatherLocation"),
            weatherDescription: document.getElementById("weatherDescription"),
            feelsLike: document.getElementById("feelsLike"),
            humidity: document.getElementById("humidity"),
            windSpeed: document.getElementById("windSpeed"),
            visibility: document.getElementById("visibility"),
            weatherIcon: document.getElementById("weatherIcon")
        };

        if (elements.currentTemp) elements.currentTemp.textContent = `${data.current.temperature}°C`;
        if (elements.weatherLocation) elements.weatherLocation.textContent = data.location;
        if (elements.weatherDescription) elements.weatherDescription.textContent = data.current.description;
        if (elements.feelsLike) elements.feelsLike.textContent = `${data.current.feels_like}°C`;
        if (elements.humidity) elements.humidity.textContent = `${data.current.humidity}%`;
        if (elements.windSpeed) elements.windSpeed.textContent = `${data.current.wind_speed} km/h`;
        if (elements.visibility) elements.visibility.textContent = `${data.current.visibility} km`;
        
        if (elements.weatherIcon) {
            elements.weatherIcon.innerHTML = `<i class="${data.current.icon}"></i>`;
        }

        // Update hourly forecast from API data
        updateHourlyForecastFromAPI(data.hourly);
    }

    function updateHourlyForecast() {
        const hourlyForecast = document.getElementById("hourlyForecast");
        if (!hourlyForecast) return;

        const hours = [];
        const now = new Date();
        
        for (let i = 1; i <= 6; i++) {
            const time = new Date(now.getTime() + (i * 60 * 60 * 1000));
            const temp = Math.floor(Math.random() * 8) + 22;
            const icons = ["bi-sun", "bi-cloud", "bi-cloud-sun", "bi-clouds"];
            const icon = icons[Math.floor(Math.random() * icons.length)];
            
            hours.push({
                time: time.getHours().toString().padStart(2, "0") + ":00",
                temp: temp,
                icon: icon
            });
        }

        hourlyForecast.innerHTML = hours.map(hour => `
            <div class="hour-item">
                <div class="hour-time">${hour.time}</div>
                <div class="hour-icon"><i class="${hour.icon}"></i></div>
                <div class="hour-temp">${hour.temp}°</div>
            </div>
        `).join("");
    }

    function updateHourlyForecastFromAPI(hourlyData) {
        const hourlyForecast = document.getElementById("hourlyForecast");
        if (!hourlyForecast || !hourlyData || hourlyData.length === 0) {
            updateHourlyForecast(); // Fallback to mock data
            return;
        }

        hourlyForecast.innerHTML = hourlyData.map(hour => `
            <div class="hour-item">
                <div class="hour-time">${hour.time}</div>
                <div class="hour-icon"><i class="${hour.icon}"></i></div>
                <div class="hour-temp">${hour.temperature}°</div>
            </div>
        `).join("");
    }

    function showWeatherError() {
        const weatherContent = document.getElementById("weatherContent");
        if (weatherContent) {
            weatherContent.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                    <p>Unable to load weather data</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshWeather()">
                        <i class="bi bi-arrow-clockwise"></i> Retry
                    </button>
                </div>
            `;
        }
    }

    // Calendar functionality
    let calendarEvents = [];

    async function loadCalendarEvents() {
        try {
            const response = await fetch(`api/calendar-events.php?year=${currentYear}&month=${currentMonth + 1}`);
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    calendarEvents = data.events || [];
                    updateTodayEventsDisplay(data.stats);
                } else {
                    console.log('Using demo calendar data');
                    calendarEvents = [];
                }
            } else {
                // Fallback to demo mode
                console.log('Calendar API not available, using demo data');
                calendarEvents = [];
            }
        } catch (error) {
            console.log('Calendar API error, using demo data:', error);
            calendarEvents = [];
        }
        
        renderCalendar();
    }

    function updateTodayEventsDisplay(stats) {
        const todayEventsElement = document.getElementById("todayEvents");
        if (todayEventsElement && stats) {
            const eventCount = stats.today || 0;
            if (eventCount === 0) {
                todayEventsElement.textContent = "No events today";
            } else if (eventCount === 1) {
                todayEventsElement.textContent = "1 event today";
            } else {
                todayEventsElement.textContent = `${eventCount} events today`;
            }
        }
    }

    function renderCalendar() {
        const calendarBody = document.getElementById("calendarBody");
        const monthYearElement = document.getElementById("currentMonthYear");
        const todayDateElement = document.getElementById("todayDate");
        
        if (!calendarBody) return;

        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const today = new Date();
        
        const monthNames = ["January", "February", "March", "April", "May", "June",
                          "July", "August", "September", "October", "November", "December"];
        
        if (monthYearElement) {
            monthYearElement.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        }
        
        if (todayDateElement) {
            todayDateElement.textContent = today.toLocaleDateString("en-US", { 
                weekday: "long", 
                year: "numeric", 
                month: "long", 
                day: "numeric" 
            });
        }

        // Clear calendar
        calendarBody.innerHTML = "";

        // Add empty cells for days before month starts
        const startDay = firstDay.getDay();
        for (let i = 0; i < startDay; i++) {
            const prevMonthDay = new Date(currentYear, currentMonth, -(startDay - i - 1));
            const dayElement = document.createElement("div");
            dayElement.className = "calendar-day other-month";
            dayElement.textContent = prevMonthDay.getDate();
            calendarBody.appendChild(dayElement);
        }

        // Add days of current month
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayElement = document.createElement("div");
            dayElement.className = "calendar-day";
            dayElement.textContent = day;
            
            const currentDate = new Date(currentYear, currentMonth, day);
            const isToday = currentDate.toDateString() === today.toDateString();
            
            if (isToday) {
                dayElement.classList.add("today");
            }
            
            // Check for events on this day
            const dateString = `${currentYear}-${(currentMonth + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            const dayEvents = calendarEvents.filter(event => event.event_date === dateString);
            
            if (dayEvents.length > 0) {
                dayElement.classList.add("has-event");
                dayElement.title = `${dayEvents.length} event${dayEvents.length > 1 ? 's' : ''} on this day`;
            }
            
            dayElement.addEventListener("click", () => selectDate(currentYear, currentMonth, day));
            calendarBody.appendChild(dayElement);
        }

        // Add empty cells for remaining days
        const remainingCells = 42 - (startDay + lastDay.getDate());
        for (let i = 1; i <= remainingCells; i++) {
            const nextMonthDay = new Date(currentYear, currentMonth + 1, i);
            const dayElement = document.createElement("div");
            dayElement.className = "calendar-day other-month";
            dayElement.textContent = nextMonthDay.getDate();
            calendarBody.appendChild(dayElement);
        }
    }

    function previousMonth() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        loadCalendarEvents();
    }

    function nextMonth() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        loadCalendarEvents();
    }

    function selectDate(year, month, day) {
        console.log(`Selected date: ${year}-${month + 1}-${day}`);
        // You can implement date selection functionality here
    }

    // Combined widget tab switching
    function switchTab(tab) {
        const weatherTab = document.getElementById("weather-tab");
        const calendarTab = document.getElementById("calendar-tab");
        const tabButtons = document.querySelectorAll(".tab-btn");
        
        // Remove active class from all tabs and buttons
        if (weatherTab) weatherTab.classList.remove("active");
        if (calendarTab) calendarTab.classList.remove("active");
        tabButtons.forEach(btn => btn.classList.remove("active"));
        
        // Add active class to selected tab
        if (tab === "weather") {
            if (weatherTab) weatherTab.classList.add("active");
            document.querySelector(`[onclick="switchTab(\'weather\')"]`).classList.add("active");
        } else if (tab === "calendar") {
            if (calendarTab) calendarTab.classList.add("active");
            document.querySelector(`[onclick="switchTab(\'calendar\')"]`).classList.add("active");
        }
    }

    // Initialize widgets when DOM is loaded
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize weather
        refreshWeather();
        
        // Initialize calendar
        loadCalendarEvents();
        
        // Set up automatic weather refresh every 10 minutes
        weatherUpdateInterval = setInterval(refreshWeather, 600000);
        
        // Set up automatic calendar refresh every 30 minutes
        setInterval(loadCalendarEvents, 1800000);
        
        // Update calendar at midnight
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(now.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);
        
        setTimeout(() => {
            loadCalendarEvents();
            // Then update daily
            setInterval(loadCalendarEvents, 24 * 60 * 60 * 1000);
        }, tomorrow.getTime() - now.getTime());
    });

    // Cleanup intervals on page unload
    window.addEventListener("beforeunload", function() {
        if (weatherUpdateInterval) {
            clearInterval(weatherUpdateInterval);
        }
    });
    </script>';
}
?>
