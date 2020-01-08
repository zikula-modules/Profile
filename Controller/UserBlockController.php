<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\BlocksModule\Entity\RepositoryInterface\BlockRepositoryInterface;
use Zikula\Core\Controller\AbstractController;
use Zikula\ProfileModule\Form\Type\UsersBlockType;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Entity\UserEntity;

class UserBlockController extends AbstractController
{
    /**
     * @Route("/usersblock")
     * @Template("@ZikulaProfileModule/UserBlock/edit.html.twig")
     *
     * Display the configuration options for the users block.
     *
     * @return array|RedirectResponse
     *
     * @throws NotFoundHttpException Thrown if the users block isn't found
     */
    public function editAction(
        Request $request,
        BlockRepositoryInterface $blockRepository,
        CurrentUserApiInterface $currentUserApi,
        UserRepositoryInterface $userRepository
    ) {
        $block = $blockRepository->findOneBy(['bkey' => 'ZikulaProfileModule:Zikula\ProfileModule\Block\UserBlock']);
        if (!isset($block)) {
            throw new NotFoundHttpException();
        }

        if (!$currentUserApi->isLoggedIn()) {
            throw new AccessDeniedException();
        }
        /** @var UserEntity $userEntity */
        $userEntity = $userRepository->find($currentUserApi->get('uid'));

        $formVars = [
            'ublockon' => $userEntity->hasAttribute('ublockon') ? (bool) $userEntity->getAttributeValue('ublockon') : false,
            'ublock'   => $userEntity->hasAttribute('ublock') ? $userEntity->getAttributeValue('ublock') : '',
        ];

        $form = $this->createForm(UsersBlockType::class, $formVars);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();

                $userEntity->setAttribute('ublockon', $formData['ublockon']);
                $userEntity->setAttribute('ublock', $formData['ublock']);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('status', $this->trans('Done! Saved custom block.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->trans('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulausersmodule_account_menu');
        }

        return [
            'form' => $form->createView()
        ];
    }
}
