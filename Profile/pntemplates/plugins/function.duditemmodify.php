<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.duditemmodify.php 370 2009-11-25 10:44:01Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Dynamic User data Module
 *
 * @package      Zikula_System_Modules
 * @subpackage   Profile
 */

/**
 * Smarty function to display an editable dynamic user data field
 *
 * Example
 * <!--[duditemmodify proplabel="_YICQ"]-->
 *
 * Example
 * <!--[duditemmodify proplabel="_YICQ" uid=$uid]-->
 *
 * Example
 * <!--[duditemmodify propattribute="signature"]-->
 *
 * Example
 * <!--[duditemmodify item=$item]-->
 *
 * @author       Mark West
 * @since         21/01/04
 * @see            function.exampleadminlinks.php::smarty_function_exampleadminlinks()
 * @param        array       $params            All attributes passed to this function from the template
 * @param        object     &$smarty            Reference to the Smarty object
 * @param        string      $item              The Profile DUD item
 * @param        string      $uid               User ID to display the field value for (-1 = do not load)
 * @param        string      $tableless         Don't use tables to render the markup (optional - default true)
 * @param        string      $class             CSS class to assign to the table row/form row div (optional)
 * @param        string      $proplabel         Property label to display (optional overrides the preformated dud item $item)
 * @param        string      $propattribute     Property attribute to display
 * @param        string      $mode              Display mode: 'normal' = normal editing, 'simple' = simplified for search window
 * @return       string      the results of the module function
 */
function smarty_function_duditemmodify($params, &$smarty)
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

    // detect if we are in the registration form
    $onregistrationform = false;
    $func = FormUtil::getPassedValue('func', 'main');
    if (pnModGetName() == 'Users' && $func == 'register') {
        $onregistrationform = true;
    }

    // TODO: skip the field if not configured to be on the registration form 

    $dom = ZLanguage::getModuleDomain('Profile');

    if (!isset($uid)) {
        $uid = pnUserGetVar('uid');
    }
    if (!isset($tableless) || !is_bool($tableless)) {
        $tableless = true;
    }
    if (!isset($class) || !is_string($class)) {
        $class = '';
    }
    if (!isset($mode) || empty ($mode)) {
        $mode = 'normal'; // alternative is 'simple'
    }
    if (!in_array($mode, array('normal', 'simple'))) {
        return __f('Unknown \'%1$s\' value [%2$s] in duditem', array('mode', $mode), $dom);
    }

    if (isset($item['temp_propdata'])) {
        $uservalue = $item['temp_propdata'];
    } elseif ($uid >= 0) {
        $uservalue = pnUserGetVar($item['prop_attribute_name'], $uid); // ($alias, $uid);
    }
    
    $render = & pnRender::getInstance('Profile', false, null, true);

    // assign the default values for the control
    $render->assign('tableless',     $tableless);
    $render->assign('class',         $class);
    $render->assign('value',         DataUtil::formatForDisplay($uservalue));
    $render->assign('prop_attribute_name', DataUtil::formatforDisplay($item['prop_attribute_name']));
    $render->assign('proplabeltext', $item['prop_label']);
    $render->assign('required',      $item['prop_required']);
    $render->assign('note',          $item['prop_note']);
    $render->assign('mode',          $mode);
    $render->assign('properror',     isset($item['prop_error']) ? $item['prop_error'] : '');
    $render->assign('tempdata',      isset($item['temp_propdata']) ? $item['temp_propdata'] : '');

    // Excluding Timezone of the generics
    if ($item['prop_attribute_name'] == 'tzoffset') {
        if (empty($uservalue)) {
            $uservalue = pnUserGetVar('tzoffset') ? pnUserGetVar('tzoffset') : pnConfigGetVar('timezone_offset');
        }

        $tzinfo = pnModGetVar(PN_CONFIG_MODULE, 'timezone_info');

        $render->assign('value',          isset($tzinfo[$uservalue]) ? $uservalue : null);
        $render->assign('selectmultiple', '');
        $render->assign('listoptions',    array_keys($tzinfo));
        $render->assign('listoutput',     array_values($tzinfo));
        return $render->fetch('profile_dudedit_select.htm');
    }

    if ($item['prop_attribute_name'] == 'avatar') {
        // detect if it's the registration form to skip this
        if ($onregistrationform) {
            return '';
        }

        // only shows a link to the Avatar module if available
        if (pnModAvailable('Avatar')) {
            $render->assign('linktext', __('Change your Avatar'));
            $render->assign('linkurl', pnModURL('Avatar'));
            $output = $render->fetch('profile_dudedit_link.htm');
            // add a hidden input if this is required
            if ($item['prop_required']) {
                $output .= $render->fetch('profile_dudedit_hidden.htm');
            }
            return $output;
        }

        // display the avatar selector
        if (empty($uservalue)) {
            $uservalue = 'blank.gif';
        }
        $render->assign('value', DataUtil::formatForDisplay($uservalue));

        $filelist = FileUtil::getFiles(pnModGetVar('Users', 'avatarpath', 'images/avatar'), false, true, array('gif', 'jpg', 'png'), 'f');
        asort($filelist);

        $listoutput = $listoptions = $filelist;
        // strip the extension of the output list
        foreach ($listoutput as $k => $output) {
            $listoutput[$k] = substr($output, 0, strrpos($output, '.'));
        }

        $selectedvalue = null;
        if (in_array($uservalue, $filelist)) {
            $selectedvalue = $uservalue;
        }

        $render->assign('value',          $selectedvalue);
        $render->assign('selectmultiple', '');
        $render->assign('listoptions',    $listoptions);
        $render->assign('listoutput',     $listoutput);
        return $render->fetch('profile_dudedit_select.htm');
    }

    switch ($item['prop_displaytype'])
    {
        case 0: // TEXT
            $type = 'text';
            break;

        case 1: // TEXTAREA
            $type = ($mode == 'normal') ? 'textarea' : 'text';
            break;

        case 2: // CHECKBOX
            $type = 'checkbox';
            break;

        case 3: // RADIO
            $type = 'radio';
            $item['prop_listoptions'] = str_replace(Chr(13), '', str_replace(Chr(13), '', $item['prop_listoptions']));

            // extract the options
            $list = array_splice(explode('@@', $item['prop_listoptions']), 1);
            $render->assign('listoptions', $list);

            // translate them if needed
            foreach ($list as $k => $value) {
                $list[$k] = __($value, $dom);
            }
            $render->assign('listoutput', $list);
            break;

        case 4: // SELECT
            $type = 'select';
            $item['prop_listoptions'] = str_replace(Chr(13), '', $item['prop_listoptions']);
            $list = explode('@@', $item['prop_listoptions']);

            // multiple flag is the first field
            $selectmultiple = $list[0] ? ' multiple="multiple"' : false;
            if ($selectmultiple && DataUtil::is_serialized($uservalue)) {
                $render->assign('value', unserialize($uservalue));
            }
            $render->assign('selectmultiple', $selectmultiple);

            $list = array_splice($list, 1);
            $render->assign('listoptions', $list);

            // translate them if needed
            foreach ($list as $k => $value) {
                $list[$k] = __($value, $dom);
            }
            $render->assign('listoutput', $list);
            break;

        case 5: // DATE
            $type = 'date';
            break;

        case 6: // EXTDATE
            $type = 'extdate';
            $dateArray = explode('-', $uservalue);
            if ($dateArray[0] == '') {
                $dateArray = array(0 => '', 1 => '', 2 => '');
            }
            $render->assign('value', $dateArray);
            break;

        case 7: // MULTICHECKBOX
            $type = 'multicheckbox';
            $render->assign('value', (array)unserialize($uservalue));

            $first_break  = ';';
            $second_break = ',';

            $combos = explode($first_break, $item['prop_listoptions']);
            $combos = array_filter($combos);

            $array = array();
            foreach ($combos as $combo) {
                list($id, $value) = explode($second_break, $combo);
                $array[$id] = __($value, $dom);
            }

            $render->assign('fields', $array);
            break;

        default: // TEXT
            $type = 'text';
            break;
    }

    return $render->fetch('profile_dudedit_'.$type.'.htm');
}
