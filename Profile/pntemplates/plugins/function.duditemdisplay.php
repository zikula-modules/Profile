<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link         http://www.zikula.org
 * @version      $Id: function.duditemdisplay.php 370 2009-11-25 10:44:01Z mateo $
 * @license      GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package      Zikula_System_Modules
 * @subpackage   Profile
 */

/**
 * Smarty function to display an editable dynamic user data field
 *
 * Example
 * <!--[duditemdisplay proplabel='_YICQ']-->
 *
 * Example
 * <!--[duditemdisplay proplabel='_YICQ' uid=$uid]-->
 *
 * Example
 * <!--[duditemdisplay propattribute='signature']-->
 *
 * Example
 * <!--[duditemdisplay item=$item]-->
 *
 * @author       Mateo Tibaquira
 * @since        25/11/09
 * @see          function.exampleadminlinks.php::smarty_function_exampleadminlinks()
 * @param        array       $params            All attributes passed to this function from the template
 * @param        object      &$smarty           Reference to the Smarty object
 * @param        string      $item              The Profile DUD item
 * @param        string      $userinfo          The userinfo information [if not set uid must be specified]
 * @param        string      $uid               User ID to display the field value for (-1 = do not load)
 * @param        string      $proplabel         Property label to display (optional overrides the preformated dud item $item)
 * @param        string      $propattribute     Property attribute to display
 * @param        string      $default           Default content for an empty DUD
 * @return       string      the results of the module function
 */
function smarty_function_duditemdisplay($params, &$smarty)
{
    extract($params);
    unset($params);

    if (!pnModAvailable('Profile')) {
        return;
    }

    if (!isset($item)) {
        if (isset($proplabel)) {
            $item = pnModAPIFunc('Profile', 'user', 'get', array('proplabel' => $proplabel));
        } else if (isset($propattribute)) {
            $item = pnModAPIFunc('Profile', 'user', 'get', array('propattribute' => $propattribute));
        } else {
            return;
        }
    }

    if (!isset($item) || empty ($item)) {
        return;
    }

    $dom = ZLanguage::getModuleDomain('Profile');

    if (!isset($default)) {
        $default = '';
    }

    if (!isset($uid)) {
        $uid = pnUserGetVar('uid');
    }

    if (!isset($userinfo)) {
        $userinfo = pnUserGetVars($uid);
    }

    // get the value of this field from the userinfo array
    if (isset($userinfo['__ATTRIBUTES__'][$item['prop_attribute_name']])) {
        $uservalue = $userinfo['__ATTRIBUTES__'][$item['prop_attribute_name']];

    } elseif (isset($userinfo[$item['prop_label']])) {
        // fool check
        $uservalue = $userinfo[$item['prop_label']];

    } else {
        return '';
    }

    // build the output
    $output = '';


    // checks the different attributes and types
    // avatar
    if ($item['prop_attribute_name'] == 'avatar') {
        if (empty($uservalue)) {
            $uservalue = 'blank.gif';
        }

        $avatar = pnModGetVar('Users', 'avatarpath', 'images/avatar');

        // TODO build the avatar IMG
        $output = '';


    // timezone
    } elseif ($item['prop_attribute_name'] == 'tzoffset') {
        if (empty($uservalue)) {
            $uservalue = pnUserGetVar('tzoffset') ? pnUserGetVar('tzoffset') : pnConfigGetVar('timezone_offset');
        }
        $tzinfo = pnModGetVar(PN_CONFIG_MODULE, 'timezone_info');

	    if (!isset($tzinfo[$uservalue])) {
	        return '';
	    }

	    // FIXME DateUtil::getTimezoneName($uservalue); in 1.2.1
	    $output = DataUtil::formatForDisplay($tzinfo[$uservalue]);


    // date and extdate
    } elseif (!empty($uservalue) && ($item['prop_displaytype'] == 5 || $item['prop_displaytype'] == 6)) {
        $output = DateUtil::getDatetime(strtotime($uservalue), 'datelong');


    // url
    } elseif ($item['prop_attribute_name'] == 'url') {
        $output = '<a href="'.DataUtil::formatForDisplay($uservalue).'" title="'.__f("%s's website URL", $userinfo['uname'], $dom).'" rel="nofollow">'.DataUtil::formatForDisplay($uservalue).'</a>';


    // process the generics
    } elseif (empty($uservalue)) {
        $output = $default;


    // serialized data
    } elseif (DataUtil::is_serialized($uservalue)) {
        $uservalue = unserialize($uservalue);
        foreach ($uservalue as $option) {
            $output .= '<span class="z-formnote">'.__($option, $dom).'</span>';
        }
        // needs to return to not read the z-formnote
        return $output;


    // a string
    } else {
        $output .= __($uservalue, $dom);
    }

    return '<span class="z-formnote">'.$output.'</span>';
}
