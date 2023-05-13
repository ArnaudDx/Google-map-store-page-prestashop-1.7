class StoreGgMap {
    constructor(domElement, settings) {
        this.isFo = false;
        this.divMap = document.getElementById(domElement)
        this.urlIcon = settings.urlIcon
        this.urlFrontController = settings.urlFrontController
        this.defaultLatitude = settings.defaultLatitude
        this.defaultLongitude = settings.defaultLongitude
        this.defaultZoom = settings.defaultZoom
        this.designCustomization = settings.designCustomization
        this.searchEnable = settings.searchEnable
        this.idLang = settings.id_lang
        this.infowindow = null
        this.ratioRadiusZoom = {
            15: 10,
            25: 9,
            50: 8,
            100: 7
        }
        
        this.defaultLocation = {
            lat: this.defaultLatitude,
            lng: this.defaultLongitude
        }
        
        this.mapConfiguration = {
            center: this.defaultLocation,
            disableDoubleClickZoom: true,
            disableDefaultUI: true,
            fullscreenControl: true,
            streetViewControl: true,
            zoom: this.defaultZoom,
            styles: this.designCustomization
        }
        
        this.map = new google.maps.Map(this.divMap, this.mapConfiguration);
        this.markers = [];
    }

    initBo() {
        this.addMarker({
            latitude: this.defaultLatitude,
            longitude: this.defaultLongitude
        });
        
        this.map.addListener('dblclick', (event) => {
            this.hideMarkers();
            document.getElementById("ggmap_lat").value = event.latLng.lat();
            document.getElementById("ggmap_long").value = event.latLng.lng();
            this.addMarker({
                latitude: event.latLng.lat(),
                longitude: event.latLng.lng()
            });
            return false;
        });
    }

    async initFo() {
        this.isFo = true;
        const stores = await this.loadStores();

        if(stores.data) {
            stores.data.forEach(store => {
                this.addMarker(store);
            });
        }
        
        if(this.searchEnable) {
            this.initSearch();
        }
    }
    
    addMarker(store) {
        const markerConfig = {
            position: {
                lat:store.latitude,
                lng:store.longitude,
            },
            map: this.map
        }
        
        if(store.title)
        {
            markerConfig['title'] = store.title;
        }
        
        if(this.urlIcon) {
            markerConfig['icon'] = this.urlIcon;
        }
        
        const marker = new google.maps.Marker(markerConfig);
        this.markers.push(marker);
         
        if(this.isFo) {
            this.showStoreDetail(marker, store.id_store);
        }
    }
    
    hideMarkers() {
        this.markers.forEach(function(marker){
            marker.setMap(null);
        });
    }
    
    initSearch() {
        const locationInput = document.getElementById("location_input");
        const locationOptions = {
            fields: ["geometry"],
            origin: this.map.getCenter()
        };
        const locationAutocomplete = new google.maps.places.Autocomplete(
            locationInput,
            locationOptions
        );
        locationAutocomplete.bindTo("bounds", this.map);

        locationAutocomplete.addListener("place_changed", () => {
            const place = locationAutocomplete.getPlace();

            if (!place.geometry || !place.geometry.location) {
                window.alert(no_data_address_message + " '" + place.name + "'");
                return;
            }

            const locationRadius = Number(document.getElementById("radius_input").value);
            if (place.geometry.viewport) {
                this.map.fitBounds(place.geometry.viewport);
            } else {
                this.map.setCenter(place.geometry.location);
            }

            let zoom_level = this.ratioRadiusZoom[locationRadius];
            if (zoom_level === undefined) {
                zoom_level = this.defaultZoom;
            }
            
            this.map.setZoom(zoom_level);

            this.filterStoreList(locationRadius, place.geometry.location);
        });
    }
    
    async filterStoreList(radius, location) {
        
        const url = new URL(this.urlFrontController);
        url.searchParams.append('action', 'searchStoreByRadius');
        url.searchParams.append('id_lang', this.idLang);
        
        const formData = new FormData();
        formData.append('radius', radius);
        formData.append('lat', location.lat());
        formData.append('lng', location.lng());
        
        const response = await fetch(url, {
            body: formData,
            method: 'post'
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const filterResponse = await response.json();
        
        if(filterResponse.error) {
            console.error(filterResponse.message);
            return;
        }
        
        const stores = filterResponse.data;
        document.querySelectorAll('.store-item').forEach(function(storeItem) {
            const id = Number(storeItem.id.replace('store-',''));
            if(stores.includes(id))
            {
                storeItem.classList.remove("storeggmap-hide");
            } else 
            {
                storeItem.classList.add("storeggmap-hide");
            }
        });
    }
    
    async loadStores() {
        const url = new URL(this.urlFrontController);
        url.searchParams.append('action', 'getStores');
        url.searchParams.append('id_lang', this.idLang);
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if(data.error) {
            console.error(data.message);
            return null;
        }
        
        return data;
    }
    
    async getStoreDetail(id_store) {
        const url = new URL(this.urlFrontController);
        url.searchParams.append('id_store', id_store);
        url.searchParams.append('action', 'getStoreDetail');
        url.searchParams.append('id_lang', this.idLang);
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if(data.error) {
            console.error(data.message);
            return null;
        }
        
        return data;
    }
    
    async showStoreDetail(marker, id_store) {
        marker.addListener('click', async () => {
            this.map.setCenter(marker.getPosition());
                    
            if (this.infowindow) {
                this.infowindow.close();
            }
            
            let storeDetail = await this.getStoreDetail(id_store);
            if(storeDetail.data) {
                this.infowindow = new google.maps.InfoWindow({
                    content: storeDetail.data,
                    maxWidth: 350,
                });
                
                this.infowindow.open(this.map, marker);
            }
        });
    }
}