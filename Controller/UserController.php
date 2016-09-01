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
use SecurityUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use System;
use UserUtil;
use Zikula\Core\Event\GenericEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route; // used in annotations - do not remove
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method; // used in annotations - do not remove
use Symfony\Component\Routing\RouterInterface;

/**
 * UI operations executable by general users.
 *
 * Class UserController
 * @package Zikula\ProfileModule\Controller
 */
class UserController extends \Zikula_AbstractController
{
    /**
     * Route not needed here because this is a legacy-only method
     *
     * The default entry point.
     *
     * This redirects back to the default entry point for the Users module.
     *
     * @return RedirectResponse
     */
    public function mainAction()
    {
        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("")
     *
     * The default entry point.
     *
     * This redirects back to the default entry point for the Users module.
     *
     * @return RedirectResponse
     */
    public function indexAction()
    {
        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_view', [], RouterInterface::ABSOLUTE_URL));
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
        if (!SecurityUtil::checkPermission($this->name.'::view', '::', ACCESS_READ)) {
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
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! Could not find this user.'));

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
        }
        // Get all the user data
        $userinfo = UserUtil::getVars($uid);
        if (!$userinfo) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! Could not find this user.'));

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
        }
        // Check if the user is watching its own profile or if he is admin
        // TODO maybe remove the four lines below
        $currentuser = UserUtil::getVar('uid');
        $ismember = $currentuser >= 2;
        $isowner = $currentuser == $uid;
        $isadmin = SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN);
        // Get all active profile fields
        $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive', ['get' => 'viewable', 'uid' => $uid]);
        $fieldsets = [];
        $items = $activeduds;
        foreach ($items as $propattr => $propdata) {
            $items[$propattr]['prop_fieldset'] = ((isset($items[$propattr]['prop_fieldset'])) && (!empty($items[$propattr]['prop_fieldset']))) ? $items[$propattr]['prop_fieldset'] : $this->__('User Information');
            $fieldsets[DataUtil::formatPermalink($items[$propattr]['prop_fieldset'])] = $items[$propattr]['prop_fieldset'];
        }
        $activeduds = $items;
        // Fill the DUD values array
        $dudarray = [];
        foreach (array_keys($activeduds) as $dudattr) {
            $dudarray[$dudattr] = isset($userinfo['__ATTRIBUTES__'][$dudattr]) ? $userinfo['__ATTRIBUTES__'][$dudattr] : '';
        }
        // Create output object
        $this->view->setCaching(false);
        $this->view->assign('dudarray', $dudarray)
            ->assign('fields', $activeduds)
            ->assign('fieldsets', $fieldsets)
            ->assign('uid', $userinfo['uid'])
            ->assign('uname', $userinfo['uname'])
            ->assign('userinfo', $userinfo)
            ->assign('ismember', $ismember)
            ->assign('isadmin', $isadmin)
            ->assign('sameuser', $isowner);
        // Return the output that has been generated by this function
        if (!empty($page)) {
            if ($this->view->template_exists("User/view_{$page}.tpl")) {

                return new Response($this->view->fetch("User/view_{$page}.tpl", $uid));
            } else {
                $request->getSession()->getFlashBag()->add('error', $this->__f('Error! Could not find profile page [%s].', DataUtil::formatForDisplay($page)));

                return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
            }
        }

        return new Response($this->view->fetch('User/view.tpl', $uid));
    }

    /**
     * @Route("/modify/{uid}", requirements={"uid" = "\d+"})
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
        if (!UserUtil::isLoggedIn() || !SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }
        
        $uid = isset($uid) ? $uid : UserUtil::getVar('uid');
        
        if ($uid != UserUtil::getVar('uid')) {
            if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_EDIT)) {
                throw new AccessDeniedException();
            }
        }

        // The API function is called.
        $items = ModUtil::apiFunc($this->name, 'user', 'getallactive', [
            'uid' => $uid,
            'get' => 'editable'
        ]);

        // The return value of the function is checked here
        if ($items === false) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! Could not load items.'));

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
        }
        
        // check if we get called form the update function in case of an error
        $uname = $request->query->get('uname', UserUtil::getVar('uname'));
        $dynadata = $request->query->get('dynadata', []);
        $fieldsets = [];
        
        foreach ($items as $propattr => $propdata) {
            $items[$propattr]['prop_fieldset'] = ((isset($items[$propattr]['prop_fieldset'])) && (!empty($items[$propattr]['prop_fieldset']))) ? $items[$propattr]['prop_fieldset'] : $this->__('User Information');
            $fieldsets[DataUtil::formatPermalink($items[$propattr]['prop_fieldset'])] = $items[$propattr]['prop_fieldset'];
        }
        
        // merge this temporary dynadata and the errors into the items array
        foreach ($dynadata as $propattr => $propdata) {
            $items[$propattr]['temp_propdata'] = $propdata;
        }
        
        // Create output object
        $this->view->setCaching(false);
        
        // Assign the items to the template
        $this->view->assign('duditems', $items);
        $this->view->assign('fieldsets', $fieldsets);
        $this->view->assign('uid', $uid);
        $this->view->assign('uname', $uname);

        return new Response($this->view->fetch('User/modify.tpl'));

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
        $this->checkCsrfToken();
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
        
        $checkrequired = ModUtil::apiFunc($this->name, 'user', 'checkrequired', ['dynadata' => $dynadata]);
        
        if ($checkrequired['result'] == true) {
            $request->getSession()->getFlashBag()->add('error', $this->__f('Error! A required profile item [%s] is missing.', $checkrequired['translatedFieldsStr']));
            // we do not send the passwords here!
            $params = [
                'uname' => $uname,
                'dynadata' => $dynadata
            ];

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_modify', $params, RouterInterface::ABSOLUTE_URL));
        }
        
        // Building the sql and saving - The API function is called.
        $save = ModUtil::apiFunc($this->name, 'user', 'savedata', ['uid' => $uid, 'dynadata' => $dynadata]);
        
        if ($save != true) {

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_view', [], RouterInterface::ABSOLUTE_URL));
        }
        
        // This function generated no output, we redirect the user
        $request->getSession()->getFlashBag()->add('status', $this->__('Done! The profile has been successfully updated.'));
        
        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_view', ['uid' => $uid], RouterInterface::ABSOLUTE_URL));
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
        if (!SecurityUtil::checkPermission($this->name.':Members:', '::', ACCESS_READ)) {
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
        if (SecurityUtil::checkPermission('Users::', '::', ACCESS_DELETE)) {
            $edit = true;
            $delete = true;
        } elseif (SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)) {
            $edit = true;
            $delete = false;
        } else {
            $edit = false;
            $delete = false;
        }
        // Create output object
        $cacheid = md5((int)$edit . (int)$delete . $startnum . $letter . $sortby);
        $this->view->setCaching(true)->setCacheId($cacheid);
        // get the number of users to show per page from the module vars
        $itemsperpage = $this->getVar('memberslistitemsperpage', 20);
        // assign values for header
        $this->view->assign('memberslistreg', ModUtil::apiFunc('Users', 'user', 'countitems') - 1);
        // discount anonymous
        $this->view->assign('memberslistonline', ModUtil::apiFunc($this->name, 'memberslist', 'getregisteredonline'));
        $this->view->assign('memberslistnewest', UserUtil::getVar('uname', ModUtil::apiFunc($this->name, 'memberslist', 'getlatestuser')));
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
        $users = ModUtil::apiFunc($this->name, 'memberslist', 'getall', $fetchargs);
        $userscount = ModUtil::apiFunc($this->name, 'memberslist', 'countitems', $fetchargs);
        // Is current user online
        $this->view->assign('loggedin', UserUtil::isLoggedIn());
        // check if we should show the extra admin column
        $this->view->assign('adminedit', $edit);
        $this->view->assign('admindelete', $delete);
        foreach ($users as $userid => $user) {
            //$user = array_merge(UserUtil::getVars($userid['uid']), $userid);
            $isonline = ModUtil::apiFunc($this->name, 'memberslist', 'isonline', ['userid' => $userid]);
            // is this user online
            $users[$userid]['onlinestatus'] = $isonline ? 1 : 0;
            // filter out any dummy url's
            if (isset($user['url']) && (!$user['url'] || in_array($user['url'], ['http://', 'http:///']))) {
                $users[$userid]['url'] = '';
            }
        }
        // get all active profile fields
        $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive');
        foreach ($activeduds as $attr => $activedud) {
            $dudarray[$attr] = $activedud['prop_id'];
        }
        unset($activeduds);
        $this->view->assign('dudarray', $dudarray)
            ->assign('users', $users)
            ->assign('letter', $letter)
            ->assign('sortby', $sortby)
            ->assign('sortorder', $sortorder)
            // check which messaging module is available and add the necessary info
            ->assign('msgmodule', ModUtil::apiFunc($this->name, 'memberslist', 'getmessagingmodule'))
            ->assign('pager', ['numitems' => $userscount, 'itemsperpage' => $itemsperpage]);

        return new Response($this->view->fetch('User/members_view.tpl'));
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
        if (!SecurityUtil::checkPermission($this->name.':Members:recent', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }
        // set the cache id
        $this->view->setCacheId('recent' . (int)UserUtil::isLoggedIn());
        // check out if the contents are cached.
        if ($this->view->is_cached('User/members_recent.tpl')) {

            return new Response($this->view->fetch('User/members_recent.tpl'));
        }
        $modvars = $this->getVars();
        // get last x user id's
        $users = ModUtil::apiFunc($this->name, 'memberslist', 'getall', [
            'sortby' => 'user_regdate',
            'numitems' => $modvars['recentmembersitemsperpage'],
            'sortorder' => 'DESC',
            'returnUids' => false]
        );
        // Is current user online
        $this->view->assign('loggedin', UserUtil::isLoggedIn());
        // assign all module vars obtained earlier
        $this->view->assign($modvars);
        // get some permissions to use in the cache id and later to filter template output
        $edit = false;
        $delete = false;
        if (SecurityUtil::checkPermission('Users::', '::', ACCESS_DELETE)) {
            $edit = true;
            $delete = true;
        } elseif (SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)) {
            $edit = true;
        }
        // check if we should show the extra admin column
        $this->view->assign('adminedit', $edit);
        $this->view->assign('admindelete', $delete);
        foreach (array_keys($users) as $userid) {
            $isonline = ModUtil::apiFunc($this->name, 'memberslist', 'isonline', ['userid' => $userid]);
            // display online status
            $users[$userid]['onlinestatus'] = $isonline ? 1 : 0;
        }
        $this->view->assign('users', $users);
        // check which messaging module is available and add the necessary info
        $this->view->assign('msgmodule', ModUtil::apiFunc($this->name, 'memberslist', 'getmessagingmodule'));
        // get all active profile fields
        $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive');
        $dudarray = array_keys($activeduds);
        unset($activeduds);
        $this->view->assign('dudarray', $dudarray);

        return new Response($this->view->fetch('User/members_recent.tpl'));
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
        if (!SecurityUtil::checkPermission($this->name.':Members:online', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }
        // Create output object
        $this->view->setCacheId('onlinemembers' . (int)UserUtil::isLoggedIn());
        // check out if the contents are cached.
        if ($this->view->is_cached('User/members_online.tpl')) {

            return new Response($this->view->fetch('User/members_online.tpl'));
        }
        // get last 10 user id's
        $users = ModUtil::apiFunc($this->name, 'memberslist', 'whosonline');
        // Current user status
        $this->view->assign('loggedin', UserUtil::isLoggedIn());
        $this->view->assign('users', $users);
        // check which messaging module is available and add the necessary info
        $this->view->assign('msgmodule', ModUtil::apiFunc($this->name, 'memberslist', 'getmessagingmodule'));
        // get all active profile fields
        $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive');
        $dudarray = array_keys($activeduds);
        unset($activeduds);
        $this->view->assign('dudarray', $dudarray);

        return new Response($this->view->fetch('User/members_online.tpl'));
    }
}
