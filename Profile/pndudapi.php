<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnuserapi.php 370 2009-11-25 10:44:01Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */

/**
 * Register a dynamic user data field
 * @author Mateo Tibaquira
 * @param  string  $args['modname']         responsible module of the new field
 * @param  string  $args['label']           label for the new account property
 * @param  string  $args['attribute_name']  name of the attribute to use inside the user's data
 * @param  string  $args['dtype']           dud type to create {normal, mandatory, noneditable}
 * @param  array   $args['validationinfo']  validation info for the new field with the following fields:
 *                   'required'    => {0: no, 1: mandatory}
 *                   'viewby'      => viewable by {0: Everyone, 1: Registered users only, 2: Admins only}
 *                   'displaytype' => {0: text box, 1: textarea, 2: checkbox, 3: radio, 4: select, 5: date, 6: extdate, 7: multi checkbox}
 *                   'listoptions' => options for the new field
 *                   'note'        => note to show in edit mode
 *                   'validation'  => [not used yet]
 *                   and any other required data
 * @return true on success or false on failure
 */
function Profile_dudapi_register($args)
{
    if (!isset($args['modname']) || empty($args['modname'])
     || !isset($args['label']) || empty($args['label'])
     || !isset($args['attribute_name']) || empty($args['attribute_name'])
     || !isset($args['dtype']) || empty($args['dtype'])
     || !isset($args['displaytype']) || !is_numeric($args['displaytype']) || (int)$args['displaytype'] < 0
     || !isset($args['validationinfo']) || empty($args['validationinfo']) || !is_array($args['validationinfo'])) {
        return LogUtil::registerArgsError();
    }

    // Security check
    if (!SecurityUtil::checkPermission('Profile::item', "$args[label]::", ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('Profile');

    if (!pnModGetIDFromName($args['modname'])) {
        return LogUtil::registerError(__f('The specified module (%s) does not exists.', DataUtil::formatForDisplay($args['modname']), $dom));
    }

    // parses the DUD type
    $dtypes = array(-1 => 'noneditable', 0 => 'mandatory', 2 => 'normal');
    if (!in_array($args['dtype'], $dtypes)) {
        return LogUtil::registerError(__f('Invalid %s passed.', 'dtype', $dom));
    }

    // Clean the label
    $permsep = pnConfigGetVar('shorturlsseparator', '-');
    $args['label'] = str_replace($permsep, '', DataUtil::formatPermalink($args['label']));
    $args['label'] = str_replace('-', '', DataUtil::formatPermalink($args['label']));

    // Check if the label or attribute name already exists
    $item = pnModAPIFunc('Profile', 'user', 'get', array('proplabel' => $args['label']));
    if ($item) {
        return LogUtil::registerError(__("An account panel property already has the label '%s'.", DataUtil::formatForDisplay($args['label']), $dom));
    }
    $item = pnModAPIFunc('Profile', 'user', 'get', array('propattribute' => $args['attribute_name']));
    if ($item) {
        return LogUtil::registerError(__("An account panel property already has the attribute name '%s'.", DataUtil::formatForDisplay($args['attribute_name']), $dom));
    }

    // Determine the new weight
    $weightlimits = pnModAPIFunc('Profile', 'user', 'getweightlimits');
    $weight = $weightlimits['max'] + 1;

    // insert the new field
    $obj = array();
    $obj['prop_label']          = $args['label'];
    $obj['prop_attribute_name'] = $args['attribute_name'];
    $obj['prop_dtype']          = array_search($args['dtype'], $dtypes);
    $obj['prop_modname']        = $args['modname'];
    $obj['prop_weight']         = $weight;
    $obj['prop_validation']     = serialize($args['validationinfo']);

    $obj = DBUtil::insertObject($obj, 'user_property', 'prop_id');

    // Check for an error with the database
    if (!$obj) {
        return LogUtil::registerError(__('Error! Creation attempt failed.', $dom));
    }

    // Let any hooks know that we have created a new item.
    pnModCallHooks('item', 'create', $obj['prop_id'], array('module' => 'Profile'));

    // Return the id of the newly created item to the calling process
    return $obj['prop_id'];
}

/**
 * Unregister a specific Dynamic user data item
 * @author Mateo Tibaquira
 * @param  integer  $args['propid']         id of property to unregister
 * @param  string   $args['proplabel']      label of property to unregister
 * @param  string   $args['propattribute']   of property to unregister
 * @return true on success or false on failure
 */
function Profile_dudapi_unregister($args)
{
    // Argument check
    if (!isset($args['propid']) && !isset($args['proplabel']) && !isset($args['propattribute'])) {
        return LogUtil::registerArgsError();
    }

    // Get item with where clause
    if (isset($args['propid'])) {
        $item = DBUtil::selectObjectByID('user_property', (int)$args['propid'], 'prop_id');
    } elseif (isset($args['proplabel'])) {
        $item = DBUtil::selectObjectByID('user_property', $args['proplabel'], 'prop_label');
    } else {
        $item = DBUtil::selectObjectByID('user_property', $args['propattribute'], 'prop_attribute_name');
    }

    // Check for no rows found, and if so return
    if (!$item) {
        return false;
    }

    // Security check
    if (!SecurityUtil::checkPermission('Profile::', "$item[prop_label]::$item[prop_id]", ACCESS_DELETE)) {
        return false;
    }

    // delete the property data aka attributes
    $pntables       = pnDBGetTables();
    $objattr_column = $pntables['objectdata_attributes_column'];

    $delwhere = "WHERE $objattr_column[attribute_name] = '" . DataUtil::formatForStore($item['prop_attribute_name']) . "'
                   AND $objattr_column[object_type] = 'users'";

    $res = DBUtil::deleteWhere('objectdata_attributes', $delwhere);
    if (!$res) {
        return LogUtil::registerError(__('Error! Deletion attempt failed.', $dom));
    }

    // delete the property
    $res = DBUtil::deleteObjectByID('user_property', $item['prop_id'], 'prop_id');
    if (!$res) {
        return LogUtil::registerError(__('Error! Deletion attempt failed.', $dom));
    }

    // Let any hooks know that we have deleted an item.
    pnModCallHooks('item', 'delete', $item['prop_id'], array('module' => 'Profile'));

    // Let the calling process know that we have finished successfully
    return true;
}
