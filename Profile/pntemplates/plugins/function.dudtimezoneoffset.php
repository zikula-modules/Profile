<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.dudtimezoneoffset.php 335 2009-11-09 06:52:03Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Dynamic User data Module
 *
 * @package      Zikula_System_Modules
 * @subpackage   Profile
 */

/**
 * Smarty function to display a timezone drop-down
 *
 * Example
 * <!--[dudtimezoneoffset zone='12.0']-->
 *
 * @author       FC
 * @since        10/08/04
 * @see          function.exampleadminlinks.php::smarty_function_exampleadminlinks()
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @param        string      $zone        The zone
 * @return       string      the results of the module function
 */
function smarty_function_dudtimezoneoffset($params, &$smarty)
{
    extract($params);
    unset($params);

    if (!pnModAvailable('Profile')) {
        return;
    }

    if (!isset($zone) || empty($zone)) {
        // Getting user or site zone as default
        $zone = pnUserGetVar('tzoffset') ? pnUserGetVar('tzoffset') : pnConfigGetVar('timezone_offset');
    }

    $tzinfo = pnModGetVar(PN_CONFIG_MODULE, 'timezone_info');

    if (isset($tzinfo[$zone])) {
        return DataUtil::formatForDisplay($tzinfo[$zone]);
    } else {
        return;
    }
}
