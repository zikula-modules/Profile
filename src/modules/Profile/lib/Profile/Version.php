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

class Profile_Version extends Zikula_AbstractVersion
{
    public function getMetaData()
    {
        return array(
            'displayname'   => $this->__('Profile'),
            'description'   => $this->__('Provides a personal account control panel for each registered user, an interface to administer the personal information items displayed within it, and a registered users list functionality. Works in close unison with the \'Users\' module.'),

            'url'           => $this->__('profile'),

            'version'       => '1.6.0',
            'core_min'      => '1.3.0',

            'capabilities'  => array(
                'profile'                   => array(
                    'version'       => '1.0'
                ),
                HookUtil::PROVIDER_CAPABLE  => array(
                    'enabled'       => true
                ),

            ),

            'securityschema'=> array(
                'Profile::'                 => '::',
                'Profile:view:'             => '::',
                'Profile::item'             => 'DynamicUserData PropertyName::DynamicUserData PropertyID',
                'Profile:Members:'          => '::',
                'Profile:Members:recent'    => '::',
                'Profile:Members:online'    => '::'
            ),
        );
    }

    protected function setupHookBundles()
    {
        $bundle = new Zikula_HookManager_ProviderBundle($this->name, 'modulehook_area.profile.profile', $this->__('Profile (dynamic user data) providers'));
        $bundle->addHook('hookhandler.profile.ui.view', 'ui.view', 'Profile_HookHandler_ProfileProvider', 'uiView', 'profile.service');
        $bundle->addHook('hookhandler.profile.ui.edit', 'ui.edit', 'Profile_HookHandler_ProfileProvider', 'uiEdit', 'profile.service');
        $bundle->addHook('hookhandler.profile.validate.edit', 'validate.edit', 'Profile_HookHandler_ProfileProvider', 'validateEdit', 'profile.service');
        $bundle->addHook('hookhandler.profile.process.edit', 'process.edit', 'Profile_HookHandler_ProfileProvider', 'processEdit', 'profile.service');
        $this->registerHookProviderBundle($bundle);
    }
}