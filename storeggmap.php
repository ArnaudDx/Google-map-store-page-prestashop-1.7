<?php
/*
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
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Storeggmap extends Module implements WidgetInterface
{
    private $templateFile;
    private $allowedPagesInit;
    private $allowedZoomLevel;
    private $defaultZoomLevel = 5;
    private $defaultRadiusList = [
        15,
        25,
        50,
        100,
    ];

    protected $jsPath;
    protected $cssPath;
    protected $imgPath;
    protected $imgLocalPath;

    public function __construct()
    {
        $this->name = 'storeggmap';
        $this->author = 'Arnaud Drieux';
        $this->version = '2.0.0';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Show your stores on a google map');
        $this->description = $this->l('Add Google map on the store page');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:storeggmap/views/templates/hook/storeggmap.tpl';
        $this->templateDetailFile = 'module:storeggmap/views/templates/front/storeggmap_detail.tpl';

        $this->allowedPagesInit = [
            ["controller" => "*", "name" => $this->l('Everywhere')],
            ["controller" => "contact", "name" => $this->l('Contact')],
            ["controller" => "discount", "name" => $this->l('Discount')],
            ["controller" => "index", "name" => $this->l('Home')],
            ["controller" => "sitemap", "name" => $this->l('Sitemap')],
            ["controller" => "stores", "name" => $this->l('Stores')],
            ["controller" => "cms", "name" => $this->l('CMS')],
            ["controller" => "product", "name" => $this->l('Product')],
            ["controller" => "category", "name" => $this->l('Category')],
            ["controller" => "manufacturer", "name" => $this->l('Manufacturer')],
            ["controller" => "supplier", "name" => $this->l('Supplier')]
        ];

        for ($i = 0; $i <= 20; $i++) {
            $this->allowedZoomLevel[] = ["level" => $i, "name" => $i];
        }

        $this->jsPath = $this->_path . 'views/js/';
        $this->cssPath = $this->_path . 'views/css/';
        $this->imgPath = $this->_path . 'views/img/';
        $this->imgLocalPath = $this->local_path . 'views/img/';
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateGlobalValue('STORE_GGMAP_PAGE', json_encode(['stores']))
            && Configuration::updateGlobalValue('STORE_GGMAP_SEARCH', 1)
            && Configuration::updateGlobalValue('STORE_GGMAP_LAT', $this->getDefaultCoordinates())
            && Configuration::updateGlobalValue('STORE_GGMAP_LONG', $this->getDefaultCoordinates('longitude'))
            && Configuration::updateGlobalValue('STORE_GGMAP_ZOOM', $this->defaultZoomLevel)
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        return Configuration::deleteByName('STORE_GGMAP_APIKEY')
            && Configuration::deleteByName('STORE_GGMAP_ICON')
            && Configuration::deleteByName('STORE_GGMAP_LAT')
            && Configuration::deleteByName('STORE_GGMAP_LONG')
            && Configuration::deleteByName('STORE_GGMAP_PAGE')
            && Configuration::deleteByName('STORE_GGMAP_CUSTOM')
            && Configuration::deleteByName('STORE_GGMAP_ZOOM')
            && Configuration::deleteByName('STORE_GGMAP_SEARCH')
            && parent::uninstall();

    }

    public function getContent()
    {
        $output = '';

        switch (true) {
            case (bool)Tools::isSubmit('delete_icon'):
                $imageName = Configuration::get('STORE_GGMAP_ICON');
                $imagePath = $this->imgLocalPath . $imageName;

                if (!file_exists($imagePath)) {
                    $output .= $this->displayError($this->l('Icon file does not exist:') . ' ' . $imagePath);
                    break;
                }

                if (!unlink($imagePath)) {
                    $output .= $this->displayError($this->l('Error while icon deletion.'));
                    break;
                }

                Configuration::updateValue('STORE_GGMAP_ICON', null);
                $this->_clearCache($this->templateFile);
                $output .= $this->displayConfirmation($this->l('Icon deleted'));
                break;

            case (bool)Tools::isSubmit('save_storemap'):
                Configuration::updateValue('STORE_GGMAP_APIKEY', Tools::getValue('ggmap_apikey'));
                Configuration::updateValue('STORE_GGMAP_LAT', (float)Tools::getValue('ggmap_lat'));
                Configuration::updateValue('STORE_GGMAP_LONG', (float)Tools::getValue('ggmap_long'));
                Configuration::updateValue('STORE_GGMAP_ZOOM', (int)Tools::getValue('ggmap_zoom'));
                Configuration::updateValue('STORE_GGMAP_SEARCH', (int)Tools::getValue('ggmap_search'));

                ## validate pages
                $pageSelection = Tools::getValue('ggmap_page');
                if (!$this->isValidPageSelection($pageSelection, $output)) {
                    break;
                }
                Configuration::updateValue('STORE_GGMAP_PAGE', json_encode($pageSelection));

                ## validate customization
                $customData = Tools::getValue('ggmap_custom', []);
                if (!$this->isValidCustomization($customData, $output)) {
                    break;
                }
                Configuration::updateValue('STORE_GGMAP_CUSTOM', (!empty($customData) ? $customData : null));

                ## upload icon
                if (!$this->isUploadedIcon($_FILES, $output)) {
                    break;
                }

                $output .= $this->displayConfirmation($this->l('Configuration updated'));
                break;
        }

        $this->_clearCache($this->templateFile);

        return $output . $this->renderForm();
    }

    protected function isValidPageSelection($submittedData, &$errorMessage)
    {
        if (!is_array($submittedData)) {
            $errorMessage .= $this->displayError($this->l('Error while saving pages.'));
            return false;
        }

        foreach ($submittedData as $page) {

            if (!in_array($page, array_column($this->allowedPagesInit, 'controller'))) {
                $errorMessage .= $this->displayError($this->l('This id is not a valid page:') . ' ' . $page);
                return false;
            }
        }

        return true;
    }

    protected function isValidCustomization($submittedData, &$errorMessage)
    {
        if (empty($submittedData)) {
            return true;
        }

        $decoded = json_decode($submittedData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage .= $this->displayError($this->l('Something is wrong with map customization'));
            return false;
        }

        return true;
    }

    protected function isUploadedIcon($submittedData, &$errorMessage)
    {
        if (empty($submittedData)
            || empty($submittedData['ggmap_icon']['name'])) {
            return true;
        }

        if (!isset($submittedData['ggmap_icon'])
            || empty($submittedData['ggmap_icon'])) {
            $errorMessage .= $this->displayConfirmation($this->l('Empty icon'));
            return false;
        }

        $file = $submittedData['ggmap_icon'];

        if (!is_uploaded_file($file['tmp_name'])
            || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage .= $this->displayError($this->l('Bad icon upload'));
            return false;
        }

        $allowedMimes = array(
            'jpeg' => image_type_to_mime_type(IMAGETYPE_JPEG),
            'jpg' => image_type_to_mime_type(IMAGETYPE_JPEG),
            'png' => image_type_to_mime_type(IMAGETYPE_PNG),
            'gif' => image_type_to_mime_type(IMAGETYPE_GIF),
            'bmp' => image_type_to_mime_type(IMAGETYPE_BMP)
        );

        if (version_compare(phpversion(), '7.1', '>=')) {
            $allowedMimes['webp'] = image_type_to_mime_type(IMAGETYPE_WEBP);
        }


        $fileDetail = pathinfo($file['name']);
        if (!isset($fileDetail['extension'])) {
            $errorMessage .= $this->displayError($this->l('Icon extension missing'));
            return false;
        }

        $extension = $fileDetail['extension'];
        if (!isset($allowedMimes[$extension])) {
            $errorMessage .= $this->displayError($this->l('Forbidden extension'));
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);

        $typeMimeFromExtension = $allowedMimes[$extension];
        if ($typeMimeFromExtension !== $realMime) {
            $errorMessage .= $this->displayError($this->l('Something is wrong with file'));
            return false;
        }

        switch ($typeMimeFromExtension) {
            case 'image/jpeg':
                $canFinishUpload = (bool)imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $canFinishUpload = (bool)imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $canFinishUpload = (bool)imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/bmp':
                $canFinishUpload = (bool)imagecreatefrombmp($file['tmp_name']);
                break;
            case 'image/webp':
                $canFinishUpload = (bool)imagecreatefromwebp($file['tmp_name']);
                break;
            default:
                $canFinishUpload = false;
        }

        if (!$canFinishUpload) {
            $errorMessage .= $this->displayError($this->l('File is not an image'));
            return false;
        }

        $iconFileName = 'icon.' . $extension;

        if (!copy($file['tmp_name'], $this->imgLocalPath . $iconFileName)) {
            $errorMessage .= $this->displayError($this->l('Error during uploading file'));
            return false;
        }

        Configuration::updateValue('STORE_GGMAP_ICON', $iconFileName);

        return true;
    }

    protected function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $file_description = null;

        if (Configuration::get('STORE_GGMAP_ICON')) {
            $file_description = '<p>' . $this->l('Actual icon') . ' : ';
            $file_description .= '<img id="ggmap_icon_value" src="' . $this->getIconUrl() . '"/> <button type="submit" name="delete_icon" class="delicon btn btn-default"><i class="icon-trash"></i></button></p>';
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
                    'desc' => $this->l('N.B: If the map is not displayed, please clear cache of the shop first (Advanced Parameters > Performance) and then check module configuration.'),
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Google Map Api key'),
                    'name' => 'ggmap_apikey',
                    'required' => true,
                    'desc' => '<p>' . $this->l('Double click on the map to define the default latitude/longitude :') . '</p><div id="ggmap" style="height:500px;"></div>',
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
                    'type' => 'select',
                    'label' => $this->l('Default Zoom level'),
                    'name' => 'ggmap_zoom',
                    'id' => 'ggmap_zoom_selector',
                    'options' => array(
                        'query' => $this->allowedZoomLevel,
                        'id' => 'level',
                        'name' => 'name'
                    ),
                    'col' => 4
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Upload your icon'),
                    'desc' => $file_description,
                    'name' => 'ggmap_icon',
                    'col' => 8
                ),
                array(
                    'type' => 'select',
                    'multiple' => true,
                    'label' => $this->l('Choose type of page to show the map'),
                    'name' => 'ggmap_page[]',
                    'required' => true,
                    'id' => 'ggmap_page_selector',
                    'options' => array(
                        'query' => $this->allowedPagesInit,
                        'id' => 'controller',
                        'name' => 'name'
                    ),
                    'col' => 4
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Customize your map'),
                    'desc' => '<p><a href="https://mapstyle.withgoogle.com/" target="_blank">' . $this->l('Go to the StylingWizard from Google') . '</a> ' . $this->l('and paste here the JSON code generated') . '.</p>',
                    'name' => 'ggmap_custom',
                    'col' => 4
                ),
                array(
                    'type' => 'switch',
                    'class' => 't',
                    'label' => $this->l('Enable search'),
                    'name' => 'ggmap_search',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
            'buttons' => array(
                array(
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
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

        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
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
        $fields_value['ggmap_zoom'] = Configuration::get('STORE_GGMAP_ZOOM');
        $fields_value['ggmap_page[]'] = json_decode(Configuration::get('STORE_GGMAP_PAGE'), true);
        $fields_value['ggmap_widget'] = '<code id="ggmap_widget">{widget name="storeggmap"}</code>';
        $fields_value['ggmap_custom'] = Configuration::get('STORE_GGMAP_CUSTOM');
        $fields_value['ggmap_search'] = Configuration::get('STORE_GGMAP_SEARCH', null, null, null, true);

        return $fields_value;
    }

    public function hookActionAdminControllerSetMedia()
    {
        if ('AdminModules' != $this->context->controller->controller_name
            || $this->name != Tools::getValue('configure')) {
            return;
        }

        ## css custom
        $this->context->controller->addCSS($this->cssPath . 'back.css');

        ## js
        Media::addJsDef([
            'storeGgMmapSettings' => $this->getMapSettings()
        ]);

        $this->context->controller->addJS([
            $this->getApiUrl(),
            $this->jsPath . 'classes/StoreGgMap.js',
            $this->jsPath . 'back.js'
        ]);
    }

    public function hookActionFrontControllerSetMedia()
    {
        $authorizedPages = Configuration::get('STORE_GGMAP_PAGE');

        if (empty(Configuration::get('STORE_GGMAP_APIKEY'))
            || empty($authorizedPages)) {
            return;
        }

        $authorized_pages = json_decode(Configuration::get('STORE_GGMAP_PAGE'), true);
        if (!in_array("*", $authorized_pages)
            && !in_array($this->context->controller->php_self, $authorized_pages)
        ) {
            return;
        }


        ## css
        $this->context->controller->registerStylesheet(
            'theme-error',
            $this->cssPath . 'front.css',
            [
                'media' => 'all',
                'priority' => 50,
                'version' => $this->version
            ]
        );

        ## js
        Media::addJsDef([
            'storeGgMmapSettings' => $this->getMapSettings()
        ]);

        $this->context->controller->registerJavascript(
            'storeggmap-api-url',
            $this->getApiUrl(true),
            [
                'position' => 'bottom',
                'priority' => 1000,
                'server' => 'remote'
            ]
        );

        $this->context->controller->registerJavascript(
            'storeggmap-class',
            $this->jsPath . 'classes/StoreGgMap.js',
            [
                'position' => 'bottom',
                'priority' => 1001,
                'version' => $this->version
            ]
        );

        $this->context->controller->registerJavascript(
            'storeggmap-front',
            $this->jsPath . 'front.js',
            [
                'position' => 'bottom',
                'priority' => 1002,
                'version' => $this->version
            ]
        );
    }

    private function getApiUrl($withSearch = false)
    {
        $params = '';
        $apiKey = Configuration::get('STORE_GGMAP_APIKEY');
        if ($apiKey) {
            $request = ['key' => $apiKey];

            if ($withSearch) {
                $request['libraries'] = 'places';
            }

            $params = '?' . http_build_query($request);
        }

        return 'https://maps.googleapis.com/maps/api/js' . $params;
    }

    private function getIconUrl()
    {
        return (Configuration::get('STORE_GGMAP_ICON') ? trim($this->context->link->getBaseLink(), '/') . $this->imgPath . Configuration::get('STORE_GGMAP_ICON') : null);
    }

    private function getMapSettings()
    {
        return [
            'id_lang' => (int)$this->context->language->id,
            'token' => $this->getToken(),
            'urlIcon' => $this->getIconUrl(),
            'urlFrontController' => Context::getContext()->link->getModuleLink('storeggmap', 'StoreInformation', array('ajax' => 1)),
            'defaultLatitude' => (float)Configuration::get('STORE_GGMAP_LAT'),
            'defaultLongitude' => (float)Configuration::get('STORE_GGMAP_LONG'),
            'defaultZoom' => (int)Configuration::get('STORE_GGMAP_ZOOM'),
            'designCustomization' => json_decode(Configuration::get('STORE_GGMAP_CUSTOM')),
            'searchEnable' => (int)Configuration::get('STORE_GGMAP_SEARCH', null, null, null, true),
            'searchErrorMessage' => $this->l('No details available for this search:'),
        ];
    }

    public function getToken()
    {
        return hash('sha256', _COOKIE_KEY_ . $this->version . date('Ymd'));
    }

    private function getDefaultCoordinates($type = 'latitude')
    {

        $store = Db::getInstance()->getRow('SELECT latitude, longitude FROM ' . _DB_PREFIX_ . 'store');
        if (!$store || !isset($store[$type])) {
            return 0;
        }

        return $store[$type];
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
        $vars = [
            'apiKey' => Configuration::get('STORE_GGMAP_APIKEY'),
            'enable_search' => (int)Configuration::get('STORE_GGMAP_SEARCH')
        ];

        if ($vars['enable_search']) {
            $distanceUnit = Configuration::get('PS_DISTANCE_UNIT');
            if (!in_array($distanceUnit, ['km', 'mi'])) {
                $distanceUnit = 'km';
            }

            foreach ($this->defaultRadiusList as $radius) {
                $vars['radius_options'][(int)$radius] = $radius . $distanceUnit;
            }
        }
        return $vars;
    }

}
