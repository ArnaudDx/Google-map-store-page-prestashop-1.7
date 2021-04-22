var map;
var infowindow = null;

$.getScript(ggapi_url, initMap);

const ratio_radius_zoom = {
    15: 10,
    25: 9,
    50: 8,
    100: 7
}

function initMap() {

    map = new google.maps.Map(document.getElementById('storemap'), {
        center: {lat: parseFloat(defaultLat), lng: parseFloat(defaultLong)},
        disableDefaultUI: true,
        fullscreenControl: true,
        streetViewControl: true,
        zoom: Number(defaultZoom),
        styles: customized_map
    });

    $.ajax({
        method: 'POST',
        url: storeGGmapCall,
        data: {
            action: 'getAllStores',
            id_lang: id_lang,
        },
        dataType: 'json',
        success: function (json) {
            var stores = json.storeList;
            stores.forEach(function (store) {
                createMarker(map, store);
            });
        }
    });

    map.addListener('click', function (e) {
        map.setCenter(e.latLng);
    });

    if (enable_search) {
        const location_input = document.getElementById("location_input");
        const location_options = {
            fields: ["geometry"],
            origin: map.getCenter()
        };
        const location_autocomplete = new google.maps.places.Autocomplete(
            location_input,
            location_options
        );
        location_autocomplete.bindTo("bounds", map);

        location_autocomplete.addListener("place_changed", () => {
            const place = location_autocomplete.getPlace();

            if (!place.geometry || !place.geometry.location) {
                window.alert(no_data_address_message + " '" + place.name + "'");
                return;
            }

            const location_radius = Number(document.getElementById("radius_input").value);
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
            }

            let zoom_level = ratio_radius_zoom[location_radius];
            if (zoom_level === undefined) {
                zoom_level = 5;
            }
            map.setZoom(zoom_level);

            filterStoreList(location_radius, place.geometry.location);
        });
    }
}

function createMarker(theMap, theStore) {
    let marker;
    if (urlIcon) {
        marker = new google.maps.Marker({
            position: {lat: theStore.latitude, lng: theStore.longitude},
            icon: urlIcon,
            title: theStore.name,
        });
    } else {
        marker = new google.maps.Marker({
            position: {lat: theStore.latitude, lng: theStore.longitude},
            title: theStore.name,
        });
    }

    marker.addListener('click', function () {
        map.setCenter(marker.getPosition());
        if (infowindow) {
            infowindow.close();
        }
        infowindow = new google.maps.InfoWindow({
            content: infosHtml(theStore),
            maxWidth: 350,
        });
        infowindow.open(theMap, marker);
    });

    marker.setMap(theMap);
}

function infosHtml(store) {
    let htmlResponse = '';
     $.ajax({
        method: 'POST',
        async: false,
        url: storeGGmapCall,
        data: {
            action: 'getStoreDetail',
            id_store: store.id_store
        },
        success: function (detail) {
            htmlResponse = detail;
        }
    });
     return JSON.parse(htmlResponse);
}

function filterStoreList(radius, location) {
    $.ajax({
        method: 'POST',
        url: storeGGmapCall,
        data: {
            action: 'getStoreListToHideRadius',
            radius: radius,
            lat: location.lat(),
            lng: location.lng()
        },
        dataType: 'json',
        success: function (json) {
            console.log(json);
            let stores = json.storeToHideList;
            $('.store-item').fadeIn();
            stores.forEach(function (id_store) {
                $('#store-' + id_store).fadeOut();
            });
        }
    });
}