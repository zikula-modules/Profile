<?php
/**
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
class Profile_Installer extends Zikula_AbstractInstaller
{
    /**
     * Provides an array containing default values for module variables (settings).
     *
     * @return array An array indexed by variable name containing the default values for those variables.
     */
    protected function getDefaultModVars()
    {
        return array(
            'memberslistitemsperpage'   => 20,
            'onlinemembersitemsperpage' => 20,
            'recentmembersitemsperpage' => 10,
            'filterunverified'          => 1,
        );
    }

    /**
     * Initialise the dynamic user data  module.
     *
     * @return boolean True on success or false on failure.
     */
    public function install()
    {
        try {
            DoctrineHelper::createSchema($this->entityManager, 'Profile_Entity_Property');
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
        switch ($oldversion)
        {
            case '1.6.0':
            case '1.6.1': // released with Core 1.3.6
                // move data from objectdata_attributes to users_attributes
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
            DoctrineHelper::dropSchema($this->entityManager, 'Profile_Entity_Property');
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
        $record['label']          = no__('_UREALNAME');
        $record['dtype']          = '1';
        $record['weight']         = '1';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'realname';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _UFAKEMAIL
        $record = array();
        $record['label']          = no__('_UFAKEMAIL');
        $record['dtype']          = '1';
        $record['weight']         = '2';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'publicemail';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YOURHOMEPAGE
        $record = array();
        $record['label']          = no__('_YOURHOMEPAGE');
        $record['dtype']          = '1';
        $record['weight']         = '3';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'url';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _TIMEZONEOFFSET
        $record = array();
        $record['label']          = no__('_TIMEZONEOFFSET');
        $record['dtype']          = '1';
        $record['weight']         = '4';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 4, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'tzoffset';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YOURAVATAR
        $record = array();
        $record['label']          = no__('_YOURAVATAR');
        $record['dtype']          = '1';
        $record['weight']         = '5';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 4, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'avatar';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YICQ
        $record = array();
        $record['label']          = no__('_YICQ');
        $record['dtype']          = '1';
        $record['weight']         = '6';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'icq';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YAIM
        $record = array();
        $record['label']          = no__('_YAIM');
        $record['dtype']          = '1';
        $record['weight']         = '7';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'aim';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YYIM
        $record = array();
        $record['label']          = no__('_YYIM');
        $record['dtype']          = '1';
        $record['weight']         = '8';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'yim';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YMSNM
        $record = array();
        $record['label']          = no__('_YMSNM');
        $record['dtype']          = '1';
        $record['weight']         = '9';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'msnm';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YLOCATION
        $record = array();
        $record['label']          = no__('_YLOCATION');
        $record['dtype']          = '1';
        $record['weight']         = '10';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'city';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YOCCUPATION
        $record = array();
        $record['label']          = no__('_YOCCUPATION');
        $record['dtype']          = '1';
        $record['weight']         = '11';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 0, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'occupation';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _SIGNATURE
        $record = array();
        $record['label']          = no__('_SIGNATURE');
        $record['dtype']          = '1';
        $record['weight']         = '12';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 1, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'signature';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _EXTRAINFO
        $record = array();
        $record['label']          = no__('_EXTRAINFO');
        $record['dtype']          = '1';
        $record['weight']         = '13';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 1, 'listoptions' => '', 'note' => ''));
        $record['attributename'] = 'extrainfo';

        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YINTERESTS
        $record = array();
        $record['label']          = no__('_YINTERESTS');
        $record['dtype']          = '1';
        $record['weight']         = '14';
        $record['validation']     = serialize(array('required' => 0, 'viewby' => 0, 'displaytype' => 1, 'listoptions' => '', 'note' => ''));
        $record['attribute_name'] = 'interests';

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