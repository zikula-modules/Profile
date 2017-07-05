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

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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
        PropertyEntity::class,
    ];

    /**
     * Provides an array containing default values for module variables (settings).
     *
     * @return array An array indexed by variable name containing the default values for those variables
     */
    protected function getDefaultModVars()
    {
        return [
            'viewregdate' => false,
            'memberslistitemsperpage' => 20,
            'onlinemembersitemsperpage' => 20,
            'recentmembersitemsperpage' => 10,
            'filterunverified' => true,
            'activeminutes' => 10,
            'allowUploads' => false,
            'shrinkLargeImages' => true,
            'maxSize' => 12000,
            'maxWidth' => 80,
            'maxHeight' => 80
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
        $locale = $this->container->get('request_stack')->getMasterRequest()->getLocale();
        $this->defaultdata($locale);

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
        if (version_compare($oldVersion, '2.0', '<')) {
            // Inform user about error, and how he can upgrade to this version
            $this->addFlash('error', $this->__('Notice: This version does not support upgrades from versions less than 2.0. Please upgrade before upgrading again to this version.'));

            return false;
        }

        switch ($oldVersion) {
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
                $locale = $this->container->get('request_stack')->getMasterRequest()->getLocale();
                $upgradeHelper = $this->container->get('zikula_profile_module.helper.upgrade_helper');
                foreach ($properties as $property) {
                    $newProperty = $upgradeHelper->mergeToNewProperty($property, $locale);
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
                        $attribute->setValue($upgradeHelper->getModifiedAttributeValue($attribute, $prefix));
                        $i++;
                    }
                    if ($i > 50) {
                        $this->entityManager->flush();
                        $i = 0;
                    }
                }
                $this->entityManager->flush();
                // update boolean vars
                $this->setVar('viewregdate', (bool)$this->getVar('viewregdate'));
                $this->setVar('filterunverified', (bool)$this->getVar('filterunverified'));
                $this->setVar('activeminutes', 10);
                // add new vars
                $this->setVar('allowUploads', false);
                $this->setVar('shrinkLargeImages', true);
                $this->setVar('maxSize', 12000);
                $this->setVar('maxWidth', 80);
                $this->setVar('maxHeight', 80);
            case '3.0.0':
                // future upgrades
        }

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

        $this->delVars();

        return true;
    }

    /**
     * Create the default data for the users module.
     */
    protected function defaultdata($locale)
    {
        $prop = new PropertyEntity();
        $prop->setId(ProfileConstant::ATTRIBUTE_NAME_DISPLAY_NAME);
        $prop->setFormType(TextType::class);
        $prop->setLabels([$locale => $this->__('Real Name')]);
        $prop->setWeight(1);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('publicemail');
        $prop->setFormType(EmailType::class);
        $prop->setLabels([$locale => $this->__('Public Email')]);
        $prop->setWeight(2);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('url');
        $prop->setFormType(UrlType::class);
        $prop->setLabels([$locale => $this->__('Homepage')]);
        $prop->setWeight(3);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('timezone');
        $prop->setFormType(TimezoneType::class);
        $prop->setLabels([$locale => $this->__('Timezone')]);
        $prop->setWeight(4);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('avatar');
        $prop->setFormType(AvatarType::class);
        $prop->setLabels([$locale => $this->__('Avatar')]);
        $prop->setWeight(5);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('city');
        $prop->setFormType(TextType::class);
        $prop->setLabels([$locale => $this->__('Location')]);
        $prop->setWeight(6);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('occupation');
        $prop->setFormType(TextType::class);
        $prop->setLabels([$locale => $this->__('Occupation')]);
        $prop->setWeight(7);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('signature');
        $prop->setFormType(TextType::class);
        $prop->setLabels([$locale => $this->__('Signature')]);
        $prop->setWeight(8);
        $this->entityManager->persist($prop);

        $prop = new PropertyEntity();
        $prop->setId('extrainfo');
        $prop->setFormType(TextareaType::class);
        $prop->setLabels([$locale => $this->__('Extra info')]);
        $prop->setWeight(9);
        $this->entityManager->persist($prop);

        $this->entityManager->flush();
    }
}
