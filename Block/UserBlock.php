<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula - https://ziku.la/
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

    public function display(array $properties): string
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if ('' === $title) {
            $title = $this->trans('Custom block content for %s%', ['%s%' => $this->currentUserApi->get('uname')]);
        }
        if (!$this->hasPermission('Userblock::', $title . '::', ACCESS_READ)) {
            return '';
        }

        /** @var ArrayCollection $attributes */
        $attributes = $this->currentUserApi->get('attributes');
        if (!$this->currentUserApi->isLoggedIn() || true !== (bool) $attributes->get('ublockon')) {
            return '';
        }

        return nl2br($attributes->get('ublock'));
    }

    public function getFormClassName(): string
    {
        return HtmlBlockType::class;
    }

    public function getFormTemplate(): string
    {
        return '@ZikulaBlocksModule/Block/html_modify.html.twig';
    }

    /**
     * @required
     */
    public function setCurrentUserApi(CurrentUserApiInterface $currentUserApi): void
    {
        $this->currentUserApi = $currentUserApi;
    }
}
