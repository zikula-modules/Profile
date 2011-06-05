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

/**
 * API functions related to member list management.
 */
class Profile_Api_Memberslist extends Zikula_AbstractApi
{
    /**
     * Get all users.
     * 
     * This API function returns all users ids. This function allows for filtering and for paged selection.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * numeric startnum  Start number for recordset.
     * numeric numitems  Number of items to return.
     * string  letter    Letter to filter by.
     * string  sortby    Attribute to sort by.
     * string  sortorder Sort order ascending/descending.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return array Matching user ids.
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
        if (!isset($args['sortby']) || empty($args['sortby'])) {
            $args['sortby'] = 'uname';
        }
        if (!isset($args['sortorder']) || empty($args['sortorder'])) {
            $args['sortorder'] = 'ASC';
        }
        if (!isset($args['sorting']) || empty($args['sorting'])) {
            $args['sorting'] = 0;
        }
        if (!isset($args['searchby']) || empty($args['searchby'])) {
            $args['searchby'] = 'uname';
        }
        if (!isset($args['letter'])) {
            $args['letter'] = null;
        }
        if (!isset($args['returnUids'])) {
            $args['returnUids'] = false;
        }

        // define the array to hold the result items
        $items = array();

        // Security check
        if (!SecurityUtil::checkPermission('Profile:Members:', '::', ACCESS_READ)) {
            return $items;
        }

        // Sanitize the args used in queries
        $args['letter']   = DataUtil::formatForStore($args['letter']);
        $args['searchby'] = DataUtil::formatForStore($args['searchby']);

        // load the database information for the users module
        ModUtil::dbInfoLoad('ObjectData');
        ModUtil::dbInfoLoad('Users');

        // Get database setup
        $dbtable = DBUtil::getTables();
        
        $userscolumn = $dbtable['users_column'];
        $datacolumn  = $dbtable['objectdata_attributes_column'];
        $propcolumn  = $dbtable['user_property_column'];
        
        $joinInfo = array();
        if ($args['searchby'] != 'uname') {
            $joinInfo[] = array(
                'join_table'            => 'objectdata_attributes',
                'join_field'            => array(),
                'object_field_name'     => array(),
                'compare_field_table'   => 'uid',
                'compare_field_join'    => 'object_id',
            );
            $joinInfo[] = array(
                'join_table'            => 'user_property',
                'join_field'            => array(),
                'object_field_name'     => array(),
                'compare_field_table'   => "a.{$datacolumn['attribute_name']}",
                'compare_field_join'    => 'prop_attribute_name',
            );
        }
        
        $where = "WHERE tbl.{$userscolumn['uid']} != 1 ";
        if ($args['searchby'] == 'uname') {
            $join  = '';
            if (!empty($args['letter']) && preg_match('/[a-z]/i', $args['letter'])) {
                // are we listing all or "other" ?
                $where .= "AND LOWER(tbl.{$userscolumn['uname']}) LIKE '".mb_strtolower($args['letter'])."%' ";
                // I guess we are not..
            } else if (!empty($args['letter'])) {
                // But other are numbers ?
                static $otherWhere;
                if (!isset($otherWhere)) {
                    $otherList = array ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-', '.', '@', '$');
                    $otherWhere = array();
                    foreach ($otherList as $other) {
                        $otherWhere[] = "tbl.{$userscolumn['uname']} LIKE '{$other}%'";
                    }
                    $otherWhere = 'AND (' . implode(' OR ', $otherWhere) . ') ';
                }
                
                $where .= $otherWhere;

                // fifers: while this is not the most eloquent solution, it is
                // cross database compatible.  We could do an if dbtype is mysql
                // then do the regexp.  consider for performance enhancement.
                //
                // if you know a better way to match only the first char
                // to be a number in uname, open a ticket with the Profile project.
            }

        } else if (is_array($args['searchby'])) {
            if (count($args['searchby']) == 1 && in_array('all', array_keys($args['searchby']))) {
                // args.searchby is all => search_value to loop all the user attributes

                $value = DataUtil::formatForStore($args['searchby']['all']);
                $where .= "AND a.{$datacolumn['object_type']} = 'users' AND a.{$datacolumn['obj_status']} = 'A' ";
                $where .= "AND b.{$propcolumn['prop_weight']} > 0 AND b.{$propcolumn['prop_dtype']} >= 0 AND a.{$datacolumn['value']} LIKE '%{$value}%' ";

            } else {
                // args.searchby is an array of the form prop_id => value
                $whereList = array();
                foreach ($args['searchby'] as $prop_id => $value) {
                    $prop_id = DataUtil::formatForStore($prop_id);
                    $value   = DataUtil::formatForStore($value);
                    $whereList[] = "(b.{$propcolumn['prop_id']} = '{$prop_id}' AND a.{$datacolumn['value']} LIKE '%{$value}%')";
                }
                // check if there where contitionals
                if (!empty($whereList)) {
                    $where .= 'AND ' . implode(' AND ', $whereList) . ' ';
                }
            }

        } else if (is_numeric($args['searchby'])) {
            $where .= "AND b.{$propcolumn['prop_id']} = '{$args['searchby']}' AND a.{$datacolumn['value']} LIKE '{$args['letter']}%' ";

        } elseif (isset($propcolumn[$args['searchby']])) {
            $where .= 'AND b.' . $propcolumn[$args['searchby']] . " LIKE '{$args['letter']}%' ";
        }

        if (!$args['sorting'] && ModUtil::getVar('Profile', 'filterunverified')) {
            $where .= "AND tbl.{$userscolumn['activated']} = " . Users_Constant::ACTIVATED_ACTIVE . ' ';
        }
        
        if (array_key_exists($args['sortby'], $userscolumn)) {
            $orderBy = 'tbl.'.$userscolumn[$args['sortby']] .' '. $args['sortorder'];
        } else {
            $orderBy = DataUtil::formatForStore($args['sortby']) .' '. $args['sortorder'];
        }
        if ($orderBy && $args['sortby'] != 'uname') {
            $orderBy .= ", {$userscolumn['uname']} ASC ";
        }
        
        $result = DBUtil::selectExpandedFieldArray('users', $joinInfo, 'uid', $where, $orderBy, true);

        // Return the items
        return $result;
    }

    /**
     * Counts the number of users.
     * 
     * This function allows for filtering by letter.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string letter Letter to filter by.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return integer Count of matching users.
     */
    public function countitems($args)
    {
        // Optional arguments.
        if (!isset($args['searchby']) || empty($args['searchby'])) {
            $args['searchby'] = 'uname';
        }
        if (!isset($args['letter'])) {
            $args['letter'] = null;
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile:Members:', '::', ACCESS_READ)) {
            return 0;
        }

        // Sanitize the args used in queries
        $args['letter']   = DataUtil::formatForStore($args['letter']);
        $args['searchby'] = DataUtil::formatForStore($args['searchby']);

        // load the database information for the users module
        ModUtil::dbInfoLoad('Users');

        // Get database setup
        $dbtable = DBUtil::getTables();

        // It's good practice to name column definitions you are getting
        // $column don't cut it in more complex modules
        $userscolumn = $dbtable['users_column'];
        $datacolumn  = $dbtable['objectdata_attributes_column'];
        $propcolumn  = $dbtable['user_property_column'];

        // Builds the sql query
        $sql   = "SELECT     COUNT(DISTINCT tbl.$userscolumn[uname])
              FROM       $dbtable[users] as tbl ";
        $join  = "LEFT JOIN  $dbtable[objectdata_attributes] as a
              ON         a.$datacolumn[object_id] = tbl.$userscolumn[uid] AND a.$datacolumn[object_type] = 'users' AND a.$datacolumn[obj_status] = 'A'
              LEFT JOIN $dbtable[user_property] as b
              ON         b.$propcolumn[prop_attribute_name] = a.$datacolumn[attribute_name] ";

        // treat a single character as from the alpha filter and everything else as from the search input
        if (strlen($args['letter']) > 1) {
            $args['letter'] = "%{$args['letter']}";
        }

        $where = '';
        if ($args['searchby'] == 'uname') {
            $join = '';
            if (!empty($args['letter']) && preg_match('/[a-z]/i', $args['letter'])) {
                // are we listing all or "other" ?
                $where = "WHERE UPPER(tbl.$userscolumn[uname]) LIKE '".strtoupper($args['letter'])."%' AND tbl.$userscolumn[uid] != '1' ";
                // I guess we are not..
            } else if (!empty($args['letter'])) {
                // But other are numbers ?
                $where = "WHERE (tbl.$userscolumn[uname] LIKE '0%'
                          OR tbl.$userscolumn[uname] LIKE '1%'
                          OR tbl.$userscolumn[uname] LIKE '2%'
                          OR tbl.$userscolumn[uname] LIKE '3%'
                          OR tbl.$userscolumn[uname] LIKE '4%'
                          OR tbl.$userscolumn[uname] LIKE '5%'
                          OR tbl.$userscolumn[uname] LIKE '6%'
                          OR tbl.$userscolumn[uname] LIKE '7%'
                          OR tbl.$userscolumn[uname] LIKE '8%'
                          OR tbl.$userscolumn[uname] LIKE '9%'
                          OR tbl.$userscolumn[uname] LIKE '-%'
                          OR tbl.$userscolumn[uname] LIKE '.%'
                          OR tbl.$userscolumn[uname] LIKE '@%'
                          OR tbl.$userscolumn[uname] LIKE '$%') ";

                // fifers: while this is not the most eloquent solution, it is
                // cross database compatible.  We could do an if dbtype is mysql
                // then do the regexp.  consider for performance enhancement.
                //
                // "WHERE $column[uname] REGEXP \"^\[1-9]\" "
                // REGEX :D, although i think its MySQL only
                // Will have to change this later.
                // if you know a better way to match only the first char
                // to be a number in uname, please change it and email
                // sweede@gallatinriver.net the correction
                // or go to post-nuke project page and post
                // your correction there. Thanks, Bjorn.
            } else {
                // or we are unknown or all..
                $where = "WHERE tbl.$userscolumn[uid] != '1' ";
                // this is to get rid of the annonymous registry
            }

        } else if (is_array($args['searchby'])) {
            if (count($args['searchby']) == 1 && in_array('all', array_keys($args['searchby']))) {
                // args.searchby is all => search_value to loop all the user attributes
                $value = DataUtil::formatForStore($args['searchby']['all']);
                $where = "WHERE b.$propcolumn[prop_weight] > '0' AND $propcolumn[prop_dtype] >= '0' AND a.$datacolumn[value] LIKE '%$value%' ";

            } else {
                // args.searchby is an array of the form prop_id => value
                $where = array();
                foreach ($args['searchby'] as $prop_id => $value) {
                    $prop_id = DataUtil::formatForStore($prop_id);
                    $value   = DataUtil::formatForStore($value);
                    $where[] = "(b.$propcolumn[prop_id] = '$prop_id' AND a.$datacolumn[value] LIKE '%$value%')";
                }
                // check if there where contitionals
                if (!empty($where)) {
                    $where = 'WHERE '.implode(' AND ', $where).' ';
                } else {
                    $where = '';
                }
            }

        } else if (is_numeric($args['searchby'])) {
            $where = "WHERE b.$propcolumn[prop_id] = '$args[searchby]' AND a.$datacolumn[value] LIKE '$args[letter]%' ";

        } elseif (isset($propcolumn[$args['searchby']])) {
            $where = "WHERE b.".$propcolumn[$args['searchby']]." LIKE '$args[letter]%' ";
        }

        if (!$args['sorting'] && ModUtil::getVar('Profile', 'filterunverified')) {
            $where .= " AND tbl.$userscolumn[activated] != '0'";
        }

        $sql   .= $join . $where;

        $result = DBUtil::executeSQL($sql);

        // Check for an error with the database code
        if ($result === false) {
            return LogUtil::registerError($this->__('Error! Could not load data.'));
        }

        // Obtain the number of items
        list($numitems) = $result->fields;

        // All successful database queries produce a result set, and that result
        // set should be closed when it has been finished with
        $result->Close();

        // Return the number of items
        return $numitems;
    }

    /**
     * Counts the number of users online.
     *
     * @return integer Count of registered users online.
     */
    public function getregisteredonline()
    {
        // Get database setup
        $dbtable = DBUtil::getTables();

        // It's good practice to name the table and column definitions you are
        // getting - $table and $column don't cut it in more complex modules
        $sessioninfocolumn = $dbtable['session_info_column'];
        $sessioninfotable  = $dbtable['session_info'];

        $activetime = date('Y-m-d H:i:s', time() - (System::getVar('secinactivemins') * 60));

        $where = "$sessioninfocolumn[uid] <> 0 AND $sessioninfocolumn[lastused] > '$activetime'";

        $result = DBUtil::selectFieldArray('session_info', 'uid', $where, '', true);

        if ($result === false) {
            return LogUtil::registerError($this->__('Error! Could not load data.'));
        }

        $numusers = count($result);

        // Return the number of items
        return $numusers;
    }

    /**
     * Get the latest registered user.
     *
     * @return integer latest registered user id
     */
    public function getlatestuser()
    {
        // load the database information for the users module
        ModUtil::dbInfoLoad('Users');

        // Get database setup
        $dbtable = DBUtil::getTables();

        // It's good practice to name the table and column definitions you are
        // getting - $table and $column don't cut it in more complex modules
        $userscolumn = $dbtable['users_column'];

        // filter out unverified users
        $where = '';
        if (ModUtil::getVar('Profile', 'filterunverified')) {
            $where = " AND $userscolumn[activated] = '1'";
        }

        // Get items
        $sql = "SELECT $userscolumn[uid]
            FROM $dbtable[users]
            WHERE $userscolumn[uname] NOT LIKE 'Anonymous' $where
            ORDER BY $userscolumn[uid] DESC";

        $result = DBUtil::executeSQL($sql);

        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($result === false) {
            return LogUtil::registerError($this->__('Error! Could not load data.'));
        }

        // Obtain the number of items
        list($lastuser) = $result->fields;

        // All successful database queries produce a result set, and that result
        // set should be closed when it has been finished with
        $result->Close();

        // Return the number of items
        return $lastuser;
    }

    /**
     * Determine if a user is online.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * numeric userid The uid of the user for whom a determination should be made; required.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return bool True if the specified user is online; false otherwise.
     */
    public function isonline($args)
    {
        // check arguments
        if (!isset($args['userid']) || empty($args['userid']) || !is_numeric($args['userid'])) {
            return false;
        }

        // Get database setup
        $dbtable = DBUtil::getTables();

        // get active time based on security settings
        $activetime = date('Y-m-d H:i:s', time() - (System::getVar('secinactivemins') * 60));

        // It's good practice to name the table and column definitions you are
        // getting - $table and $column don't cut it in more complex modules
        $sessioninfocolumn = $dbtable['session_info_column'];
        $sessioninfotable  = $dbtable['session_info'];

        // Get items
        $sql = "SELECT DISTINCT $sessioninfocolumn[uid]
            FROM $sessioninfotable
            WHERE $sessioninfocolumn[uid] = $args[userid] and $sessioninfocolumn[lastused] > '$activetime'";

        $result = DBUtil::executeSQL($sql);

        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($result === false) {
            return LogUtil::registerError($this->__('Error! Could not load data.'));
        }

        // Obtain the item
        list($online) = $result->fields;

        // All successful database queries produce a result set, and that result
        // set should be closed when it has been finished with
        $result->Close();

        // Return if the user is online
        if ($online > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return registered users online.
     *
     * @return array Registered users who are online.
     */
    public function whosonline()
    {
        // Get database setup
        $dbtable = DBUtil::getTables();

        // define the array to hold the resultant items
        $items = array();
        // It's good practice to name the table and column definitions you are
        // getting - $table and $column don't cut it in more complex modules
        $sessioninfocolumn = $dbtable['session_info_column'];
        $sessioninfotable  = $dbtable['session_info'];

        // get active time based on security settings
        $activetime = date('Y-m-d H:i:s', time() - (System::getVar('secinactivemins') * 60));

        // Get items
        $sql = "SELECT DISTINCT $sessioninfocolumn[uid]
            FROM $sessioninfotable
            WHERE $sessioninfocolumn[uid] != 0
            AND $sessioninfocolumn[lastused] > '$activetime'
            GROUP BY $sessioninfocolumn[uid]";

        $result = DBUtil::executeSQL($sql);

        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($result === false) {
            return LogUtil::registerError($this->__('Error! Could not load data.'));
        }

        // Obtain the number of items
        list($numitems) = $result->fields;

        // Put items into result array.
        for (; !$result->EOF; $result->MoveNext()) {
            list($uid) = $result->fields;
            $items[$uid] = UserUtil::getVars($uid);
        }

        // All successful database queries produce a result set, and that result
        // set should be closed when it has been finished with
        $result->Close();

        // Return the items
        return $items;
    }

    /**
     * Returns all users online.
     *
     * @return array All online visitors (including anonymous).
     */
    public function getallonline()
    {
        // Get database setup
        $dbtable = DBUtil::getTables();

        // define the array to hold the resultant items
        $items = array();

        $sessioninfotable  = $dbtable['session_info'];
        $sessioninfocolumn = &$dbtable['session_info_column'];
        $usertbl           = $dbtable['users'];
        $usercol           = &$dbtable['users_column'];

        // get active time based on security
        $activetime = date('Y-m-d H:i:s', time() - (System::getVar('secinactivemins') * 60));

        // Check if anonymous session are on
        if (System::getVar('anonymoussessions')) {
            $anonwhere = "AND $sessioninfotable.$sessioninfocolumn[uid] >= '0' ";
        } else {
            $anonwhere = "AND $sessioninfotable.$sessioninfocolumn[uid] > '0'";
        }

        // Get items
        $sql = "SELECT   $sessioninfotable.$sessioninfocolumn[uid],
                $usertbl.$usercol[uname]
            FROM     $sessioninfotable, $usertbl
            WHERE    $sessioninfocolumn[lastused] > '$activetime'
                $anonwhere
            AND      IF($sessioninfotable.$sessioninfocolumn[uid]='0','1',
                $sessioninfotable.$sessioninfocolumn[uid]) = $usertbl.$usercol[uid]
            GROUP BY $sessioninfocolumn[ipaddr], $sessioninfotable.$sessioninfocolumn[uid]
            ORDER BY $usercol[uname]";

        $result = DBUtil::executeSQL($sql);

        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($result === false) {
            return LogUtil::registerError($this->__('Error! Could not load data.'));
        }

        $numusers  = 0;
        $numguests = 0;
        $unames = array();
        for (; !$result->EOF; $result->MoveNext()) {
            list($uid, $uname) = $result->fields;

            if ($uid != 0) {
                $unames[] = array('uid'   => $uid,
                        'uname' => $uname);
                $numusers++;
            } else {
                $numguests++;
            }
        }

        $items = array('unames'    => $unames,
                'numusers'  => $numusers,
                'numguests' => $numguests,
                'total'     => $numguests + $numusers);

        $result->Close();

        // Return the items
        return $items;
    }

    /**
     * Find out which messages module is installed.
     *
     * @return string Name of the messaging module found, empty if none.
     */
    public function getmessagingmodule()
    {
        $msgmodule = System::getVar('messagemodule', '');
        if (!ModUtil::available($msgmodule)) {
            $msgmodule = '';
        }

        return $msgmodule;
    }
}