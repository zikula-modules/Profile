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

    } elseif (isset($userinfo[$item['prop_attribute_name']])) {
        // user's temp view for non-approved users needs this
        $uservalue = $userinfo[$item['prop_attribute_name']];

    } else {
        // can be a non-marked checkbox in the user temp data
        $uservalue = '';
    }

    // try to get the DUD output if it's Third Party
    if ($item['prop_dtype'] != 1) {
        $output = pnModAPIFunc($item['prop_modname'], 'dud', 'edit',
                               array('item'      => $item,
                                     'userinfo'  => $userinfo,
                                     'uservalue' => $uservalue,
                                     'default'   => $default));
        if ($output) {
            return $output;
        }
    }

    // build the output
    $output = '';
    $render = & pnRender::getInstance('Profile', false, null, true);
    $render->assign('item',      $item);
    $render->assign('userinfo',  $userinfo);
    $render->assign('uservalue', $uservalue);

    // detects the template to use
    $template = 'profile_duddisplay_'.$item['prop_id'].'.htm';
    if (!$render->template_exists($template)) {
        $template = 'profile_duddisplay_generic.htm';
    }


    // checks the different attributes and types
    // avatar
    if ($item['prop_attribute_name'] == 'avatar') {
        $baseurl = pnGetBaseURL();
        $avatarpath = pnModGetVar('Users', 'avatarpath', 'images/avatar');
        if (empty($uservalue)) {
            $uservalue = 'blank.gif';
        }

        // TODO build the avatar IMG
        $output = "<img alt=\"\" src=\"{$baseurl}{$avatarpath}/{$uservalue}\" />";


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


    // checkbox
    } elseif ($item['prop_displaytype'] == 2) {
        $default = array('No', 'Yes');
        $output  = explode('@@', $item['prop_listoptions']);
        if (!is_array($output) || count($output) < 2) {
            $output = $default;
        }
        $output = isset($output[(int)$uservalue]) && !empty($output[(int)$uservalue]) ? __($output[(int)$uservalue], $dom) : __($default[(int)$uservalue], $dom);


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
    } elseif (DataUtil::is_serialized($uservalue) || is_array($uservalue)) {
        $uservalue = !is_array($uservalue) ? unserialize($uservalue) : $uservalue;
        foreach ($uservalue as $option) {
            $output .= '<span class="z-formnote">'.__($option, $dom).'</span>';
        }
        // needs to return to not read the z-formnote
        $render->assign('output', $output);
        return $render->fetch($template);


    // a string
    } else {
        $output .= __($uservalue, $dom);
    }

    $render->assign('output', '<span class="z-formnote">'.$output.'</span>');

    return $render->fetch($template);
}
