{if $storeGgMapOrderAddress}
<div class="card mt-2" data-role="message-card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div id="ggmap" style="height:300px;" data-address="{$storeGgMapOrderAddress|escape:'html':'UTF-8'}"></div>
            </div>
        </div>
    </div>
</div>
{/if}