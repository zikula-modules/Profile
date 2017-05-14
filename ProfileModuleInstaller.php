<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule;

use EventUtil;
use System;
use Zikula\Core\AbstractExtensionInstaller;
use Zikula\ProfileModule\Entity\PropertyEntity;

/**
 * Profile module installer.
 */
class ProfileModuleInstaller extends AbstractExtensionInstaller
{
    /**
     * @var array
     */
    private $entities = [
        'Zikula\ProfileModule\Entity\PropertyEntity',
    ];

    /**
     * Provides an array containing default values for module variables (settings).
     *
     * @return array An array indexed by variable name containing the default values for those variables
     */
    protected function getDefaultModVars()
    {
        return [
            'viewregdate'               => 0,
            'memberslistitemsperpage'   => 20,
            'onlinemembersitemsperpage' => 20,
            'recentmembersitemsperpage' => 10,
            'filterunverified'          => 1,
        ];
    }

    /**
     * Initialise the dynamic user data  module.
     *
     * @return bool True on success or false on failure
     */
    public function install()
    {
        try {
            $this->schemaTool->create($this->entities);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return false;
        }

        $this->setVars($this->getDefaultModVars());

        // create the default data for the module
        $this->defaultdata();

        // Initialisation successful
        return true;
    }

    /**
     * Upgrade the dynamic user data module from an old version.
     *
     * @param string $oldVersion The version from which the upgrade is beginning (the currently installed version)
     *
     * @return bool True on success or false on failure
     */
    public function upgrade($oldVersion)
    {
        // Only support upgrade from version 1.6 and up. Notify users if they have a version below that one.
        if (version_compare($oldVersion, '1.6', '<')) {
            // Inform user about error, and how he can upgrade to this version
            $this->addFlash('error', $this->__('Notice: This version does not support upgrades from versions less than 1.6. Please upgrade before upgrading again to this version.'));

            return false;
        }

        $connection = $this->entityManager->getConnection();
        switch ($oldVersion) {
            case '1.6.0':
            case '1.6.1':
                // released with Core 1.3.6
                // attributes migrated by Users mod

                // check core for profile setting and update name
                $profileModule = System::getVar('profilemodule', '');
                if ($profileModule == 'Profile') {
                    System::setVar('profilemodule', 'ZikulaProfileModule');
                }
                // remove persistent handlers which are replaced by event subscribers
                EventUtil::unregisterPersistentModuleHandlers('Profile'); // use old name on purpose here
            case '2.0.0':
                // nothing
            case '2.1.0':
                // @todo
                // update user_attributes table and rename attributes from profile module to include new prefix
                $sql = "SELECT * FROM user_property";
                $properties = $this->entityManager->getConnection()->fetchAll($sql);
                $sql = "DROP TABLE user_property";
                $this->entityManager->getConnection()->executeQuery($sql);
                $this->schemaTool->create($this->entities);
                foreach ($properties as $property) {
                    $newProperty = $this->mergeToNewProperty($property);
                }


        }

        // Update successful
        return true;
    }

    /**
     * Delete the profile module.
     *
     * @return bool True on success or false on failure
     */
    public function uninstall()
    {
        try {
            $this->schemaTool->drop($this->entities);
        } catch (\PDOException $e) {
            $this->addFlash('error', $e->getMessage());

            return false;
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
        // _UREALNAME
        $record = [];
        $record['prop_label'] = no__('_UREALNAME');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 1;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = ProfileConstant::ATTRIBUTE_NAME_DISPLAY_NAME;
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _UFAKEMAIL
        $record = [];
        $record['prop_label'] = no__('_UFAKEMAIL');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 2;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'publicemail';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YOURHOMEPAGE
        $record = [];
        $record['prop_label'] = no__('_YOURHOMEPAGE');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 3;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'url';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _TIMEZONEOFFSET
        $record = [];
        $record['prop_label'] = no__('_TIMEZONEOFFSET');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 4;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 2,
            'displaytype' => 4,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'tzoffset';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YOURAVATAR
        $record = [];
        $record['prop_label'] = no__('_YOURAVATAR');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 5;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 4,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'avatar';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YICQ
        $record = [];
        $record['prop_label'] = no__('_YICQ');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 6;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'icq';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YAIM
        $record = [];
        $record['prop_label'] = no__('_YAIM');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 7;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'aim';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YYIM
        $record = [];
        $record['prop_label'] = no__('_YYIM');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 8;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'yim';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YMSNM
        $record = [];
        $record['prop_label'] = no__('_YMSNM');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 9;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'msnm';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YLOCATION
        $record = [];
        $record['prop_label'] = no__('_YLOCATION');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 10;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'city';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YOCCUPATION
        $record = [];
        $record['prop_label'] = no__('_YOCCUPATION');
        $record['prop_dtype'] = '1';
        $record['prop_weight'] = '11';
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 0,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'occupation';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _SIGNATURE
        $record = [];
        $record['prop_label'] = no__('_SIGNATURE');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 12;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 1,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'signature';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _EXTRAINFO
        $record = [];
        $record['prop_label'] = no__('_EXTRAINFO');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 13;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 1,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'extrainfo';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // _YINTERESTS
        $record = [];
        $record['prop_label'] = no__('_YINTERESTS');
        $record['prop_dtype'] = 1;
        $record['prop_weight'] = 14;
        $record['prop_validation'] = serialize([
            'required'    => 0,
            'viewby'      => 0,
            'displaytype' => 1,
            'listoptions' => '',
            'note'        => '',
            'pattern'     => null,
        ]);
        $record['prop_attribute_name'] = 'interests';
        $prop = new PropertyEntity();
        $prop->merge($record);
        $this->entityManager->persist($prop);

        // flush all persisted entities
        $this->entityManager->flush();

        // Set "tzoffset" and "avatar" to be shown in the registration form by default.
        $this->setVar('dudregshow', ['tzoffset', 'avatar']);
    }
}
