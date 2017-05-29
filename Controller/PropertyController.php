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
use Zikula\Bundle\FormExtensionBundle\Form\Type\DeletionType;
use Zikula\Core\Controller\AbstractController;
use Zikula\ProfileModule\Entity\PropertyEntity;
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
     * @Template
     * @param Request $request
     * @return array
     */
    public function listAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $properties = $this->getDoctrine()->getRepository(PropertyEntity::class)->findBy([], ['weight' => 'ASC']);

        return [
            'properties' => $properties
        ];
    }

    /**
     * @Route("/edit/{id}", defaults={"id" = null})
     * @Theme("admin")
     * @Template
     *
     * @param Request $request
     * @param PropertyEntity $propertyEntity
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
        $this->transformTranslations($propertyEntity, $request);
        $form = $this->createForm(PropertyType::class, $propertyEntity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $propertyEntity = $form->getData();
                $this->reverseTransformTranslations($propertyEntity, $request);
                $this->getDoctrine()->getManager()->persist($propertyEntity);
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $this->__('Property saved.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('info', $this->__('Operation cancelled.'));
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
     * @Template
     * @param Request $request
     * @param PropertyEntity $propertyEntity
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
                $this->addFlash('success', $this->__('Property removed.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('info', $this->__('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulaprofilemodule_property_list');
        }

        return [
            'id' => $propertyEntity->getId(),
            'form' => $form->createView()
        ];
    }

    private function transformTranslations(PropertyEntity $propertyEntity, Request $request)
    {
        $installedLanguageNames = $this->get('zikula_settings_module.locale_api')->getSupportedLocaleNames(null, $request->getLocale());
        $values = [];
        $labels = $propertyEntity->getLabels();
        foreach ($installedLanguageNames as $languageName => $code) {
            $values[$languageName] = !empty($labels[$code]) ? $labels[$code] : '';
        }
        $propertyEntity->setLabels($values);
    }

    private function reverseTransformTranslations(PropertyEntity $propertyEntity, Request $request)
    {
        $installedLanguageNames = $this->get('zikula_settings_module.locale_api')->getSupportedLocaleNames(null, $request->getLocale());
        $values = [];
        $labels = $propertyEntity->getLabels();
        foreach ($installedLanguageNames as $languageName => $code) {
            $values[$code] = !empty($labels[$languageName]) ? $labels[$languageName] : '';
        }
        $propertyEntity->setLabels($values);
    }
}
