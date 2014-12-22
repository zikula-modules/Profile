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
        return array(
            'displayname' => $this->__('Profile'),
            'oldnames' => array('Profile'),
            'description' => $this->__('Provides a personal account control panel for each registered user, an interface to administer the personal information items displayed within it, and a registered users list functionality. Works in close unison with the \'Users\' module.'),
            'url' => $this->__('profile'),
            'version' => '2.0.0-beta',
            'core_min' => '1.4.0',
            'core_max' => '1.4.99',
            'capabilities' => array(
                'profile' => array('version' => '1.0')
            ),
            'securityschema' => array(
                $this->name.'::' => '::',
                $this->name.'::view' => '::',
                $this->name.'::item' => 'DynamicUserData PropertyName::DynamicUserData PropertyID',
                $this->name.':Members:' => '::',
                $this->name.':Members:recent' => '::',
                $this->name.':Members:online' => '::'
            )
        );
    }

}
