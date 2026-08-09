{*
 * storeggmap - Show your stores on a Google Map
 *
 * @author    Arnaud Drieux <contact@awb-dsgn.com>
 * @copyright 2026 awb-dsgn.com
 * @license   https://opensource.org/licenses/AFL-3.0 AFL-3.0
 *}

<div class="store_infos">
    <p><b>{$store->name}</b></p>
    {if $store->email != ''}
        <p></p>
    {/if}
    <p>{$store->address1}
        {if $store->address2 != ''}<br>{$store->address2}{/if}
        <br>{$store->city}{if $store->postcode != ''}, {$store->postcode}{/if}
        <br>{$store->country}{if $store->state != ''}, {$store->state}{/if}
    </p>
    {if $store->phone != '' || $store->fax != '' || $store->email != ''}
        <p>
            {if $store->phone != ''}{l s='Phone:' mod="storeggmap"} {$store->phone}{/if}
            {if $store->fax != ''}<br>{l s='Fax:' mod="storeggmap"} {$store->fax}{/if}
            {if $store->email != ''}<br>{l s='Email:' mod="storeggmap"} <a href="mailto:{$store->email}">{$store->email}</a>{/if}
        </p>
    {/if}
    {if $store->note != ''}
        <p>{l s='Note:' mod="storeggmap"}<br>{$store->note}</p>
    {/if}
    <p>{l s='Our hours' mod="storeggmap"}</p>
    {if $store->hours}
        <ul class="store_hours">
            {foreach from=$store->hours key=day item=hour}
                <li>
                    <span class="day">{$day} :</span>
                    <span class="hour">{$hour}</span>
                </li>
            {/foreach}
        </ul>
    {/if}
</div>

