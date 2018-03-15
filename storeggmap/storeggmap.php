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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2018 PrestaShop SA

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
        $this->version = '1.3.15';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Show your stores on a google map', array());
        $this->description = $this->trans('Add Google map on the store page', array());

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:storeggmap/views/templates/hook/storeggmap.tpl';
    }

    public function install()
    {
        return parent::install() &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return Configuration::deleteByName('STORE_GGMAP_APIKEY') &&
        Configuration::deleteByName('STORE_GGMAP_ICON') &&
        Configuration::deleteByName('STORE_GGMAP_LAT') &&
        Configuration::deleteByName('STORE_GGMAP_LONG') &&
        parent::uninstall();
        
    }
    
    public function getContent()
    {
        $output = '';
        $url_path = dirname(__FILE__).'/views/img/';

        if (Tools::isSubmit('delicon')) {
            $imageName = Configuration::get('STORE_GGMAP_ICON');
            if (unlink($url_path.$imageName)) {
                Configuration::updateValue('STORE_GGMAP_ICON', null);
                $this->_clearCache($this->templateFile);
                $output .= $this->displayConfirmation($this->trans('Icon deleted', array(), 'Admin.Notifications.Error'));
            } else {
                $output .= $this->displayError($this->trans('Error while icon deletion.', array(), 'Admin.Notifications.Error'));
            }
        } elseif (Tools::isSubmit('save_storemap')) {
            Configuration::updateValue('STORE_GGMAP_APIKEY', Tools::getValue('ggmap_apikey'));
            Configuration::updateValue('STORE_GGMAP_LAT', Tools::getValue('ggmap_lat'));
            Configuration::updateValue('STORE_GGMAP_LONG', Tools::getValue('ggmap_long'));
            
            if (isset($_FILES['ggmap_icon']['name']) && !empty($_FILES['ggmap_icon']['name'])) {
                Configuration::updateValue('STORE_GGMAP_ICON', $_FILES['ggmap_icon']['name']);
                
                if (isset($_FILES['ggmap_icon']) && $_FILES['ggmap_icon']) {
                    $tmp_name = $_FILES['ggmap_icon']['tmp_name'];
                    $name = $_FILES['ggmap_icon']['name'];
                    $type = Tools::strtolower(Tools::substr(strrchr($_FILES['ggmap_icon']['name'], '.'), 1));
                    $imagesize = @getimagesize($_FILES['ggmap_icon']['tmp_name']);
                    if (in_array( 
                        Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)), array(
                            'jpg',
                            'gif',
                            'jpeg',
                            'png'
                        )) &&
                    in_array($type, array('jpg', 'gif', 'jpeg', 'png'))) {
                        move_uploaded_file($tmp_name, $url_path.$name);
                        $output .= $this->displayConfirmation($this->trans('Icon added', array(), 'Admin.Notifications.Error'));
                    } else {
                        $output .= $this->displayError($this->trans('Image format error.', array(), 'Admin.Notifications.Error'));
                    }
                }
            } else {
                $output .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Error'));
            }

            $this->_clearCache($this->templateFile);
        }

        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $file_description = null;
        
        if (Configuration::get('STORE_GGMAP_ICON')) {
            $image_Url = '/modules/'.$this->name.'/views/img/'.Configuration::get('STORE_GGMAP_ICON');
            $file_description = '<p>'.$this->trans('Actual icon', array(), 'Modules.storeggmap').' : ';
            $file_description .= '<img src="'.$image_Url.'"/> <button type="submit" name="delicon" class="delicon btn btn-default"><i class="icon-trash"></i></button></p>';
        }

        $fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->trans('Google map store block', array()),
            ),
            'input' => array(
                'content' => array(
                    'type' => 'text',
                    'label' => $this->trans('Google Map Api key', array(), 'Modules.storeggmap'),
                    'name' => 'ggmap_apikey',
                    'desc' => '<p>'.$this->trans('Double click on the map to define the default latitude/longitude :', array(), 'Modules.storeggmap').'</p><div id="ggmap" style="height:500px;"></div>',
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Default latitude', array(), 'Modules.storeggmap'),
                    'name' => 'ggmap_lat',
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Default longitude', array(), 'Modules.storeggmap'),
                    'name' => 'ggmap_long',
                    'col' => 4
                ),
                array(
                    'type' => 'file',
                    'label' => $this->trans('Upload your icon', array(), 'Modules.storeggmap'),
                    'desc' => $file_description,
                    'name' => 'ggmap_icon',
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
        $fields_value['ggmap_icon'] = Configuration::get('STORE_GGMAP_ICON');
        $fields_value['ggmap_lat'] = Configuration::get('STORE_GGMAP_LAT');
        $fields_value['ggmap_long'] = Configuration::get('STORE_GGMAP_LONG');

        return $fields_value;
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
        $this->context->controller->registerStylesheet('modules-ggmap', _MODULE_DIR_.'/'.$this->name.'/views/css/ggmap.css', ['media' => 'all', 'priority' => 150]);
		$apikey = Configuration::get('STORE_GGMAP_APIKEY');
        if ('stores' == $this->context->controller->php_self && !empty($apikey)) {
			$this->context->controller->addJquery();
            $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/front-ggmap.js');
            Media::addJsDef(array(
                'storeGGmapCall' => _MODULE_DIR_.$this->name.'/'.$this->name.'Call.php',
                'urlIcon' => (Configuration::get('STORE_GGMAP_ICON') ? _MODULE_DIR_.$this->name.'/views/img/'.Configuration::get('STORE_GGMAP_ICON') : null),
                'id_lang' => (int)$this->context->language->id,
                'defaultLat' => Configuration::get('STORE_GGMAP_LAT'),
                'defaultLong' => Configuration::get('STORE_GGMAP_LONG'),
                'ggApiKey' => $apikey,
            ));
        }
    }
    
    public function hookdisplayBackOfficeHeader($params)
    {

        if ('AdminModules' == Tools::getValue('controller') && $this->name == Tools::getValue('configure')) {
            $apikey = Configuration::get('STORE_GGMAP_APIKEY');
            $this->context->controller->addJquery();
            $this->context->controller->addJS('https://maps.googleapis.com/maps/api/js?key='.$apikey);
            $this->context->controller->addJS(_MODULE_DIR_.'/'.$this->name.'/views/js/back-ggmap.js');
            Media::addJsDef(array(
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
