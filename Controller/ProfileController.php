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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UserUtil;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Event\GenericEvent;

/**
 * Class ProfileController.
 *
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/display/{uid}", requirements={"uid" = "\d+"})
     * @Template
     *
     * Display a profile.
     *
     * @param Request $request
     * @param integer $uid
     *
     * Parameters passed via GET:
     * --------------------------------------------------
     * numeric uid   The user account id (uid) of the user for whom to display profile information; optional, ignored if uname is supplied, if not provided
     *                  and if uname is not supplied then defaults to the current user.
     * string  uname The user name of the user for whom to display profile information; optional, if not supplied, then uid is used to determine the user.
     * string  page  The name of the Profile "page" (display template) to display; optional, if not provided then the standard display template is used
     *
     * @throws AccessDeniedException on failed permission check
     *
     * @return array
     */
    public function displayAction(Request $request, $uid = null)
    {
        // Security check
        if (!$this->hasPermission('ZikulaProfileModule::view', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }
        $uid = isset($uid) ? $uid : $this->get('zikula_users_module.current_user')->get('uid');
        $userEntity = $this->get('zikula_users_module.user_repository')->find($uid);
//        $properties = $this->getDoctrine()->getRepository('ZikulaProfileModule:PropertyEntity')->findBy(['active' => true]);

        return [
            'user' => $userEntity
        ];
    }

    /**
     * @Route("/edit/{uid}", requirements={"uid" = "\d+"})
     *
     * @Template
     *
     * Modify a users profile information.
     *
     * @param Request $request
     * @param int     $uid
     *
     * Parameters passed via GET:
     * --------------------------------------------------
     * string   uname The user name of the account for which profile information should be modified; defaults to the uname of the current user.
     * dynadata array The modified profile information passed into this function in case of an error in the update function
     *
     * @throws AccessDeniedException on failed permission check
     *
     * @return RedirectResponse|string The rendered template output
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
        $dynaData = $request->query->get('dynadata', []);
        $fieldSets = [];

        foreach ($items as $propAttr => $propData) {
            $fieldSet = (isset($propData['prop_fieldset']) && !empty($propData['prop_fieldset'])) ? $propData['prop_fieldset'] : $this->__('User Information');
            $items[$propAttr]['prop_fieldset'] = $fieldSet;
            $fieldSets[$fieldSet] = $fieldSet;
        }

        // merge temporary dynaData into the items array
        foreach ($dynaData as $propAttr => $propData) {
            $items[$propAttr]['temp_propdata'] = $propData;
        }

        return [
            'dudItems'  => $items,
            'fieldSets' => $fieldSets,
            'uid'       => $uid,
            'uname'     => $uname,
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
     * array  dynadata An array containing the updated profile information for the user account
     *
     * @return RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $uid = $request->query->get('uid', UserUtil::getVar('uid'));
        $user = UserUtil::getVars($uid);
        $dynaData = $request->request->get('dynadata', null);
        $event_args = [
            'uid'      => $uid,
            'dynadata' => $dynaData,
        ];
        $event = new GenericEvent($user, $event_args);
        $this->get('event_dispatcher')->dispatch('module.profile.update', $event);

        // Get parameters from whatever input we need.
        $uname = $request->request->get('uname', null);

        /**
         * Set $dynaData again, in case it has been modified by
         * a persistent module handler.
         */
        $dynaData = $request->request->get('dynadata', null);

        // Check for required fields - The API function is called.
        $checkrequired = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'checkrequired', ['dynadata' => $dynaData]);
        if ($checkrequired['result'] == true) {
            $this->addFlash('error', $this->__f('Error! A required profile item [%s] is missing.', ['%s' => $checkrequired['translatedFieldsStr']]));
            // we do not send the passwords here!
            $params = [
                'uname'    => $uname,
                'dynadata' => $dynaData,
            ];

            return $this->redirectToRoute('zikulaprofilemodule_user_modify');
        }

        // Save updated data
        $save = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'savedata', [
            'uid'      => $uid,
            'dynadata' => $dynaData,
        ]);
        if (true === $save) {
            $this->addFlash('status', $this->__('Done! The profile has been successfully updated.'));
        }

        return $this->redirectToRoute('zikulaprofilemodule_profile_display');
    }
}
