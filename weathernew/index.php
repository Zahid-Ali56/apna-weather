<?php
// Error blocking for clean production display
error_reporting(0);

// Default tracking node (Clean and stable default)
$city = "Karachi,PK";

if (isset($_GET['city']) && !empty($_GET['city'])) {
    $city = urlencode(trim($_GET['city']));
}

// --- VISUAL CROSSING WEATHER ENGINE CONFIGURATION ---
// ⚠️ YAHAN APNI ASLI API KEY INPUT KAREIN
$api_key = "NSN7RMKXLEJBGVZ36FC4B6Y3A"; 

// URL string ko bilkul clean aur fix kar diya hai
$api_url = "https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/" . $city . "?unitGroup=metric&key=" . $api_key . "&contentType=json&iconSet=icons2";

// Fetching Data via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if ($data && isset($data['currentConditions'])) {
    // Current Node Variables
    $resolved_address = $data['resolvedAddress'];
    
    // --- 1. DADU / INTERIOR EXTRA SPIKE CORRECTION ---
    $raw_temp = $data['currentConditions']['temp'];
    if ($raw_temp >= 40) {
        $temp = round($raw_temp - 1.5); 
    } else {
        $temp = round($raw_temp);
    }

    $humidity = round($data['currentConditions']['humidity']);
    $wind_speed = round($data['currentConditions']['windspeed']);
    $uv_index = $data['currentConditions']['uvindex'];
    $solar_rad = round($data['currentConditions']['solarradiation']);
    $conditions = $data['currentConditions']['conditions'];
    $icon = $data['currentConditions']['icon'];
    
    // Geographical Mapping Nodes
    $lat = $data['latitude'];
    $lon = $data['longitude'];
    
    // 7-Days Forecast Data Extract
    $forecast_days = array_slice($data['days'], 1, 7);

    // =========================================================================
    // 🔥 PAKISTAN REGIONAL STABILIZED REALFEEL ENGINE
    // =========================================================================
    $feels_like = $temp; 

    if ($temp >= 26) {
        $T = ($temp * 9/5) + 32; 
        $RH = $humidity;

        $hi_f = -42.379 + 2.04901523 * $T + 10.14333127 * $RH 
                - 0.22475541 * $T * $RH - 0.00683783 * $T * $T 
                - 0.05481717 * $RH * $RH + 0.00122874 * $T * $T * $RH 
                + 0.00085282 * $T * $RH * $RH - 0.00000199 * $T * $T * $RH * $RH;

        $calc_feels = ($hi_f - 32) * 5/9;

        if ($calc_feels > ($temp + 6)) {
            $overshoot = $calc_feels - ($temp + 4);
            $calc_feels = ($temp + 4) + ($overshoot * 0.25); 
        }

        if ($temp >= 33 && $temp <= 36 && $humidity >= 55) {
            $calc_feels = $temp + 3.5; 
        }

        $feels_like = round($calc_feels);

        if ($feels_like < $temp) {
            $feels_like = $temp + 1;
        }
    } else {
        if ($temp <= 12 && $wind_speed > 5) {
            $feels_like = round(13.12 + 0.6215 * $temp - 11.37 * pow($wind_speed, 0.16) + 0.3965 * $temp * pow($wind_speed, 0.16));
        }
    }
    // =========================================================================

    // DYNAMIC FORECAST VECTOR MAPPER FUNCTION
    function getWeatherIcon($iconName) {
        switch ($iconName) {
            case 'clear-day':
                return '<i class="fas fa-sun color-feel" title="Sunny / Clear"></i>';
            case 'clear-night':
                return '<i class="fas fa-moon" style="color: #f1f5f9;" title="Clear Night"></i>';
            case 'partly-cloudy-day':
                return '<i class="fas fa-cloud-sun" style="color: #cbd5e1;" title="Partly Cloudy"></i>';
            case 'partly-cloudy-night':
                return '<i class="fas fa-cloud-moon" style="color: #94a3b8;" title="Partly Cloudy Night"></i>';
            case 'cloudy':
                return '<i class="fas fa-cloud" style="color: #64748b;" title="Overcast / Cloudy"></i>';
            case 'rain':
            case 'showers-day':
            case 'showers-night':
                return '<i class="fas fa-cloud-sun-rain" style="color: #60a5fa;" title="Light Rain / Showers"></i>';
            case 'heavy-rain':
                return '<i class="fas fa-cloud-showers-heavy" style="color: #2563eb;" title="Heavy Rain"></i>';
            case 'thunder-rain':
            case 'thunder-showers':
                return '<i class="fas fa-cloud-bolt" style="color: #eab308;" title="Thunderstorms with Rain"></i>';
            case 'thunder':
                return '<i class="fas fa-bolt-lightning" style="color: #f59e0b;" title="Lightning Strands"></i>';
            case 'wind':
                return '<i class="fas fa-wind color-wind" title="Strong Windy Waves"></i>';
            case 'fog':
                return '<i class="fas fa-smog" style="color: #94a3b8;" title="Dense Fog"></i>';
            default:
                return '<i class="fas fa-cloud" style="color: #38bdf8;"></i>';
        }
    }

} else {
    if(isset($data['message'])) {
        $error = "API Error: " . $data['message'];
    } else {
        $error = "Aapki API Key invalid hai ya sheher ka naam galat hai. Please check karein!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apna-Weather Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard-wrapper">
    <header class="search-container">
        <form action="" method="GET" class="search-form">
            <div class="input-group">
                <i class="fas fa-search-location"></i>
                <input type="text" name="city" placeholder="Search Pakistani or International cities..." required>
            </div>
            <button type="submit" class="btn-action btn-search">Search</button>
            <button type="button" onclick="triggerGeoLocation()" class="btn-action btn-geo"><i class="fas fa-crosshairs"></i> Auto</button>
        </form>
    </header>

    <?php if (isset($error)): ?>
        <div class="error-toast" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($resolved_address)): ?>
    <main class="main-dashboard">
        <section class="hero-weather-card">
            <div class="meta-location">
                <h2><?php echo explode(',', $resolved_address)[0]; ?></h2>
                <p><i class="fas fa-globe-asia"></i> <?php echo $resolved_address; ?></p>
                <span class="badge-condition"><?php echo $conditions; ?></span>
            </div>
            <div class="temperature-display">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="main-weather-icon-view" style="font-size: 64px;">
                        <?php echo getWeatherIcon($icon); ?>
                    </div>
                    <h1><?php echo $temp; ?><sup>°C</sup></h1>
                </div>
            </div>
        </section>

        <section class="metrics-grid">
            <div class="card">
                <i class="fas fa-temperature-high color-feel"></i>
                <span class="label">Real Feel</span>
                <h3><?php echo $feels_like; ?>°C</h3>
            </div>
            <div class="card">
                <i class="fas fa-tint color-humid"></i>
                <span class="label">Humidity</span>
                <h3><?php echo $humidity; ?>%</h3>
            </div>
            <div class="card">
                <i class="fas fa-wind color-wind"></i>
                <span class="label">Wind Speed</span>
                <h3><?php echo $wind_speed; ?> km/h</h3>
            </div>
            <div class="card">
                <i class="fas fa-sun color-uv"></i>
                <span class="label">UV Index</span>
                <h3><?php echo $uv_index; ?> <small>/ 10</small></h3>
            </div>
            <div class="card">
                <i class="fas fa-solar-panel color-solar"></i>
                <span class="label">Solar Rad</span>
                <h3><?php echo $solar_rad; ?> <small>W/m²</small></h3>
            </div>
        </section>

        <section class="forecast-section">
            <h3><i class="far fa-calendar-alt"></i> 7-Day Extended Forecast</h3>
            <div class="forecast-container">
                <?php foreach($forecast_days as $day): 
                    $day_name = date('D', strtotime($day['datetime']));
                    $max_t = round($day['tempmax']);
                    $min_t = round($day['tempmin']);
                    $day_icon = $day['icon']; 
                ?>
                <div class="forecast-row">
                    <span class="day-title"><?php echo $day_name; ?></span>
                    <div class="forecast-icon-wrapper" style="font-size: 18px; width: 30px; text-align: center;">
                        <?php echo getWeatherIcon($day_icon); ?>
                    </div>
                    <span class="forecast-condition-text" style="flex: 1; margin-left: 15px;"><?php echo $day['conditions']; ?></span>
                    <div class="range-bar-temp">
                        <span class="t-max"><?php echo $max_t; ?>°</span>
                        <span class="t-min"><?php echo $min_t; ?>°</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="map-section">
            <div id="live-viewport-map"></div>
        </section>
    </main>
    <?php endif; ?>

    <!-- PREMIUM PROFESSIONAL ATTRIBUTION BRANDING FOOTER -->
    <footer class="app-branding-footer" style="text-align: center; margin-top: 15px; padding: 15px; background: rgba(22, 28, 45, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.04); border-radius: 16px;">
        <p style="font-size: 13px; color: #9ca3af; letter-spacing: 0.8px; font-weight: 500;">
            <i class="fas fa-code" style="color: #00b4db; margin-right: 5px;"></i> Apna-Weather &bull; Developed by <span style="color: #fff; font-weight: 600; text-shadow: 0 0 10px rgba(0,180,219,0.3);"><?php echo "Zahid Ali"; ?></span>
        </p>
    </footer>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    <?php if (isset($lat)): ?>
    var map = L.map('live-viewport-map', { zoomControl: false }).setView([<?php echo $lat; ?>, <?php echo $lon; ?>], 11);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.marker([<?php echo $lat; ?>, <?php echo $lon; ?>]).addTo(map)
        .bindPopup('<b>Tracking Station Active</b>').openPopup();
    <?php endif; ?>

    function triggerGeoLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                var coordinate_query = pos.coords.latitude + "," + pos.coords.longitude;
                window.location.href = "?city=" + encodeURIComponent(coordinate_query);
            }, function(err) {
                alert("Location permission blocked. Please enter city name manually.");
            });
        } else {
            alert("Browser functionality constraint detected for tracking.");
        }
    }
</script>
</body>
</html>