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
     * @Route("/usersblock")
     * @Template
     *
     * Display the configuration options for the users block.
     *
     * @throws NotFoundHttpException Thrown if the users block isn't found
     *
     * @return array|RedirectResponse
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
