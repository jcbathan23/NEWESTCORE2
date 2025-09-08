// Real-Time Mini Map Widget for Dashboards
class MiniMap {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.options = {
            height: options.height || '300px',
            showVehicles: options.showVehicles !== false,
            showRoutes: options.showRoutes !== false,
            showStats: options.showStats !== false,
            autoUpdate: options.autoUpdate !== false,
            updateInterval: options.updateInterval || 5000, // Reduced to 5 seconds for real-time
            realTime: options.realTime !== false,
            websocketUrl: options.websocketUrl || null,
            animateMovement: options.animateMovement !== false,
            showConnectionStatus: options.showConnectionStatus !== false,
            ...options
        };
        
        this.map = null;
        this.markers = {}; // Changed to object for easier vehicle tracking
        this.vehicleTrails = {}; // Store movement trails
        this.updateTimer = null;
        this.fallbackMode = false;
        this.websocket = null;
        this.connectionStatus = 'connecting';
        this.lastUpdateTime = null;
        this.animationFrameId = null;
        
        this.init();
    }
    
    init() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error(`Mini map container '${this.containerId}' not found`);
            return;
        }
        
        container.innerHTML = `
            <div class="mini-map-container" style="
                height: ${this.options.height}; 
                width: 100%; 
                position: relative; 
                border-radius: 0.375rem; 
                overflow: hidden;
                min-height: 200px;
                max-height: 400px;
            ">
                <div id="${this.containerId}_loading" class="mini-map-loading" style="
                    position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
                    display: flex; align-items: center; justify-content: center; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; z-index: 10;
                ">
                    <div style="text-align: center;">
                        <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                        <div style="font-size: 0.85rem;">Loading Map...</div>
                    </div>
                </div>
                <div id="${this.containerId}_map" style="height: 100%; width: 100%; display: block;"></div>
                ${this.options.showStats ? this.createStatsOverlay() : ''}
            </div>
        `;
        
        // Try to initialize with Leaflet
        if (typeof L !== 'undefined') {
            this.initLeafletMap();
        } else {
            // Load Leaflet or use fallback
            this.loadLeafletOrFallback();
        }
        
        if (this.options.autoUpdate) {
            this.startAutoUpdate();
        }
        
        // Initialize real-time features
        if (this.options.realTime) {
            this.initWebSocket();
        }
    }
    
    createStatsOverlay() {
        return `
            <div class="mini-map-stats" style="
                position: absolute; top: 10px; left: 10px; 
                background: rgba(0,0,0,0.7); color: white; 
                padding: 0.5rem; border-radius: 0.25rem; 
                font-size: 0.75rem; z-index: 1000;
                backdrop-filter: blur(5px);
                min-width: 120px;
            ">
                <div id="${this.containerId}_stats">
                    <div>🚛 <span id="${this.containerId}_vehicles">0</span> Vehicles</div>
                    <div>🛣️ <span id="${this.containerId}_routes">0</span> Routes</div>
                    ${this.options.showConnectionStatus ? `
                    <div style="margin-top: 0.25rem; padding-top: 0.25rem; border-top: 1px solid rgba(255,255,255,0.2);">
                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                            <span id="${this.containerId}_status_dot" class="status-dot" style="
                                width: 6px; height: 6px; border-radius: 50%; 
                                background: #ffa500; animation: pulse 1.5s infinite;
                            "></span>
                            <span id="${this.containerId}_status_text" style="font-size: 0.65rem; opacity: 0.9;">Connecting...</span>
                        </div>
                        <div style="font-size: 0.6rem; opacity: 0.7; margin-top: 0.15rem;" id="${this.containerId}_last_update">Last: --</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            <style>
                @keyframes pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.5; transform: scale(0.8); }
                }
                
                @keyframes marker-pulse {
                    0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
                    70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
                }
                
                .enhanced-tooltip {
                    background: rgba(0, 0, 0, 0.9) !important;
                    border: 1px solid rgba(255, 255, 255, 0.2) !important;
                    border-radius: 0.25rem !important;
                    backdrop-filter: blur(10px) !important;
                    color: white !important;
                    font-family: system-ui, -apple-system, sans-serif !important;
                }
                
                .enhanced-tooltip .leaflet-tooltip-content {
                    margin: 0 !important;
                    padding: 0.5rem !important;
                }
                
                .vehicle-marker.vehicle-active {
                    animation: marker-pulse 2s infinite;
                }
                
                .mini-map-container .leaflet-marker-icon {
                    transition: all 0.3s ease-in-out;
                }
            </style>
        `;
    }
    
    loadLeafletOrFallback() {
        console.log('Loading Leaflet or using fallback...');
        
        // Try local Leaflet first, but timeout quickly
        const script = document.createElement('script');
        script.src = 'js/leaflet/leaflet.js';
        
        let loaded = false;
        
        script.onload = () => {
            if (!loaded && typeof L !== 'undefined') {
                loaded = true;
                console.log('Local Leaflet loaded successfully');
                this.initLeafletMap();
            } else if (!loaded) {
                console.log('Local Leaflet failed, using fallback');
                this.initFallbackMap();
            }
        };
        
        script.onerror = () => {
            if (!loaded) {
                console.log('Local Leaflet failed to load, trying CDN');
                // Try CDN quickly
                const cdnScript = document.createElement('script');
                cdnScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                cdnScript.onload = () => {
                    if (!loaded) {
                        loaded = true;
                        console.log('CDN Leaflet loaded');
                        this.initLeafletMap();
                    }
                };
                cdnScript.onerror = () => {
                    if (!loaded) {
                        loaded = true;
                        console.log('CDN also failed, using fallback');
                        this.initFallbackMap();
                    }
                };
                document.head.appendChild(cdnScript);
                
                // Fallback timeout
                setTimeout(() => {
                    if (!loaded) {
                        loaded = true;
                        console.log('Timeout reached, using fallback');
                        this.initFallbackMap();
                    }
                }, 2000);
            }
        };
        
        document.head.appendChild(script);
        
        // Emergency timeout
        setTimeout(() => {
            if (!loaded) {
                loaded = true;
                console.log('Emergency timeout, using fallback');
                this.initFallbackMap();
            }
        }, 3000);
    }
    
    initLeafletMap() {
        try {
            const mapContainer = document.getElementById(`${this.containerId}_map`);
            if (!mapContainer) {
                console.error('Map container element not found');
                this.initFallbackMap();
                return;
            }
            
            // Force explicit dimensions and visibility
            mapContainer.style.height = '280px';
            mapContainer.style.width = '100%';
            mapContainer.style.position = 'relative';
            mapContainer.style.zIndex = '1';
            mapContainer.style.display = 'block';
            mapContainer.style.visibility = 'visible';
            mapContainer.style.backgroundColor = '#e0e0e0';
            
            console.log('Initializing Leaflet map with container:', mapContainer);
            
            this.map = L.map(`${this.containerId}_map`, {
                zoomControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                dragging: false,
                touchZoom: false,
                boxZoom: false,
                keyboard: false,
                attribution: false,
                preferCanvas: false
            }).setView([14.5995, 120.9842], 11);
            
            console.log('Map object created:', this.map);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: ''
            }).addTo(this.map);
            
            console.log('Tile layer added');
            
            // Multiple attempts at size invalidation
            const invalidateSize = () => {
                if (this.map) {
                    this.map.invalidateSize(true);
                    console.log('Mini map size invalidated');
                }
            };
            
            setTimeout(invalidateSize, 100);
            setTimeout(invalidateSize, 300);
            setTimeout(invalidateSize, 500);
            setTimeout(invalidateSize, 1000);
            
            // Hide loading
            this.hideLoading();
            
            // Load data
            setTimeout(() => {
                this.loadMapData();
            }, 500);
            
            // Add click to expand
            mapContainer.style.cursor = 'pointer';
            mapContainer.onclick = () => this.expandMap();
            
            console.log('Mini map initialization complete');
            
        } catch (error) {
            console.error('Failed to initialize Leaflet mini map:', error);
            this.initFallbackMap();
        }
    }
    
    initFallbackMap() {
        this.fallbackMode = true;
        const mapContainer = document.getElementById(`${this.containerId}_map`);
        
        mapContainer.innerHTML = `
            <div style="
                height: ${this.options.height}; width: 100%; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex; align-items: center; justify-content: center;
                color: white; text-align: center; cursor: pointer;
                position: relative; overflow: hidden;
                min-height: 280px;
            " onclick="this.expandMap && this.expandMap()">
                <div style="z-index: 2;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🗺️</div>
                    <div style="font-weight: bold; margin-bottom: 0.25rem; font-size: 1.1rem;">Service Network</div>
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">Manila Metro Area</div>
                    <div style="display: flex; gap: 1.2rem; margin-top: 0.75rem; font-size: 0.8rem; justify-content: center;">
                        <div><span style="color: #4CAF50;">●</span> 5 Active</div>
                        <div><span style="color: #FF9800;">●</span> 1 Maintenance</div>
                    </div>
                    <div style="margin-top: 0.75rem; font-size: 0.7rem; opacity: 0.7;">Click to view full map</div>
                </div>
                
                <!-- Animated background -->
                <div style="position: absolute; top: 20%; left: 20%; width: 8px; height: 8px; background: rgba(255,255,255,0.3); border-radius: 50%; animation: float 3s ease-in-out infinite;"></div>
                <div style="position: absolute; top: 60%; left: 70%; width: 6px; height: 6px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 4s ease-in-out infinite 1s;"></div>
                <div style="position: absolute; top: 30%; left: 80%; width: 10px; height: 10px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: float 5s ease-in-out infinite 2s;"></div>
            </div>
            
            <style>
                @keyframes float {
                    0%, 100% { transform: translateY(0px) scale(1); opacity: 0.5; }
                    50% { transform: translateY(-10px) scale(1.2); opacity: 1; }
                }
            </style>
        `;
        
        mapContainer.onclick = () => this.expandMap();
        this.hideLoading();
        this.updateStats({ vehicles: 5, routes: 3 });
    }
    
    // WebSocket and Real-time Methods
    initWebSocket() {
        if (!this.options.websocketUrl) {
            console.log('WebSocket URL not provided, using polling mode');
            return;
        }
        
        try {
            this.websocket = new WebSocket(this.options.websocketUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connected for mini map');
                this.updateConnectionStatus('connected');
                
                // Subscribe to vehicle updates
                this.websocket.send(JSON.stringify({
                    type: 'subscribe',
                    channel: 'vehicle_tracking'
                }));
            };
            
            this.websocket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleRealTimeUpdate(data);
                } catch (error) {
                    console.error('Error parsing WebSocket message:', error);
                }
            };
            
            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.updateConnectionStatus('error');
            };
            
            this.websocket.onclose = () => {
                console.log('WebSocket disconnected');
                this.updateConnectionStatus('disconnected');
                
                // Attempt to reconnect after 3 seconds
                setTimeout(() => {
                    if (this.options.realTime) {
                        this.initWebSocket();
                    }
                }, 3000);
            };
            
        } catch (error) {
            console.error('Failed to initialize WebSocket:', error);
            this.updateConnectionStatus('error');
        }
    }
    
    handleRealTimeUpdate(data) {
        if (data.type === 'vehicle_update' && data.vehicle) {
            this.updateVehiclePosition(data.vehicle);
        } else if (data.type === 'vehicles_batch' && data.vehicles) {
            this.updateVehiclesPositions(data.vehicles);
        }
        
        this.lastUpdateTime = new Date();
        this.updateLastUpdateTime();
    }
    
    updateConnectionStatus(status) {
        this.connectionStatus = status;
        const statusDot = document.getElementById(`${this.containerId}_status_dot`);
        const statusText = document.getElementById(`${this.containerId}_status_text`);
        
        if (!statusDot || !statusText) return;
        
        switch (status) {
            case 'connected':
                statusDot.style.background = '#4CAF50';
                statusDot.style.animation = 'none';
                statusText.textContent = 'Live';
                break;
            case 'connecting':
                statusDot.style.background = '#FFA500';
                statusDot.style.animation = 'pulse 1.5s infinite';
                statusText.textContent = 'Connecting...';
                break;
            case 'disconnected':
                statusDot.style.background = '#F44336';
                statusDot.style.animation = 'pulse 2s infinite';
                statusText.textContent = 'Offline';
                break;
            case 'error':
                statusDot.style.background = '#F44336';
                statusDot.style.animation = 'pulse 1s infinite';
                statusText.textContent = 'Error';
                break;
        }
    }
    
    updateLastUpdateTime() {
        const lastUpdateEl = document.getElementById(`${this.containerId}_last_update`);
        if (lastUpdateEl && this.lastUpdateTime) {
            const now = new Date();
            const diff = Math.floor((now - this.lastUpdateTime) / 1000);
            const timeStr = diff < 60 ? `${diff}s ago` : `${Math.floor(diff/60)}m ago`;
            lastUpdateEl.textContent = `Last: ${timeStr}`;
        }
    }
    
    updateVehiclePosition(vehicle) {
        if (!this.map || this.fallbackMode || !vehicle.latitude || !vehicle.longitude) return;
        
        const vehicleId = vehicle.id;
        const newPosition = [vehicle.latitude, vehicle.longitude];
        
        if (this.markers[vehicleId]) {
            // Animate existing vehicle to new position
            if (this.options.animateMovement) {
                this.animateVehicleMovement(vehicleId, newPosition, vehicle);
            } else {
                this.markers[vehicleId].setLatLng(newPosition);
                this.updateVehicleMarker(this.markers[vehicleId], vehicle);
            }
        } else {
            // Create new vehicle marker
            this.addSingleVehicleMarker(vehicle);
        }
    }
    
    updateVehiclesPositions(vehicles) {
        vehicles.forEach(vehicle => {
            this.updateVehiclePosition(vehicle);
        });
        
        // Update stats
        const activeVehicles = vehicles.filter(v => v.tracking_status === 'Active').length;
        this.updateStats({ vehicles: vehicles.length, routes: 3 });
    }
    
    animateVehicleMovement(vehicleId, newPosition, vehicle) {
        const marker = this.markers[vehicleId];
        if (!marker) return;
        
        const currentPos = marker.getLatLng();
        const startPos = [currentPos.lat, currentPos.lng];
        const endPos = newPosition;
        
        const steps = 20;
        let currentStep = 0;
        
        const animate = () => {
            if (currentStep <= steps) {
                const progress = currentStep / steps;
                const lat = startPos[0] + (endPos[0] - startPos[0]) * progress;
                const lng = startPos[1] + (endPos[1] - startPos[1]) * progress;
                
                marker.setLatLng([lat, lng]);
                
                if (currentStep === steps) {
                    this.updateVehicleMarker(marker, vehicle);
                }
                
                currentStep++;
                if (currentStep <= steps) {
                    this.animationFrameId = requestAnimationFrame(animate);
                }
            }
        };
        
        animate();
    }
    
    async loadMapData() {
        if (this.fallbackMode) {
            this.updateStats({ vehicles: 5, routes: 3 });
            this.lastUpdateTime = new Date();
            this.updateLastUpdateTime();
            return;
        }
        
        try {
            // Update connection status if not using WebSocket
            if (!this.websocket) {
                this.updateConnectionStatus('connecting');
            }
            
            // Load vehicles
            const response = await fetch('api/real-time-tracking.php?type=vehicles');
            const vehicles = await response.json();
            
            if (this.options.showVehicles && Array.isArray(vehicles)) {
                this.addVehicleMarkers(vehicles);
                this.updateStats({ vehicles: vehicles.length, routes: 3 });
                
                // Update connection status and time
                if (!this.websocket) {
                    this.updateConnectionStatus('connected');
                }
                this.lastUpdateTime = new Date();
                this.updateLastUpdateTime();
            }
            
        } catch (error) {
            console.error('Failed to load mini map data:', error);
            this.updateStats({ vehicles: 5, routes: 3 });
            if (!this.websocket) {
                this.updateConnectionStatus('error');
            }
        }
    }
    
    addVehicleMarkers(vehicles) {
        if (!this.map || this.fallbackMode) return;
        
        // Clear existing markers
        Object.values(this.markers).forEach(marker => {
            if (marker && this.map.hasLayer(marker)) {
                this.map.removeLayer(marker);
            }
        });
        this.markers = {};
        
        vehicles.forEach(vehicle => {
            this.addSingleVehicleMarker(vehicle);
        });
    }
    
    addSingleVehicleMarker(vehicle) {
        if (!this.map || this.fallbackMode || !vehicle.latitude || !vehicle.longitude) return;
        
        const status = vehicle.tracking_status || 'Active';
        const color = this.getVehicleColor(status);
        const vehicleId = vehicle.id;
        
        // Create enhanced marker with real-time styling
        const marker = L.circleMarker([vehicle.latitude, vehicle.longitude], {
            radius: status === 'Active' ? 5 : 4,
            fillColor: color,
            color: 'white',
            weight: status === 'Active' ? 2 : 1,
            opacity: 1,
            fillOpacity: status === 'Active' ? 0.9 : 0.7,
            className: `vehicle-marker vehicle-${status.toLowerCase()}`
        }).addTo(this.map);
        
        // Enhanced tooltip with more information
        const tooltipContent = this.createVehicleTooltip(vehicle);
        marker.bindTooltip(tooltipContent, {
            className: 'mini-map-tooltip enhanced-tooltip',
            direction: 'top',
            offset: [0, -10]
        });
        
        // Store marker with vehicle ID
        this.markers[vehicleId] = marker;
        
        // Add pulsing effect for active vehicles
        if (status === 'Active' && this.options.animateMovement) {
            this.addPulseEffect(marker);
        }
        
        return marker;
    }
    
    getVehicleColor(status) {
        const colors = {
            'Active': '#4CAF50',
            'Maintenance': '#FF9800', 
            'Offline': '#F44336',
            'Warning': '#FFC107',
            'Emergency': '#E91E63'
        };
        return colors[status] || '#9E9E9E';
    }
    
    createVehicleTooltip(vehicle) {
        const status = vehicle.tracking_status || 'Active';
        const lastUpdate = vehicle.last_update ? new Date(vehicle.last_update) : null;
        const updateText = lastUpdate ? this.formatTimeAgo(lastUpdate) : 'Unknown';
        
        return `
            <div style="font-size: 0.75rem; line-height: 1.3;">
                <div style="font-weight: bold; margin-bottom: 0.25rem;">${vehicle.name || vehicle.id}</div>
                <div style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.15rem;">
                    <span style="color: ${this.getVehicleColor(status)}; font-size: 0.8rem;">●</span>
                    <span>${status}</span>
                </div>
                ${vehicle.route_name ? `<div style="opacity: 0.8; font-size: 0.7rem;">📍 ${vehicle.route_name}</div>` : ''}
                ${vehicle.speed ? `<div style="opacity: 0.8; font-size: 0.7rem;">🚄 ${vehicle.speed} km/h</div>` : ''}
                <div style="opacity: 0.7; font-size: 0.65rem; margin-top: 0.15rem;">Updated: ${updateText}</div>
            </div>
        `;
    }
    
    updateVehicleMarker(marker, vehicle) {
        if (!marker) return;
        
        const status = vehicle.tracking_status || 'Active';
        const color = this.getVehicleColor(status);
        
        // Update marker style
        marker.setStyle({
            radius: status === 'Active' ? 5 : 4,
            fillColor: color,
            weight: status === 'Active' ? 2 : 1,
            fillOpacity: status === 'Active' ? 0.9 : 0.7
        });
        
        // Update tooltip
        const tooltipContent = this.createVehicleTooltip(vehicle);
        marker.setTooltipContent(tooltipContent);
    }
    
    addPulseEffect(marker) {
        // Add CSS animation for pulsing effect
        const markerElement = marker.getElement();
        if (markerElement) {
            markerElement.style.animation = 'marker-pulse 2s infinite';
        }
    }
    
    formatTimeAgo(date) {
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return `${diff}s ago`;
        if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
        return `${Math.floor(diff/86400)}d ago`;
    }
    
    updateStats(stats) {
        if (this.options.showStats) {
            const vehiclesEl = document.getElementById(`${this.containerId}_vehicles`);
            const routesEl = document.getElementById(`${this.containerId}_routes`);
            
            if (vehiclesEl) vehiclesEl.textContent = stats.vehicles || 0;
            if (routesEl) routesEl.textContent = stats.routes || 0;
        }
    }
    
    hideLoading() {
        const loading = document.getElementById(`${this.containerId}_loading`);
        if (loading) {
            loading.style.display = 'none';
        }
    }
    
    expandMap() {
        // Navigate to full map page
        window.open('service-network.php', '_blank');
    }
    
    startAutoUpdate() {
        this.updateTimer = setInterval(() => {
            this.loadMapData();
        }, this.options.updateInterval);
        
        // Also update the "last update" time display every 10 seconds
        this.timeUpdateTimer = setInterval(() => {
            this.updateLastUpdateTime();
        }, 10000);
        
        console.log(`Mini map auto-update started with ${this.options.updateInterval/1000}s interval`);
    }
    
    stopAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
        if (this.timeUpdateTimer) {
            clearInterval(this.timeUpdateTimer);
            this.timeUpdateTimer = null;
        }
    }
    
    destroy() {
        this.stopAutoUpdate();
        
        // Close WebSocket connection
        if (this.websocket) {
            this.websocket.close();
            this.websocket = null;
        }
        
        // Cancel any ongoing animations
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        
        // Clear markers
        Object.values(this.markers).forEach(marker => {
            if (marker && this.map && this.map.hasLayer(marker)) {
                this.map.removeLayer(marker);
            }
        });
        this.markers = {};
        
        // Remove map
        if (this.map && !this.fallbackMode) {
            this.map.remove();
        }
    }
}

// Easy initialization function
function createMiniMap(containerId, options = {}) {
    return new MiniMap(containerId, options);
}

// Global mini map instances
window.MiniMapInstances = window.MiniMapInstances || {};

// Initialize mini map with fallback
function initMiniMap(containerId, options = {}) {
    if (window.MiniMapInstances[containerId]) {
        window.MiniMapInstances[containerId].destroy();
    }
    
    window.MiniMapInstances[containerId] = new MiniMap(containerId, options);
    return window.MiniMapInstances[containerId];
}
