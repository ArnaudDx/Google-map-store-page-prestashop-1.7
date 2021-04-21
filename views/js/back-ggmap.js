var map;
var markers = [];

function initMap() {

    map = new google.maps.Map(document.getElementById('ggmap'), {
        center: {lat: parseFloat(defaultLat), lng: parseFloat(defaultLong)},
        disableDefaultUI: true,
        fullscreenControl: true,
        streetViewControl: true,
        zoom: Number(defaultZoom) || 5,
        styles: customized_map
    });

    addMarker({lat: parseFloat(defaultLat), lng: parseFloat(defaultLong)});

    map.addListener('click', function (event) {
        deleteMarkers();
        $('#ggmap_lat').val(event.latLng.lat());
        $('#ggmap_long').val(event.latLng.lng());
        addMarker(event.latLng);
    });

}

// Adds a marker to the map and push to the array.
function addMarker(location) {
    var marker = new google.maps.Marker({
        position: location,
        icon: urlIcon,
        map: map
    });
    markers.push(marker);
}

// Sets the map on all markers in the array.
function setMapOnAll(map) {
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(map);
    }
}

// Deletes all markers in the array by removing references to them.
function deleteMarkers() {
    setMapOnAll(null);
    markers = [];
}

$(document).ready(function () {
    if ($('#ggmap_apikey').val()) {
        if ($('#ggmap_lat').val() && $('#ggmap_long').val()) {
            defaultLat = $('#ggmap_lat').val();
            defaultLong = $('#ggmap_long').val();
        }
        initMap();
    } else {
        $('#ggmap').hide();
    }
});
