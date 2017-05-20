<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Zikula\Common\Translator\TranslatorTrait;

class ActionsMenu implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use TranslatorTrait;

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function adminMenu(FactoryInterface $factory, array $options)
    {
        $this->setTranslator($this->container->get('translator.default'));
        $permissionApi = $this->container->get('zikula_permissions_module.api.permission');
        $user = $options['user'];
        $menu = $factory->createItem('adminActions');
        $menu->setChildrenAttribute('class', 'list-inline');
        if ($permissionApi->hasPermission('ZikulaUsersModule::', '::', ACCESS_EDIT)) {
            $menu->addChild($this->__f('Edit ":name"', [':name' => $user->getUname()]), [
                'route' => 'zikulausersmodule_useradministration_modify',
                'routeParameters' => ['user' => $user->getUid()],
            ])->setAttribute('icon', 'fa fa-pencil');
        }
        if ($permissionApi->hasPermission('ZikulaUsersModule::', '::', ACCESS_DELETE)) {
            $menu->addChild($this->__f('Delete ":name"', [':name' => $user->getUname()]), [
                'route' => 'zikulausersmodule_useradministration_delete',
                'routeParameters' => ['user' => $user->getUid()],
            ])->setAttribute('icon', 'fa fa-trash-o');
        }

        return $menu;
    }
}
