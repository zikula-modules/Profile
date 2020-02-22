<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Zikula\BlocksModule\Entity\RepositoryInterface\BlockRepositoryInterface;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\MenuModule\ExtensionMenu\ExtensionMenuInterface;
use Zikula\PermissionsModule\Api\ApiInterface\PermissionApiInterface;
use Zikula\SettingsModule\SettingsConstant;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Collector\MessageModuleCollector;
use Zikula\UsersModule\Constant as UsersConstant;
use Zikula\ZAuthModule\ZAuthConstant;

class ExtensionMenu implements ExtensionMenuInterface
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var PermissionApiInterface
     */
    private $permissionApi;

    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var CurrentUserApiInterface
     */
    private $currentUserApi;

    /**
     * @var MessageModuleCollector
     */
    private $messageModuleCollector;

    /**
     * @var BlockRepositoryInterface
     */
    private $blocksRepository;

    public function __construct(
        FactoryInterface $factory,
        PermissionApiInterface $permissionApi,
        ZikulaHttpKernelInterface $kernel,
        VariableApiInterface $variableApi,
        CurrentUserApiInterface $currentUserApi,
        MessageModuleCollector $messageModuleCollector,
        BlockRepositoryInterface $blockRepository
    ) {
        $this->factory = $factory;
        $this->permissionApi = $permissionApi;
        $this->kernel = $kernel;
        $this->variableApi = $variableApi;
        $this->currentUserApi = $currentUserApi;
        $this->messageModuleCollector = $messageModuleCollector;
        $this->blocksRepository = $blockRepository;
    }

    public function get(string $type = self::TYPE_ADMIN): ?ItemInterface
    {
        $method = 'get' . ucfirst(mb_strtolower($type));
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return null;
    }

    private function getAdmin(): ?ItemInterface
    {
        $menu = $this->factory->createItem('profileAdminMenu');
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_EDIT)) {
            $menu->addChild('Property list', [
                'route' => 'zikulaprofilemodule_property_list',
            ])->setAttribute('icon', 'fas fa-list');
        }
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_ADD)) {
            $menu->addChild('Create new property', [
                'route' => 'zikulaprofilemodule_property_edit',
            ])->setAttribute('icon', 'fas fa-plus')
            ->setLinkAttribute('class', 'text-success');
        }
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_ADMIN)) {
            $menu->addChild('Settings', [
                'route' => 'zikulaprofilemodule_config_config',
            ])->setAttribute('icon', 'fas fa-wrench');
        }

        return 0 === $menu->count() ? null : $menu;
    }

    private function getUser(): ?ItemInterface
    {
        $menu = $this->factory->createItem('profileUserMenu');
        if ($this->currentUserApi->isLoggedIn()) {
            if ($this->permissionApi->hasPermission(UsersConstant::MODNAME . '::', '::', ACCESS_READ)) {
                $menu->addChild('Account menu', [
                    'route' => 'zikulausersmodule_account_menu',
                ])->setAttribute('icon', 'fas fa-user-circle');
            }

            if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_READ)) {
                $menu->addChild('Profile', [
                    'route' => 'zikulaprofilemodule_profile_display',
                ])->setAttribute('icon', 'fas fa-user');

                $menu['Profile']->addChild('Display profile', [
                    'route' => 'zikulaprofilemodule_profile_display',
                ]);
                $menu['Profile']->addChild('Edit profile', [
                    'route' => 'zikulaprofilemodule_profile_edit',
                ]);

                $attributes = $this->currentUserApi->get('attributes');
                $authMethod = $attributes->offsetExists(UsersConstant::AUTHENTICATION_METHOD_ATTRIBUTE_KEY)
                    ? $attributes->get(UsersConstant::AUTHENTICATION_METHOD_ATTRIBUTE_KEY)->getValue()
                    : ZAuthConstant::AUTHENTICATION_METHOD_UNAME
                ;
                $zauthMethods = [
                    ZAuthConstant::AUTHENTICATION_METHOD_EITHER,
                    ZAuthConstant::AUTHENTICATION_METHOD_UNAME,
                    ZAuthConstant::AUTHENTICATION_METHOD_EMAIL
                ];
                if (in_array($authMethod, $zauthMethods)) {
                    $menu['Profile']->addChild('Change email address', [
                        'route' => 'zikulazauthmodule_account_changeemail',
                    ]);
                    $menu['Profile']->addChild('Change password', [
                        'route' => 'zikulazauthmodule_account_changepassword',
                    ]);
                }
            }

            $messageModule = $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '');
            if (null !== $messageModule && '' !== $messageModule && $this->kernel->isBundle($messageModule)
                && $this->permissionApi->hasPermission($messageModule . '::', '::', ACCESS_READ)
            ) {
                $menu->addChild('Messages', [
                    'uri' => $this->messageModuleCollector->getSelected()->getInboxUrl(),
                ])->setAttribute('icon', 'fas fa-envelope');
            }
        }

        $component = $this->getBundleName() . ':Members:';
        if ($this->permissionApi->hasPermission($component, '::', ACCESS_READ)) {
            $menu->addChild('Members', [
                'route' => 'zikulaprofilemodule_members_list',
            ])->setAttribute('icon', 'fas fa-users');

            if ($this->permissionApi->hasPermission($component, '::', ACCESS_READ)) {
                $menu['Members']->addChild('Change password', [
                    'route' => 'zikulaprofilemodule_members_list',
                ])->setAttribute('icon', 'fas fa-user-friends');
            }
            if ($this->permissionApi->hasPermission($component . 'recent', '::', ACCESS_READ)) {
                $menu['Members']->addChild('Last %s% registered users', [
                    'route' => 'zikulaprofilemodule_members_recent',
                ])->setExtra('translation_params', ['%s%' => $this->variableApi->get($this->getBundleName(), 'recentmembersitemsperpage', 10)])
                    ->setAttribute('icon', 'fas fa-door-open');
            }
            if ($this->permissionApi->hasPermission($component . 'online', '::', ACCESS_READ)) {
                $menu['Members']->addChild('Users online', [
                    'route' => 'zikulaprofilemodule_members_online',
                ])->setAttribute('icon', 'fas fa-user-check');
            }
        }

        return 0 === $menu->count() ? null : $menu;
    }

    private function getAccount(): ?ItemInterface
    {
        $menu = $this->factory->createItem('profileAccountMenu');
        // do not show any account links if Profile is not the Profile manager
        $profileModule = $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_PROFILE_MODULE, '');
        if ($profileModule !== $this->getBundleName()) {
            return null;
        }

        if (!$this->currentUserApi->isLoggedIn()) {
            return null;
        }

        $menu->addChild('Profile', [
            'route' => 'zikulaprofilemodule_profile_display',
            'routeParameters' => ['uid' => $this->currentUserApi->get('uid')]
        ])->setAttribute('icon', 'fas fa-user');

        if ($this->permissionApi->hasPermission($this->getBundleName() . ':Members:', '::', ACCESS_READ)) {
            $menu->addChild('Registered users', [
                'route' => 'zikulaprofilemodule_members_list',
            ])->setAttribute('icon', 'fas fa-user-friends');
        }

        // check if the users block exists
        $block = $this->blocksRepository->findOneBy(['bkey' => 'ZikulaProfileModule:Zikula\ProfileModule\Block\UserBlock']);
        if (isset($block)) {
            $menu->addChild('Personal custom block', [
                'route' => 'zikulaprofilemodule_userblock_edit',
            ])->setAttribute('icon', 'fas fa-cube');
        }

        return 0 === $menu->count() ? null : $menu;
    }

    public function getBundleName(): string
    {
        return 'ZikulaProfileModule';
    }
}