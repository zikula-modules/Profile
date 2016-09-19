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
use UserUtil;
use Zikula\BlocksModule\AbstractBlockHandler;

/**
 * "Featured User" block.
 */
class FeaturedUserBlock extends AbstractBlockHandler
{
    /**
     * @inheritdoc
     */
    public function display(array $properties)
    {
        $title = !empty($properties['title']) ? $properties['title'] : '';
        if (!$this->hasPermission('ZikulaProfileModule:FeaturedUserblock:', $title . '::', ACCESS_READ)) {
            return '';
        }

        // If there's no user to show, nothing to do
        if (!isset($properties['username']) || empty($properties['username'])) {
            return false;
        }

        // Defaults
        if (!isset($properties['fieldstoshow']) || !is_array($properties['fieldstoshow']) || empty($properties['fieldstoshow'])) {
            $properties['fieldstoshow'] = [];
        }
        if (!isset($properties['showregdate']) || !is_bool($properties['showregdate'])) {
            $properties['showregdate'] = false;
        }

        $userInfo = UserUtil::getVars(UserUtil::getIdFromName($properties['username']));
        $currentUserApi = $this->get('zikula_users_module.current_user');

        // Check if the user is watching its own profile or if he is admin
        $currentUser = $currentUserApi->get('uid');
        $isMember = $currentUser >= 2;
        $sameUser = $currentUser == $userInfo['uid'];
        $isAdmin = $this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN);

        // get all active profile fields
        $dudArray = [];
        $activeDuds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['index' => 'prop_label']);
        foreach ($activeDuds as $dudLabel => $activeDud) {
            // check if the attribute is set to be shown in the block
            if (!in_array($activeDud['prop_attribute_name'], $properties['fieldstoshow'])) {
                continue;
            }
            // discard empty fields
            if (empty($userInfo['__ATTRIBUTES__'][$activeDud['prop_attribute_name']])) {
                continue;
            }
            // check the access to this field
            if ($activeDud['prop_viewby'] != 0) {
                // not to everyone, checks members only or higher
                if (!($activeDud['prop_viewby'] == 1 && $isMember)) {
                    // lastly check for the same user or admin
                    if (!($activeDud['prop_viewby'] == 2 && ($sameUser || $isAdmin))) {
                        continue;
                    }
                }
            }
            // add it to the viewable properties
            $dudArray[$dudLabel] = $userInfo['__ATTRIBUTES__'][$activeDud['prop_attribute_name']];
        }
        unset($activeDuds);

        return $this->renderView('@ZikulaProfileModule/Block/featuredUser.html.twig', [
            'userInfo', $userInfo,
            'showRegDate' => $properties['showregdate'],
            'dudArray' => $dudArray
        ]);
    }

    /**
     * Returns any array of form options.
     *
     * @return array Options array
     */
    public function getFormOptions()
    {
        // get all active profile fields
        $activeDuds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        $dudArray = [];
        foreach ($activeDuds as $attr => $activeDud) {
            $dudArray[$activeDud['prop_label']] = $attr;
        }

        return [
            'dudArray' => $dudArray
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFormClassName()
    {
        return 'Zikula\ProfileModule\Block\Form\Type\FeaturedUserBlockType';
    }

    /**
     * @inheritdoc
     */
    public function getFormTemplate()
    {
        return '@ZikulaProfileModule/Block/featuredUser_modify.html.twig';
    }
}
