{*
 * storeggmap - Show your stores on a Google Map
 *
 * @author    Arnaud Drieux <contact@awb-dsgn.com>
 * @copyright 2026 awb-dsgn.com
 * @license   https://opensource.org/licenses/AFL-3.0 AFL-3.0
 *}

<section id="map-style">
    {if $apiKey}
        <div id="storemap" style="height: 500px;position: relative;overflow: hidden;"></div>
        {if $enable_search}
            <section id="map_location_search" class="card">
                <section class="form-fields">
                    <div class="form-group">
                        <label for="radius_input" class="form-control-label">{l s='Select a radius' mod='storeggmap'}</label>
                        <select name="radius_input" id="radius_input" class="form-control">
                            {foreach from=$radius_options key=radius item=radius_label}
                            <option value="{$radius|strip_tags}">{$radius_label|strip_tags}</option>
                            {/foreach}
                        </select>
                        <label for="location_input" class="form-control-label">{l s='Your search' mod='storeggmap'}</label>
                        <input type="text" name="location_input" id="location_input" class="form-control">
                    </div>
                </section>
            </section>
        {/if}
    {else}
        <div class="alert-warning">{l s='No api key registered' mod='storeggmap'}</div>
    {/if}
</section>

