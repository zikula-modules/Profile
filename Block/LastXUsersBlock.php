<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block;

use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\ProfileModule\Block\Form\Type\LastXUsersBlockType;

class LastXUsersBlock extends AbstractBlockHandler
{
    /**
     * Display block.
     *
     * @param array $properties
     *
     * @return string the rendered block
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:LastXUsersblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        return $this->renderView('@ZikulaProfileModule/Block/lastXUsers.html.twig', [
            'users' => $this->get('zikula_users_module.user_repository')->findBy([], ['user_regdate' => 'DESC'], $properties['amount']),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [
            'translator' => $this->get('translator.default'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return LastXUsersBlockType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/lastXUsers_modify.html.twig';
    }
}
