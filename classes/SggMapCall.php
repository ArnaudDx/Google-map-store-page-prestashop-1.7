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
    
    private function getStoreDetail()
    {
        $id_store = (int)Tools::getValue('id_store', null);
        
        if ($id_store) {
            $module = Module::getInstanceByName('storeggmap');
            $context = Context::getContext();
            
            $store = new Store($id_store, $context->language->id);
            $store->country = Country::getNameById($context->language->id, $store->id_country);
            $store->state = State::getNameById($store->id_state);
            if (!empty($store->hours)) {
                $store->hours = array_values(json_decode($store->hours));
                $hours_with_day = array(
                    $module->l('Monday'),
                    $module->l('Tuesday'),
                    $module->l('Wednesday'),
                    $module->l('Thursday'),
                    $module->l('Friday'),
                    $module->l('Saturday'),
                    $module->l('Sunday'),
                );
                $new_hours = array();
                foreach ($store->hours as $k => $hours) {
                    $new_hours[$hours_with_day[$k]] = $hours[0];
                }
                $store->hours = $new_hours;
            }
            
            $context->smarty->assign(array(
                'store' => $store
            ));
            
            $this->response = $module->fetch($module->templateDetailFile);
        }
    }
}