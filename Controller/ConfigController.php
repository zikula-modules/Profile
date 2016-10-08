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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * Class ConfigController
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
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function configAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $dataValues = $this->getVars();
        $dataValues['viewregdate'] = (bool)$dataValues['viewregdate'];
        $dudFields = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['get' => 'editable', 'index' => 'prop_id']);

        foreach ($dudFields as $key => $item) {
            $dataValues['dudregshow_' . $item['prop_attribute_name']] = in_array($item['prop_attribute_name'], $dataValues['dudregshow']);
        }

        $form = $this->createForm('Zikula\ProfileModule\Form\Type\ConfigType',
            $dataValues, [
                'translator' => $this->get('translator.default'),
                'dudFields' => $dudFields
            ]
        );

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                $formData['viewregdate'] = ($formData['viewregdate'] == true ? 1 : 0);

                $formData['dudregshow'] = [];
                foreach ($dudFields as $key => $item) {
                    if (!isset($formData['dudregshow_' . $item['prop_attribute_name']])) {
                        continue;
                    }
                    $formData['dudregshow'][] = $item['prop_attribute_name'];
                    unset($formData['dudregshow_' . $item['prop_attribute_name']]);
                }

                // save modvars
                $this->setVars($formData);

                $this->addFlash('status', $this->__('Done! Module configuration updated.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }
        }

        $fieldSets = [];

        foreach ($dudFields as $k => $item) {
            $item['prop_fieldset'] = (isset($item['prop_fieldset']) && !empty($item['prop_fieldset'])) ? $item['prop_fieldset'] : $this->__('User Information');
            $dudFields[$k] = (array)$item;
            $fieldSets[$item['prop_fieldset']] = $item['prop_fieldset'];
        }

        return [
            'form' => $form->createView(),
            'dudFieldSets' => $fieldSets,
            'dudFields' => $dudFields,
            'dudFieldsActive' => (isset($dataValues['dudregshow']) ? $dataValues['dudregshow'] : [])
        ];
    }
}
