<?php
/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

use Symfony\Component\HttpFoundation\Request;
use Zikula\Module\ProfileModule\Constant as ProfileConstant;
use Zikula\Module\UsersModule\Constant as UsersConstant;

/**
 * Smarty function to display an editable dynamic user data field.
 *
 * Example
 * {duditemmodify propattribute='avatar'}
 *
 * Example
 * {duditemmodify propattribute='realname' uid=$uid}
 *
 * Example
 * {duditemmodify item=$item}
 *
 * Example
 * {duditemmodify item=$item field_name=false}
 *
 * Parameters passed in via the $params array:
 * -------------------------------------------
 * string item The Profile DUD item.
 * string uid User ID to display the field value for (-1 = do not load).
 * string class CSS class to assign to the table row/form row div (optional).
 * string proplabel Property label to display (optional overrides the preformated dud item $item).
 * string propattribute Property attribute to display.
 * string error Property error message.
 * bool|string field_name The name of the array of elements that comprise the fields. Defaults to "dynadata[example]".
 *        Set to FALSE for fields without an array.
 *
 * @param array $params All attributes passed to this function from the template.
 * @param object $view Reference to the Zikula_View object.
 *
 * @return string|boolean The results of the module function; empty string if the Profile module is not available; false if error.
 */
function smarty_function_duditemmodify(array $params = array(), Zikula_View $view)
{

    $request = Request::createFromGlobals();

    extract($params);
    unset($params);

    if (!ModUtil::available(ProfileConstant::MODNAME)) {
        return '';
    }

    if (!isset($item)) {
        if (isset($proplabel)) {
            $item = ModUtil::apiFunc(ProfileConstant::MODNAME, 'user', 'get', array('proplabel' => $proplabel));
        } else if (isset($propattribute)) {
            $item = ModUtil::apiFunc(ProfileConstant::MODNAME, 'user', 'get', array('propattribute' => $propattribute));
        } else {
            return false;
        }
    }
    if (!isset($item) || empty($item)) {
        return false;
    }

    // detect if we are in the registration form
    $onregistrationform = false;

    if ((strtolower($request->query->get('module')) == 'users')
        && (strtolower($request->query->get('type')) == 'user')
        && (strtolower($request->query->get('func')) == 'register')) {
            $onregistrationform = true;
    }

    // skip the field if not configured to be on the registration form 
    if (($onregistrationform) && (!$item['prop_required'])) {
        $dudregshow = ModUtil::getVar(ProfileConstant::MODNAME, 'dudregshow', array());

        if (!in_array($item['prop_attribute_name'], $dudregshow)) {
            return;
        }
    }

    $dom = ZLanguage::getModuleDomain(ProfileConstant::MODNAME);

    if (!isset($uid)) {
        $uid = UserUtil::getVar('uid');
    }
    if (!isset($class) || !is_string($class)) {
        $class = '';
    }

    if (isset($item['temp_propdata'])) {
        $uservalue = $item['temp_propdata'];
    } elseif ($uid >= 0) {
        // @todo - This is a bit of a hack for admin editing. Need to know if it is a reg.
        $user = UserUtil::getVars($uid);
        $isRegistration = UserUtil::isRegistration($uid);
        $uservalue = UserUtil::getVar($item['prop_attribute_name'], $uid, false, $isRegistration); // ($alias, $uid);
    }

    // try to get the DUD output if it's Third Party
    if ($item['prop_dtype'] != 1) {
        $output = ModUtil::apiFunc($item['prop_modname'], 'dud', 'edit',
            array('item' => $item,
                'uservalue' => $uservalue,
                'class' => $class));
        if ($output) {
            return $output;
        }
    }

    $field_name = ((isset($field_name)) ? ((!$field_name) ? $item['prop_attribute_name'] : $field_name . '[' . $item['prop_attribute_name'] . ']') : 'dynadata[' . $item['prop_attribute_name'] . ']');

    // assign the default values for the control
    $view->assign('class', $class);
    $view->assign('field_name', $field_name);
    $view->assign('value', DataUtil::formatForDisplay($uservalue));

    $view->assign('attributename', $item['prop_attribute_name']);
    $view->assign('proplabeltext', $item['prop_label']);
    $view->assign('note', $item['prop_note']);
    $view->assign('required', (bool)$item['prop_required']);
    $view->assign('error', ((isset($error)) ? $error : ''));

    // Excluding Timezone of the generics
    if ($item['prop_attribute_name'] == 'tzoffset') {
        if (empty($uservalue)) {
            $uservalue = UserUtil::getVar('tzoffset') ? UserUtil::getVar('tzoffset') : System::getVar('timezone_offset');
        }

        $tzinfo = DateUtil::getTimezones();

        $view->assign('value', isset($tzinfo["$uservalue"]) ? "$uservalue" : null);
        $view->assign('selectmultiple', '');
        $view->assign('listoptions', array_keys($tzinfo));
        $view->assign('listoutput', array_values($tzinfo));
        return $view->fetch('Dudedit/select.tpl');
    }

    if ($item['prop_attribute_name'] == 'avatar') {
        // only shows a link to the Avatar module if available
        if (ModUtil::available('Avatar')) {
            // TODO Add a change-link to the admins
            // only shows the link for the own user
            if (UserUtil::getVar('uid') != $uid) {
                return '';
            }
            $view->assign('linktext', __('Go to the Avatar manager', $dom));
            $view->assign('linkurl', ModUtil::url('Avatar', 'user', 'main'));
            $output = $view->fetch('Dudedit/link.tpl');
            // add a hidden input if this is required
            if ($item['prop_required']) {
                $output .= $view->fetch('Dudedit/hidden.tpl');
            }

            return $output;
        }

        // display the avatar selector
        if (empty($uservalue)) {
            $uservalue = 'gravatar.gif';
        }
        $view->assign('value', DataUtil::formatForDisplay($uservalue));
        $avatarPath = ModUtil::getVar(UsersConstant::MODNAME, UsersConstant::MODVAR_AVATAR_IMAGE_PATH, UsersConstant::DEFAULT_AVATAR_IMAGE_PATH);
        $filelist = FileUtil::getFiles($avatarPath, false, true, array('gif', 'jpg', 'png'), 'f');
        asort($filelist);

        $listoutput = $listoptions = $filelist;

        // strip the extension of the output list
        foreach ($listoutput as $k => $output) {
            $listoutput[$k] = $output; //substr($output, 0, strrpos($output, '.'));
        }

        $selectedvalue = $uservalue;
//        if (in_array($uservalue, $filelist)) {
//            $selectedvalue = $uservalue;
//        }

        $view->assign('value', $selectedvalue);
        $view->assign('selectmultiple', '');
        $view->assign('listoptions', $listoptions);
        $view->assign('listoutput', $listoutput);
        return $view->fetch('Dudedit/select.tpl');
    }

    switch ($item['prop_displaytype']) {
        case 0: // TEXT
            $type = 'text';
            break;

        case 1: // TEXTAREA
            $type = 'textarea';
            break;

        case 2: // CHECKBOX
            $type = 'checkbox';

            $options = array('No', 'Yes');

            if (!empty($item['prop_listoptions'])) {
                $options = array_values(array_filter(explode('@@', $item['prop_listoptions'])));

                /**
                 * Detect if the list options include the modification of the label.
                 */
                if (substr($item['prop_listoptions'], 0, 2) != '@@') {
                    $label = array_shift($options);
                    $view->assign('proplabeltext', __($label, $dom));
                }
            }

            break;
        case 3: // RADIO
            $type = 'radio';

            $options = ModUtil::apiFunc(ProfileConstant::MODNAME, 'dud', 'getoptions', array('item' => $item));

            $view->assign('listoptions', array_keys($options));
            $view->assign('listoutput', array_values($options));
            break;

        case 4: // SELECT
            $type = 'select';
            if (DataUtil::is_serialized($uservalue)) {
                $view->assign('value', unserialize($uservalue));
            }

            // multiple flag is the first field
            $options = explode('@@', $item['prop_listoptions'], 2);
            $selectmultiple = $options[0] ? ' multiple="multiple"' : '';
            $view->assign('selectmultiple', $selectmultiple);

            $options = ModUtil::apiFunc(ProfileConstant::MODNAME, 'dud', 'getoptions', array('item' => $item));

            $view->assign('listoptions', array_keys($options));
            $view->assign('listoutput', array_values($options));
            break;

        case 5: // DATE
            $type = 'date';

            // gets the format to use
            $format = ModUtil::apiFunc(ProfileConstant::MODNAME, 'dud', 'getoptions', array('item' => $item));

            switch (trim(strtolower($format))) {
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

            $view->assign('value', $uservalue);
            $view->assign('timestamp', $timestamp);
            $view->assign('dudformat', $format);
            break;

        case 6: // EXTDATE (deprecated)
            // TODO [deprecate completely]
            $type = 'hidden';
            break;

        case 7: // MULTICHECKBOX
            $type = 'multicheckbox';
            $view->assign('value', (array)unserialize($uservalue));

            $options = ModUtil::apiFunc(ProfileConstant::MODNAME, 'dud', 'getoptions', array('item' => $item));

            $view->assign('fields', $options);
            break;

        default: // TEXT
            $type = 'text';
            break;
    }

    return $view->fetch('Dudedit/' . $type . '.tpl');
}
