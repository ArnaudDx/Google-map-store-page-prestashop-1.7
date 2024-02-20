window.addEventListener("load", (event) => {
    
    const divStoreGgMap = document.getElementById("storemap");
    if(divStoreGgMap)
    {
        const storeggmap = new StoreGgMap('storemap', storeGgMmapSettings);
        storeggmap.initFo();
    } else {
        console.info('StoreGgMap initialized. Template widget missing.');
    }
});