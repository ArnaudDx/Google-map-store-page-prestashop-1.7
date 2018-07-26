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

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Storeggmap extends Module implements WidgetInterface
{
    private $templateFile;
	private $allowed_pages_init;

    public function __construct()
    {
        $this->name = 'storeggmap';
        $this->author = 'Arnaud Drieux';
        $this->version = '1.4.17';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Show your stores on a google map');
        $this->description = $this->l('Add Google map on the store page');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:storeggmap/views/templates/hook/storeggmap.tpl';
		$this->allowed_pages_init = array(
			array("controller"=>"*", "name"=> $this->l('Everywhere')),
			array("controller"=>"contact", "name"=> $this->l('Contact')),
			array("controller"=>"discount", "name"=> $this->l('Discount')),
			array("controller"=>"index", "name"=> $this->l('Home')),
			array("controller"=>"sitemap", "name"=> $this->l('Sitemap')),
			array("controller"=>"stores", "name"=> $this->l('Stores')),
			array("controller"=>"cms", "name"=> $this->l('CMS')),
			array("controller"=>"product", "name"=> $this->l('Product')),
			array("controller"=>"category", "name"=> $this->l('Category')),
			array("controller"=>"manufacturer", "name"=> $this->l('Manufacturer')),
			array("controller"=>"supplier", "name"=> $this->l('Supplier')),
		);
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
		Configuration::deleteByName('STORE_GGMAP_PAGE') &&
		Configuration::deleteByName('STORE_GGMAP_CUSTOM') &&
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
                $output .= $this->displayConfirmation($this->l('Icon deleted'));
            } else {
                $output .= $this->displayError($this->l('Error while icon deletion.'));
            }
        } elseif (Tools::isSubmit('save_storemap')) {
            Configuration::updateValue('STORE_GGMAP_APIKEY', Tools::getValue('ggmap_apikey'));
            Configuration::updateValue('STORE_GGMAP_LAT', Tools::getValue('ggmap_lat'));
            Configuration::updateValue('STORE_GGMAP_LONG', Tools::getValue('ggmap_long'));
			Configuration::updateValue('STORE_GGMAP_PAGE', json_encode(Tools::getValue('ggmap_page')));
			$custom_data = json_decode(Tools::getValue('ggmap_custom',null));
			$custom_data = (!empty($custom_data) ? json_encode($custom_data) : null);
			Configuration::updateValue('STORE_GGMAP_CUSTOM', $custom_data);
            
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
                        $output .= $this->displayConfirmation($this->l('Icon added'));
                    } else {
                        $output .= $this->displayError($this->l('Image format error.'));
                    }
                }
            } else {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
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
            $image_Url = $this->_path.'views/img/'.Configuration::get('STORE_GGMAP_ICON');
            $file_description = '<p>'.$this->l('Actual icon').' : ';
            $file_description .= '<img src="'.$image_Url.'"/> <button type="submit" name="delicon" class="delicon btn btn-default"><i class="icon-trash"></i></button></p>';
        }

        $fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Google map store block'),
            ),
            'input' => array(
                'content' => array(
                    'type' => 'free',
                    'label' => $this->l('Widget code to copy in your template files'),
                    'name' => 'ggmap_widget',
                    'col' => 4
                ),
				array(
                    'type' => 'text',
                    'label' => $this->l('Google Map Api key'),
                    'name' => 'ggmap_apikey',
					'required' => true, 
                    'desc' => '<p>'.$this->l('Double click on the map to define the default latitude/longitude :').'</p><div id="ggmap" style="height:500px;"></div>',
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Default latitude'),
                    'name' => 'ggmap_lat',
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Default longitude'),
                    'name' => 'ggmap_long',
                    'col' => 4
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Upload your icon'),
                    'desc' => $file_description,
                    'name' => 'ggmap_icon',
                ),
				array(
					'type' => 'select',
					'multiple' => true,
					'label' => $this->l('Choose type of page to show the map'),
					'name' => 'ggmap_page[]',
					'required' => true,
					'id' => 'ggmap_page_selector',
					'options' => array(
					'query' => $this->allowed_pages_init,
					'id' => 'controller',
					'name' => 'name'
					),
					'class'=> 'fixed-width-xxl',
					'col' => 4,
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Customize your map'),
					'desc' => '<p><a href="https://mapstyle.withgoogle.com/" target="_blank">'.$this->l('Go to the StylingWizard from Google').'</a> '.$this->l('and paste here the JSON code generated').'.</p>',
                    'name' => 'ggmap_custom',
                    'col' => 4
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
            'buttons' => array(
                array(
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->l('Back to list'),
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
		$fields_value['ggmap_page[]'] = json_decode(Configuration::get('STORE_GGMAP_PAGE'),true);
		$fields_value['ggmap_widget'] = '<code id="ggmap_widget">{widget name="storeggmap"}</code>';
		$fields_value['ggmap_custom'] = Configuration::get('STORE_GGMAP_CUSTOM');

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
		$authorized_pages = json_decode(Configuration::get('STORE_GGMAP_PAGE'),true);
        if ((in_array("*", $authorized_pages) || in_array($this->context->controller->php_self, $authorized_pages)) && !empty($apikey)) {
			$this->context->controller->addJquery();
            $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/front-ggmap.js');
            Media::addJsDef(array(
                'storeGGmapCall' => _MODULE_DIR_.$this->name.'/'.$this->name.'Call.php',
                'urlIcon' => (Configuration::get('STORE_GGMAP_ICON') ? _MODULE_DIR_.$this->name.'/views/img/'.Configuration::get('STORE_GGMAP_ICON') : null),
                'id_lang' => (int)$this->context->language->id,
                'defaultLat' => Configuration::get('STORE_GGMAP_LAT'),
                'defaultLong' => Configuration::get('STORE_GGMAP_LONG'),
                'ggApiKey' => $apikey,
				'customized_map' => json_decode(Configuration::get('STORE_GGMAP_CUSTOM')),
            ));
        }
    }
    
    public function hookdisplayBackOfficeHeader($params)
    {

        if ('AdminModules' == Tools::getValue('controller') && $this->name == Tools::getValue('configure')) {
            $apikey = Configuration::get('STORE_GGMAP_APIKEY');
            $this->context->controller->addCSS(_MODULE_DIR_.'/'.$this->name.'/views/css/back-ggmap.css');
            $this->context->controller->addJquery();
            $this->context->controller->addJS('https://maps.googleapis.com/maps/api/js?key='.$apikey);
            $this->context->controller->addJS(_MODULE_DIR_.'/'.$this->name.'/views/js/back-ggmap.js?'.rand());
            Media::addJsDef(array(
                'defaultLat' => $this->defaultLatLng(),
                'defaultLong' => $this->defaultLatLng(1),
				'urlIcon' => (Configuration::get('STORE_GGMAP_ICON') ? _MODULE_DIR_.$this->name.'/views/img/'.Configuration::get('STORE_GGMAP_ICON') : null),
                'customized_map' => json_decode(Configuration::get('STORE_GGMAP_CUSTOM')),
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
