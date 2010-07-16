<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */

class Profile_Version extends Zikula_Version
{
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname']    = $this->__('Profile manager');
        $meta['description']    = $this->__("Provides a personal account control panel for each registered user, an interface to administer the personal information items displayed within it, and a registered users list functionality. Works in close unison with the 'Users' module.");
        //! module name that appears in URL
        $meta['url']            = $this->__('profile');
        $meta['version']        = '1.5.3';
        $meta['capabilities']   = array('profile' => array('version' => '1.0'));

        $meta['securityschema'] = array('Profile::' => '::',
                'Profile::item' => 'DynamicUserData PropertyName::DynamicUserData PropertyID',
                'Profile:Members:' => '::',
                'Profile:Members:recent' => '::',
                'Profile:Members:online' => '::');
        return $meta;
    }
}