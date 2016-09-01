<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Api;

use ModUtil;
use SecurityUtil;
use System;
use UserUtil;

/**
 * User account links api.
 */
class AccountApi extends \Zikula_AbstractApi
{
    /**
     * Return an array of items to show in the "user account page".
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * string uname The user name of the user for whom links should be returned; optional, defaults to the current user.
     *
     * @param array $args All parameters passed to this function.
     *
     * @return array array of items, or false on failure
     */
    public function getall(array $args = [])
    {
        $items = [];
        // do not show the account links if Profile is not the Profile manager
        $profilemodule = System::getVar('profilemodule', '');
        if ($profilemodule != $this->name) {
            return $items;
        }
        $uname = isset($args['uname']) ? $args['uname'] : null;
        if (!$uname && UserUtil::isLoggedIn()) {
            $uname = UserUtil::getVar('uname');
        }
        // Create an array of links to return
        if (!empty($uname)) {
            $uid = UserUtil::getIdFromName($uname);
            $items[] = [
                'url' => $this->get('router')->generate('zikulaprofilemodule_user_view', ['uid' => $uid]),
                'module' => $this->name,
                'title' => $this->__('Profile'),
                'icon' => 'user'
            ];
            if (SecurityUtil::checkPermission($this->name.':Members:', '::', ACCESS_READ)) {
                $items[] = [
                    'url' => $this->get('router')->generate('zikulaprofilemodule_user_viewmembers'),
                    'module' => $this->name,
                    'title' => $this->__('Registered Users'),
                    'icon' => 'users'
                ];
            }
        }

        // Return the items
        return $items;
    }
}
