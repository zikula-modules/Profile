<?php
/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\ProfileModule\Api;

use ModUtil;
use SecurityUtil;
use System;
use UserUtil;

/**
 * The Account API provides links for modules on the "user account page"; this class provides them for the Profile module.
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
     * @return   array   array of items, or false on failure
     */
    public function getall(array $args = array())
    {
        $items = array();
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
            $items[] = array(
                'url' => ModUtil::url($this->name, 'user', 'view', array('uid' => $uid)),
                'module' => $this->name,
                'title' => $this->__('Your Profile'),
                'icon' => 'admin.png');
            if (SecurityUtil::checkPermission($this->name.':Members:', '::', ACCESS_READ)) {
                $items[] = array(
                    'url' => ModUtil::url($this->name, 'user', 'viewmembers'),
                    'module' => $this->name,
                    'title' => $this->__('Registered Users'),
                    'icon' => 'members.png');
            }
        }
        // Return the items
        return $items;
    }

}