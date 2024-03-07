{if isset($storeGgMapOrderAddressCoordinate->lng) && isset($storeGgMapOrderAddressCoordinate->lat)}
<div class="card mt-2" data-role="message-card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div id="ggmap" style="height:300px;" data-lng="{$storeGgMapOrderAddressCoordinate->lng}" data-lat="{$storeGgMapOrderAddressCoordinate->lat}"></div>
            </div>
        </div>
    </div>
</div>
{/if}