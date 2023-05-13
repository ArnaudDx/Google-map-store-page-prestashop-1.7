<?php

class StoreggmapStoreInformationModuleFrontController extends ModuleFrontController
{

    protected $allowedActions = ['getStores', 'getStoreDetail', 'searchStoreByRadius'];
    public $response = [];

    public function displayAjax()
    {
        $this->response = [
            'error' => true,
            'message' => null,
            'data' => null
        ];

        if (!Tools::isSubmit('action')) {
            $this->response['message'] = $this->l('Missing parameter');
            $this->returnJsonResponse();
        }

        $action = Tools::getValue('action');
        if (!in_array($action, $this->allowedActions)) {
            $this->response['message'] = $this->l('Missing parameter');
            $this->returnJsonResponse();
        }

        switch ($action) {
            case 'getStores':
                $this->response['error'] = false;
                $this->response['data'] = $this->getAllStores();
                break;
            case 'getStoreDetail':
                $this->response['error'] = false;
                $this->response['data'] = $this->getStoreDetail();
                break;
            case 'searchStoreByRadius':
                $this->response['error'] = false;
                $this->response['data'] = $this->searchStoreByRadius();
                break;
            default:
                $this->response['message'] = $this->l('I don\'t know what to say');
        }

        $this->returnJsonResponse();
    }

    public function returnJsonResponse()
    {
        die(json_encode($this->response));
    }

    private function getAllStores()
    {
        $id_lang = (int)Tools::getValue('id_lang', $this->context->language->id);
        $stores = Store::getStores($id_lang);
        if (!$stores) {
            return [];
        }

        $storeList = [];
        foreach ($stores as $key => $storeData) {
            if (empty($storeData['latitude'])
                || empty($storeData['longitude'])) {
                continue;
            }

            $storeList[] = [
                'id_store' => $storeData['id_store'],
                'title' => $storeData['name'],
                'latitude' => (float) $storeData['latitude'],
                'longitude' => (float) $storeData['longitude']
            ];
        }
        
        return $storeList;
    }
    
    private function getStoreDetail()
    {
        $id_store = (int)Tools::getValue('id_store', null);
        $id_lang = (int)Tools::getValue('id_lang', $this->context->language->id);
        
        if (!$id_store) {
            return [];
        }

        $store = new Store($id_store, $id_lang);
        $store->country = Country::getNameById($id_lang, $store->id_country);
        $store->state = State::getNameById($store->id_state);
        
        if (!empty($store->hours)) {
            $store->hours = array_values(json_decode($store->hours));
            $hours_with_day = array(
                $this->l('Monday'),
                $this->l('Tuesday'),
                $this->l('Wednesday'),
                $this->l('Thursday'),
                $this->l('Friday'),
                $this->l('Saturday'),
                $this->l('Sunday'),
            );
            $new_hours = [];
            foreach ($store->hours as $k => $hours) {
                $new_hours[$hours_with_day[$k]] = $hours[0];
            }
            $store->hours = $new_hours;
        }
        
        $this->context->smarty->assign(array(
            'store' => $store
        ));
        
        return $this->module->fetch($this->module->templateDetailFile);
    }

    private function searchStoreByRadius()
    {
        $radius = (int)Tools::getValue('radius', 15);
        $lat = (float)Tools::getValue('lat', null);
        $lng = (float)Tools::getValue('lng', null);
    
        if (empty($lat) || empty($lng)) 
        {
            return [];
        }
        
        $distance_unit = Configuration::get('PS_DISTANCE_UNIT');
        if (!in_array($distance_unit, ['km', 'mi'])) {
            $distance_unit = 'km';
        }
        
        $multiplicator = ($distance_unit == 'km' ? 6371 : 3959);
        
        $stores = Db::getInstance()->executeS('SELECT s.id_store,
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
        
        return $stores;
    }
}