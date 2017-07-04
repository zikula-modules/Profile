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
use Zikula\BlocksModule\Entity\RepositoryInterface\BlockRepositoryInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\PermissionsModule\Api\PermissionApi;
use Zikula\SettingsModule\SettingsConstant;
use Zikula\UsersModule\Api\CurrentUserApi;
use Zikula\UsersModule\Collector\MessageModuleCollector;
use Zikula\UsersModule\Container\LinkContainer as UsersLinkContainer;

class LinkContainer implements LinkContainerInterface
{
    /**
     * @var TranslatorInterface
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
     * @var MessageModuleCollector
     */
    private $messageModuleCollector;

    /**
     * @var BlockRepositoryInterface
     */
    private $blocksRepository;

    /**
     * LinkContainer constructor.
     *
     * @param TranslatorInterface $translator Translator service instance
     * @param RouterInterface $router RouterInterface service instance
     * @param PermissionApi $permissionApi PermissionApi service instance
     * @param VariableApi $variableApi VariableApi service instance
     * @param CurrentUserApi $currentUserApi CurrentUserApi service instance
     * @param UsersLinkContainer $usersLinkContainer UsersLinkContainer service instance
     * @param MessageModuleCollector $messageModuleCollector
     * @param BlockRepositoryInterface $blockRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router,
        PermissionApi $permissionApi,
        VariableApi $variableApi,
        CurrentUserApi $currentUserApi,
        UsersLinkContainer $usersLinkContainer,
        MessageModuleCollector $messageModuleCollector,
        BlockRepositoryInterface $blockRepository
    ) {
        $this->translator = $translator;
        $this->router = $router;
        $this->permissionApi = $permissionApi;
        $this->variableApi = $variableApi;
        $this->currentUserApi = $currentUserApi;
        $this->usersLinkContainer = $usersLinkContainer;
        $this->messageModuleCollector = $messageModuleCollector;
        $this->blocksRepository = $blockRepository;
    }

    /**
     * get Links of any type for this extension
     * required by the interface.
     *
     * @param string $type
     *
     * @return array
     */
    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $method = 'get'.ucfirst(strtolower($type));
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return [];
    }

    /**
     * get the Admin links for this extension.
     *
     * @return array
     */
    private function getAdmin()
    {
        $links = [];

        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_EDIT)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_property_list'),
                'text' => $this->translator->__('Property list', 'zikulaprofilemodule'),
                'icon' => 'list',
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_ADD)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_property_edit'),
                'text' => $this->translator->__('Create new property', 'zikulaprofilemodule'),
                'icon' => 'plus text-success',
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_ADMIN)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_config_config'),
                'text' => $this->translator->__('Settings', 'zikulaprofilemodule'),
                'icon' => 'wrench',
            ];
        }

        return $links;
    }

    /**
     * get the User links for this extension.
     *
     * @return array
     */
    private function getUser()
    {
        $links = [];

        if ($this->currentUserApi->isLoggedIn()) {
            if ($this->permissionApi->hasPermission('ZikulaUsersModule::', '::', ACCESS_READ)) {
                $links[] = [
                    'url'  => $this->router->generate('zikulausersmodule_account_menu'),
                    'icon' => 'user-circle-o',
                    'text' => $this->translator->__('Account menu'),
                ];
            }

            if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_READ)) {
                $links[] = [
                    'url'   => $this->router->generate('zikulaprofilemodule_profile_display'),
                    'text'  => $this->translator->__('Profile'),
                    'icon'  => 'user',
                    'links' => [
                        [
                            'url'  => $this->router->generate('zikulaprofilemodule_profile_display'),
                            'text' => $this->translator->__('Display profile'),
                        ],
                        [
                            'url'  => $this->router->generate('zikulaprofilemodule_profile_edit'),
                            'text' => $this->translator->__('Edit profile'),
                        ],
                        [
                            'url'  => $this->router->generate('zikulazauthmodule_account_changeemail'),
                            'text' => $this->translator->__('Change email address'),
                        ],
                        [
                            'url'  => $this->router->generate('zikulazauthmodule_account_changepassword'),
                            'text' => $this->translator->__('Change password'),
                        ],
                    ],
                ];
            }

            $messageModule = $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '');
            if ($messageModule != '' && ModUtil::available($messageModule) && $this->permissionApi->hasPermission($messageModule.'::', '::', ACCESS_READ)) {
                $links[] = [
                    'url'  => $this->messageModuleCollector->getSelected()->getInboxUrl(),
                    'text' => $this->translator->__('Messages'),
                    'icon' => 'envelope',
                ];
            }
        }

        if ($this->permissionApi->hasPermission($this->getBundleName().':Members:', '::', ACCESS_READ)) {
            $membersLinks = [];
            if ($this->permissionApi->hasPermission($this->getBundleName().':Members:', '::', ACCESS_READ)) {
                $membersLinks[] = [
                    'url'  => $this->router->generate('zikulaprofilemodule_members_list'),
                    'text' => $this->translator->__('Registered users'),
                    'icon' => 'users',
                ];
            }
            if ($this->permissionApi->hasPermission($this->getBundleName().':Members:recent', '::', ACCESS_READ)) {
                $membersLinks[] = [
                    'url'  => $this->router->generate('zikulaprofilemodule_members_recent'),
                    'text' => $this->translator->__f('Last %s registered users', ['%s' => $this->variableApi->get($this->getBundleName(), 'recentmembersitemsperpage', 10)]),
                ];
            }
            if ($this->permissionApi->hasPermission($this->getBundleName().':Members:online', '::', ACCESS_READ)) {
                $membersLinks[] = [
                    'url'  => $this->router->generate('zikulaprofilemodule_members_online'),
                    'text' => $this->translator->__('Users online'),
                ];
            }
            $links[] = [
                'url'   => $this->router->generate('zikulaprofilemodule_members_list'),
                'text'  => $this->translator->__('Registered users'),
                'icon'  => 'list',
                'links' => $membersLinks,
            ];
        }

        return $links;
    }

    /**
     * get the Account links for this extension.
     *
     * @return array
     */
    private function getAccount()
    {
        $links = [];

        // do not show any account links if Profile is not the Profile manager
        $profileModule = $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_PROFILE_MODULE, '');
        if ($profileModule != $this->getBundleName()) {
            return $links;
        }

        if (!$this->currentUserApi->isLoggedIn()) {
            return $links;
        }

        $links[] = [
            'url'  => $this->router->generate('zikulaprofilemodule_profile_display', ['uid' => $this->currentUserApi->get('uid')]),
            'text' => $this->translator->__('Profile'),
            'icon' => 'user',
        ];

        if ($this->permissionApi->hasPermission($this->getBundleName().':Members:', '::', ACCESS_READ)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_members_list'),
                'text' => $this->translator->__('Registered users'),
                'icon' => 'users',
            ];
        }

        // check if the users block exists
        $block = $this->blocksRepository->findOneBy(['bkey' => 'ZikulaProfileModule:Zikula\ProfileModule\Block\UserBlock']);
        if (isset($block)) {
            $links[] = [
                'url'   => $this->router->generate('zikulaprofilemodule_userblock_edit'),
                'text'  => $this->translator->__('Personal custom block'),
                'icon'  => 'cube',
            ];
        }

        return $links;
    }

    /**
     * set the BundleName as required by the interface.
     *
     * @return string
     */
    public function getBundleName()
    {
        return 'ZikulaProfileModule';
    }
}
