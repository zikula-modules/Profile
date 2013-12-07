<?php/**
 * Copyright Zikula Foundation 2011 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
use Profile_Entity_Property as PropertyEntity;

/**
 * Profile module installer.
 */

namespace Zikula\Module\ProfileModule;

use DoctrineHelper;
use LogUtil;
use EventUtil;
use PropertyEntity;

class ProfileModuleInstaller extends \Zikula_AbstractInstaller
{
    /**
     * Provides an array containing default values for module variables (settings).
     *
     * @return array An array indexed by variable name containing the default values for those variables.
     */
    protected function getDefaultModVars()
    {
        return array('memberslistitemsperpage' => 20, 'onlinemembersitemsperpage' => 20, 'recentmembersitemsperpage' => 10, 'filterunverified' => 1, 'viewregdate' => 0);
    }
    
    /**
     * Initialise the dynamic user data  module.
     *
     * @return boolean True on success or false on failure.
     */
    public function install()
    {
        try {
            DoctrineHelper::createSchema($this->entityManager, array('Profile_Entity_Property'));
        } catch (\Exception $e) {
            return LogUtil::registerError($e->getMessage());
        }
        $this->setVars($this->getDefaultModVars());
        // create the default data for the module
        $this->defaultdata();
        EventUtil::registerPersistentEventHandlerClass($this->name, 'Profile_Listener_UsersUiHandler');
        // Initialisation successful
        return true;
    }
    
    /**
     * Upgrade the dynamic user data module from an old version.
     * 
     * @param string $oldversion The version from which the upgrade is beginning (the currently installed version); this should be compatible 
     *                              with {@link version_compare()}.
     * 
     * @return boolean True on success or false on failure.
     */
    public function upgrade($oldversion)
    {
        // Only support upgrade from version 1.6 and up. Notify users if they have a version below that one.
        if (version_compare($oldversion, '1.6', '<')) {
            // Inform user about error, and how he can upgrade to $modversion
            $upgradeToVersion = $this->version->getVersion();
            return LogUtil::registerError($this->__f('Notice: This version does not support upgrades from versions less than 1.6. Please upgrade before upgrading again to version %s.', $upgradeToVersion));
        }
        $connection = $this->entityManager->getConnection();
        switch ($oldversion) {
            case '1.6.0':
            case '1.6.1':
                // released with Core 1.3.6
                $sqls = array();
                // copy data from objectdata_attributes to users_attributes
                // NOTE: this routine *may* copy additional data to the user_property table that does not belong to
                // the Profile module. It doesn't copy data from the Legal module because Legal attribute names begin
                // with '_' (note discriminator in query). It is impossible to discern what else may be in the table
                // that meets the discriminator criteria
                $sqls[] = 'INSERT INTO user_property
                    (user_id, name, value)
                    SELECT object_id, attribute_name, value
                    FROM objectdata_attributes
                    WHERE object_type = \'users\'
                    AND LEFT(attribute_name, 1) <> \'_\'
                    ORDER BY object_id, attribute_name';
                // remove old data
                $sqls[] = 'DELETE FROM objectdata_attributes
                    WHERE object_type = \'users\'
                    AND LEFT(attribute_name, 1) <> \'_\'';
                foreach ($sqls as $sql) {
                    $stmt = $connection->prepare($sql);
                    $stmt->execute();
                }
            case '2.0.0':
        }
        $modVars = $this->getVars();
        $defaultModVars = $this->getDefaultModVars();
        // Remove modvars no longer in the default set.
        foreach ($modVars as $modVar => $value) {
            if (!array_key_exists($modVar, $defaultModVars)) {
                $this->delVar($modVar);
            }
        }
        // Add vars defined in the default set, but missing from the current set.
        foreach ($defaultModVars as $modVar => $value) {
            if (!array_key_exists($modVar, $modVars)) {
                $this->setVar($modVar, $value);
            }
        }
        // Update successful
        return true;
    }
    
    /**
     * Delete the dynamic user data module.
     *
     * @return boolean True on success or false on failure.
     */
    public function uninstall()
    {
        try {
            DoctrineHelper::dropSchema($this->entityManager, array('Profile_Entity_Property'));
        } catch (Exception $e) {
            return LogUtil::registerError($e->getMessage());
        }
        // Delete any module variables
        $this->delVars();
        // Deletion successful
        return true;
    }
    
    /**
     * Create the default data for the users module.
     *
     * @return void
     */
    protected function defaultdata()
    {
        // Make assumption that if were upgrading from 76x to 1.x
        // that user properties already exist and abort inserts.
        if (isset($_SESSION['_PNUpgrader']['_PNUpgradeFrom76x'])) {
            return;
        }
        // _UREALNAME
        $record = array();
        $record['prop_label']          = no__('_UREALNAME');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 1;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'realname';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _UFAKEMAIL
        $record = array();
        $record['prop_label']          = no__('_UFAKEMAIL');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 2;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'publicemail';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YOURHOMEPAGE
        $record = array();
        $record['prop_label']          = no__('_YOURHOMEPAGE');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 3;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'url';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _TIMEZONEOFFSET
        $record = array();
        $record['prop_label']          = no__('_TIMEZONEOFFSET');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 4;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 2, 'displaytype' => 4, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'tzoffset';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YOURAVATAR
        $record = array();
        $record['prop_label']          = no__('_YOURAVATAR');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 5;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 4, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'avatar';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YICQ
        $record = array();
        $record['prop_label']          = no__('_YICQ');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 6;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'icq';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YAIM
        $record = array();
        $record['prop_label']          = no__('_YAIM');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 7;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'aim';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YYIM
        $record = array();
        $record['prop_label']          = no__('_YYIM');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 8;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'yim';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YMSNM
        $record = array();
        $record['prop_label']          = no__('_YMSNM');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 9;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'msnm';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YLOCATION
        $record = array();
        $record['prop_label']          = no__('_YLOCATION');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 10;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'city';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YOCCUPATION
        $record = array();
        $record['prop_label'] = no__('_YOCCUPATION');
        $record['prop_dtype'] = '1';
        $record['prop_weight'] = '11';
        $record['prop_validation'] = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'occupation';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _SIGNATURE
        $record = array();
        $record['prop_label']          = no__('_SIGNATURE');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 12;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 1, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'signature';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _EXTRAINFO
        $record = array();
        $record['prop_label']          = no__('_EXTRAINFO');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 13;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 1, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'extrainfo';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // _YINTERESTS
        $record = array();
        $record['prop_label']          = no__('_YINTERESTS');
        $record['prop_dtype']          = 1;
        $record['prop_weight']         = 14;
        $record['prop_validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 1, 'listoptions' => '', 'note' => ''));
        $record['prop_attribute_name'] = 'interests';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);
        // flush all persisted entities
        $this->entityManager->flush();
        // set realname, homepage, timezone offset, location and ocupation
        // to be shown in the registration form by default
        $this->setVar('dudregshow', array(1, 3, 4, 10, 11));
    }
}
