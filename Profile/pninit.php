<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pninit.php 370 2009-11-25 10:44:01Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @author Mark West
*/

/**
 * Initialise the dynamic user data  module
 *
 * @author Mark West
 * @return bool true on success or false on failure
 */
function Profile_init()
{
    if (!DBUtil::createTable('user_property')) {
        return false;
    }

    pnModSetVar('Profile', 'itemsperpage',    25);
    pnModSetVar('Profile', 'itemsperrow',     5);
    pnModSetVar('Profile', 'displaygraphics', 1);

    pnModSetVar('Profile', 'memberslistitemsperpage', 20);
    pnModSetVar('Profile', 'onlinemembersitemsperpage', 20);
    pnModSetVar('Profile', 'recentmembersitemsperpage', 10);
    pnModSetVar('Profile', 'filterunverified', 1);
    
    pnModSetVar('Profile', 'dudtextdisplaytags', 0);

    // Set up module hooks  - currently this hook isn't present (markwest)
    /*
    if (!pnModRegisterHook('item', 'display', 'GUI', 'Profile', 'user', 'display')) {
        return false;
    }*/

    // create the default data for the module
    Profile_defaultdata();

    // Initialisation successful
    return true;
}

/**
 * Upgrade the dynamic user data module from an old version
 * This function can be called multiple times
 * @author Mark West
 * @param int $oldversion version to upgrade from
 * @return bool true on success or false on failure
 */
function Profile_upgrade($oldversion)
{
    $dom = ZLanguage::getModuleDomain('Profile');

    // in mysql 5 strict mode we need to set any null values before changing the table
    $table = DBUtil::getLimitedTableName('user_property');
    DBUtil::executeSQL("UPDATE {$table} SET pn_prop_validation = '' WHERE pn_prop_validation IS NULL");

    if (version_compare($oldversion, '1.0', '<')) {
        if (!DBUtil::changeTable('user_property')) {
            return $oldversion;
        }
    }

    switch ($oldversion)
    {
        case '0.8':
            pnModSetVar('Profile', 'itemsperpage',    25);
            pnModSetVar('Profile', 'itemsperrow',     5);
            pnModSetVar('Profile', 'displaygraphics', 1);
            // fix the data types of any existing properties
            DBUtil::executeSQL("UPDATE {$table} SET pn_prop_dtype = '1' WHERE pn_prop_dtype = '0'");

        case '1.1':
            pnModSetVar('Profile', 'memberslistitemsperpage',   pnModGetVar('Members_List', 'memberslistitemsperpage', 20));
            pnModSetVar('Profile', 'onlinemembersitemsperpage', pnModGetVar('Members_List', 'onlinemembersitemsperpage', 20));
            pnModSetVar('Profile', 'recentmembersitemsperpage', pnModGetVar('Members_List', 'recentmembersitemsperpage', 10));
            pnModSetVar('Profile', 'filterunverified',          pnModGetVar('Members_List', 'filterunverified', 1));
            pnModDelVar('Members_List');

            // upgrade blocks table to migrate Members_List blocks to Profile
            $btable = DBUtil::getLimitedTablename('blocks');
            $oldModuleID = pnModGetIDFromName('Members_List');
            $newModuleID = pnModGetIDFromName('Profile');
            DBUtil::executeSQL("UPDATE {$btable} SET pn_mid = '{$newModuleID}' WHERE pn_mid = '{$oldModuleID}'");

        case '1.2':
            // dependencies do not work during upgrade yet so we check it manually
            $usersmod = pnModGetInfo(pnModGetIDFromName('Users'));
            if (version_compare($usersmod['version'], '1.9', '<=')) {
                LogUtil::registerError(__('Users module has to be upgraded to v1.10 before you can upgrade the Profile module!', $dom));
                return '1.2';
            }

            if (!DBUtil::changeTable('user_property')) {
                return '1.2';
            }

            // finally drop the user_data table, its contents has been moved to attributes
            // during the upgrade of the Users module
            if (!DBUtil::dropTable('user_data')) {
                return '1.2';
            }

        case '1.3':
            pnModSetVar('Profile', 'dudtextdisplaytags', 0);

        case '1.4':
            // remove definitely the user_data table
            if (!DBUtil::dropTable('user_data')) {
                return '1.2';
            }

            if (!DBUtil::changeTable('user_property')) {
                return $oldversion;
            }

            // re-update the old DUDs
            // this array maps old DUDs to new attributes
            $mappingarray = array('_UREALNAME'      => 'realname',
                                  '_UFAKEMAIL'      => 'publicemail',
                                  '_YOURHOMEPAGE'   => 'url',
                                  '_TIMEZONEOFFSET' => 'tzoffset',
                                  '_YOURAVATAR'     => 'avatar',
                                  '_YLOCATION'      => 'city',
                                  '_YICQ'           => 'icq',
                                  '_YAIM'           => 'aim',
                                  '_YYIM'           => 'yim',
                                  '_YMSNM'          => 'msnm',
                                  '_YOCCUPATION'    => 'occupation',
                                  '_SIGNATURE'      => 'signature',
                                  '_EXTRAINFO'      => 'extrainfo',
                                  '_YINTERESTS'     => 'interests');

            // load the user properties into an assoc array with prop_label as key
            $userprops = DBUtil::selectObjectArray('user_property', '', '', -1, -1, 'prop_label');

            $newprops = array();
            // expand the old DUDs with the new attribute names
            foreach ($userprops as $prop_label => $userprop)
            {
                if (in_array($prop_label, array('_PASSWORD', '_UREALEMAIL'))) {
                    // delete these props as they are not needed any longer
                    DBUtil::deleteObjectByID('user_property', $userprop['prop_id'], 'prop_id');
                    // and then
                    continue;
                }

                if (array_key_exists($prop_label, $mappingarray)) {
                    // old DUD found
                    $userprop['prop_attribute_name'] = $mappingarray[$prop_label];
                    
                } else {
                    // seems to be user defined, dont touch it
                    $userprop['prop_attribute_name'] = $prop_label;
                }
                // set the types to 'Normal'
                $userprop['prop_dtype'] = 1;
                $newprops[] = $userprop;
            }
            // store updated properties
            DBUtil::updateObjectArray($newprops, 'user_property', 'prop_id');

        case '1.5':
            // future upgrade routines
    }

    // Update successful
    return true;
}

/**
 * Delete the dynamic user data module
 *
 * @author Mark West
 * @return bool true on success or false on failure
 */
function Profile_delete()
{
    if (!DBUtil::dropTable('user_property')) {
        return false;
    }

    // Delete any module variables
    pnModDelVar('Profile');

    // Deletion successful
    return true;
}

/**
 * create the default data for the users module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 *
 */
function Profile_defaultdata()
{
    $dom = ZLanguage::getModuleDomain('Profile');
    // Make assumption that if were upgrading from 76x to 1.x
    // that user properties already exist and abort inserts.
    if (isset($_SESSION['_PNUpgrader']['_PNUpgradeFrom76x'])) {
        return;
    }

    // _UREALNAME
    $record = array();
    $record['prop_label']          = no__('_UREALNAME');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '1';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'realname';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _UFAKEMAIL
    $record = array();
    $record['prop_label']          = no__('_UFAKEMAIL');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '2';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'publicemail';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YOURHOMEPAGE
    $record = array();
    $record['prop_label']          = no__('_YOURHOMEPAGE');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '3';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'url';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _TIMEZONEOFFSET
    $record = array();
    $record['prop_label']          = no__('_TIMEZONEOFFSET');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '4';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'tzoffset';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YOURAVATAR
    $record = array();
    $record['prop_label']          = no__('_YOURAVATAR');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '5';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'avatar';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YICQ
    $record = array();
    $record['prop_label']          = no__('_YICQ');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '6';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'icq';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YAIM
    $record = array();
    $record['prop_label']          = no__('_YAIM');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '7';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'aim';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YYIM
    $record = array();
    $record['prop_label']          = no__('_YYIM');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '8';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'yim';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YMSNM
    $record = array();
    $record['prop_label']          = no__('_YMSNM');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '9';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'msnm';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YLOCATION
    $record = array();
    $record['prop_label']          = no__('_YLOCATION');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '10';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'city';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YOCCUPATION
    $record = array();
    $record['prop_label']          = no__('_YOCCUPATION');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '11';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"0";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'occupation';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _SIGNATURE
    $record = array();
    $record['prop_label']          = no__('_SIGNATURE');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '12';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"1";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'signature';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _EXTRAINFO
    $record = array();
    $record['prop_label']          = no__('_EXTRAINFO');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '13';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"1";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'extrainfo';

    DBUtil::insertObject($record, 'user_property', 'prop_id');

    // _YINTERESTS
    $record = array();
    $record['prop_label']          = no__('_YINTERESTS');
    $record['prop_dtype']          = '1';
    $record['prop_weight']         = '14';
    $record['prop_validation']     = 'a:6:{s:8:"required";s:1:"0";s:6:"viewby";s:1:"0";s:11:"displaytype";s:1:"1";s:11:"listoptions";s:0:"";s:4:"note";s:0:"";s:10:"validation";s:0:"";}';
    $record['prop_attribute_name'] = 'interests';

    DBUtil::insertObject($record, 'user_property', 'prop_id');
}
