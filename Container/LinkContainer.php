<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Container;

use ModUtil;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Common\Translator\Translator;
use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\ExtensionsModule\Api\ExtensionApi;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\PermissionsModule\Api\PermissionApi;
use Zikula\UsersModule\Api\CurrentUserApi;
use Zikula\UsersModule\Container\LinkContainer as UsersLinkContainer;

class LinkContainer implements LinkContainerInterface
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PermissionApi
     */
    private $permissionApi;

    /**
     * @var ExtensionApi
     */
    private $extensionApi;

    /**
     * @var VariableApi
     */
    private $variableApi;

    /**
     * @var CurrentUserApi
     */
    private $currentUserApi;

    /**
     * @var UsersLinkContainer
     */
    private $usersLinkContainer;

    /**
     * LinkContainer constructor.
     *
     * @param Translator         $translator         Translator service instance
     * @param RouterInterface    $router             RouterInterface service instance
     * @param PermissionApi      $permissionApi      PermissionApi service instance
     * @param ExtensionApi       $extensionApi       ExtensionApi service instance
     * @param VariableApi        $variableApi        VariableApi service instance
     * @param CurrentUserApi     $currentUserApi     CurrentUserApi service instance
     * @param UsersLinkContainer $usersLinkContainer UsersLinkContainer service instance
     */
    public function __construct(
        $translator,
        RouterInterface $router,
        PermissionApi $permissionApi,
        ExtensionApi $extensionApi,
        VariableApi $variableApi,
        CurrentUserApi $currentUserApi,
        UsersLinkContainer $usersLinkContainer)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->permissionApi = $permissionApi;
        $this->extensionApi = $extensionApi;
        $this->variableApi = $variableApi;
        $this->currentUserApi = $currentUserApi;
        $this->usersLinkContainer = $usersLinkContainer;
    }

    /**
     * get Links of any type for this extension
     * required by the interface
     *
     * @param string $type
     * @return array
     */
    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $method = 'get' . ucfirst(strtolower($type));
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return [];
    }

    /**
     * get the Admin links for this extension
     *
     * @return array
     */
    private function getAdmin()
    {
        $links = [];

        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_EDIT)) {
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_admin_view'),
                'text' => $this->translator->__('Fields'),
                'icon' => 'list'
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_ADD)) {
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_admin_edit'),
                'text' => $this->translator->__('Create new field'),
                'icon' => 'plus text-success'
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_ADMIN)) {
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_config_config'),
                'text' => $this->translator->__('Settings'),
                'icon' => 'wrench'
            ];
        }
        if ($this->permissionApi->hasPermission('ZikulaUsersModule::', '::', ACCESS_MODERATE)) {
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_admin_view'),
                'text' => $this->translator->__('Users administration'),
                'icon' => 'user',
                'links' => $this->usersLinkContainer->getAdmin()
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_EDIT)) {
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_admin_help'),
                'text' => $this->translator->__('Help'),
                'icon' => 'ambulance text-danger'
            ];
        }

        return $links;
    }

    /**
     * get the User links for this extension
     *
     * @return array
     */
    private function getUser()
    {
        $links = [];

        $profileIsAvailable = null !== $this->extensionApi->getModuleInstanceOrNull($this->getBundleName());

        if ($this->currentUserApi->isLoggedIn()) {
            if ($this->permissionApi->hasPermission('ZikulaUsersModule::', '::', ACCESS_READ)) {
                $links[] = [
                    'url' => $this->router->generate('zikulausersmodule_user_index'),
                    'icon' => 'wrench',
                    'text' => $this->translator->__('Account settings')
                ];
            }

            if ($profileIsAvailable && $this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_READ)) {
                $links[] = [
                    'url' => $this->router->generate('zikulaprofilemodule_profile_display'),
                    'text' => $this->translator->__('Profile'),
                    'icon' => 'user',
                    'links' => [
                        [
                            'url' => $this->router->generate('zikulaprofilemodule_profile_edit'),
                            'text' => $this->translator->__('Edit profile')
                        ],
                        [
                            'url' => $this->router->generate('zikulazauthmodule_account_changeemail'),
                            'text' => $this->translator->__('Change email address')
                        ],
                        [
                            'url' => $this->router->generate('zikulazauthmodule_account_changepassword'),
                            'text' => $this->translator->__('Change password')
                        ]
                    ]
                ];
            }

            $messageModule = $this->variableApi::get(VariableApi::CONFIG, 'messagemodule', '');
            if ($messageModule != '' && ModUtil::available($messageModule) && $this->permissionApi->hasPermission($messageModule . '::', '::', ACCESS_READ)) {
                $links[] = [
                    'url' => ModUtil::url($messageModule, 'user', 'main'),
                    'text' => $this->translator->__('Messages'),
                    'icon' => 'envelope'
                ];
            }
        }

        if ($profileIsAvailable && $this->permissionApi->hasPermission($this->getBundleName() . ':Members:', '::', ACCESS_READ)) {
            $membersLinks = [];
            if ($this->permissionApi->hasPermission($this->getBundleName() . ':Members:recent', '::', ACCESS_READ)) {
                $membersLinks[] = [
                    'url' => $this->router->generate('zikulaprofilemodule_members_recent'),
                    'text' => $this->translator->__f('Last %s registered users', $this->variableApi->get($this->getBundleName(), 'recentmembersitemsperpage', 10))
                );
            ]
            if ($this->permissionApi->hasPermission($this->getBundleName() . ':Members:online', '::', ACCESS_READ)) {
                $membersLinks[] = [
                    'url' => $this->router->generate('zikulaprofilemodule_members_online'),
                    'text' => $this->translator->__('Users online')
                ];
            }
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_members_view'),
                'text' => $this->translator->__('Registered users'),
                'icon' => 'list',
                'links' => $membersLinks
            ];
        }

        return $links;
    }

    /**
     * get the Account links for this extension
     *
     * @return array
     */
    private function getAccount()
    {
        $links = [];

        // do not show any account links if Profile is not the Profile manager
        $profileModule = $this->variableApi::get(VariableApi::CONFIG, 'profilemodule', '');
        if ($profileModule != $this->getBundleName()) {
            return $links;
        }

        if (!$this->currentUserApi->isLoggedIn()) {
            return $links;
        }

        $links[] = [
            'url' => $this->router->generate('zikulaprofilemodule_profile_display', ['uid' => $this->currentUserApi->get('uid')]),
            'title' => $this->translator->__('Profile'),
            'icon' => 'user'
        ];

        if ($this->permissionApi->hasPermission($this->getBundleName() . ':Members:', '::', ACCESS_READ)) {
            $links[] = [
                'url' => $this->router->generate('zikulaprofilemodule_members_view'),
                'title' => $this->translator->__('Registered users'),
                'icon' => 'users'
            ];
        }

        // check if the users block exists
        $blocks = ModUtil::apiFunc('ZikulaBlocksModule', 'user', 'getall');
        $profileModuleId = ModUtil::getIdFromName($this->currentUserApi->get($this->getBundleName()));
        $found = false;
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if ($block['mid'] == $profileModuleId && $block['bkey'] == 'user') {
                    $found = true;
                    break;
                }
            }
        }
        if ($found) {
            $links[] = [
                'url'   => $this->router->generate('zikulaprofilemodule_user_usersblock'),
                'title' => $this->translator->__('Personal custom block'),
                'icon'  => 'home'
            ];
        }

        return $links;
    }

    /**
     * set the BundleName as required by the interface
     *
     * @return string
     */
    public function getBundleName()
    {
        return 'ZikulaProfileModule';
    }
}
