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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use System;

/**
 * Class FormController
 * UI operations related to the display of dynamically defined user attributes.
 */
class FormController extends \Zikula_AbstractController
{
    public function postInitialize()
    {
        // disable view caching for all admin functions
        $this->view->setCaching(false);
    }

    /**
     * Display the dynadata section of a form for editing user accounts or registering for a new account.
     *
     * @param Request $request
     *
     * Parameters passed via POST:
     * ---------------------------------------------------
     * integer userid   The user id of the user for which the form section is being rendered; optional; defaults to 1, which will result in the use of
     *                      default values from the anonymous user.
     * array   dynadata The dynamic user data with which to populate the form section; retrieved from a GET, POST,
     *                      REQUEST, COOKIE, or SESSION variable.
     *
     * @return string The rendered template output.
     */
    public function editAction(Request $request)
    {
        // can't use this function directly
        if (ModUtil::getName() == 'ZikulaProfileModule') {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! You cannot access form functions directly.'));

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
        }

        // The API function is called.
        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['get' => 'editable']);
        // The return value of the function is checked here
        if ($items == false) {
            return '';
        }

        // check if there's a user to edit
        // or uses uid=1 to pull the default values from the annonymous user
        $userid = isset($args['userid']) ? $args['userid'] : 1;
        $dynadata = isset($args['dynadata']) ? $args['dynadata'] : $request->request->get('dynadata', []);
        // merge this temporary dynadata and the errors into the items array
        foreach ($items as $prop_label => $item) {
            foreach ($dynadata as $propname => $propdata) {
                if ($item['prop_attribute_name'] == $propname) {
                    $items[$prop_label]['temp_propdata'] = $propdata;
                }
            }
        }

        $this->view
            ->assign('duditems', $items)
            ->assign('userid', $userid);

        // Return the dynamic data section
        return new Response($this->view->fetch('Form/edit.tpl'));
    }

    /**
     * Display the dynadata section of the search form.
     *
     * @param Request $request
     *
     * @return string The rendered template output.
     */
    public function searchAction(Request $request)
    {
        // can't use this function directly
        if (ModUtil::getName() == 'ZikulaProfileModule') {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! You cannot access form functions directly.'));

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
        }

        // The API function is called.
        $items = ModUtil::apiFunc($this->name, 'user', 'getallactive');
        // The return value of the function is checked here
        if ($items == false) {
            return '';
        }

        // unset the avatar and timezone fields
        if (isset($items['avatar'])) {
            unset($items['avatar']);
        }
        if (isset($items['tzoffset'])) {
            unset($items['tzoffset']);
        }
        // reset the 'required' flags
        foreach (array_keys($items) as $k) {
            $items[$k]['prop_required'] = false;
        }

        $this->view
            ->assign('duditems', $items)
            ->assign('userid', 1);
        // Return the dynamic data section
        return new Response($this->view->fetch('Form/edit.tpl'));
    }

    /**
     * Fills a z-datatable body with the passed dynadata.
     *
     * @param Request $request
     *
     * Parameters passed via the $args array:
     * --------------------------------------
     * array userinfo The dynadata with which to populate the data table.
     *
     * @return string The rendered template output.
     */
    public function displayAction(Request $request)
    {
        // can't use this function directly
        if (ModUtil::getName() == 'ZikulaProfileModule') {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! You cannot access form functions directly.'));

            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_user_viewmembers', [], RouterInterface::ABSOLUTE_URL));
        }

        // The API function is called.
        $items = ModUtil::apiFunc($this->name, 'user', 'getallactive');
        // The return value of the function is checked here
        if ($items == false) {
            return '';
        }

        $userinfo = isset($args['userinfo']) ? $args['userinfo'] : [];

        // Create output object
        $this->view
            ->assign('duditems', $items)
            ->assign('userinfo', $userinfo);

        // Return the dynamic data rows
        return new Response($this->view->fetch('Form/display.tpl'));
    }
}
