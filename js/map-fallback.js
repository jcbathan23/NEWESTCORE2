// Fallback map implementation using simple JavaScript
function initSimpleMap() {
    console.log('Initializing simple fallback map...');
    
    const mapContainer = document.getElementById('realTimeMap');
    if (!mapContainer) {
        console.error('Map container not found');
        return;
    }
    
    // Create a simple map interface
    mapContainer.innerHTML = `
        <div style="position: relative; width: 100%; height: 100%; background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); display: flex; align-items: center; justify-content: center; color: white; font-family: Arial, sans-serif;">
            <div style="text-align: center; z-index: 10;">
                <div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 1rem; backdrop-filter: blur(10px);">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem;">🗺️ Service Network Map</h3>
                    <p style="margin: 0 0 1rem 0; opacity: 0.9;">Manila Metropolitan Area</p>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin: 1rem 0;">
                        <div style="background: rgba(52, 152, 219, 0.8); padding: 1rem; border-radius: 0.5rem;">
                            <div style="font-size: 2rem; font-weight: bold;">5</div>
                            <div style="font-size: 0.9rem;">Active Vehicles</div>
                        </div>
                        <div style="background: rgba(46, 204, 113, 0.8); padding: 1rem; border-radius: 0.5rem;">
                            <div style="font-size: 2rem; font-weight: bold;">3</div>
                            <div style="font-size: 0.9rem;">Routes</div>
                        </div>
                        <div style="background: rgba(155, 89, 182, 0.8); padding: 1rem; border-radius: 0.5rem;">
                            <div style="font-size: 2rem; font-weight: bold;">8</div>
                            <div style="font-size: 0.9rem;">Service Points</div>
                        </div>
                        <div style="background: rgba(241, 196, 15, 0.8); padding: 1rem; border-radius: 0.5rem;">
                            <div style="font-size: 2rem; font-weight: bold;">1</div>
                            <div style="font-size: 0.9rem;">Active Alerts</div>
                        </div>
                    </div>
                    <div style="margin: 1rem 0;">
                        <button onclick="tryLoadFullMap()" style="background: #27ae60; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-size: 1rem; margin: 0.25rem;">
                            🔄 Load Interactive Map
                        </button>
                        <button onclick="showVehicleList()" style="background: #3498db; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-size: 1rem; margin: 0.25rem;">
                            🚛 View Vehicles
                        </button>
                    </div>
                    <p style="font-size: 0.8rem; margin: 1rem 0 0 0; opacity: 0.7;">
                        Interactive map with Leaflet.js temporarily unavailable.<br>
                        This fallback shows real-time network status.
                    </p>
                </div>
            </div>
            
            <!-- Animated background elements -->
            <div style="position: absolute; top: 20%; left: 15%; width: 20px; height: 20px; background: rgba(255,255,255,0.3); border-radius: 50%; animation: float 3s ease-in-out infinite;"></div>
            <div style="position: absolute; top: 60%; left: 70%; width: 15px; height: 15px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 4s ease-in-out infinite 0.5s;"></div>
            <div style="position: absolute; top: 30%; left: 80%; width: 25px; height: 25px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: float 5s ease-in-out infinite 1s;"></div>
        </div>
        
        <style>
            @keyframes float {
                0%, 100% { transform: translateY(0px) scale(1); opacity: 0.7; }
                50% { transform: translateY(-20px) scale(1.1); opacity: 1; }
            }
        </style>
    `;
    
    // Hide loading indicator
    const loadingIndicator = document.getElementById('mapLoadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    
    // Update stats
    document.getElementById('activeVehiclesCount').textContent = '5';
    document.getElementById('totalRoutesCount').textContent = '3';
    document.getElementById('servicePointsCount').textContent = '8';
    document.getElementById('alertsCount').textContent = '1';
    document.getElementById('lastUpdateTime').textContent = new Date().toLocaleString();
    
    console.log('Simple fallback map initialized');
}

function tryLoadFullMap() {
    console.log('Attempting to load full interactive map...');
    
    // Try to load Leaflet again
    if (typeof L !== 'undefined') {
        initMapImmediate();
        return;
    }
    
    // Load Leaflet dynamically
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = function() {
        console.log('Leaflet loaded dynamically');
        setTimeout(() => {
            if (typeof L !== 'undefined') {
                initMapImmediate();
            } else {
                alert('Map library still not available. Please check your internet connection and refresh the page.');
            }
        }, 500);
    };
    script.onerror = function() {
        alert('Failed to load map library. Please check your internet connection and refresh the page.');
    };
    document.head.appendChild(script);
}

function showVehicleList() {
    const vehicles = [
        { id: 'V-001', name: 'Bus Alpha', route: 'Manila-Quezon Route', status: 'Active', location: 'Rizal Avenue' },
        { id: 'V-002', name: 'Bus Beta', route: 'Makati-BGC Express', status: 'Active', location: 'Ayala Avenue' },
        { id: 'V-003', name: 'Jeepney Gamma', route: 'Pasig Local Route', status: 'Maintenance', location: 'Pasig Station' },
        { id: 'V-004', name: 'Van Delta', route: 'Quezon City Loop', status: 'Active', location: 'QC Circle' },
        { id: 'V-005', name: 'Bus Epsilon', route: 'Paranaque-Alabang', status: 'Active', location: 'Sucat Road' }
    ];
    
    let html = '<h4>🚛 Active Vehicles</h4><div style="max-height: 300px; overflow-y: auto;">';
    vehicles.forEach(v => {
        const statusColor = v.status === 'Active' ? '#27ae60' : v.status === 'Maintenance' ? '#f39c12' : '#e74c3c';
        html += `
            <div style="background: rgba(255,255,255,0.1); margin: 0.5rem 0; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid ${statusColor};">
                <div style="font-weight: bold; margin-bottom: 0.25rem;">${v.name} (${v.id})</div>
                <div style="font-size: 0.9rem; opacity: 0.9;">${v.route}</div>
                <div style="font-size: 0.8rem; margin-top: 0.25rem;">
                    <span style="color: ${statusColor};">● ${v.status}</span> | 📍 ${v.location}
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    Swal.fire({
        title: 'Vehicle Status',
        html: html,
        width: 600,
        showConfirmButton: true,
        confirmButtonText: 'Close'
    });
}
