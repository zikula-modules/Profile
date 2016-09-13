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
use UserUtil;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Event\GenericEvent;

/**
 * Class ProfileController
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/display")
     * 
     * Display a profile.
     *
     * @param Request $request
     *
     * Parameters passed via GET:
     * --------------------------------------------------
     * numeric uid   The user account id (uid) of the user for whom to display profile information; optional, ignored if uname is supplied, if not provided
     *                  and if uname is not supplied then defaults to the current user.
     * string  uname The user name of the user for whom to display profile information; optional, if not supplied, then uid is used to determine the user.
     * string  page  The name of the Profile "page" (display template) to display; optional, if not provided then the standard display template is used.
     *
     * @return RedirectResponse|string The rendered template output.
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function displayAction(Request $request)
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

            return $this->redirectToRoute('zikulaprofilemodule_members_view');
        }

        // Get all the user data
        $userInfo = UserUtil::getVars($uid);
        if (!$userInfo) {
            $this->addFlash('error', $this->__('Error! Could not find this user.'));

            return $this->redirectToRoute('zikulaprofilemodule_members_view');
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
            /*if ($this->view->template_exists('Profile/display_' . $page . '.tpl')) {
                return new Response($this->view->fetch('Profile/display_' . $page . '.tpl', $uid));
            }*/

            $this->addFlash('error', $this->__f('Error! Could not find profile page [%s].', ['%s' => DataUtil::formatForDisplay($page)]));

            return $this->redirectToRoute('zikulaprofilemodule_members_view');
        }

        return $this->render('@ZikulaProfileModule/Profile/display.html.twig', $templateParameters);
    }

    /**
     * @Route("/edit/{uid}", requirements={"uid" = "\d+"})
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
    public function editAction(Request $request, $uid = null)
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

            return $this->redirectToRoute('zikulaprofilemodule_members_view');
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

        return $this->redirectToRoute('zikulaprofilemodule_profile_display');
    }
}
