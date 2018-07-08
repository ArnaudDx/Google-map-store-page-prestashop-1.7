var map;
var infowindow = null;

$.getScript('https://maps.googleapis.com/maps/api/js?key=' + ggApiKey, initMap);

function initMap() {
    
    map = new google.maps.Map(document.getElementById('storemap'), {
        center: {lat: parseFloat(defaultLat), lng: parseFloat(defaultLong)},
        disableDefaultUI:true,
        fullscreenControl:true,
        streetViewControl:true,
        zoom: 5,
		styles:customized_map
    });
    
    $.ajax({
        method: 'POST',
        url: storeGGmapCall,
        data: { 
            allStores: 1,
            id_lang: id_lang,
        },
        dataType: 'json',
        success: function(json) {
            var stores = json.storeList;
            stores.forEach(function(store){
                createMarker(map, store);
            });
        }
    });
    
    map.addListener('click', function(e){
        map.setCenter(e.latLng);
    });
    
}

function createMarker(theMap, theStore) {
    
    if (urlIcon) {
        var marker = new google.maps.Marker({
            position: {lat: theStore.latitude, lng: theStore.longitude},
            icon : urlIcon,
            title: theStore.name,
        });
    } else {
        var marker = new google.maps.Marker({
            position: {lat: theStore.latitude, lng: theStore.longitude},
            title: theStore.name,
        });
    }
    
    marker.addListener('click', function() {
        map.setZoom(8);
        map.setCenter(marker.getPosition());
        if (infowindow) {
            infowindow.close();
        }
        infowindow = new google.maps.InfoWindow({
            content: infosHtml(theStore),
            maxWidth : 350,
        });
        infowindow.open(theMap, marker);
    });
    
    marker.setMap(theMap);
}

function infosHtml(store){
    var storeHtml = '<div id="store_infos">';
    storeHtml += '<p><b>' + store.name + '</b></p>';
    storeHtml += '<p>' + store.address1 + (store.address2 ? '<br />' + store.address2 : '') + '<br/>' + store.city + ', ' + (store.postcode ? store.postcode : '');
    storeHtml += '<br/>' + store.country + (store.state ? ', ' + store.state : '') + '</p>';
    if ( store.phone || store.fax) {
        storeHtml += '<p> Phone : ' + (store.phone ? store.phone : ' -') + '<br />Fax : ' + (store.fax ? store.fax : ' -') + '</p><hr/>';
    }
    if (store.note) {
        storeHtml += '<p> Note : ' + store.note + '</p><hr/>';
    }
    if (store.hours) {
        storeHtml += '<ul>';
        storeHtml += '<li>Our Hours :</li>';
        var hoursList = store.hours;
        hoursList.forEach(function(hours){
            storeHtml += '<li>' + hours + '</li>';
        });
        storeHtml += '</ul>';
    }
    storeHtml += '</div>';
    return storeHtml;
}
