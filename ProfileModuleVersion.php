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

/**
 * Profile module version information and other metadata.
 */
class ProfileModuleVersion extends \Zikula_AbstractVersion
{
    /**
     * Provides an array of standard Zikula Extension metadata.
     *
     * @return array Zikula Extension metadata.
     */
    public function getMetaData()
    {
        return [
            'displayname' => $this->__('Profile'),
            'oldnames' => ['Profile'],
            'description' => $this->__('Provides a personal account control panel for each registered user, an interface to administer the personal information items displayed within it, and a registered users list functionality. Works in close unison with the \'Users\' module.'),
            'url' => $this->__('profile'),
            'version' => '2.1.0',
            'core_min' => '1.4.0',
            'core_max' => '1.4.99',
            'capabilities' => [
                'profile' => ['version' => '1.0']
            ],
            'securityschema' => [
                $this->name.'::' => '::',
                $this->name.'::view' => '::',
                $this->name.'::item' => 'DynamicUserData PropertyName::DynamicUserData PropertyID',
                $this->name.':Members:' => '::',
                $this->name.':Members:recent' => '::',
                $this->name.':Members:online' => '::'
            ]
        ];
    }
}
