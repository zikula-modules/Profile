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

use Doctrine\Common\Collections\ArrayCollection;
use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\BlocksModule\Block\Form\Type\HtmlBlockType;

/**
 * A user-customizable block.
 */
class UserBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        $currentUserApi = $this->get('zikula_users_module.current_user');
        if ($title == '') {
            $title = $this->__f('Custom block content for %s', ['%s' => $currentUserApi->get('uname')]);
        }
        if (!$this->hasPermission('Userblock::', $title.'::', ACCESS_READ)) {
            return '';
        }
        /** @var ArrayCollection $attributes */
        $attributes = $currentUserApi->get('attributes');
        if (!$currentUserApi->isLoggedIn() || (bool) $attributes->get('ublockon') != true) {
            return '';
        }

        return nl2br($attributes->get('ublock'));
    }

    public function getFormClassName()
    {
        return HtmlBlockType::class;
    }

    public function getFormTemplate()
    {
        return '@ZikulaBlocksModule/Block/html_modify.html.twig';
    }
}
