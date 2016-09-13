<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use DataUtil;
use DateUtil;
use ModUtil;
use ServiceUtil;
use System;
use UserUtil;
use Zikula\UsersModule\Constant as UsersConstant;
use ZLanguage;

/**
 * Smarty function to display an editable dynamic user data field.
 *
 * Example
 * {duditemdisplay propattribute='avatar'}
 *
 * Example
 * {duditemdisplay propattribute='realname' uid=$uid}
 *
 * Example
 * {duditemdisplay item=$item}
 *
 * Parameters passed in the $params array:
 * ---------------------------------------
 * string  item          The Profile DUD item.
 * string  userInfo      The userinfo information [if not set uid must be specified].
 * string  uid           User ID to display the field value for (-1 = do not load).
 * string  proplabel     Property label to display (optional overrides the preformated dud item $item).
 * string  propattribute Property attribute to display.
 * string  default       Default content for an empty DUD.
 * boolean showlabel     Show the label? default = true.
 *
 * @param array $params All attributes passed to this function from the template.
 * @param Zikula_View $view Reference to the Zikula_View/Smarty object.
 *
 * @return string|boolean The results of the module function; empty string if the Profile module is not available; false if error.
 */
function smarty_function_duditemdisplay($params, Zikula_View $view)
{
    extract($params);
    unset($params);

    $sm = ServiceUtil::getManager();

    if (null === $sm->get('zikula_extensions_module.api.extension')->getModuleInstanceOrNull('ZikulaProfileModule')) {
        return '';
    }

    if (!isset($item)) {
        if (isset($proplabel)) {
            $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $proplabel]);
        } elseif (isset($propattribute)) {
            $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $propattribute]);
        } else {
            return false;
        }
    }

    if (!isset($item) || empty ($item)) {
        return false;
    }

    $dom = ZLanguage::getModuleDomain('ZikulaProfileModule');

    // check for a template set
    if (!isset($tplset)) {
        $tplset = 'duddisplay';
    }

    // a default value if the user data is empty
    if (!isset($default)) {
        $default = '';
    }

    if (!isset($uid)) {
        $uid = UserUtil::getVar('uid');
    }

    if (!isset($userInfo)) {
        $userInfo = UserUtil::getVars($uid);
    }

    // get the value of this field from the userinfo array
    if (isset($userInfo['__ATTRIBUTES__'][$item['prop_attribute_name']])) {
        $userValue = $userInfo['__ATTRIBUTES__'][$item['prop_attribute_name']];
    } elseif (isset($userInfo[$item['prop_attribute_name']])) {
        // user's temp view for non-approved users needs this
        $userValue = $userInfo[$item['prop_attribute_name']];
    } else {
        // can be a non-marked checkbox in the user temp data
        $userValue = '';
    }

    // try to get the DUD output if it's Third Party
    if ($item['prop_dtype'] != 1) {
        $output = ModUtil::apiFunc($item['prop_modname'], 'dud', 'edit', [
            'item' => $item,
            'userinfo' => $$userInfo,
            'uservalue' => $userValue,
            'default' => $default
        ]);
        if ($output) {
            return $output;
        }
    }

    // build the output
    $view->setCaching(Zikula_View::CACHE_DISABLED);
    $view->assign('userinfo', $userinfo)
         ->assign('uservalue', $userValue);

    // detects the template to use
    // TODO refactor to Twig
    /*$template = $tplset . '_' . $item['prop_id'] . '.html.twig';
    if (!$view->template_exists($template)) {*/
        $template = $tplset . '_generic.html.twig';
    /*}*/

    $output = '';

    // checks the different attributes and types
    // avatar
    if ($item['prop_attribute_name'] == 'avatar') {
        $baseurl = System::getBaseUrl();
        $avatarPath = ModUtil::getVar(UsersConstant::MODNAME, UsersConstant::MODVAR_AVATAR_IMAGE_PATH, UsersConstant::DEFAULT_AVATAR_IMAGE_PATH);
        if (empty($userValue)) {
            $userValue = 'blank.png';
        }

        $output = '<img alt="" src="' . $baseurl . $avatarPath . '/' . $userValue . '" />';
    } elseif ($item['prop_attribute_name'] == 'tzoffset') {
        // timezone
        if (empty($userValue)) {
            $userValue = UserUtil::getVar('tzoffset') ? UserUtil::getVar('tzoffset') : System::getVar('timezone_offset');
        }

        $output = DateUtil::getTimezoneText($userValue);
        if (!$output) {
            return '';
        }
    } elseif ($item['prop_displaytype'] == 2) {
        // checkbox
        $item['prop_listoptions'] = (empty($item['prop_listoptions'])) ? '@@No@@Yes' : $item['prop_listoptions'];

        if (!empty($item['prop_listoptions'])) {
            $options = array_values(array_filter(explode('@@', $item['prop_listoptions'])));

            /**
             * Detect if the list options include the modification of the label.
             */
            if (substr($item['prop_listoptions'], 0, 2) != '@@') {
                $label = array_shift($options);
                $item['prop_label'] = __($label, $dom);
            }

            $userValue = (isset($userValue)) ? (bool)$userValue : 0;
            $output = __($options[$userValue], $dom);
        } else {
            $output = $userValue;
        }

    } elseif ($item['prop_displaytype'] == 3) {
        // radio
        $options = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);

        // process the user value and get the translated label
        $output = isset($options[$userValue]) ? $options[$userValue] : $default;
    } elseif ($item['prop_displaytype'] == 4) {
        // select
        $options = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);

        $output = [];
        foreach ((array)$userValue as $id) {
            if (isset($options[$id])) {
                $output[] = $options[$id];
            }
        }
    } elseif (!empty($userValue) && $item['prop_displaytype'] == 5) {
        // date
        $format = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);
        switch (trim(strtolower($format))) {
            case 'us':
                $dateformat = 'F j, Y';
                break;
            case 'db':
                $dateformat = 'Y-m-d';
                break;
            default:
            case 'eur':
                $dateformat = 'j F Y';
                break;
        }
        $date = new DateTime($userValue);
        $output = $date->format($dateformat);
    } elseif ($item['prop_displaytype'] == 7) {
        // multicheckbox
        $options = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);

        // process the user values and get the translated label
        $userValue = @unserialize($userValue);

        $output = [];
        foreach ((array)$userValue as $id) {
            if (isset($options[$id])) {
                $output[] = $options[$id];
            }
        }
    } elseif ($item['prop_attribute_name'] == 'url') {
        // url
        if (!empty($userValue) && $userValue != 'http://') {
            //! string to describe the user's site
            $output = '<a href="' . DataUtil::formatForDisplay($userValue) . '" title="' . __f("%s's site", $userinfo['uname'], $dom) . '" rel="nofollow">' . DataUtil::formatForDisplay($userValue) . '</a>';
        }
    } elseif (empty($userValue)) {
        // process the generics
        $output = $default;
    } elseif (DataUtil::is_serialized($userValue) || is_array($userValue)) {
        // serialized data
        $userValue = !is_array($userValue) ? unserialize($userValue) : $userValue;
        $output = [];
        foreach ((array)$userValue as $option) {
            $output[] = __($option, $dom);
        }
    } else {
        // a string
        $output .= __($userValue, $dom);
    }

    $view->assign('item', $item);

    // omit this field if is empty after the process
    if (empty($output)) {
        return '';
    }

    return $view->assign('output', is_array($output) ? $output : [$output])
                ->fetch($template);
}
