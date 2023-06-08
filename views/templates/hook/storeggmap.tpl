{*
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Arnaud Drieux <contact@awb-dsgn.com>
*  @copyright  2007-2023 awb-dsgn.com
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
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

