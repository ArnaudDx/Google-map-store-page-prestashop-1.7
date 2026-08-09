window.addEventListener("load", async (event) => {
    /**
     Ce fichier est charge sur tout AdminOrders, liste comprise, et le template
     n'affiche rien quand l'adresse de la commande est vide : le conteneur est souvent absent.
     */
    const mapContainer = document.getElementById("ggmap");
    if (!mapContainer) {
        return;
    }

    const address = mapContainer.dataset.address;
    if (!address) {
        return;
    }

    /**
     Le geocodage se fait ici, dans le navigateur, et non cote PHP : une cle Maps
     restreinte par referent HTTP est refusee par le web service Geocoding
     (REQUEST_DENIED), qui recoit une requete serveur donc sans referent.
     Le service JS, lui, porte le referent de la page et passe avec la meme cle.
     */
    let location = null;
    try {
        const response = await new google.maps.Geocoder().geocode({address: address});
        if (response.results.length) {
            location = response.results[0].geometry.location;
        }
    } catch (error) {
        console.error("storeggmap - geocodage de l'adresse de la commande impossible :", error);
    }

    if (!location) {
        return;
    }

    storeGgMmapSettings.defaultLatitude = location.lat();
    storeGgMmapSettings.defaultLongitude = location.lng();
    storeGgMmapSettings.defaultZoom = 10;

    const storeggmap = new StoreGgMap('ggmap', storeGgMmapSettings);
    storeggmap.initBo();
});