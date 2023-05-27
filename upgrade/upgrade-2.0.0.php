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

function upgrade_module_2_0_0($module)
{
    if($module->unregisterHook('displayHeader') &&
        $module->unregisterHook('displayBackOfficeHeader') &&
        $module->registerHook('actionAdminControllerSetMedia') &&
        $module->registerHook('actionFrontControllerSetMedia'))
    {
        
        $moduleLocalPath = _PS_MODULE_DIR_ . $module->name . '/';
        $modulePath = __PS_BASE_URI__ . 'modules/' . $module->name . '/';
        
        $toDelete = [
            'classes/SggMapCall.php',
            'classes/index.php',
            'classes',
            'views/js/back-ggmap.js',
            'views/js/front-ggmap.js',
            'views/css/back-ggmap.css',
            'views/css/ggmap.css',
            'views/templates/hook/storeggmap_detail.tpl',
            'storeggmapCall.php',
        ];
        
        foreach($toDelete as $dataPath)
        {
            if(!file_exists($moduleLocalPath.$dataPath))
            {
                continue;
            }
            
            if (is_dir($moduleLocalPath.$dataPath)) 
            {
                if(!rmdir($moduleLocalPath.$dataPath))
                {
                    PrestaShopLogger::addLog($module->name.' - '.__FUNCTION__.' : unable to delete folder'.$modulePath.$dataPath, 1, null, null, null, true);
                }
                continue;
            }
            
            if(!unlink($moduleLocalPath.$dataPath))
            {
                PrestaShopLogger::addLog($module->name.' - '.__FUNCTION__.' : unable to delete file '.$modulePath.$dataPath, 1, null, null, null, true);
            }
        }
        return true;
    }
    return false;
}