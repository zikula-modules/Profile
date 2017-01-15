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
use Zikula\SettingsModule\SettingsConstant;

/**
 * "Members Online" block.
 */
class MembersOnlineBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:MembersOnlineblock:', $title.'::', ACCESS_READ)) {
            return '';
        }

        // Defaults
        if (!isset($properties['lengthmax']) || empty($properties['lengthmax'])) {
            $properties['lengthmax'] = 30;
        }

        $currentUserApi = $this->get('zikula_users_module.current_user');
        $userId = $currentUserApi->get('uid');
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getallonline');
        $usersOnline = [];
        if ($users) {
            foreach ($users['unames'] as $user) {
                $usersOnline[] = $user;
            }
        }

        return $this->renderView('@ZikulaProfileModule/Block/membersOnline.html.twig', [
            'currentUserId'         => $userId,
            'usersOnline'           => $usersOnline,
            'maxLength'             => $properties['lengthmax'],
            'messageModule'         => $this->get('zikula_extensions_module.api.variable')->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, ''),
            'amountOfOnlineMembers' => $users['numusers'],
            'amountOfOnlineGuests'  => $users['numguests'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormClassName()
    {
        return 'Zikula\ProfileModule\Block\Form\Type\MembersOnlineBlockType';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/membersOnline_modify.html.twig';
    }
}
