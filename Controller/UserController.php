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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UserUtil;
use Zikula\Core\Controller\AbstractController;

/**
 * Class UserController
 * UI operations executable by general users.
 */
class UserController extends AbstractController
{
    /**
     * Route not needed here because this is a legacy-only method.
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
     * @Route("/")
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

        return $this->redirectToRoute('zikulaprofilemodule_profile_edit', ['uid' => $uid]);
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
     * @throws NotFoundHttpException Thrown if the users block isn't found
     *
     * @return array
     */
    public function usersBlockAction(Request $request)
    {
        $blocks = ModUtil::apiFunc('ZikulaBlocksModule', 'user', 'getall');
        $profileModuleId = ModUtil::getIdFromName('ZikulaProfileModule');
        $found = false;
        foreach ($blocks as $block) {
            if ($block['module']['id'] == $profileModuleId && $block['bkey'] == 'ZikulaProfileModule:Zikula\ProfileModule\Block\UserBlock') {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new NotFoundHttpException();
        }

        $currentUserApi = $this->get('zikula_users_module.current_user');
        if (!$currentUserApi->isLoggedIn()) {
            throw new AccessDeniedException();
        }

        $formVars = [
            'ublockon' => (bool) UserUtil::getVar('ublockon'),
            'ublock'   => UserUtil::getVar('ublock'),
        ];

        $form = $this->createForm('Zikula\ProfileModule\Form\Type\UsersBlockType', $formVars, [
            'translator' => $this->get('translator.default'),
        ]);

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                $ublockon = isset($formData['ublockon']) ? (bool) $formData['ublockon'] : false;
                $ublock = isset($formData['ublock']) ? $formData['ublock'] : '';

                UserUtil::setVar('ublockon', $ublockon);
                UserUtil::setVar('ublock', $ublock);

                $this->addFlash('status', $this->__('Done! Saved custom block.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulausersmodule_account_menu');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
