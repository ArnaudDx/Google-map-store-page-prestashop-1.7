<?php
/*
* 2007-2018 PrestaShop
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
*  @copyright  2007-2018 awb-dsgn.com

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

include dirname(__FILE__).'/../../config/config.inc.php';

if (isset($_POST['allStores']) && $_POST['allStores']) {
    
    $id_lang = Tools::getValue('id_lang',null);
    $stores = Store::getStores($id_lang);
    
    if ($stores) {
        $storeList = array();
        foreach ($stores as $key => $storeData) {
            if ($storeData['latitude'] && $storeData['longitude']) { 
                $storeList[$key]['id_store'] = $storeData['id_store'];
                $storeList[$key]['country'] = Country::getNameById((!empty($id_lang) ? (int)$id_lang : Configuration::get('PS_LANG_DEFAULT')), (int)$storeData['id_country']);
                $storeList[$key]['state'] = State::getNameById((int)$storeData['id_state']);
                $storeList[$key]['name'] = $storeData['name'];
                $storeList[$key]['address1'] = $storeData['address1'];
                $storeList[$key]['address2'] = $storeData['address2'];
                $storeList[$key]['city'] = $storeData['city'];
                $storeList[$key]['postcode'] = $storeData['postcode'];
                $storeList[$key]['hours'] = reorderHours($storeData['hours']);
                $storeList[$key]['phone'] = $storeData['phone'];
                $storeList[$key]['fax'] = $storeData['fax'];
                $storeList[$key]['email'] = $storeData['email'];
                $storeList[$key]['note'] = $storeData['note'];
                $storeList[$key]['latitude'] = (float)$storeData['latitude'];
                $storeList[$key]['longitude'] = (float)$storeData['longitude'];
            }
        }
        die(json_encode(array('storeList' => $storeList)));
    }
}

function reorderHours($hoursArray)
{
    $str = str_replace('[', '', $hoursArray);
    $str = str_replace(']', '', $str);
    $str = str_replace('"', '', $str);
    $str = explode(",", $str);
    return $str;
}
