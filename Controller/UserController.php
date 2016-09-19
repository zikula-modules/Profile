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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
        @trigger_error('The zikulaprofilemodule_user_main route is deprecated. please use zikulaprofilemodule_profile_display instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_profile_display');
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
        @trigger_error('The zikulaprofilemodule_user_index route is deprecated. please use zikulaprofilemodule_profile_display instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_profile_display');
    }

    /**
     * @Route("/view")
     * 
     * Display item.
     *
     * @return RedirectResponse
     */
    public function viewAction(Request $request)
    {
        @trigger_error('The zikulaprofilemodule_user_view route is deprecated. please use zikulaprofilemodule_profile_display instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_profile_display');
    }

    /**
     * @Route("/modify/{uid}", requirements={"uid" = "\d+"})
     *
     * Modify a users profile information.
     *
     * @return RedirectResponse
     */
    public function modifyAction(Request $request, $uid = null)
    {
        @trigger_error('The zikulaprofilemodule_user_modify route is deprecated. please use zikulaprofilemodule_profile_edit instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_profile_edit');
    }

    /**
     * @Route("/update")
     *
     * Update a users profile.
     *
     * @return RedirectResponse
     */
    public function updateAction(Request $request)
    {
        @trigger_error('The zikulaprofilemodule_user_update route is deprecated. please use zikulaprofilemodule_profile_update instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_profile_update');
    }

    /**
     * @Route("/viewmembers")
     *
     * View members list.
     *
     * @return RedirectResponse
     */
    public function viewmembersAction(Request $request)
    {
        @trigger_error('The zikulaprofilemodule_user_viewmembers route is deprecated. please use zikulaprofilemodule_members_view instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_members_view');
    }

    /**
     * @Route("/recentmembers")
     *
     * Displays last X registered users.
     *
     * @return RedirectResponse
     */
    public function recentmembersAction()
    {
        @trigger_error('The zikulaprofilemodule_user_recentmembers route is deprecated. please use zikulaprofilemodule_members_recent instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_members_recent');
    }

    /**
     * @Route("/membersonline")
     *
     * View users online.
     *
     * @return RedirectResponse
     */
    public function onlinemembersAction()
    {
        @trigger_error('The zikulaprofilemodule_user_membersonline route is deprecated. please use zikulaprofilemodule_members_online instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_members_online');
    }

    /**
     * @Route("/usersblock")
     * @Template
     *
     * Display the configuration options for the users block.
     *
     * @return Response symfony response object
     *
     * @throws NotFoundHttpException Thrown if the users block isn't found
     */
    public function usersBlockAction()
    {
        $blocks = ModUtil::apiFunc('ZikulaBlocksModule', 'user', 'getall');
        $profileModuleId = ModUtil::getIdFromName('ZikulaProfileModule');
        $found = false;
        foreach ($blocks as $block) {
            if ($block['mid'] == $profileModuleId && $block['bkey'] == 'user') {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new NotFoundHttpException();
        }

        $userId = $this->get('zikula_users_module.current_user')->get('uid');

        return UserUtil::getVars($userId);
    }

    /**
     * @Route("/updateusersblock")
     * @Method("POST")
     *
     * Update the custom users block.
     *
     * @param Request $request
     *
     * Parameters passed via POST:
     * ---------------------------
     * boolean ublockon Whether the block is displayed or not.
     * mixed   ublock   ?.
     *
     * @return RedirectResponse
     *
     * @return AccessDeniedException Thrown if the user isn't logged in or if there are no post parameters
     * @throws NotFoundHttpException Thrown if the users block isn't found
     */
    public function updateUsersBlockAction(Request $request)
    {
        $currentUserApi = $this->get('zikula_users_module.current_user');

        if (!$currentUserApi->isLoggedIn()) {
            throw new AccessDeniedException();
        }

        $blocks = ModUtil::apiFunc('ZikulaBlocksModule', 'user', 'getall');
        $profileModuleId = ModUtil::getIdFromName('ZikulaProfileModule');

        $found = false;
        foreach ($blocks as $block) {
            if ($block['mid'] == $profileModuleId && $block['bkey'] == 'user') {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new NotFoundHttpException();
        }

        $ublockon = (bool)$request->request->get('ublockon', false);
        $ublock = (string)$request->request->get('ublock', '');

        $userId = $currentUserApi->get('uid');

        UserUtil::setVar('ublockon', $ublockon);
        UserUtil::setVar('ublock', $ublock);

        $this->addFlash('status', $this->__('Done! Saved custom block.'));

        return $this->redirectToRoute('zikulausersmodule_user_index');
    }
}
