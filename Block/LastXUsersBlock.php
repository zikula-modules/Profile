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

use ModUtil;
use Zikula\BlocksModule\AbstractBlockHandler;

/**
 * "Last X Registered Users" block.
 */
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

        // Defaults
        if (!isset($properties['amount']) || empty($properties['amount'])) {
            $properties['amount'] = 5;
        }

        // get last x registered user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', [
            'sortby'    => 'user_regdate',
            'numitems'  => $properties['amount'],
            'sortorder' => 'DESC',
        ]);

        return $this->renderView('@ZikulaProfileModule/Block/lastXUsers.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return 'Zikula\ProfileModule\Block\Form\Type\LastXUsersBlockType';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/lastXUsers_modify.html.twig';
    }
}
