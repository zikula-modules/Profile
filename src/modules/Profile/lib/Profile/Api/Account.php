<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnaccountapi.php 91 2010-01-25 09:05:01Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @author Mark West
 */

class Profile_Api_Account extends Zikula_AbstractApi
{

    /**
     * Return an array of items to show in the your account panel
     *
     * @return   array   array of items, or false on failure
     */
    public function getall($args)
    {

        $items = array();

        // do not show the account links if Profile is not the Profile manager
        $profilemodule = System::getVar('profilemodule', '');
        if ($profilemodule != 'Profile') {
            return $items;
        }

        $uname = isset($args['uname']) ? $args['uname'] : null;
        if (!$uname && UserUtil::isLoggedIn()) {
            $uname = UserUtil::getVar('uname');
        }

        // Create an array of links to return
        if (!empty($uname)) {
            $uid = UserUtil::getIdFromName($uname);
            $items['0'] = array('url'     => ModUtil::url('Profile', 'user', 'view', array('uid' => $uid)),
                    'module'  => 'Profile',
                    //! account panel link
                    'title'   => $this->__('Personal info'),
                    'icon'    => 'admin.gif');

            if (SecurityUtil::checkPermission('Profile:Members:', '::', ACCESS_READ)) {
                $items['1'] = array('url'     => ModUtil::url('Profile', 'user', 'viewmembers'),
                        'module'  => 'Profile',
                        'title'   => $this->__('Registered users list'),
                        'icon'    => 'members.gif');
            }
        }

        // Return the items
        return $items;
    }
}