<?php
/**
 * MODULE 9 — A4 — Carte Interactive des Agences
 * FrontOffice interactive map of agencies with Leaflet.js
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

SessionGuard::requireLogin();

$db = config::getConnexion();

// Get all agencies with coordinates
$stmt = $db->query("
    SELECT 
        a.id_agence,
        a.nom_agence,
        a.adresse,
        a.ville,
        a.telephone,
        a.email,
        a.latitude,
        a.longitude,
        (SELECT AVG(note) FROM agence_avis WHERE id_agence = a.id_agence) as note_moyenne
    FROM agence a
    WHERE a.latitude IS NOT NULL AND a.longitude IS NOT NULL
    ORDER BY a.nom_agence
");
$agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to GeoJSON for Leaflet
$geojson = [
    'type' => 'FeatureCollection',
    'features' => []
];

foreach ($agencies as $agence) {
    $geojson['features'][] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float)$agence['longitude'], (float)$agence['latitude']]
        ],
        'properties' => [
            'id' => $agence['id_agence'],
            'nom' => $agence['nom_agence'],
            'adresse' => $agence['adresse'],
            'ville' => $agence['ville'],
            'telephone' => $agence['telephone'],
            'email' => $agence['email'],
            'note' => $agence['note_moyenne']
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Agences — Carte Interactive</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map {
            height: 70vh;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .sidebar {
            height: 70vh;
            overflow-y: auto;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        .agency-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .agency-card:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        }
        .agency-card.active {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
        }
        .agency-card h6 {
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }
        .agency-card small {
            display: block;
            color: #666;
            margin-bottom: 4px;
        }
        .leaflet-popup-content {
            font-size: 13px;
        }
        .closest-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <h1 class="mb-4">📍 Nos Agences — Trouvez la Plus Proche</h1>

        <button class="closest-btn" id="findClosest" onclick="findClosestAgency()">
            📍 Trouver l'agence la plus proche
        </button>

        <div class="row" style="height: 70vh;">
            <div class="col-md-8">
                <div id="map"></div>
            </div>
            <div class="col-md-4">
                <div class="sidebar">
                    <h5>Agences</h5>
                    <div id="agenciesList"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize map
        const map = L.map('map').setView([35.8, 10.2], 7); // Tunisia center

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // GeoJSON data
        const geoData = <?php echo json_encode($geojson); ?>;
        const markers = {};

        // Add markers to map
        const featureGroup = L.featureGroup();

        geoData.features.forEach(feature => {
            const { properties, geometry } = feature;
            const [lng, lat] = geometry.coordinates;

            const marker = L.marker([lat, lng]).addTo(map);
            
            const popupContent = `
                <div>
                    <h6 style="margin-bottom: 8px;">${properties.nom}</h6>
                    <small>📍 ${properties.adresse}, ${properties.ville}</small>
                    <small>📞 ${properties.telephone}</small>
                    <small>📧 ${properties.email}</small>
                    ${properties.note ? `<small>⭐ ${parseFloat(properties.note).toFixed(1)}</small>` : ''}
                    <div style="margin-top: 12px;">
                        <button class="btn btn-sm btn-primary" onclick="bookAppointment(${properties.id})">
                            📅 Prendre RDV
                        </button>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);
            markers[properties.id] = { marker, lat, lng, properties };
            featureGroup.addLayer(marker);

            // Add to sidebar
            const card = document.createElement('div');
            card.className = 'agency-card';
            card.id = `agency-${properties.id}`;
            card.onclick = () => {
                marker.openPopup();
                selectAgency(properties.id);
            };
            card.innerHTML = `
                <h6>${properties.nom}</h6>
                <small>📍 ${properties.ville}</small>
                <small>📞 ${properties.telephone}</small>
                ${properties.note ? `<small>⭐ ${parseFloat(properties.note).toFixed(1)}</small>` : ''}
            `;
            document.getElementById('agenciesList').appendChild(card);
        });

        map.fitBounds(featureGroup.getBounds(), { padding: [50, 50] });

        function selectAgency(id) {
            document.querySelectorAll('.agency-card').forEach(c => c.classList.remove('active'));
            document.getElementById(`agency-${id}`).classList.add('active');
            const { lat, lng } = markers[id];
            map.setView([lat, lng], 15);
        }

        function bookAppointment(id) {
            window.location.href = `/view/FrontOffice/prendre_rdv.php?agence=${id}`;
        }

        function findClosestAgency() {
            if (!navigator.geolocation) {
                alert('Géolocalisation non disponible');
                return;
            }

            navigator.geolocation.getCurrentPosition(position => {
                const { latitude, longitude } = position.coords;

                // Haversine formula to find closest agency
                let closest = null;
                let minDistance = Infinity;

                Object.values(markers).forEach(({ lat, lng, properties }) => {
                    const R = 6371; // Earth radius in km
                    const dLat = (lat - latitude) * Math.PI / 180;
                    const dLng = (lng - longitude) * Math.PI / 180;
                    const a = Math.sin(dLat/2)**2 + Math.cos(latitude*Math.PI/180)**2 * Math.sin(dLng/2)**2;
                    const c = 2 * Math.asin(Math.sqrt(a));
                    const distance = R * c;

                    if (distance < minDistance) {
                        minDistance = distance;
                        closest = properties.id;
                    }
                });

                if (closest) {
                    selectAgency(closest);
                    alert(`Agence la plus proche: ${markers[closest].properties.nom} (${minDistance.toFixed(1)} km)`);
                }
            }, error => {
                alert('Impossible de récupérer votre localisation');
            });
        }
    </script>
</body>
</html>
