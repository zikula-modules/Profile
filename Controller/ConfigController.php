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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ProfileModule\Form\Type\ConfigType;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\ThemeModule\Engine\Annotation\Theme;
use Zikula\UsersModule\Constant as UsersConstant;

/**
 * @Route("/config")
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/config")
     * @Theme("admin")
     * @Template("ZikulaProfileModule:Config:config.html.twig")
     *
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     */
    public function configAction(Request $request, VariableApiInterface $variableApi): array
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $modVars = $this->getVars();

        $varsInUsersModule = [
            ProfileConstant::MODVAR_AVATAR_IMAGE_PATH => ProfileConstant::DEFAULT_AVATAR_IMAGE_PATH,
            ProfileConstant::MODVAR_GRAVATARS_ENABLED => ProfileConstant::DEFAULT_GRAVATARS_ENABLED,
            ProfileConstant::MODVAR_GRAVATAR_IMAGE =>  ProfileConstant::DEFAULT_GRAVATAR_IMAGE
        ];
        foreach ($varsInUsersModule as $varName => $defaultValue) {
            $modVars[$varName] = $variableApi->get(UsersConstant::MODNAME, $varName, $defaultValue);
        }

        $form = $this->createForm(ConfigType::class, $modVars);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();

                foreach ($varsInUsersModule as $varName => $defaultValue) {
                    $value = $formData[$varName] ?? $defaultValue;
                    $variableApi->set(UsersConstant::MODNAME, $varName, $value);
                    if (isset($formData[$varName])) {
                        unset($formData[$varName]);
                    }
                }

                $this->setVars($formData);
                $this->addFlash('status', $this->__('Done! Module configuration updated.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }
        }

        $pathWarning = '';
        if (true === $modVars['allowUploads']) {
            $path = $modVars[ProfileConstant::MODVAR_AVATAR_IMAGE_PATH];
            if (!(file_exists($path) && is_readable($path))) {
                $pathWarning = $this->__('Warning! The avatar directory does not exist or is not readable for the webserver.');
            } elseif (!is_writable($path)) {
                $pathWarning = $this->__('Warning! The webserver cannot write to the avatar directory.');
            }
        }

        return [
            'form' => $form->createView(),
            'pathWarning' => $pathWarning
        ];
    }
}
