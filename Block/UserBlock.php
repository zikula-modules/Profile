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


        if (!$currentUserApi->isLoggedIn() || \UserUtil::getVar('ublockon') != 1) {
            return '';
        }

        return nl2br(\UserUtil::getVar('ublock'));
    }

    public function getFormClassName()
    {
        return 'Zikula\BlocksModule\Block\Form\Type\HtmlBlockType';
    }

    public function getFormTemplate()
    {
        return '@ZikulaBlocksModule/Block/html_modify.html.twig';
    }
}
