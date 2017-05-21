<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block;

use Zikula\BlocksModule\AbstractBlockHandler;

class FeaturedUserBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        if (!$this->hasPermission('ZikulaProfileModule:FeaturedUserblock:', $properties['title'] . '::', ACCESS_READ)) {
            return '';
        }
        $user = $this->get('zikula_users_module.user_repository')->findOneBy(['uname' => $properties['username']]);
        if (empty($user)) {
            return '';
        }

        return $this->renderView('@ZikulaProfileModule/Block/featuredUser.html.twig', [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'user' => $user,
            'blockProperties' => $properties,
            'activeProperties' => $this->get('zikula_profile_module.property_repository')->findBy(['active' => true]),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [
            'activeProperties' => $this->get('zikula_profile_module.property_repository')->findBy(['active' => true]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return 'Zikula\ProfileModule\Block\Form\Type\FeaturedUserBlockType';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/featuredUser_modify.html.twig';
    }
}
