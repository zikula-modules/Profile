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
 * "Last Seen" block.
 */
class LastSeenBlock extends AbstractBlockHandler
{
    /**
     * @inheritdoc
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:LastSeenblock:', $title . '::', ACCESS_READ)) {
            return '';
        }

        // Defaults
        if (!isset($properties['amount']) || empty($properties['amount'])) {
            $properties['amount'] = 5;
        }

        // get last x logged in user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', [
            'sortby' => 'lastlogin',
            'numitems' => $properties['amount'],
            'sortorder' => 'DESC'
        ]);

        return $this->renderView('@ZikulaProfileModule/Block/lastSeen.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFormClassName()
    {
        return 'Zikula\ProfileModule\Block\Form\Type\LastSeenBlockType';
    }

    /**
     * @inheritdoc
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/lastSeen_modify.html.twig';
    }
}
