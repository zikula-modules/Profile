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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\UsersModule\Entity\UserEntity;

class ProfileController extends AbstractController
{
    /**
     * @Route("/display/{uid}", requirements={"uid" = "\d+"}, defaults={"uid" = null})
     * @Template
     * @param UserEntity|null $userEntity
     * @throws AccessDeniedException on failed permission check
     * @return array
     */
    public function displayAction(UserEntity $userEntity = null)
    {
        if (!$this->hasPermission('ZikulaProfileModule::view', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }
        if (empty($userEntity)) {
            $userEntity = $this->get('zikula_users_module.user_repository')->find($this->get('zikula_users_module.current_user')->get('uid'));
        }

        return [
            'prefix' => $this->getParameter('zikula_profile_module.property_prefix'),
            'user' => $userEntity
        ];
    }

    /**
     * @Route("/edit/{uid}", requirements={"uid" = "\d+"}, defaults={"uid" = null})
     * @Template
     * @param Request $request
     * @param UserEntity|null $userEntity
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, UserEntity $userEntity = null)
    {
        $currentUserUid = $this->get('zikula_users_module.current_user')->get('uid');
        if (empty($userEntity)) {
            $userEntity = $this->get('zikula_users_module.user_repository')->find($currentUserUid);
        }
        if ($userEntity->getUid() != $currentUserUid && !$this->hasPermission('ZikulaProfileModule::edit', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $form = $this->get('zikula_profile_module.form.profile_type_factory')->createForm($userEntity->getAttributes());
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked() && $form->isValid()) {
                $attributes = $form->getData();
                foreach ($attributes as $attribute => $value) {
                    $userEntity->setAttribute($attribute, $value);
                }
                $this->getDoctrine()->getManager()->flush();
            }

            return $this->redirectToRoute('zikulaprofilemodule_profile_display', ['uid' => $userEntity->getUid()]);
        }

        return [
            'user' => $userEntity,
            'form' => $form->createView()
        ];
    }
}
