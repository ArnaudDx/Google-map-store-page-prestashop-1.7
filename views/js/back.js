window.addEventListener("load", (event) => {
    /**
        I have to do this because hookActionAdminControllerSetMedia called before postProcess
    */
    storeGgMmapSettings.defaultLatitude = parseFloat(document.getElementById("ggmap_lat").value);
    storeGgMmapSettings.defaultLongitude = parseFloat(document.getElementById("ggmap_long").value);
    storeGgMmapSettings.defaultZoom = Number(document.getElementById('ggmap_zoom_selector').value);
    storeGgMmapSettings.urlIcon = null;
    
    if(document.getElementById("ggmap_icon_value"))
    {
        storeGgMmapSettings.urlIcon = document.getElementById("ggmap_icon_value").getAttribute('src');
    }
    
    const storeGgMap = new StoreGgMap('ggmap', storeGgMmapSettings);
    storeGgMap.initBo();
});

