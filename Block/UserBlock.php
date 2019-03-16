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

use Doctrine\Common\Collections\ArrayCollection;
use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\BlocksModule\Block\Form\Type\HtmlBlockType;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;

/**
 * A user-customizable block.
 */
class UserBlock extends AbstractBlockHandler
{
    /**
     * @var CurrentUserApiInterface
     */
    private $currentUserApi;

    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if ('' == $title) {
            $title = $this->__f('Custom block content for %s', ['%s' => $this->currentUserApi->get('uname')]);
        }
        if (!$this->hasPermission('Userblock::', $title.'::', ACCESS_READ)) {
            return '';
        }

        /** @var ArrayCollection $attributes */
        $attributes = $this->currentUserApi->get('attributes');
        if (!$this->currentUserApi->isLoggedIn() || true != (bool) $attributes->get('ublockon')) {
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

    /**
     * @required
     * @param CurrentUserApiInterface $currentUserApi
     */
    public function setCurrentUserApi(CurrentUserApiInterface $currentUserApi)
    {
        $this->currentUserApi = $currentUserApi;
    }
}
