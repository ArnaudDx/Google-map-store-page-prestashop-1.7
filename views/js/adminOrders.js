window.addEventListener("load", (event) => {
    /**
     Ce fichier est charge sur tout AdminOrders, liste comprise, et le template
     n'affiche rien quand le geocodage echoue : le conteneur est souvent absent.
     */
    const mapContainer = document.getElementById("ggmap");
    if (!mapContainer) {
        return;
    }

    storeGgMmapSettings.defaultLatitude = parseFloat(mapContainer.dataset.lat);
    storeGgMmapSettings.defaultLongitude = parseFloat(mapContainer.dataset.lng);
    storeGgMmapSettings.defaultZoom = 10;

    if(!isNaN(storeGgMmapSettings.defaultLatitude) && !isNaN(storeGgMmapSettings.defaultLongitude)) {
        const storeggmap = new StoreGgMap('ggmap', storeGgMmapSettings);
        storeggmap.initBo();
    }
});

