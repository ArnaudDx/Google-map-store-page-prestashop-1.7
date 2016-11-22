<?php

include dirname(__FILE__).'/../../config/config.inc.php';

if ( isset($_POST['allStores']) && $_POST['allStores']) {
    
    $stores = Store::getStores();
    $id_lang = Tools::getValue($_POST['id_lang']);
    
    if ($stores) {
        $storeList = array();
        foreach ($stores as $key => $storeData) {
            if ($storeData['latitude'] && $storeData['latitude']) { 
                $storeList[$key]['id_store'] = $storeData['id_store'];
                $storeList[$key]['country'] = Country::getNameById((int)$id_lang, (int)$storeData['id_country']);
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
            } else {
                continue;
            }
        }
        die(json_encode(array('storeList' => $storeList)));
    }
}

function reorderHours($hoursArray)
{
    // die($hoursArray);
    $str = str_replace('[', '', $hoursArray);
    $str = str_replace(']', '', $str);
    $str = str_replace('"', '', $str);
    $str = explode(",", $str);
    return $str;
}
