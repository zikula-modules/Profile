<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnuserapi.php 370 2009-11-25 10:44:01Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @license http://www.gnu.org/copyleft/gpl.html
*/

/**
 * Get all Dynamic user data fields
 * @author Mark West
 * @param int args['startnum'] starting record number for request
 * @param int args['numitems'] number of records to retrieve
 * @return mixed array of items, or false on failure
 */
function Profile_userapi_getall($args)
{
    // Optional arguments.
    if (!isset($args['startnum'])) {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems'])) {
        $args['numitems'] = -1;
    }

    if (!isset($args['startnum']) || !isset($args['numitems'])) {
        return LogUtil::registerArgsError();
    }

    $items   = array();
    $results = array();

    // Security check
    if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_READ)) {
        return $items;
    }

    // We now generate a where-clause
    $where   = '';
    $orderBy = 'prop_weight';

    $results = DBUtil::selectObjectArray('user_property', $where, $orderBy, $args['startnum'], $args['numitems']);

    // Put items into result array.
    foreach ($results as $item)
    {
        if (SecurityUtil::checkPermission('Profile::', $item['prop_label'].'::'.$item['prop_id'], ACCESS_READ)) {
            // Extract the validation info array
            $validationinfo = @unserialize($item['prop_validation']);

            // Create the item array
            $item['prop_required']      = $validationinfo['required'];
            $item['prop_viewby']        = $validationinfo['viewby'];
            $item['prop_displaytype']   = $validationinfo['displaytype'];
            $item['prop_listoptions']   = $validationinfo['listoptions'];
            $item['prop_note']          = $validationinfo['note'];
            $item['prop_validation']    = $validationinfo['validation'];
            $items[$item['prop_label']] = $item;
        }
    }

    // Return the items
    return $items;
}

/**
 * Get a specific Dynamic user data item
 * @author Mark West
 * @param $args['propid'] id of property to get
 * @return mixed item array, or false on failure
 */
function Profile_userapi_get($args)
{
    // Argument check
    if (!isset($args['propid']) && !isset($args['proplabel']) && !isset($args['propattribute'])) {
        return LogUtil::registerArgsError();
    }

    // Get item with where clause
    if (isset($args['propid'])) {
        $result = DBUtil::selectObjectByID('user_property', (int)$args['propid'], 'prop_id');
    } elseif (isset($args['proplabel'])) {
        $result = DBUtil::selectObjectByID('user_property', $args['proplabel'], 'prop_label');
    } else {
        $result = DBUtil::selectObjectByID('user_property', $args['propattribute'], 'prop_attribute_name');
    }

    // Check for no rows found, and if so return
    if (!$result) {
        return false;
    }

    // Security check
    if (!SecurityUtil::checkPermission('Profile::', $result['prop_label'].'::'.$result['prop_id'], ACCESS_READ)) {
        return false;
    }

    // Extract the validation info array
    $validationinfo = @unserialize($result['prop_validation']);

    // Create the item array
    $item = array('prop_id'             => $result['prop_id'],
                  'prop_label'          => $result['prop_label'],
                  'prop_dtype'          => $result['prop_dtype'],
                  'prop_length'         => $result['prop_length'],
                  'prop_weight'         => $result['prop_weight'],
                  'prop_attribute_name' => $result['prop_attribute_name'],
                  'prop_required'       => $validationinfo['required'],
                  'prop_viewby'         => $validationinfo['viewby'],
                  'prop_displaytype'    => $validationinfo['displaytype'],
                  'prop_listoptions'    => $validationinfo['listoptions'],
                  'prop_note'           => $validationinfo['note'],
                  'prop_validation'     => $validationinfo['validation']);

    // Return the item array
    return $item;
}

/**
 * Get all active Dynamic user data fields
 * @author Mark West - FC
 * @param int args['startnum'] starting record number for request
 * @param int args['numitems'] number of records to retrieve
 * @return mixed array of items, or false on failure
 */
function Profile_userapi_getallactive($args)
{
    // Optional arguments.
    if (!isset($args['startnum'])) {
        $args['startnum'] = 1;
    }
    if (!isset($args['numitems'])) {
        $args['numitems'] = -1;
    }

    if (!isset($args['startnum']) || !isset($args['numitems'])) {
        return LogUtil::registerArgsError();
    }

    static $items;

    // Security check
    if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_READ)) {
        return array();
    }

    if (!isset($items)) {
        // Get datbase setup
        $pntable = pnDBGetTables();
        $column  = $pntable['user_property_column'];
        $where   = "WHERE $column[prop_weight] > '0'
                    AND   $column[prop_dtype] >= '0'";
        $orderBy = $column['prop_weight'];
        $props   = DBUtil::selectObjectArray('user_property', $where, $orderBy, $args['startnum'], $args['numitems'], 'prop_label');

        // Put items into result array.
        foreach ($props as $item)
        {
            if (SecurityUtil::checkPermission('Profile::', $item['prop_label'].'::'.$item['prop_id'], ACCESS_READ)) {
                // Extract the validation info array
                $validationinfo = @unserialize($item['prop_validation']);

                $item['prop_required']    = $validationinfo['required'];
                $item['prop_viewby']      = $validationinfo['viewby'];
                $item['prop_displaytype'] = $validationinfo['displaytype'];
                $item['prop_listoptions'] = $validationinfo['listoptions'];
                $item['prop_note']        = $validationinfo['note'];
                $item['prop_validation']  = $validationinfo['validation'];

                $items[$item['prop_label']] = $item;
            }
        }
    }

    // Return the items
    return $items;
}

/**
 * Utility function to count the number of items held by this module
 * @author Mark West
 * @return int number of items held by this module
 */
function Profile_userapi_countitems()
{
    // Return the number of items
    return DBUtil::selectObjectCount('user_property');
}

/**
 * Utility function to get the weight limits
 * @author Mark West
 * @return mixed array of items, or false on failure
 */
function Profile_userapi_getweightlimits()
{
    // Get datbase setup
    $pntable = pnDBGetTables();
    $column  = $pntable['user_property_column'];

    $where = "WHERE $column[prop_weight] <> 0";
    $max   = DBUtil::selectFieldMax('user_property', 'prop_weight', 'MAX', $where);

    $where = "WHERE $column[prop_weight] <> 0";
    $min   = DBUtil::selectFieldMax('user_property', 'prop_weight', 'MIN', $where);

    // Return the number of items
    return array('min' => $min, 'max' => $max);
}

/**
 * Utility function to check if a mail exists
 * @author FC
 * @return int number of items held by this module
 */
function Profile_userapi_checkmailexists($args)
{
    $dom = ZLanguage::getModuleDomain('Profile');

    // Argument check
    if (empty($args['newmail'])) {
        // must be set!
        return 1;
    }

    if (empty($args['uid'])) {
        return LogUtil::registerArgsError();
        // FIXME
    }

    $pntable = pnDBGetTables();
    $column  = $pntable['users_column'];
    $where   = "WHERE $column[uid]!='". (int) DataUtil::formatForStore($args['uid'])."'
                AND   $column[email]= '" . DataUtil::formatForStore($args['newmail'])."'";

    return DBUtil::selectObjectCount ('users', $where);
}

/**
 * Utility function to save the data of the user
 * @author FC
 * @return true - success; false - failure
 */
function Profile_userapi_savedata($args)
{
    // Argument check
    if (!isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    $fieldlist = $args['dynadata'];

    // create the basic array for dbutil
    $profile = array('uid' => $args['uid']);

    while (list($fieldattribute, $fieldvalue) = each($fieldlist))
    {
        // Combining fields, TODO: Extend to other types than only EXTDATE
        if (is_array($fieldvalue)) {
            $definition = pnModAPIFunc('Profile', 'user', 'get', array('propattribute' => $fieldattribute));
            if ($definition) {
                // Must check type, if EXTDATE { implode } else { serialize }
                if ($definition['prop_displaytype'] == 6) {
                    $fieldvalue = implode('-', $fieldvalue);
                } else {
                    $fieldvalue = serialize(array_values($fieldvalue));
                }
            }
        }

        pnUserSetVar($fieldattribute, $fieldvalue, $args['uid']);
    }

    // Return the result (true = success, false = failure
    // At this point, the result is true.
    return true;
}

/**
 * Profile_Manager function to check the required missing
 * @author FC
 * @return false - success (no errors), otherwise array('result' => true, 'fields' => array of field names)
 */
function Profile_userapi_checkrequired($args)
{
    // Argument check
    if (!isset($args['dynadata'])) {
        $args['dynadata'] = FormUtil::getPassedValue('dynadata');
    }

    $dom = ZLanguage::getModuleDomain('Profile');

    // The API function is called.
    $items = pnModAPIFunc('Profile', 'user', 'getallactive');

    // Initializing Error check
    $error = false;

    foreach ($items as $item)
    {
        if ($item['prop_required'] == 1) {
            if (is_array($args['dynadata'][$item['prop_attribute_name']])) {
                while (list(,$value) = each($args['dynadata'][$item['prop_attribute_name']]))
                {
                    if (_ProfileIsEmptyValue($value)) {
                        $error['result'] = true;
                        $error['fields'][] = $item['prop_attribute_name'];
                        $error['translatedFields'][] = __($item['prop_label'], $dom);
                    }
                }
            } elseif (_ProfileIsEmptyValue($args['dynadata'][$item['prop_attribute_name']])) {
                $error['result'] = true;
                $error['fields'][] = $item['prop_attribute_name'];
                $error['translatedFields'][] = __($item['prop_label'], $dom);
            }
        }
    }

    if (!empty($error)) {
        $error['translatedFieldsStr'] = join(', ', $error['translatedFields']);
    }

    // Return the result
    return $error;
}

/**
 * Checks if a value is empty
 */
function _ProfileIsEmptyValue($value)
{
    $empty = false;

    if (empty($value)) {
        $empty = true;
    }

    if (!$empty && (trim($value) == '')) {
        $empty = true;
    }

    if ($empty && is_numeric($value) && $value == 0) {
        $empty = false;
    }

    return $empty;
}

/**
 * Profile_Manager function to retrieve the dynamic data to the user object
 * @author Mateo Tibaquira
 * @return array of data to attach to the users object or false
 */
function Profile_userapi_insertdyndata($args)
{
    $dynadata = isset($args['dynadata']) ? $args['dynadata'] : FormUtil::getPassedValue('dynadata');

    // Validate if there's any dynamic data
    if (empty($dynadata)) {
        return false;
    }

    // attach the dynadata as attributes to the user object
    return array('__ATTRIBUTES__' => $dynadata);
}

/**
 * Search the input values through the dynadata
 *
 * @author Mateo Tibaquira
 * @return array of matching UIDs
 */
function Profile_userapi_searchdynadata($args)
{
    $uids = array();

    $dynadata = isset($args['dynadata']) ? $args['dynadata'] : FormUtil::getPassedValue('dynadata');

    // Validate if there's any dynamic data
    if (empty($dynadata) || !is_array($dynadata)) {
        return $uids;
    }

    if (count($dynadata) == 1 && in_array('all', array_keys($dynadata))) {
        $params = array('searchby' => $dynadata, 'returnUids' => true);

    } else {
        $duditems = pnModAPIFunc('Profile', 'user', 'getall');

        $params = array('searchby' => array(), 'returnUids' => true);
        foreach ($duditems as $item) {
            if (isset($dynadata[$item['prop_attribute_name']]) && !empty($dynadata[$item['prop_attribute_name']])) {
                $params['searchby'][$item['prop_id']] = $dynadata[$item['prop_attribute_name']];
            }
        }
    }

    if (!empty($params['searchby'])) {
        $uids = pnModAPIFunc('Profile', 'memberslist', 'getall', $params);
    }

    return $uids;
}
