<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pntables.php 370 2009-11-25 10:44:01Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @author Mark West
*/

/**
 * This function is called internally by the core whenever the module is
 * loaded. It adds in the information
 * @author Mark West
 * @return array table definition array
 */
function Profile_pntables()
{
    // Initialise table array
    $pntable = array();

    // Set the table name
    $pntable['user_property'] = DBUtil::getLimitedTablename('user_property');

    // Set the column names.  Note that the array has been formatted
    // on-screen to be very easy to read by a user.
    $pntable['user_property_column'] = array('prop_id'             => 'pn_prop_id',
                                             'prop_label'          => 'pn_prop_label',
                                             'prop_dtype'          => 'pn_prop_dtype',
                                             'prop_weight'         => 'pn_prop_weight',
                                             'prop_validation'     => 'pn_prop_validation',
                                             'prop_attribute_name' => 'pn_prop_attribute_name');

    $pntable['user_property_column_def'] = array('prop_id'             => 'I4 NOTNULL AUTO PRIMARY',
                                                 'prop_label'          => "C(255) NOTNULL DEFAULT ''",
                                                 'prop_dtype'          => "C(64) NOTNULL DEFAULT ''",
                                                 'prop_weight'         => 'I4 NOTNULL DEFAULT 0',
                                                 'prop_validation'     => 'X',
                                                 'prop_attribute_name' => "C(80) NOTNULL DEFAULT ''");

    $pntable['user_property_column_idx'] = array ('prop_label' => 'prop_label');

    //
    // declaration of user_data is still needed for upgrade purposes
    // in the Users module and cannot be removed
    //

    // Set the table name
    $pntable['user_data'] = DBUtil::getLimitedTablename('user_data');

    // Set the column names.  Note that the array has been formatted
    // on-screen to be very easy to read by a user.
    $pntable['user_data_column'] = array('uda_id'     => 'pn_uda_id',
                                         'uda_propid' => 'pn_uda_propid',
                                         'uda_uid'    => 'pn_uda_uid',
                                         'uda_value'  => 'pn_uda_value');

    $pntable['user_data_column_def'] = array('uda_id'     => 'I4 NOTNULL AUTO PRIMARY',
                                             'uda_propid' => 'I4 NOTNULL DEFAULT 0',
                                             'uda_uid'    => 'I4 NOTNULL DEFAULT 0',
                                             'uda_value'  => 'XL NOTNULL');

    $pntable['user_data_column_idx'] = array ('uid_propid' => array('uda_propid', 'uda_uid'));

    // Return the table information
    return $pntable;
}
