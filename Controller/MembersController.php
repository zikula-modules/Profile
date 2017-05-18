<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\SecurityCenterModule\Constant;
use Zikula\SettingsModule\SettingsConstant;

/**
 * Class MembersController.
 *
 * @Route("/members")
 */
class MembersController extends AbstractController
{
    /**
     * @Route("/view")
     * @Template
     * @param Request $request
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function viewAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule:Members:', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        // Get parameters from whatever input we need
        $startnum = $request->query->get('startnum', null);
        $sortby = $request->get('sortby', null);
        $searchby = $request->get('searchby', null);
        $sortorder = $request->get('sortorder', null);
        $letter = $request->get('letter', null);

        // Set some defaults
        if (empty($sortby)) {
            $sortby = 'uname';
        }
        if (empty($letter)) {
            $letter = null;
        }
        if (empty($startnum)) {
            $startnum = null;
        }

        // get some permissions to filter item actions
        $edit = false;
        $delete = false;
        if ($this->hasPermission('ZikulaUsersModule::', '::', ACCESS_DELETE)) {
            $edit = true;
            $delete = true;
        } elseif ($this->hasPermission('ZikulaUsersModule::', '::', ACCESS_EDIT)) {
            $edit = true;
        }

        // get the number of users to show per page from the module vars
        $itemsPerPage = $this->getVar('memberslistitemsperpage', 20);

        $fetchargs = [
            'letter'     => $letter,
            'sortby'     => $sortby,
            'sortorder'  => $sortorder,
            'searchby'   => $searchby,
            'startnum'   => $startnum,
            'numitems'   => $itemsPerPage,
            'returnUids' => false,
        ];

        // get full list of user id's
        $users = $this->get('zikula_users_module.user_repository')->query([], [$sortby => $sortorder], $itemsPerPage, $startnum);
        $amountOfUsers = $this->get('zikula_users_module.user_repository')->count();

//        foreach ($users as $userid => $user) {
//            //$user = array_merge(UserUtil::getVars($userid['uid']), $userid);
//            $isOnline = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'isonline', ['userid' => $userid]);
//            // is this user online
//            $users[$userid]['onlinestatus'] = $isOnline ? 1 : 0;
//            // filter out any dummy url's
//            if (isset($user['url']) && (!$user['url'] || in_array($user['url'], ['http://', 'http:///']))) {
//                $users[$userid]['url'] = '';
//            }
//        }
        // get all active profile fields
//        $activeDuds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
//        $dudArray = [];
//        foreach ($activeDuds as $attr => $activeDud) {
//            $dudArray[$attr] = $activeDud['prop_id'];
//        }
//        unset($activeDuds);

        return [
//            'amountOfRegisteredMembers' => ModUtil::apiFunc('Users', 'user', 'countitems') - 1,
//            'amountOfOnlineMembers'     => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getregisteredonline'),
//            'newestMemberName'          => UserUtil::getVar('uname', ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getlatestuser')),
            // check if we should show the extra admin column
            'adminEdit'   => $edit,
            'adminDelete' => $delete,
//            'dudArray'    => $dudArray,
            'users'       => $users,
            'letter'      => $letter,
            'sortby'      => $sortby,
            'sortorder'   => $sortorder,
            // check which messaging module is available and add the necessary info
            'messageModule' => $this->get('zikula_extensions_module.api.variable')->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, ''),
            'pager'         => [
                'amountOfItems' => $amountOfUsers,
                'itemsPerPage'  => $itemsPerPage,
            ],
        ];
    }

    /**
     * @Route("/recent")
     * @Template
     *
     * Displays last X registered users.
     *
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function recentAction()
    {
        if (!$this->hasPermission('ZikulaProfileModule:Members:recent', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        $modVars = $this->getVars();

        // get last x user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', [
            'sortby'     => 'user_regdate',
            'numitems'   => $modVars['recentmembersitemsperpage'],
            'sortorder'  => 'DESC',
            'returnUids' => false, ]
        );

        $templateParameters = $modVars;

        // get some permissions to filter item actions
        $edit = false;
        $delete = false;
        if ($this->hasPermission('ZikulaUsersModule::', '::', ACCESS_DELETE)) {
            $edit = true;
            $delete = true;
        } elseif ($this->hasPermission('ZikulaUsersModule::', '::', ACCESS_EDIT)) {
            $edit = true;
        }

        // check if we should show the extra admin column
        $templateParameters['adminEdit'] = $edit;
        $templateParameters['adminDelete'] = $delete;

        foreach (array_keys($users) as $userid) {
            $isOnline = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'isonline', ['userid' => $userid]);
            // display online status
            $users[$userid]['onlinestatus'] = $isOnline ? 1 : 0;
        }
        $templateParameters['users'] = $users;

        // check which messaging module is available and add the necessary info
        $templateParameters['messageModule'] = $this->get('zikula_extensions_module.api.variable')->getSystemVar(SettingsConstant::SYSTEM_VAR_MESSAGE_MODULE, '');

        // get all active profile fields
        $activeDuds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        $dudArray = array_keys($activeDuds);
        unset($activeDuds);
        $templateParameters['dudArray'] = $dudArray;

        return $templateParameters;
    }

    /**
     * @Route("/online")
     * @Template
     *
     * View users online.
     *
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function onlineAction()
    {
        if (!$this->hasPermission('ZikulaProfileModule:Members:online', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        $activeMinutes = $this->getVar('activeminutes');
        $activeSince = new \DateTime();
        $activeSince->modify("-$activeMinutes minutes");
        $uids = $this->getDoctrine()->getRepository('ZikulaUsersModule:UserSessionEntity')->getUsersSince($activeSince);
        $users = $this->getDoctrine()->getRepository('ZikulaUsersModule:UserEntity')->findBy(['uid' => $uids]);
        if ($this->get('zikula_extensions_module.api.variable')->get('ZikulaSecurityCenterModule', 'sessionstoretofile', Constant::SESSION_STORAGE_FILE) == Constant::SESSION_STORAGE_FILE) {
            $this->addFlash('danger', $this->__('Sessions are configured to store in a file and therefore this list is inaccurate.'));
            if ($this->hasPermission('ZikulaSecurityCenterModule::', '::', ACCESS_ADMIN)) {
                $link = $this->get('router')->generate('zikulasecuritycentermodule_config_config');
                $this->addFlash('info', $this->__('Admin Message') . ": <a href='$link'>" . $this->__('Reconfigure sessions storage') . "</a>");
            }
        }

        return [
            'users' => $users,
        ];
    }
}
