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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use System;
use Zikula\Core\AbstractExtensionInstaller;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\Form\Type\AvatarType;

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
            'viewregdate'               => false,
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
                // get old data and drop table
                $sql = "SELECT * FROM user_property";
                $properties = $this->entityManager->getConnection()->fetchAll($sql);
                $sql = "DROP TABLE user_property";
                $this->entityManager->getConnection()->executeQuery($sql);
                // create new table & insert upgraded data
                $this->schemaTool->create($this->entities);
                $propertyToIdMap = [];
                foreach ($properties as $property) {
                    $newProperty = $this->container->get('zikula_profile_module.helper.upgrade_helper')->mergeToNewProperty($property);
                    $this->entityManager->persist($newProperty);
                    $this->entityManager->flush();
                    $propertyToIdMap[$property['attributename']] = $newProperty->getId();
                }
                // upgrade user attribute data to match new ids
                $prefix = $this->container->getParameter('zikula_profile_module.property_prefix');
                $attributes = $this->entityManager->getRepository('ZikulaUsersModule:UserAttributeEntity')->findAll();
                $i = 0;
                foreach ($attributes as $attribute) {
                    if (array_key_exists($attribute->getName(), $propertyToIdMap)) {
                        $attribute->setName($prefix . ':' . $propertyToIdMap[$attribute->getName()]);
                        $i++;
                    }
                    if ($i > 50) {
                        $this->entityManager->flush();
                        $i = 0;
                    }
                }
                $this->entityManager->flush();
                // update boolean vars
                $this->setVar('viewregdate', (bool) $this->getVar('viewregdate'));
            case '3.0.0':
                // future upgrades
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
        $prop = new PropertyEntity();
        $prop->setFormType(TextType::class);
        $prop->setLabel($this->__('Real Name'));
        $prop->setWeight(1);
        $this->entityManager->persist($prop);

        // _UFAKEMAIL
        $prop = new PropertyEntity();
        $prop->setFormType(TextType::class);
        $prop->setLabel($this->__('Public Email'));
        $prop->setWeight(2);
        $this->entityManager->persist($prop);

        // _YOURHOMEPAGE
        $prop = new PropertyEntity();
        $prop->setFormType(TextType::class);
        $prop->setLabel($this->__('Homepage'));
        $prop->setWeight(3);
        $this->entityManager->persist($prop);

        // _TIMEZONE
        $prop = new PropertyEntity();
        $prop->setFormType(TimezoneType::class);
        $prop->setLabel($this->__('Timezone'));
        $prop->setWeight(4);
        $this->entityManager->persist($prop);

        // _YOURAVATAR
        $prop = new PropertyEntity();
        $prop->setFormType(AvatarType::class);
        $prop->setLabel($this->__('Avatar'));
        $prop->setWeight(5);
        $this->entityManager->persist($prop);

        // _YLOCATION
        $prop = new PropertyEntity();
        $prop->setFormType(TextType::class);
        $prop->setLabel($this->__('Location'));
        $prop->setWeight(6);
        $this->entityManager->persist($prop);

        // _YOCCUPATION
        $prop = new PropertyEntity();
        $prop->setFormType(TextType::class);
        $prop->setLabel($this->__('Occupation'));
        $prop->setWeight(7);
        $this->entityManager->persist($prop);

        // _SIGNATURE
        $prop = new PropertyEntity();
        $prop->setFormType(TextType::class);
        $prop->setLabel($this->__('Signature'));
        $prop->setWeight(8);
        $this->entityManager->persist($prop);

        // _EXTRAINFO
        $prop = new PropertyEntity();
        $prop->setFormType(TextareaType::class);
        $prop->setLabel($this->__('Extra info'));
        $prop->setWeight(9);
        $this->entityManager->persist($prop);

        // flush all persisted entities
        $this->entityManager->flush();
    }
}
