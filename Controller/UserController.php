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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ProfileModule\Form\Type\UsersBlockType;
use Zikula\UsersModule\Entity\UserEntity;

class UserController extends AbstractController
{
    /**
     * @Route("/usersblock")
     * @Template
     *
     * Display the configuration options for the users block.
     *
     * @throws NotFoundHttpException Thrown if the users block isn't found
     * @return array|RedirectResponse
     */
    public function usersBlockAction(Request $request)
    {
        $block = $this->get('zikula_blocks_module.block_repository')->findOneBy(['bkey' => 'ZikulaProfileModule:Zikula\ProfileModule\Block\UserBlock']);
        if (!isset($block)) {
            throw new NotFoundHttpException();
        }

        $currentUserApi = $this->get('zikula_users_module.current_user');
        if (!$currentUserApi->isLoggedIn()) {
            throw new AccessDeniedException();
        }
        /** @var UserEntity $userEntity */
        $userEntity = $this->get('zikula_users_module.user_repository')->find($currentUserApi->get('uid'));

        $formVars = [
            'ublockon' => $userEntity->hasAttribute('ublockon') ? (bool) $userEntity->getAttributeValue('ublockon') : false,
            'ublock'   => $userEntity->hasAttribute('ublock') ? $userEntity->getAttributeValue('ublock') : '',
        ];

        $form = $this->createForm(UsersBlockType::class, $formVars, [
            'translator' => $this->get('translator.default'),
        ]);

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();

                $userEntity->setAttribute('ublockon', $formData['ublockon']);
                $userEntity->setAttribute('ublock', $formData['ublock']);
                $this->getDoctrine()->getManager()->flush();

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
