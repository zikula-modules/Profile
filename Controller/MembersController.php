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

use ModUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UserUtil;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Event\GenericEvent;

/**
 * Class MembersController
 * @Route("/members")
 */
class MembersController extends AbstractController
{
    /**
     * @Route("/view")
     * @Template
     *
     * View members list.
     *
     * This function provides the main members list view.
     *
     * @param Request $request
     *
     * Parameters passed via GET or via POST:
     * ---------------------------------------------------------------
     * numeric startnum The ordinal number of the record at which to begin displaying records; not obtained via POST.
     * string  sortby    A comma-separated list of fields on which the list of members should be sorted.
     * mixed   searchby  Selection criteria for the query that retrieves the member list; one of 'uname' to select by user name, 'all' to select on all
     *                      available dynamic user data properites, a numeric value indicating the property id of the property on which to select,
     *                      an array indexed by property id containing values for each property on which to select, or a string containing the name of
     *                      a property on which to select.
     * string  sortorder One of 'ASC' or 'DESC' indicating whether sorting should be in ascending order or descending order.
     * string  letter    If searchby is 'uname' then either a letter on which to match the beginning of a user name or a non-letter indicating that
     *                      selection should include user names beginning with numbers and/or other symbols, if searchby is a numeric propery id or
     *                      is a string containing the name of a property then the string on which to match the begining of the value for that property.
     *
     * @return string The rendered template output.
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function viewmembersAction(Request $request)
    {
        // Security check
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
            $startnum = -1;
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
            'letter' => $letter,
            'sortby' => $sortby,
            'sortorder' => $sortorder,
            'searchby' => $searchby,
            'startnum' => $startnum,
            'numitems' => $itemsPerPage,
            'returnUids' => false
        ];

        // get full list of user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', $fetchargs);
        $amountOfUsers = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'countitems', $fetchargs);

        foreach ($users as $userid => $user) {
            //$user = array_merge(UserUtil::getVars($userid['uid']), $userid);
            $isOnline = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'isonline', ['userid' => $userid]);
            // is this user online
            $users[$userid]['onlinestatus'] = $isOnline ? 1 : 0;
            // filter out any dummy url's
            if (isset($user['url']) && (!$user['url'] || in_array($user['url'], ['http://', 'http:///']))) {
                $users[$userid]['url'] = '';
            }
        }
        // get all active profile fields
        $activeDuds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        $dudArray = [];
        foreach ($activeDuds as $attr => $activeDud) {
            $dudArray[$attr] = $activeDud['prop_id'];
        }
        unset($activeDuds);

        return [
            'amountOfRegisteredMembers' => ModUtil::apiFunc('Users', 'user', 'countitems') - 1,
            'amountOfOnlineMembers' => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getregisteredonline'),
            'newestMemberName' => UserUtil::getVar('uname', ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getlatestuser')),
            // check if we should show the extra admin column
            'adminedit' => $edit,
            'admindelete' => $delete,
            'dudArray' => $dudArray,
            'users' => $users,
            'letter' => $letter,
            'sortby' => $sortby,
            'sortorder' => $sortorder,
            // check which messaging module is available and add the necessary info
            'messageModule' => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getmessagingmodule'),
            'pager' => [
                'amountOfItems' => $amountOfUsers,
                'itemsPerPage' => $itemsPerPage
            ]
        ];
    }

    /**
     * @Route("/recent")
     * @Template
     *
     * Displays last X registered users.
     *
     * This function displays the last X users who registered at this site available from the module.
     *
     * @return string The rendered template output.
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function recentmembersAction()
    {
        // Security check
        if (!$this->hasPermission('ZikulaProfileModule:Members:recent', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        $modVars = $this->getVars();

        // get last x user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', [
            'sortby' => 'user_regdate',
            'numitems' => $modVars['recentmembersitemsperpage'],
            'sortorder' => 'DESC',
            'returnUids' => false]
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
        $templateParameters['adminedit'] = $edit;
        $templateParameters['admindelete'] = $delete;

        foreach (array_keys($users) as $userid) {
            $isOnline = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'isonline', ['userid' => $userid]);
            // display online status
            $users[$userid]['onlinestatus'] = $isOnline ? 1 : 0;
        }
        $templateParameters['users'] = $users;

        // check which messaging module is available and add the necessary info
        $templateParameters['messageModule'] = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getmessagingmodule');

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
     * This function displays the currently online users.
     *
     * @return string The rendered template output.
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function onlinemembersAction()
    {
        // Security check
        if (!$this->hasPermission('ZikulaProfileModule:Members:online', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        // get last 10 user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'whosonline');

        // get all active profile fields
        $activeDuds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        $dudArray = array_keys($activeDuds);
        unset($activeDuds);

        return [
            'users' => $users,
            // check which messaging module is available and add the necessary info
            'messageModule' => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getmessagingmodule'),
            'dudArray' => $dudArray
        ];
    }
}
