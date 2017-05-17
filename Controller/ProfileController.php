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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\UsersModule\Entity\UserEntity;

class ProfileController extends AbstractController
{
    /**
     * @Route("/display/{uid}", requirements={"uid" = "\d+"}, defaults={"uid" = null})
     * @Template
     *
     * Display a profile.
     *
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
            'user' => $userEntity
        ];
    }

    /**
     * @Route("/edit/{uid}", requirements={"uid" = "\d+"}, defaults={"uid" = null})
     * @Template
     * @param UserEntity|null $userEntity
     * @return array
     */
    public function editAction(UserEntity $userEntity = null)
    {
        $currentUserUid = $this->get('zikula_users_module.current_user')->get('uid');
        if (empty($userEntity)) {
            $userEntity = $this->get('zikula_users_module.user_repository')->find($currentUserUid);
        }
        if ($userEntity->getUid() != $currentUserUid && !$this->hasPermission('ZikulaProfileModule::edit', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $prefix = $this->getParameter('zikula_profile_module.property_prefix');
        $attributeValues = [];
        foreach ($userEntity->getAttributes() as $attribute) {
            if (0 === strpos($attribute->getName(), $prefix)) {
                $attributeValues[$attribute->getName()] = $attribute->getValue();
            }
        }
        $properties = $this->getDoctrine()->getRepository('ZikulaProfileModule:PropertyEntity')->findBy(['active' => true], ['weight' => 'ASC']);
        $formBuilder = $this->createFormBuilder($attributeValues);
        foreach ($properties as $property) {
            $child = $prefix . ':' .$property->getId();
            $options = $property->getFormOptions();
            $options['label'] = isset($options['label']) ? $options['label'] : $userEntity->getAttributes()->get($child)->getExtra();
            $formBuilder->add($child, $property->getFormType(), $options);
        }
        $formBuilder->add('save', SubmitType::class, [
            'label' => $this->__('Save'),
            'icon'  => 'fa-check',
            'attr'  => [
                'class' => 'btn btn-success',
            ],
        ]);
        $formBuilder->add('cancel', SubmitType::class, [
        'label' => $this->__('Cancel'),
        'icon'  => 'fa-times',
        'attr'  => [
            'class' => 'btn btn-default',
        ],
    ]);

        return [
            'user' => $userEntity,
            'form' => $formBuilder->getForm()->createView()
        ];
    }
}
