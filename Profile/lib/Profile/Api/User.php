<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnuserapi.php 118 2010-03-12 10:40:23Z yokav $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @license http://www.gnu.org/copyleft/gpl.html
 */

class Profile_Api_User extends Zikula_Api
{
    /**
     * Get all Dynamic user data fields
     * @author Mateo Tibaquira
     * @author Mark West
     * @param int args['startnum'] starting record number for request
     * @param int args['numitems'] number of records to retrieve
     * @return mixed array of items, or false on failure
     */
    public function getall($args)
    {
        // Optional arguments.
        if (!isset($args['startnum'])) {
            $args['startnum'] = 1;
        }
        if (!isset($args['numitems'])) {
            $args['numitems'] = -1;
        }
        if (!isset($args['index']) || !in_array($args['index'], array('prop_id', 'prop_label', 'prop_attribute_name'))) {
            $args['index'] = 'prop_label';
        }

        if (!isset($args['startnum']) || !isset($args['numitems'])) {
            return LogUtil::registerArgsError();
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_READ)) {
            return array();
        }

        // We now generate a where-clause
        $where   = '';
        $orderBy = 'prop_weight';

        $permFilter = array();
        $permFilter[] = array('component_left'   =>  'Profile',
                'component_middle' =>  '',
                'component_right'  =>  'item',
                'instance_left'    =>  'prop_label',
                'instance_middle'  =>  '',
                'instance_right'   =>  'prop_id',
                'level'            =>  ACCESS_READ);

        $items = DBUtil::selectObjectArray('user_property', $where, $orderBy, $args['startnum']-1, $args['numitems'], $args['index'], $permFilter);

        // Put items into result array.
        foreach (array_keys($items) as $k)
        {
            $validationinfo = @unserialize($items[$k]['prop_validation']);
            unset($items[$k]['prop_validation']);

            // Expand the item array
            foreach ((array)$validationinfo as $infolabel => $infofield) {
                $items[$k]["prop_$infolabel"] = $infofield;
            }
        }

        // Return the items
        return $items;
    }

    /**
     * Get a specific Dynamic user data item
     * @author Mateo Tibaquira
     * @author Mark West
     * @param $args['propid'] id of property to get
     * @return mixed item array, or false on failure
     */
    public function get($args)
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
        if (!SecurityUtil::checkPermission('Profile::', "$item[prop_label]::$item[prop_id]", ACCESS_READ)) {
            return false;
        }

        // Extract the validation info array
        $validationinfo = @unserialize($item['prop_validation']);
        unset($item['prop_validation']);

        // Expand the item array
        foreach ((array)$validationinfo as $infolabel => $infofield) {
            $item["prop_$infolabel"] = $infofield;
        }

        // Return the item array
        return $item;
    }

    /**
     * Get all active Dynamic user data fields
     * @author Mateo Tibaquira
     * @author Mark West
     * @param int args['startnum'] starting record number for request
     * @param int args['numitems'] number of records to retrieve
     * @return mixed array of items, or false on failure
     */
    public function getallactive($args)
    {
        // Optional arguments.
        if (!isset($args['startnum'])) {
            $args['startnum'] = -1;
        }
        if (!isset($args['numitems']) || $args['numitems'] <= 0) {
            $args['numitems'] = 0;
        }

        if (!isset($args['index']) || !in_array($args['index'], array('prop_id', 'prop_label', 'prop_attribute_name'))) {
            $args['index'] = 'prop_attribute_name';
        }
        if (!isset($args['get']) || !in_array($args['get'], array('editable', 'viewable', 'all'))) {
            $args['get'] = 'all';
        }
        if (!isset($args['uid']) || !is_numeric($args['uid'])) {
            $args['uid'] = -1;
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_READ)) {
            return array();
        }

        static $items;

        if (!isset($items)) {
            // Get datbase setup
            $dbtable = DBUtil::getTables();
            $column  = $dbtable['user_property_column'];
            $where   = "WHERE $column[prop_weight] > '0'
                    AND   $column[prop_dtype] >= '0'";
            $orderBy = $column['prop_weight'];

            $permFilter = array();
            $permFilter[] = array('component_left'   =>  'Profile',
                    'component_middle' =>  '',
                    'component_right'  =>  '',
                    'instance_left'    =>  'prop_label',
                    'instance_middle'  =>  '',
                    'instance_right'   =>  'prop_id',
                    'level'            =>  ACCESS_READ);

            $items = DBUtil::selectObjectArray('user_property', $where, $orderBy, -1, -1, 'prop_id', $permFilter);

            foreach (array_keys($items) as $k)
            {
                // Extract the validation info array
                $validationinfo = @unserialize($items[$k]['prop_validation']);
                unset($items[$k]['prop_validation']);

                foreach ((array)$validationinfo as $infolabel => $infofield) {
                    $items[$k]["prop_$infolabel"] = $infofield;
                }
            }
        }

        // process the startnum and numitems
        if ($args['numitems']) {
            $items = array_splice($items, $args['startnum']+1, $args['numitems']);
        } else {
            $items = array_splice($items, $args['startnum']+1);
        }

        // Put items into result array and filter if needed
        $currentuser = (int)UserUtil::getVar('uid');
        $ismember    = ($currentuser >= 2);
        $isowner     = ($currentuser == (int)$args['uid']);
        $isadmin     = SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN);

        $result  = array();
        foreach ($items as $item)
        {
            switch ($args['get'])
            {
                case 'editable':
                // check the display type
                    if ($item['prop_dtype'] < 0) {
                        break;
                    }
                case 'editable':
                case 'viewable':
                    $isallowed = true;
                    // check the item visibility
                    switch ($item['prop_viewby'])
                    {
                        // everyone, do nothing
                        case '0':
                            break;
                        // members only or higher
                        case '1':
                            $isallowed = $ismember;
                            break;
                        // account owner or admin
                        case '2':
                            $isallowed = ($isowner || $isadmin);
                            break;
                        // admins only
                        case '3':
                            $isallowed = $isadmin;
                            break;
                    }
                    // break if it's not viewable
                    if (!$isallowed) {
                        break;
                    }
                    $result[$item[$args['index']]] = $item;
                    break;
                case 'all':
                    $result[$item[$args['index']]] = $item;
            }

        }

        // Return the items
        return $result;
    }

    /**
     * Utility function to count the number of items held by this module
     * @author Mark West
     * @return int number of items held by this module
     */
    public function countitems()
    {
        // Return the number of items
        return DBUtil::selectObjectCount('user_property');
    }

    /**
     * Utility function to get the weight limits
     * @author Mark West
     * @return mixed array of items, or false on failure
     */
    public function getweightlimits()
    {
        // Get datbase setup
        $dbtable = DBUtil::getTables();
        $column  = $dbtable['user_property_column'];

        $where = "WHERE $column[prop_weight] <> 0";
        $max   = DBUtil::selectFieldMax('user_property', 'prop_weight', 'MAX', $where);

        $where = "WHERE $column[prop_weight] <> 0";
        $min   = DBUtil::selectFieldMax('user_property', 'prop_weight', 'MIN', $where);

        // Return the number of items
        return array('min' => $min, 'max' => $max);
    }

    /**
     * Utility function to save the data of the user
     * @author FC
     * @return true - success; false - failure
     */
    public function savedata($args)
    {
        // Argument check
        if (!isset($args['uid'])) {
            return LogUtil::registerArgsError();
        }

        $fields = $args['dynadata'];

        $duds = ModUtil::apiFunc('Profile', 'user', 'getallactive', array('get' => 'editable', 'uid' => $args['uid']));

        foreach ($duds as $attrname => $dud)
        {
            // exclude avatar update when Avatar module is present
            if ($attrname == 'avatar' && ModUtil::available('Avatar')) {
                continue;
            }

            $fieldvalue = '';
            if (isset($fields[$attrname])) {
                // Process the Date DUD separately
                if ($dud['prop_displaytype'] == 5 && !empty($fields[$attrname])) {
                    $fieldvalue = DateUtil::parseUIDate($fields[$attrname]);
                    $fieldvalue = DateUtil::transformInternalDate($fieldvalue);
                } elseif (is_array($fields[$attrname])) {
                    $fieldvalue = serialize(array_values($fields[$attrname]));
                } else {
                    $fieldvalue = $fields[$attrname];
                }
            }
            UserUtil::setVar($attrname, $fieldvalue, $args['uid']);
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
    public function checkrequired($args)
    {
        // Argument check
        if (!isset($args['dynadata'])) {
            $args['dynadata'] = FormUtil::getPassedValue('dynadata');
        }



        // The API function is called.
        $items = ModUtil::apiFunc('Profile', 'user', 'getallactive');

        // Initializing Error check
        $error = false;

        foreach ($items as $item)
        {
            if ($item['prop_required'] == 1) {
                // exclude the checkboxes from required check
                if (in_array($item['prop_displaytype'], array(2, 7))) {
                    continue;
                } elseif (!isset($args['dynadata'][$item['prop_attribute_name']])) {
                    $error['result'] = true;
                    $error['fields'][] = $item['prop_attribute_name'];
                    $error['translatedFields'][] = $this->__($item['prop_label']);
                } elseif (is_array($args['dynadata'][$item['prop_attribute_name']])) {
                    while (list(,$value) = each($args['dynadata'][$item['prop_attribute_name']]))
                    {
                        if (_ProfileIsEmptyValue($value)) {
                            $error['result'] = true;
                            $error['fields'][] = $item['prop_attribute_name'];
                            $error['translatedFields'][] = $this->__($item['prop_label']);
                        }
                    }
                } elseif (_ProfileIsEmptyValue($args['dynadata'][$item['prop_attribute_name']])) {
                    $error['result'] = true;
                    $error['fields'][] = $item['prop_attribute_name'];
                    $error['translatedFields'][] = $this->__($item['prop_label']);
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
    public function insertdyndata($args)
    {
        $dynadata = isset($args['dynadata']) ? $args['dynadata'] : FormUtil::getPassedValue('dynadata');

        // Validate if there's no dynadata
        // do not touch the __ATTRIBUTES__ field
        if (empty($dynadata)) {
            return array();
        }

        // Validate if it's an existing user
        if (!isset($args['uid'])) {
            return array('__ATTRIBUTES__' => $dynadata);
        }

        // Needs to merge the existing attributes to not delete any of them
        $user = DBUtil::selectObjectByID('users', $args['uid'], 'uid');

        if ($user === false || !isset($user['__ATTRIBUTES__'])) {
            return array('__ATTRIBUTES__' => $dynadata);
        }

        // attach the dynadata as attributes to the user object
        return array('__ATTRIBUTES__' => array_merge($user['__ATTRIBUTES__'], $dynadata));
    }

    /**
     * Search the input values through the dynadata
     *
     * @author Mateo Tibaquira
     * @return array of matching UIDs
     */
    public function searchdynadata($args)
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
            $duditems = ModUtil::apiFunc('Profile', 'user', 'getall');

            $params = array('searchby' => array(), 'returnUids' => true);
            foreach ($duditems as $item) {
                if (isset($dynadata[$item['prop_attribute_name']]) && !empty($dynadata[$item['prop_attribute_name']])) {
                    $params['searchby'][$item['prop_id']] = $dynadata[$item['prop_attribute_name']];
                }
            }
        }

        if (!empty($params['searchby'])) {
            $uids = ModUtil::apiFunc('Profile', 'memberslist', 'getall', $params);
        }

        return $uids;
    }


    /**
     * decode the custom url string
     *
     * @author Mark West
     * @return bool true if successful, false otherwise
     */
    public function decodeurl($args)
    {
        // check we actually have some vars to work with...
        if (!isset($args['vars'])) {
            return LogUtil::registerArgsError();
        }

        // let the core handled everything except the view function
        if (!isset($args['vars'][2]) || empty($args['vars'][2]) || $args['vars'][2] != 'view') {
            return false;
        }
        System::queryStringSetVar('func', 'view');

        // identify the correct parameter to identify the user
        if (isset($args['vars'][3])) {
            if (is_numeric($args['vars'][3])) {
                System::queryStringSetVar('uid', $args['vars'][3]);
            } else {
                System::queryStringSetVar('uname', $args['vars'][3]);
            }
        }

        if (isset($args['vars'][4])) {
            System::queryStringSetVar('page', $args['vars'][4]);
        }

        return true;
    }

    /**
     * form custom url string
     *
     * @author Mark West
     * @return string custom url string
     */
    public function encodeurl($args)
    {
        // check we have the required input
        if (!isset($args['modname']) || !isset($args['func']) || !isset($args['args'])) {
            return LogUtil::registerArgsError();
        }

        if (!isset($args['type'])) {
            $args['type'] = 'user';
        }

        // create an empty string ready for population
        $vars = '';

        // let the core handled everything except the view function
        if ($args['func'] == 'view' && (isset($args['args']['uname']) || isset($args['args']['uid']))) {
            isset($args['args']['uname']) ? $vars = $args['args']['uname'] : $vars = $args['args']['uid'];
        } else {
            return false;
        }

        if (isset($args['args']['page'])) {
            $vars .= "/{$args['args']['page']}";
        }

        // construct the custom url part
        return $args['modname'] . '/' . $args['func'] . '/' . $vars;
    }


}