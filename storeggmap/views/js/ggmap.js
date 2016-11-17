var map;

function initMap() {
    map = new google.maps.Map(document.getElementById('map-style'), {
        center: {lat: parseFloat(defaultLat), lng: parseFloat(defaultLong)},
        disableDefaultUI:true,
        fullscreenControl:true,
        streetViewControl:true,
        zoom: 5
    });
    
    // Transforme String en Array
    var storeList = (new Function("return [" + storeArrayContent+ "];")());
    
    for (store of storeList) {
        createMarker(map, store.name, store.latitude, store.longitude);
    }
}

function createMarker(theMap, name, Lat, Long) {
    var marker = new google.maps.Marker({
        position: {lat: Lat, lng: Long},
        title:"name: "+name
    });
    marker.setMap(theMap);
}

$(document).ready(function(){
    initMap();
});
