<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.duditemmodify.php 121 2010-06-03 05:02:54Z drak $
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
 * <!--[duditemmodify propattribute='avatar']-->
 *
 * Example
 * <!--[duditemmodify propattribute='realname' uid=$uid]-->
 *
 * Example
 * <!--[duditemmodify item=$item]-->
 *
 * @author       Mark West
 * @since        21/01/04
 * @see          function.exampleadminlinks.php::smarty_function_exampleadminlinks()
 * @param        array       $params            All attributes passed to this function from the template
 * @param        object     &$smarty            Reference to the Smarty object
 * @param        string      $item              The Profile DUD item
 * @param        string      $uid               User ID to display the field value for (-1 = do not load)
 * @param        string      $class             CSS class to assign to the table row/form row div (optional)
 * @param        string      $proplabel         Property label to display (optional overrides the preformated dud item $item)
 * @param        string      $propattribute     Property attribute to display
 * @param        string      error              Property error message
 * @return       string      the results of the module function
 */
function smarty_function_duditemmodify($params, &$smarty)
{
    extract($params);
    unset($params);

    if (!ModUtil::available('Profile')) {
        return;
    }

    if (!isset($item)) {
        if (isset($proplabel)) {
            $item = ModUtil::apiFunc('Profile', 'user', 'get', array('proplabel' => $proplabel));
        } else if (isset($propattribute)) {
            $item = ModUtil::apiFunc('Profile', 'user', 'get', array('propattribute' => $propattribute));
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
    if (ModUtil::getName() == 'Users' && $func == 'register') {
        $onregistrationform = true;
    }

    // skip the field if not configured to be on the registration form 
    if ($onregistrationform && !$item['prop_required']) {
        $dudregshow = ModUtil::getVar('Profile', 'dudregshow', array());
        if (!in_array($item['prop_id'], $dudregshow)) {
            return '';
        }
    }

    $dom = ZLanguage::getModuleDomain('Profile');

    if (!isset($uid)) {
        $uid = UserUtil::getVar('uid');
    }
    if (!isset($class) || !is_string($class)) {
        $class = '';
    }

    if (isset($item['temp_propdata'])) {
        $uservalue = $item['temp_propdata'];
    } elseif ($uid >= 0) {
        // TODO - This is a bit of a hack for admin editing. Need to know if it is a reg.
        $user = UserUtil::getVars($uid);
        $isRegistration = UserUtil::isRegistration($uid);
        $uservalue = UserUtil::getVar($item['prop_attribute_name'], $uid, false, $isRegistration); // ($alias, $uid);
    }

    // try to get the DUD output if it's Third Party
    if ($item['prop_dtype'] != 1) {
        $output = ModUtil::apiFunc($item['prop_modname'], 'dud', 'edit',
                               array('item'      => $item,
                                     'uservalue' => $uservalue,
                                     'class'     => $class));
        if ($output) {
            return $output;
        }
    }

    $render = Zikula_View::getInstance('Profile', false, null, true);

    // assign the default values for the control
    $render->assign('class',         $class);
    $render->assign('value',         DataUtil::formatForDisplay($uservalue));
    $render->assign('attributename', $item['prop_attribute_name']);
    $render->assign('proplabeltext', $item['prop_label']);
    $render->assign('note',          $item['prop_note']);
    $render->assign('required',      (bool)$item['prop_required']);
    $render->assign('error',         isset($error) ? $error : '');

    // Excluding Timezone of the generics
    if ($item['prop_attribute_name'] == 'tzoffset') {
        if (empty($uservalue)) {
            $uservalue = UserUtil::getVar('tzoffset') ? UserUtil::getVar('tzoffset') : System::getVar('timezone_offset');
        }

        $tzinfo = DateUtil::getTimezones();

        $render->assign('value',          isset($tzinfo["$uservalue"]) ? "$uservalue" : null);
        $render->assign('selectmultiple', '');
        $render->assign('listoptions',    array_keys($tzinfo));
        $render->assign('listoutput',     array_values($tzinfo));
        return $render->fetch('profile_dudedit_select.tpl');
    }

    if ($item['prop_attribute_name'] == 'avatar') {
        // detect if it's the registration form to skip this
        if ($onregistrationform) {
            return '';
        }

        // only shows a link to the Avatar module if available
        if (ModUtil::available('Avatar')) {
            // TODO Add a change-link to the admins
            // only shows the link for the own user
            if (UserUtil::getVar('uid') != $uid) {
                return '';
            }
            $render->assign('linktext', __('Go to the Avatar manager', $dom));
            $render->assign('linkurl', ModUtil::url('Avatar'));
            $output = $render->fetch('profile_dudedit_link.tpl');
            // add a hidden input if this is required
            if ($item['prop_required']) {
                $output .= $render->fetch('profile_dudedit_hidden.tpl');
            }
            return $output;
        }

        // display the avatar selector
        if (empty($uservalue)) {
            $uservalue = 'blank.gif';
        }
        $render->assign('value', DataUtil::formatForDisplay($uservalue));

        $avatarPath = ModUtil::getVar(Users_Constant::MODNAME, Users_Constant::MODVAR_AVATAR_IMAGE_PATH, Users_Constant::DEFAULT_AVATAR_IMAGE_PATH);
        $filelist = FileUtil::getFiles($avatarPath, false, true, array('gif', 'jpg', 'png'), 'f');
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
        return $render->fetch('profile_dudedit_select.tpl');
    }

    switch ($item['prop_displaytype'])
    {
        case 0: // TEXT
            $type = 'text';
            break;

        case 1: // TEXTAREA
            $type = 'textarea';
            break;

        case 2: // CHECKBOX
            $type = 'checkbox';

            $editlabel = array_splice(explode('@@', $item['prop_listoptions']), 0, 1);
            if (!empty($editlabel[0])) {
                $render->assign('proplabeltext', __($editlabel[0], $dom));
            }
            break;

        case 3: // RADIO
            $type = 'radio';

            $options = ModUtil::apiFunc('Profile', 'dud', 'getoptions', array('item' => $item));

            $render->assign('listoptions', array_keys($options));
            $render->assign('listoutput', array_values($options));
            break;

        case 4: // SELECT
            $type = 'select';
            if (DataUtil::is_serialized($uservalue)) {
                $render->assign('value', unserialize($uservalue));
            }

            // multiple flag is the first field
            $options = explode('@@', $item['prop_listoptions'], 2);
            $selectmultiple = $options[0] ? ' multiple="multiple"' : '';
            $render->assign('selectmultiple', $selectmultiple);

            $options = ModUtil::apiFunc('Profile', 'dud', 'getoptions', array('item' => $item));

            $render->assign('listoptions', array_keys($options));
            $render->assign('listoutput', array_values($options));
            break;

        case 5: // DATE
            $type = 'date';

            // gets the format to use
            $format = ModUtil::apiFunc('Profile', 'dud', 'getoptions', array('item' => $item));
            
            switch (trim(strtolower($format)))
            {
                case 'datelong':
                    //! This is from the core domain (datelong)
                    $format = __('%A, %B %d, %Y');
                    break;
                case 'datebrief':
                    //! This is from the core domain (datebrief)
                    $format = __('%b %d, %Y');
                    break;
                case 'datestring':
                    //! This is from the core domain (datestring)
                    $format = __('%A, %B %d @ %H:%M:%S');
                    break;
                case 'datestring2':
                    //! This is from the core domain (datestring2)
                    $format = __('%A, %B %d');
                    break;
                case 'datetimebrief':
                    //! This is from the core domain (datetimebrief)
                    $format = __('%b %d, %Y - %I:%M %p');
                    break;
                case 'datetimelong':
                    //! This is from the core domain (datetimelong)
                    $format = __('%A, %B %d, %Y - %I:%M %p');
                    break;
                case 'timebrief':
                    //! This is from the core domain (timebrief)
                    $format = __('%I:%M %p');
                    break;
                case 'timelong':
                    //! This is from the core domain (timelong)
                    $format = __('%T %p');
                    break;
            }
            //! This is from the core domain (datebrief)
            $format = !empty($format) ? $format : __('%b %d, %Y');

            // process the temporal data if any
            $timestamp = null;
            if (isset($item['temp_propdata'])) {
                $timestamp = DateUtil::parseUIDate($item['temp_propdata']);
                $uservalue = DateUtil::transformInternalDate($timestamp);
            } elseif (!empty($uservalue)) {
                $timestamp = DateUtil::makeTimestamp($uservalue);
            }

            $render->assign('value',     $uservalue);
            $render->assign('timestamp', $timestamp);
            $render->assign('dudformat', $format);
            break;

        case 6: // EXTDATE (deprecated)
            // TODO [deprecate completely]
            $type = 'hidden';
            break;

        case 7: // MULTICHECKBOX
            $type = 'multicheckbox';
            $render->assign('value', (array)unserialize($uservalue));

            $options = ModUtil::apiFunc('Profile', 'dud', 'getoptions', array('item' => $item));

            $render->assign('fields', $options);
            break;

        default: // TEXT
            $type = 'text';
            break;
    }

    return $render->fetch('profile_dudedit_'.$type.'.tpl');
}