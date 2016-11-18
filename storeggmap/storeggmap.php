<?php
/*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Storeggmap extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'storeggmap';
        $this->author = 'ArnaudDx';
        $this->version = '1.1.6';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Show your stores on google map', array());
        $this->description = $this->trans('Add Google map on the store page', array());

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:storeggmap/views/templates/hook/storeggmap.tpl';
    }

    public function install()
    {
        return parent::install() &&
        $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return Configuration::deleteByName('STORE_GGMAP_APIKEY') &&
        parent::uninstall();
        
    }
    
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('save_storemap')) {
            if (!Tools::getValue('ggmap_apikey')) {
                $output = $this->displayError($this->trans('Please fill out all fields.', array(), 'Admin.Notifications.Error')) . $this->renderForm();
            } else {
                Configuration::updateValue('STORE_GGMAP_APIKEY', Tools::getValue('ggmap_apikey'));

                $this->_clearCache($this->templateFile);
            }
        }

        return $output.$this->renderForm();
    }

    public function processSaveCustomText()
    {
        $info = new CustomText(Tools::getValue('id_info', 1));

        $text = array();
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $text[$lang['id_lang']] = Tools::getValue('text_'.$lang['id_lang']);
        }

        $info->text = $text;

        if (Shop::isFeatureActive() && !$info->id_shop) {
            $saved = true;
            $shop_ids = Shop::getShops();
            foreach ($shop_ids as $id_shop) {
                $info->id_shop = $id_shop;
                $saved &= $info->add();
            }
        } else {
            $saved = $info->save();
        }

        return $saved;
    }

    protected function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->trans('CMS block', array()),
            ),
            'input' => array(
                'content' => array(
                    'type' => 'text',
                    'label' => $this->trans('Google Map Api', array(), 'Modules.storeggmap'),
                    'name' => 'ggmap_apikey',
                    'cols' => 40,
                    'rows' => 10,
                ),
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
            ),
            'buttons' => array(
                array(
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->trans('Back to list', array(), 'Admin.Actions'),
                    'icon' => 'process-icon-back'
                )
            )
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = 'storeggmap';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        }

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'save_storemap';

        $helper->fields_value = $this->getFormValues();

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    public function getFormValues()
    {
        $fields_value['ggmap_apikey'] = Configuration::get('STORE_GGMAP_APIKEY');

        return $fields_value;
    }
        
    public function JsStoreList($stores)
    {
        if ($stores) {
            $stringOutput = '';
            end($stores);
            $lastKey = key($stores);
            foreach ($stores as $key => $storeData) {
                if ($storeData['latitude'] && $storeData['latitude']) {        
                    $stringOutput .= '{';
                    $stringOutput .= 'id_store : "'.$storeData['id_store'].'", ';
                    $stringOutput .= 'name : "'.$storeData['name'].'", ';
                    $stringOutput .= 'latitude : '.$storeData['latitude'].', ';
                    $stringOutput .= 'longitude : '.$storeData['longitude'];
                    if ($key == $lastKey) {
                        $stringOutput .= '}';
                    } else {
                        $stringOutput .= '},';
                    }
                } else {
                    continue;
                }
            }
            return $stringOutput;
        }
    }
    
    public function defaultLatLng($lng = null) {
        $store = Db::getInstance()->getRow('SELECT latitude, longitude FROM '._DB_PREFIX_.'store');
        if ($lng) {
            return $store['longitude'];
        } else{
            return $store['latitude'];
        }
    }
    
    public function hookdisplayHeader($params)
    {
        $stores = Store::getStores();
        $apikey = Configuration::get('STORE_GGMAP_APIKEY');
        if ('stores' == $this->context->controller->php_self && $stores && $apikey) {
            $this->context->controller->registerStylesheet('modules-ggmap', 'modules/'.$this->name.'/views/css/ggmap.css', ['media' => 'all', 'priority' => 150]);
            // TODO 
            // Comment appeler cette url en externe?
            // $this->context->controller->registerJavascript('modules-initmap', 'https://maps.googleapis.com/maps/api/js?key='.$apikey.'&callback=initMap', ['position' => 'bottom', 'priority' => 100, 'inline' => true, 'attribute' => 'async']);
            $this->context->controller->registerJavascript('modules-ggmap', 'modules/'.$this->name.'/views/js/ggmap.js', ['position' => 'bottom', 'priority' => 150]); 
            Media::addJsDef(array(
                'storeArrayContent' => $this->JsStoreList($stores),
                'defaultLat' => $this->defaultLatLng(),
                'defaultLong' => $this->defaultLatLng(1),
            ));
        }
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('storeggmap'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('storeggmap'));
    }
    
    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return array(
            'apiKey' => Configuration::get('STORE_GGMAP_APIKEY'),
        );
    }

}
