<h4 class="mb-3"><i class="fa-solid fa-map-location-dot me-2"></i>Map View</h4>
<div class="card">
    <div class="card-body p-0">
        <div id="mapView" style="height:70vh; width:100%; border-radius:.5rem;"></div>
    </div>
</div>

<?php
$extraScripts = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var map = L.map("mapView").setView([2.0469, 45.3182], 12);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "&copy; OpenStreetMap contributors" }).addTo(map);
    fetch("' . url('/map/data') . '?module=' . e($module) . '")
        .then(function (r) { return r.json(); })
        .then(function (points) {
            if (!points.length) return;
            var group = [];
            points.forEach(function (p) {
                var marker = L.circleMarker([p.lat, p.lng], { radius: 8, color: p.color, fillColor: p.color, fillOpacity: 0.8 }).addTo(map);
                marker.bindPopup("<strong>" + p.name + "</strong><br><a href=\"" + p.url + "\">View details</a>");
                group.push(marker);
            });
            var featureGroup = L.featureGroup(group);
            map.fitBounds(featureGroup.getBounds().pad(0.2));
        });
});
</script>';
?>
