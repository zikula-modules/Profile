<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnajax.php 366 2009-11-23 16:19:37Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
*/

/**
 * change the weight of a profile item
 *
 * @author Mark West
 * @param blockorder array of sorted properties (value = prop_id)
 * @return mixed true or Ajax error
 */
function Profile_ajax_changeprofileweight()
{
    $dom = ZLanguage::getModuleDomain('Profile');

    if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('No authorization to access this module.', $dom));
    }

    if (!SecurityUtil::confirmAuthKey()) {
        AjaxUtil::error(__("Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again.", $dom));
    }

    $profilelist = FormUtil::getPassedValue('profilelist');
    $startnum = FormUtil::getPassedValue('startnum');

    // update the items with the new weights
    $items = array();
    $weight = $startnum + 1;
    foreach ($profilelist as $prop_id)
    {
        if (empty($prop_id)) {
            continue;
        }

        $items[] = array('prop_id' => $prop_id,
                         'prop_weight' => $weight);
        $weight++;
    }

    // update the db
    $res = DBUtil::updateObjectArray($items, 'user_property', 'prop_id');

    if (!$res) {
        AjaxUtil::error(__('Error! Update attempt failed.', $dom));
    }

    return array('result' => true);
}

/**
 * change the status of a profile item
 *
 * @author Mateo Tibaquira
 * @param  dudid id of the property to update
 * @param  oldstatus to activate or deactivate the item
 * @return mixed true or Ajax error
 */
function Profile_ajax_changeprofilestatus()
{
    $dom = ZLanguage::getModuleDomain('Profile');

    if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('No authorization to access this module.', $dom));
    }

    if (!SecurityUtil::confirmAuthKey()) {
        //AjaxUtil::error(__("Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again.", $dom));
    }

    $prop_id   = FormUtil::getPassedValue('dudid');
    $oldstatus = (bool)FormUtil::getPassedValue('oldstatus');

    if (!$prop_id) {
        return array('result' => false);
    }

    // update the item status
    $func = ($oldstatus ? 'deactivate' : 'activate');

    $res = pnModAPIFunc('Profile', 'admin', $func, array('dudid' => $prop_id));
    if (!$res) {
        AjaxUtil::error(__('Error! Update attempt failed.', $dom));
    }

    return array('result' => true,
                 'dudid' => $prop_id,
                 'newstatus' => !$oldstatus);
}
