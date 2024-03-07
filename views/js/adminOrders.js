window.addEventListener("load", (event) => {
    
    storeGgMmapSettings.defaultLatitude = parseFloat(document.getElementById("ggmap").dataset.lat);
    storeGgMmapSettings.defaultLongitude = parseFloat(document.getElementById("ggmap").dataset.lng);
    storeGgMmapSettings.defaultZoom = 10;
    
    if(!isNaN(storeGgMmapSettings.defaultLatitude) && !isNaN(storeGgMmapSettings.defaultLongitude)) {
        const storeggmap = new StoreGgMap('ggmap', storeGgMmapSettings);
        storeggmap.initBo();
    }
});

