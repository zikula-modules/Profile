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

namespace Zikula\ProfileModule\Container;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\BlocksModule\Entity\RepositoryInterface\BlockRepositoryInterface;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\PermissionsModule\Api\ApiInterface\PermissionApiInterface;
use Zikula\SettingsModule\SettingsConstant;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Collector\MessageModuleCollector;
use Zikula\UsersModule\Constant as UsersConstant;
use Zikula\ZAuthModule\ZAuthConstant;

class LinkContainer implements LinkContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PermissionApiInterface
     */
    private $permissionApi;

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
        TranslatorInterface $translator,
        ZikulaHttpKernelInterface $kernel,
        RouterInterface $router,
        PermissionApiInterface $permissionApi,
        VariableApiInterface $variableApi,
        CurrentUserApiInterface $currentUserApi,
        MessageModuleCollector $messageModuleCollector,
        BlockRepositoryInterface $blockRepository
    ) {
        $this->translator = $translator;
        $this->kernel = $kernel;
        $this->router = $router;
        $this->permissionApi = $permissionApi;
        $this->variableApi = $variableApi;
        $this->currentUserApi = $currentUserApi;
        $this->messageModuleCollector = $messageModuleCollector;
        $this->blocksRepository = $blockRepository;
    }

    public function getLinks(string $type = LinkContainerInterface::TYPE_ADMIN): array
    {
        $method = 'get'.ucfirst(mb_strtolower($type));
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * Get the Admin links for this extension.
     */
    private function getAdmin(): array
    {
        $links = [];

        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_EDIT)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_property_list'),
                'text' => $this->translator->trans('Property list', [], 'zikulaprofilemodule'),
                'icon' => 'list'
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_ADD)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_property_edit'),
                'text' => $this->translator->trans('Create new property', [], 'zikulaprofilemodule'),
                'icon' => 'plus text-success'
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_ADMIN)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_config_config'),
                'text' => $this->translator->trans('Settings', [], 'zikulaprofilemodule'),
                'icon' => 'wrench'
            ];
        }

        return $links;
    }

    /**
     * Get the User links for this extension.
     */
    private function getUser(): array
    {
        $links = [];

        if ($this->currentUserApi->isLoggedIn()) {
            if ($this->permissionApi->hasPermission(UsersConstant::MODNAME . '::', '::', ACCESS_READ)) {
                $links[] = [
                    'url'  => $this->router->generate('zikulausersmodule_account_menu'),
                    'icon' => 'user-circle',
                    'text' => $this->translator->trans('Account menu', [], 'zikulaprofilemodule')
                ];
            }

            if ($this->permissionApi->hasPermission($this->getBundleName().'::', '::', ACCESS_READ)) {
                $subLinks = [
                    [
                        'url'  => $this->router->generate('zikulaprofilemodule_profile_display'),
                        'text' => $this->translator->trans('Display profile', [], 'zikulaprofilemodule'),
                    ],
                    [
                        'url'  => $this->router->generate('zikulaprofilemodule_profile_edit'),
                        'text' => $this->translator->trans('Edit profile', [], 'zikulaprofilemodule'),
                    ]
                ];
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
                    $subLinks[] = [
                        'url'  => $this->router->generate('zikulazauthmodule_account_changeemail'),
                        'text' => $this->translator->trans('Change email address', [], 'zikulaprofilemodule'),
                    ];
                    $subLinks[] = [
                        'url'  => $this->router->generate('zikulazauthmodule_account_changepassword'),
                        'text' => $this->translator->trans('Change password', [], 'zikulaprofilemodule'),
                    ];
                }
                $links[] = [
                    'url'   => $this->router->generate('zikulaprofilemodule_profile_display'),
                    'text'  => $this->translator->trans('Profile', [], 'zikulaprofilemodule'),
                    'icon'  => 'user',
                    'links' => $subLinks
                ];
            }

            $messageModule = $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '');
            if (null !== $messageModule && '' !== $messageModule && $this->kernel->isBundle($messageModule)
                && $this->permissionApi->hasPermission($messageModule . '::', '::', ACCESS_READ)
            ) {
                $links[] = [
                    'url'  => $this->messageModuleCollector->getSelected()->getInboxUrl(),
                    'text' => $this->translator->trans('Messages', [], 'zikulaprofilemodule'),
                    'icon' => 'envelope'
                ];
            }
        }

        $component = $this->getBundleName() . ':Members:';
        if ($this->permissionApi->hasPermission($component, '::', ACCESS_READ)) {
            $subLinks = [];
            if ($this->permissionApi->hasPermission($component, '::', ACCESS_READ)) {
                $subLinks[] = [
                    'url'  => $this->router->generate('zikulaprofilemodule_members_list'),
                    'text' => $this->translator->trans('Registered users', [], 'zikulaprofilemodule'),
                    'icon' => 'user-friends'
                ];
            }
            if ($this->permissionApi->hasPermission($component . 'recent', '::', ACCESS_READ)) {
                $subLinks[] = [
                    'url'  => $this->router->generate('zikulaprofilemodule_members_recent'),
                    'text' => $this->translator->trans('Last %s registered users', ['%s' => $this->variableApi->get($this->getBundleName(), 'recentmembersitemsperpage', 10)], 'zikulaprofilemodule'),
                    'icon' => 'door-open'
                ];
            }
            if ($this->permissionApi->hasPermission($component . 'online', '::', ACCESS_READ)) {
                $subLinks[] = [
                    'url'  => $this->router->generate('zikulaprofilemodule_members_online'),
                    'text' => $this->translator->trans('Users online', [], 'zikulaprofilemodule'),
                    'icon' => 'user-check'
                ];
            }
            $links[] = [
                'url'   => $this->router->generate('zikulaprofilemodule_members_list'),
                'text'  => $this->translator->trans('Members', [], 'zikulaprofilemodule'),
                'icon' => 'users',
                'links' => $subLinks
            ];
        }

        return $links;
    }

    /**
     * Get the Account links for this extension.
     */
    private function getAccount(): array
    {
        $links = [];

        // do not show any account links if Profile is not the Profile manager
        $profileModule = $this->variableApi->getSystemVar(SettingsConstant::SYSTEM_VAR_PROFILE_MODULE, '');
        if ($profileModule !== $this->getBundleName()) {
            return $links;
        }

        if (!$this->currentUserApi->isLoggedIn()) {
            return $links;
        }

        $links[] = [
            'url'  => $this->router->generate('zikulaprofilemodule_profile_display', ['uid' => $this->currentUserApi->get('uid')]),
            'text' => $this->translator->trans('Profile', [], 'zikulaprofilemodule'),
            'icon' => 'user'
        ];

        if ($this->permissionApi->hasPermission($this->getBundleName() . ':Members:', '::', ACCESS_READ)) {
            $links[] = [
                'url'  => $this->router->generate('zikulaprofilemodule_members_list'),
                'text' => $this->translator->trans('Registered users', [], 'zikulaprofilemodule'),
                'icon' => 'user-friends'
            ];
        }

        // check if the users block exists
        $block = $this->blocksRepository->findOneBy(['bkey' => 'ZikulaProfileModule:Zikula\ProfileModule\Block\UserBlock']);
        if (isset($block)) {
            $links[] = [
                'url'   => $this->router->generate('zikulaprofilemodule_userblock_edit'),
                'text'  => $this->translator->trans('Personal custom block', [], 'zikulaprofilemodule'),
                'icon'  => 'cube'
            ];
        }

        return $links;
    }

    public function getBundleName(): string
    {
        return 'ZikulaProfileModule';
    }
}
