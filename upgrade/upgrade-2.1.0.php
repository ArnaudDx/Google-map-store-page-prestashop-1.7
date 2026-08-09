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

function upgrade_module_2_1_0($module)
{
    return $module->registerHook('displayAdminOrderSide');
}