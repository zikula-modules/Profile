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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Bundle\CoreBundle\Controller\AbstractController;
use Zikula\Bundle\FormExtensionBundle\Form\Type\DeletionType;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;
use Zikula\ProfileModule\Form\Type\PropertyType;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * @Route("/property")
 */
class PropertyController extends AbstractController
{
    /**
     * @Route("/list")
     * @Theme("admin")
     * @Template("@ZikulaProfileModule/Property/list.html.twig")
     */
    public function listAction(PropertyRepositoryInterface $propertyRepository): array
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $properties = $propertyRepository->findBy([], ['weight' => 'ASC']);

        return [
            'properties' => $properties
        ];
    }

    /**
     * @Route("/edit/{id}", defaults={"id" = null})
     * @Theme("admin")
     * @Template("@ZikulaProfileModule/Property/edit.html.twig")
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, PropertyEntity $propertyEntity = null)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        if (!isset($propertyEntity)) {
            $propertyEntity = new PropertyEntity();
        }
        $form = $this->createForm(PropertyType::class, $propertyEntity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $propertyEntity = $form->getData();
                $this->getDoctrine()->getManager()->persist($propertyEntity);
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $this->trans('Property saved.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('info', $this->trans('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulaprofilemodule_property_list');
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/delete/{id}")
     * @Theme("admin")
     * @Template("@ZikulaProfileModule/Property/delete.html.twig")
     *
     * @return array|RedirectResponse
     */
    public function deleteAction(Request $request, PropertyEntity $propertyEntity)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $form = $this->createForm(DeletionType::class, $propertyEntity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $propertyEntity = $form->getData();
                $this->getDoctrine()->getManager()->remove($propertyEntity);
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $this->trans('Property removed.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('info', $this->trans('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulaprofilemodule_property_list');
        }

        return [
            'id' => $propertyEntity->getId(),
            'form' => $form->createView()
        ];
    }
}
