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
*  @copyright  2007-2021 awb-dsgn.com

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class SggMapCall
{
    private $action;
    private $response = array();
    
    public function __construct($action)
    {
        $this->action = (string)$action;
    }
    
    public function run()
    {
        if (method_exists($this, $this->action)) {
            $this->{$this->action}();
        }
    }
    
    public function returnJsonResponse()
    {
        die(json_encode($this->response));
    }
    
    private function getAllStores()
    {
        $id_lang = Tools::getValue('id_lang', null);
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
                    $storeList[$key]['hours'] = $this->reorderHours($storeData['hours']);
                    $storeList[$key]['phone'] = $storeData['phone'];
                    $storeList[$key]['fax'] = $storeData['fax'];
                    $storeList[$key]['email'] = $storeData['email'];
                    $storeList[$key]['note'] = $storeData['note'];
                    $storeList[$key]['latitude'] = (float)$storeData['latitude'];
                    $storeList[$key]['longitude'] = (float)$storeData['longitude'];
                }
            }
            $this->response = array('storeList' => $storeList);
        }
    }
    
    private function reorderHours($hoursArray)
    {
        $str = str_replace('[', '', $hoursArray);
        $str = str_replace(']', '', $str);
        $str = str_replace('"', '', $str);
        $str = explode(",", $str);
        foreach ($str as &$row) {
            $row = str_replace('\\', '', preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $row));
        }
        return $str;
    }
    
    private function getStoreListToHideRadius()
    {
        $radius = (int)Tools::getValue('id_lang', 15);
        $lat = (float)Tools::getValue('lat', null);
        $lng = (float)Tools::getValue('lng', null);
        if (!empty($lat) && !empty($lng)) {
            $distance_unit = Configuration::get('PS_DISTANCE_UNIT');
            if (!in_array($distance_unit, ['km', 'mi'])) {
                $distance_unit = 'km';
            }
            
            $multiplicator = ($distance_unit == 'km' ? 6371 : 3959);
            
            $stores = Db::getInstance()->executeS('
            SELECT s.id_store,
            (' . (int)$multiplicator . '
                * acos(
                    cos(radians(' . (float)$lat . '))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(' . (float)$lng . '))
                    + sin(radians(' . (float)$lat . '))
                    * sin(radians(latitude))
                )
            ) distance
            FROM ' . _DB_PREFIX_ . 'store s
            ' . Shop::addSqlAssociation('store', 's') . '
            WHERE s.active = 1
            HAVING CAST(distance AS UNSIGNED) > ' . (int)$radius . '
            ORDER BY distance ASC');
            if (!empty($stores)) {
                $stores = array_column($stores, 'id_store');
            }
        }
        
        $this->response = array('storeToHideList' => $stores);
    }
}