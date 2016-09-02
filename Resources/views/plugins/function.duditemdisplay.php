<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Zikula\UsersModule\Constant as UsersConstant;

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
 * string  userinfo      The userinfo information [if not set uid must be specified].
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

    if (!ModUtil::available('ZikulaProfileModule')) {
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
        $tplset = 'profile_duddisplay';
    }

    // a default value if the user data is empty
    if (!isset($default)) {
        $default = '';
    }

    if (!isset($uid)) {
        $uid = UserUtil::getVar('uid');
    }

    if (!isset($userinfo)) {
        $userinfo = UserUtil::getVars($uid);
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
        $output = ModUtil::apiFunc($item['prop_modname'], 'dud', 'edit', [
            'item' => $item,
            'userinfo' => $userinfo,
            'uservalue' => $uservalue,
            'default' => $default
        ]);
        if ($output) {
            return $output;
        }
    }

    // build the output
    $view->setCaching(0);
    $view->assign('userinfo', $userinfo);
    $view->assign('uservalue', $uservalue);

    // detects the template to use
    $template = $tplset . '_' . $item['prop_id'] . '.tpl';
    if (!$view->template_exists($template)) {
        $template = $tplset . '_generic.tpl';
    }

    $output = '';

    // checks the different attributes and types
    // avatar
    if ($item['prop_attribute_name'] == 'avatar') {
        $baseurl = System::getBaseUrl();
        $avatarpath = ModUtil::getVar(UsersConstant::MODNAME, UsersConstant::MODVAR_AVATAR_IMAGE_PATH, UsersConstant::DEFAULT_AVATAR_IMAGE_PATH);
        if (empty($uservalue)) {
            $uservalue = 'blank.png';
        }

        $output = "<img alt=\"\" src=\"{$baseurl}{$avatarpath}/{$uservalue}\" />";
    } elseif ($item['prop_attribute_name'] == 'tzoffset') {
        // timezone
        if (empty($uservalue)) {
            $uservalue = UserUtil::getVar('tzoffset') ? UserUtil::getVar('tzoffset') : System::getVar('timezone_offset');
        }

        $output = DateUtil::getTimezoneText($uservalue);
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

            $uservalue = (isset($uservalue)) ? (bool)$uservalue : 0;
            $output = __($options[$uservalue], $dom);
        } else {
            $output = $uservalue;
        }

    } elseif ($item['prop_displaytype'] == 3) {
        // radio
        $options = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);

        // process the user value and get the translated label
        $output = isset($options[$uservalue]) ? $options[$uservalue] : $default;
    } elseif ($item['prop_displaytype'] == 4) {
        // select
        $options = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);

        $output = [];
        foreach ((array)$uservalue as $id) {
            if (isset($options[$id])) {
                $output[] = $options[$id];
            }
        }
    } elseif (!empty($uservalue) && $item['prop_displaytype'] == 5) {
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
        $date = new DateTime($uservalue);
        $output = $date->format($dateformat);
    } elseif ($item['prop_displaytype'] == 7) {
        // multicheckbox
        $options = ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);

        // process the user values and get the translated label
        $uservalue = @unserialize($uservalue);

        $output = [];
        foreach ((array)$uservalue as $id) {
            if (isset($options[$id])) {
                $output[] = $options[$id];
            }
        }
    } elseif ($item['prop_attribute_name'] == 'url') {
        // url
        if (!empty($uservalue) && $uservalue != 'http://') {
            //! string to describe the user's site
            $output = '<a href="' . DataUtil::formatForDisplay($uservalue) . '" title="' . __f("%s's site", $userinfo['uname'], $dom) . '" rel="nofollow">' . DataUtil::formatForDisplay($uservalue) . '</a>';
        }
    } elseif (empty($uservalue)) {
        // process the generics
        $output = $default;
    } elseif (DataUtil::is_serialized($uservalue) || is_array($uservalue)) {
        // serialized data
        $uservalue = !is_array($uservalue) ? unserialize($uservalue) : $uservalue;
        $output = [];
        foreach ((array)$uservalue as $option) {
            $output[] = __($option, $dom);
        }
    } else {
        // a string
        $output .= __($uservalue, $dom);
    }

    $view->assign('item', $item);

    // omit this field if is empty after the process
    if (empty($output)) {
        return '';
    }

    return $view->assign('output', is_array($output) ? $output : [$output])
                ->fetch($template);
}
