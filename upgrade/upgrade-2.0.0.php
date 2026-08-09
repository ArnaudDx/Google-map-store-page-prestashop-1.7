<?php
/**
 * storeggmap - Show your stores on a Google Map
 *
 * @author    Arnaud Drieux <contact@awb-dsgn.com>
 * @copyright 2026 awb-dsgn.com
 * @license   https://opensource.org/licenses/AFL-3.0 AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($module)
{
    if ($module->unregisterHook('displayHeader') &&
        $module->unregisterHook('displayBackOfficeHeader') &&
        $module->registerHook('actionAdminControllerSetMedia') &&
        $module->registerHook('actionFrontControllerSetMedia')) {
        
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
        
        foreach ($toDelete as $dataPath) {
            if (!file_exists($moduleLocalPath . $dataPath)) {
                continue;
            }
            
            if (is_dir($moduleLocalPath . $dataPath)) {
                if (!rmdir($moduleLocalPath . $dataPath)) {
                    PrestaShopLogger::addLog($module->name . ' - ' . __FUNCTION__ . ' : unable to delete folder' . $modulePath . $dataPath, 1, null, null, null, true);
                }
                continue;
            }
            
            if (!unlink($moduleLocalPath . $dataPath)) {
                PrestaShopLogger::addLog($module->name . ' - ' . __FUNCTION__ . ' : unable to delete file ' . $modulePath . $dataPath, 1, null, null, null, true);
            }
        }
        return true;
    }
    return false;
}