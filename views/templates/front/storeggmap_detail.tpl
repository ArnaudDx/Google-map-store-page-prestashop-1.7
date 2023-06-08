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

