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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ProfileModule\Form\Type\ConfigType;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * Class ConfigController.
 *
 * @Route("/config")
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/config")
     * @Theme("admin")
     * @Template
     *
     * @param Request $request
     *
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     *
     * @return array
     */
    public function configAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $dataValues = $this->getVars();
        $dataValues['viewregdate'] = (bool) $dataValues['viewregdate'];

        $form = $this->createForm(ConfigType::class,
            $dataValues, [
                'translator' => $this->get('translator.default'),
            ]
        );

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                $formData['viewregdate'] = ($formData['viewregdate'] == true ? 1 : 0);

                // save modvars
                $this->setVars($formData);

                $this->addFlash('status', $this->__('Done! Module configuration updated.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }
        }

        $fieldSets = [];

        return [
            'form'            => $form->createView(),
        ];
    }
}
