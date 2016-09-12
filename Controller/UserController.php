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

use DataUtil;
use ModUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use System;
use UserUtil;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Event\GenericEvent;

/**
 * Class UserController
 * UI operations executable by general users.
 */
class UserController extends AbstractController
{
    /**
     * Route not needed here because this is a legacy-only method
     *
     * The default entry point.
     *
     * This redirects back to the default entry point for the Profile module.
     *
     * @return RedirectResponse
     */
    public function mainAction()
    {
        @trigger_error('The zikulaprofilemodule_user_main route is deprecated. please use zikulaprofilemodule_user_view instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_user_view');
    }

    /**
     * @Route("")
     *
     * The default entry point.
     *
     * This redirects back to the default entry point for the Profile module.
     *
     * @return RedirectResponse
     */
    public function indexAction()
    {
        @trigger_error('The zikulaprofilemodule_user_index route is deprecated. please use zikulaprofilemodule_user_view instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_user_view');
    }

    /**
     * @Route("/view")
     * 
     * Display item.
     *
     * @param Request $request
     *
     * Parameters passed via GET:
     * --------------------------------------------------
     * numeric uid   The user account id (uid) of the user for whom to display profile information; optional, ignored if uname is supplied, if not provided
     *                  and if uname is not supplied then defaults to the current user.
     * string  uname The user name of the user for whom to display profile information; optional, if not supplied, then uid is used to determine the user.
     * string  page  The name of the Profile "page" (view template) to display; optional, if not provided then the standard view template is used.
     *
     * @return RedirectResponse|string The rendered template output.
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function viewAction(Request $request)
    {
        // Security check
        if (!$this->hasPermission('ZikulaProfileModule::view', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }
        // Get parameters from whatever input we need.
        $uid = (int)$request->query->get('uid', null);
        $uname = $request->query->get('uname', null);
        $page = $request->query->get('page', null);

        // Getting uid by uname
        if (!empty($uname)) {
            $uid = UserUtil::getIdFromName($uname);
        } elseif (empty($uid)) {
            $uid = UserUtil::getVar('uid');
        }

        // Check for an invalid uid (uid = 1 is the anonymous user)
        if ($uid < 2) {
            $this->addFlash('error', $this->__('Error! Could not find this user.'));

            return $this->redirectToRoute('zikulaprofilemodule_user_viewmembers');
        }

        // Get all the user data
        $userInfo = UserUtil::getVars($uid);
        if (!$userInfo) {
            $this->addFlash('error', $this->__('Error! Could not find this user.'));

            return $this->redirectToRoute('zikulaprofilemodule_user_viewmembers');
        }

        // Check if the user is watching its own profile or if he is admin
        // TODO maybe remove the four lines below
        $currentUser = UserUtil::getVar('uid');
        $isMember = $currentUser >= 2;
        $isOwner = $currentUser == $uid;
        $isAdmin = $this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN);

        // Get all active profile fields
        $activeduds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['get' => 'viewable', 'uid' => $uid]);
        $fieldsets = [];
        $items = $activeduds;
        foreach ($items as $propattr => $propdata) {
            $items[$propattr]['prop_fieldset'] = (isset($propdata['prop_fieldset']) && !empty($propdata['prop_fieldset'])) ? $propdata['prop_fieldset'] : $this->__('User Information');
            $fieldsets[$propdata['prop_fieldset']] = $propdata['prop_fieldset'];
        }
        $activeduds = $items;

        // Fill the DUD values array
        $dudarray = [];
        foreach (array_keys($activeduds) as $dudattr) {
            $dudarray[$dudattr] = isset($userInfo['__ATTRIBUTES__'][$dudattr]) ? $userInfo['__ATTRIBUTES__'][$dudattr] : '';
        }

        $templateParameters = [
            'dudArray' => $dudarray,
            'fields' => $activeduds,
            'fieldSets' => $fieldsets,
            'uid' => $userInfo['uid'],
            'uname' => $userInfo['uname'],
            'userInfo' => $userInfo,
            'isMember' => $isMember,
            'isAdmin' => $isAdmin,
            'sameUser' => $isOwner
        ];

        // Return the output that has been generated by this function
        if (!empty($page)) {
            // TODO refactor to Twig
            /*if ($this->view->template_exists('User/view_' . $page . '.tpl')) {
                return new Response($this->view->fetch('User/view_' . $page . '.tpl', $uid));
            }*/

            $this->addFlash('error', $this->__f('Error! Could not find profile page [%s].', ['%s' => DataUtil::formatForDisplay($page)]));

            return $this->redirectToRoute('zikulaprofilemodule_user_viewmembers');
        }

        return $this->render('@ZikulaProfileModule/User/view.html.twig', $templateParameters);
    }

    /**
     * @Route("/modify/{uid}", requirements={"uid" = "\d+"})
     *
     * @Template
     *
     * Modify a users profile information.
     *
     * @param Request $request
     * @param integer $uid
     *
     * Parameters passed via GET:
     * --------------------------------------------------
     * string   uname The user name of the account for which profile information should be modified; defaults to the uname of the current user.
     * dynadata array The modified profile information passed into this function in case of an error in the update function.
     *
     * @return RedirectResponse|string The rendered template output.
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function modifyAction(Request $request, $uid = null)
    {
        // Security check
        if (!UserUtil::isLoggedIn() || !$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        $uid = isset($uid) ? $uid : UserUtil::getVar('uid');

        if ($uid != UserUtil::getVar('uid')) {
            if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
                throw new AccessDeniedException();
            }
        }

        // The API function is called.
        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['uid' => $uid, 'get' => 'editable']);

        // The return value of the function is checked here
        if ($items === false) {
            $this->addFlash('error', $this->__('Error! Could not load items.'));

            return $this->redirectToRoute('zikulaprofilemodule_user_viewmembers');
        }
        
        // check if we get called form the update function in case of an error
        $uname = $request->query->get('uname', UserUtil::getVar('uname'));
        $dynadata = $request->query->get('dynadata', []);
        $fieldsets = [];
        
        foreach ($items as $propattr => $propdata) {
            $items[$propattr]['prop_fieldset'] = (isset($propdata['prop_fieldset']) && (!empty($propdata['prop_fieldset'])) ? $propdata['prop_fieldset'] : $this->__('User Information');
            $fieldsets[$propdata['prop_fieldset']] = $propdata['prop_fieldset'];
        }
        
        // merge this temporary dynadata and the errors into the items array
        foreach ($dynadata as $propattr => $propdata) {
            $items[$propattr]['temp_propdata'] = $propdata;
        }
        
        return [
            'dudItems' => $items,
            'fieldSets' => $fieldsets,
            'uid' => $uid,
            'uname' => $uname
        ];
    }

    /**
     * @Route("/update")
     *
     * Update a users profile.
     *
     * @param Request $request
     *
     * Parameters passed via POST:
     * ---------------------------
     * string uname    The user name of the account for which profile information should be updated.
     * array  dynadata An array containing the updated profile information for the user account.
     *
     * @return RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $uid = $request->query->get('uid', UserUtil::getVar('uid'));
        $user = UserUtil::getVars($uid);
        $dynadata = $request->request->get('dynadata', null);
        $event_args = [
            'uid' => $uid,
            'dynadata' => $dynadata
        ];
        $event = new GenericEvent($user, $event_args);
        $event = $this->getDispatcher()->dispatch('module.profile.update', $event);

        // Get parameters from whatever input we need.
        $uname = $request->request->get('uname', null);

        /**
         * Set $dynadata again, in case it has been modified by
         * a persistent module handler.
         */
        $dynadata = $request->request->get('dynadata', null);

        // Check for required fields - The API function is called.
        $checkrequired = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'checkrequired', ['dynadata' => $dynadata]);
        if ($checkrequired['result'] == true) {
            $this->addFlash('error', $this->__f('Error! A required profile item [%s] is missing.', ['%s' => $checkrequired['translatedFieldsStr']]));
            // we do not send the passwords here!
            $params = [
                'uname' => $uname,
                'dynadata' => $dynadata
            ];

            return $this->redirectToRoute('zikulaprofilemodule_user_modify');
        }

        // Save updated data
        $save = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'savedata', ['uid' => $uid, 'dynadata' => $dynadata]);
        if ($save == true) {
            $this->addFlash('status', $this->__('Done! The profile has been successfully updated.'));
        }

        return $this->redirectToRoute('zikulaprofilemodule_user_view');
    }

    /**
     * @Route("/viewmembers")
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

        // get some permissions to use in the cache id and later to filter template output
        $edit = false;
        $delete = false;
        if ($this->hasPermission('ZikulaUsersModule::', '::', ACCESS_DELETE)) {
            $edit = true;
            $delete = true;
        } elseif ($this->hasPermission('ZikulaUsersModule::', '::', ACCESS_EDIT)) {
            $edit = true;
            $delete = false;
        }

        // get the number of users to show per page from the module vars
        $itemsperpage = $this->getVar('memberslistitemsperpage', 20);

        $fetchargs = [
            'letter' => $letter,
            'sortby' => $sortby,
            'sortorder' => $sortorder,
            'searchby' => $searchby,
            'startnum' => $startnum,
            'numitems' => $itemsperpage,
            'returnUids' => false
        ];

        // get full list of user id's
        $users = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getall', $fetchargs);
        $userscount = ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'countitems', $fetchargs);

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
        $activeduds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        foreach ($activeduds as $attr => $activedud) {
            $dudarray[$attr] = $activedud['prop_id'];
        }
        unset($activeduds);

        $templateParameters = [
            // values for header
            'memberslistreg' => ModUtil::apiFunc('Users', 'user', 'countitems') - 1,
            // discount anonymous
            'memberslistonline' => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getregisteredonline'),
            'memberslistnewest' => UserUtil::getVar('uname', ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getlatestuser')),
            'loggedin' => UserUtil::isLoggedIn(),
            // check if we should show the extra admin column
            'adminedit' => $edit,
            'admindelete' => $delete,
            'dudArray' => $dudarray,
            'users' => $users,
            'letter' => $letter,
            'sortby' => $sortby,
            'sortorder' => $sortorder,
            // check which messaging module is available and add the necessary info
            'messageModule' => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getmessagingmodule'),
            'pager' => [
                'amountOfItems' => $userscount,
                'itemsPerPage' => $itemsperpage
            ]
        ];

        return $this->render('@ZikulaProfileModule/User/members_view.html.twig', $templateParameters);
    }

    /**
     * @Route("/recentmembers")
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

        // Is current user online
        $templateParameters['loggedin'] = UserUtil::isLoggedIn();

        // get some permissions to filter template output
        $edit = false;
        $delete = false;
        if ($this->hasPermission('Users::', '::', ACCESS_DELETE)) {
            $edit = true;
            $delete = true;
        } elseif ($this->hasPermission('Users::', '::', ACCESS_EDIT)) {
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
        $activeduds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        $dudarray = array_keys($activeduds);
        unset($activeduds);
        $templateParameters['dudArray'] = $dudarray;

        return $this->render('@ZikulaProfileModule/User/members_recent.html.twig', $templateParameters);
    }

    /**
     * @Route("/membersonline")
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
        $activeduds = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        $dudarray = array_keys($activeduds);
        unset($activeduds);

        $templateParameters = [
            'loggedin' => UserUtil::isLoggedIn(),
            'users' => $users,
            // check which messaging module is available and add the necessary info
            'messageModule' => ModUtil::apiFunc('ZikulaProfileModule', 'memberslist', 'getmessagingmodule'),
            'dudArray' => $dudarray
        ];

        return $this->render('@ZikulaProfileModule/User/members_online.html.twig', $templateParameters);
    }
}
