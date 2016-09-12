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
use SecurityUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use System;

/**
 * Class AdminController
 * @Route("/admin")
 */
class AdminController extends \Zikula_AbstractController
{
    public function postInitialize()
    {
        // disable view caching for all admin functions
        $this->view->setCaching(false);
    }

    /**
     * Route not needed here because this is a legacy-only method
     * 
     * The default entrypoint.
     *
     * @return RedirectResponse
     */
    public function mainAction()
    {
        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("")
     * 
     * the default entrypoint.
     * 
     * @return RedirectResponse
     */
    public function indexAction()
    {
        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/help")
     * 
     * The Profile help page.
     *
     * @return Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function helpAction()
    {
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        return new Response($this->view->fetch('Admin/help.tpl'));
    }

    /**
     * @Route("/view/{numitems}/{startnum}", requirements={"numitems" = "\d+", "startnum" = "\d+"})
     * @Method("GET")
     * 
     * View all items managed by this module.
     * 
     * @param integer $numitems
     * @param integer $startnum
     *
     * @return Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function viewAction($numitems = -1, $startnum = 1)
    {
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getall', ['startnum' => $startnum, 'numitems' => $numitems]);
        $count = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'countitems');
        $csrftoken = SecurityUtil::generateCsrfToken();
        $x = 1;
        $duditems = [];
        foreach ($items as $item) {
            // display the proper icon and link to enable or disable the field
            switch (true) {
                // 0 <= DUD types can't be disabled
                case $item['prop_dtype'] <= 0:
                    $statusval = 1;
                    $status = [
                        'url' => '',
                        'labelClass' => 'label label-success',
                        'current' => $this->__('Active'),
                        'title' => $this->__('Required')
                    ];
                    break;
                case $item['prop_weight'] != 0:
                    $statusval = 1;
                    $status = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_deactivate', ['dudid' => $item['prop_id'], 'weight' => $item['prop_weight'], 'csrftoken' => $csrftoken]),
                        'labelClass' => 'label label-success',
                        'current' => $this->__('Active'),
                        'title' => $this->__('Deactivate')
                    ];
                    break;
                default:
                    $statusval = 0;
                    $status = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_activate', ['dudid' => $item['prop_id'], 'csrftoken' => $csrftoken]),
                        'labelClass' => 'label label-danger',
                        'current' => $this->__('Inactive'),
                        'title' => $this->__('Activate')
                    ];
            }
            // analyzes the DUD type
            switch ($item['prop_dtype']) {
                case '-2':
                    // non-editable field
                    $data_type_text = $this->__('Not editable field');
                    break;
                case '-1':
                    // Third party (non-editable)
                    $data_type_text = $this->__('Third-party (not editable)');
                    break;
                case '0':
                    // Third party (mandatory)
                    $data_type_text = $this->__('Third-party') . ($item['prop_required'] ? ', ' . $this->__('Required') : '');
                    break;
                default:
                case '1':
                    // Normal property
                    $data_type_text = $this->__('Normal') . ($item['prop_required'] ? ', ' . $this->__('Required') : '');
                    break;
                case '2':
                    // Third party (normal field)
                    $data_type_text = $this->__('Third-party') . ($item['prop_required'] ? ', ' . $this->__('Required') : '');
                    break;
            }
            // Options for the item.
            $options = [];
            if (SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
                $options[] = [
                    'url' => $this->get('router')->generate('zikulaprofilemodule_admin_edit', ['dudid' => $item['prop_id']]),
                    'class' => '',
                    'iconClass' => 'fa fa-pencil fa-lg',
                    'title' => $this->__('Edit')
                ];
                if ($item['prop_weight'] > 1) {
                    $options[] = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_decreaseweight', ['dudid' => $item['prop_id']]),
                        'class' => 'profile_up',
                        'iconClass' => 'fa fa-arrow-up fa-lg',
                        'title' => $this->__('Up')
                    ];
                }
                if ($x < $count) {
                    $options[] = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_increaseweight', ['dudid' => $item['prop_id']]),
                        'class' => 'profile_down',
                        'iconClass' => 'fa fa-arrow-down fa-lg',
                        'title' => $this->__('Down')
                    ];
                }
                if (SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_DELETE) && $item['prop_dtype'] > 0) {
                    $options[] = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_delete', ['dudid' => $item['prop_id']]),
                        'class' => '', 'title' => $this->__('Delete'),
                        'iconClass' => 'fa fa-trash-o fa-lg text-danger'
                    ];
                }
            }
            $item['status'] = $status;
            $item['statusval'] = $statusval;
            $item['options'] = $options;
            $item['dtype'] = $data_type_text;
            $item['prop_fieldset'] = ((isset($item['prop_fieldset'])) && (!empty($item['prop_fieldset']))) ? $item['prop_fieldset'] : $this->__('User Information');
            $duditems[] = $item;
            $x++;
        }
        $this->view->setCaching(false)
            ->assign('startnum', $startnum)
            ->assign('duditems', $duditems)
            ->assign('pager', ['numitems' => $count, 'itemsperpage' => $numitems]);

        return new Response($this->view->fetch('Admin/view.tpl'));
    }

    /**
     * @Route("/modify")
     * @Method("POST")
     * 
     * Create the dud - process the edit form.
     * 
     * @param Request $request
     *
     * Parameters passed via POST:
     * ----------------------------------------------------
     * integer dudid         (if editing) the property id
     * string  label         The name
     * string  attributename The attribute name
     * numeric required      0 if not required, 1 if required.
     * numeric viewby        Viewable-by option; 0 thru 3, everyone, registered users, admins and account owners, admin only.
     * numeric displaytype   Display type; 0 thru 7.
     * array   listoptions   If the display type is a list, then the options to display in the list.
     * string  note          Note for the item.
     * string  fieldset      The fieldset to group the item.
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function modifyAction(Request $request)
    {
        $this->checkCsrfToken();
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }
    
        // Get parameters from whatever input we need.
        $dudid = (int)$request->request->get('dudid', 0);
        $label = $request->request->get('label', null);
        $attrname = $request->request->get('attributename', null);
        $required = $request->request->get('required', null);
        $viewby = $request->request->get('viewby', null);
        $displaytype = $request->request->get('displaytype', null);
        $listoptions = $request->request->get('listoptions', null);
        $note = $request->request->get('note', null);
        $fieldset = $request->request->get('fieldset', null);
        $pattern = $request->request->get('pattern', null);
    
        // Validates and check if empty or already existing...
        if (empty($label)) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! The item must have a label. An example of a recommended label is: \'_MYDUDLABEL\'.'));
            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
        }
        if (empty($dudid) && empty($attrname)) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! The item must have an attribute name. An example of an acceptable name is: \'mydudfield\'.'));
            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
        }
        //@todo The check needs to occur for both the label and fieldset.
        //if (ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $label, 'propfieldset' => $fieldset])) {
        //    $request->getSession()->getFlashBag()->add('error', $this->__('Error! There is already a label with this name.'));
        //    return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
        //}
        if (isset($attrname) && ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $attrname])) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! There is already an attribute name with this naming.'));
            return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
        }
        $filteredlabel = $label;

        $parameters = [
            'dudid' => $dudid,
            'label' => $filteredlabel,
            'attribute_name' => $attrname,
            'required' => $required,
            'viewby' => $viewby,
            'dtype' => 1,
            'displaytype' => $displaytype,
            'listoptions' => $listoptions,
            'note' => $note,
            'fieldset' => $fieldset,
            'pattern' => $pattern
        ];
        if (empty($dudid)) {
            $dudid = ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'create', $parameters);
            $successMessage = $this->__('Done! Created new personal info item.');
        } else {
            $dudid = ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'update', $parameters);
            $successMessage = $this->__('Done! Saved your changes.');
        }
        if ($dudid != false) {
            // Success
            $request->getSession()->getFlashBag()->add('status', $successMessage);
        }

        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/edit/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     *
     * Show form to create or modify a dynamic user data item.
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be modified.
     *
     * @return Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function editAction(Request $request, $dudid = 0)
    {
        if (!empty($dudid)) {
            $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
            if ($item == false) {
                $request->getSession()->getFlashBag()->add('error', $this->__('Error! No such personal info item found.'));
                return new Response();
            }
            // Security check
            if (!SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$dudid}", ACCESS_EDIT)) {
                throw new AccessDeniedException();
            }
            // backward check to remove any 1.4- forbidden char in listoptions 10 = New Line /n and 13 = Carriage Return /r
            $item['prop_listoptions'] = str_replace(Chr(10), '', str_replace(Chr(13), '', $item['prop_listoptions']));
            $item['prop_fieldset'] = (isset($item['prop_fieldset']) && !empty($item['prop_fieldset'])) ? $item['prop_fieldset'] : $this->__('User Information');
            $item['prop_listoptions'] = str_replace(' ', '', $item['prop_listoptions']);
            $this->view->assign('item', $item);
        } else {
            if (!SecurityUtil::checkPermission('ZikulaProfileModule::', '::', ACCESS_ADD)) {
                throw new AccessDeniedException();
            }
        }
        // create arrays for select boxes
        $this->view->assign('displaytypes', [
            0 => $this->__('Text box'),
            1 => $this->__('Text area'),
            2 => $this->__('Checkbox'),
            3 => $this->__('Radio button'),
            4 => $this->__('Dropdown list'),
            5 => $this->__('Date'),
            7 => $this->__('Multiple checkbox set'))
        ];
        $this->view->assign('requiredoptions', [
            0 => $this->__('No'),
            1 => $this->__('Yes'))
        ];
        $this->view->assign('viewbyoptions', [
            0 => $this->__('Everyone'),
            1 => $this->__('Registered users only'),
            2 => $this->__('Admins and account owner only'),
            3 => $this->__('Admins only'))
        ];
        // Add a hidden variable for the item id.
        $this->view->assign('dudid', $dudid);

        return new Response($this->view->fetch('Admin/edit.tpl'));
    }

    /**
     * @Route("/delete")
     *
     * Delete a dud item.
     *
     * @param Request $request
     *
     * Parameters passed via GET or via POST:
     * ------------------------------------------------------------
     * int  dudid        The id of the item to be deleted.
     * bool confirmation Confirmation that this item can be deleted.
     *
     * @return RedirectResponse|Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function deleteAction(Request $request)
    {
        // Get parameters from whatever input we need.
        $dudid = (int)$request->get('dudid', null);
        $confirmation = (bool)$request->get('confirmation', null);

        // The user API function is called.
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if ($item == false) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! No such personal info item found.'));
            return new Response();
        }
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$dudid}", ACCESS_DELETE)) {
            throw new AccessDeniedException();
        }
        // Check for confirmation.
        if (empty($confirmation)) {
            // No confirmation yet - display a suitable form to obtain confirmation
            // of this action from the user
            // Add hidden item id to form
            $this->view->assign('dudid', $dudid);
            // Return the output that has been generated by this function
            return new Response($this->view->fetch('Admin/delete.tpl'));
        }
        // If we get here it means that the user has confirmed the action
        // Check CsrfToken
        $this->checkCsrfToken();
        // The API function is called.
        if (ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'delete', ['dudid' => $dudid])) {
            // Success
            $request->getSession()->getFlashBag()->add('status', $this->__('Done! The field has been successfully deleted.'));
        }

        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/increaseweight/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     *
     * Increase weight of a dud item in the sorted list.
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function increaseWeightAction(Request $request, $dudid)
    {
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if ($item == false) {
            $request->getSession()->getFlashBag()->add('error', $this->__('Error! No such personal info item found.'));
            return new Response();
        }
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        /** @var $prop \Zikula\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $dudid);
        $prop->incrementWeight();
        $this->entityManager->flush();

        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/decreaseweight/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     *
     * Decrease weight of a dud item in the sorted list.
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse|Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function decreaseWeightAction(Request $request, $dudid)
    {
        $flashBag = $request->getSession()->getFlashBag();

        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if ($item == false) {
            $flashBag->add('error', $this->__('Error! No such personal info item found.'));
            return new Response();
        }
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        if ($item['prop_weight'] <= 1) {
            $flashBag->add('error', $this->__('Error! You cannot decrease the weight of this account property.'));
            return new Response();
        }
        /** @var $prop \Zikula\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $dudid);
        $prop->decrementWeight();
        $this->entityManager->flush();

        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/activate/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     *
     * Process item activation request
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse
     */
    public function activateAction(Request $request, $dudid)
    {
        $this->checkCsrfToken($request->query->get('csrftoken'));
        // The API function is called.
        if (ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'activate', ['dudid' => $dudid])) {
            // Success
            $request->getSession()->getFlashBag()->add('status', $this->__('Done! Saved your changes.'));
        }

        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/deactivate/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     *
     * Process item deactivation request
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse
     */
    public function deactivateAction(Request $request, $dudid)
    {
        $this->checkCsrfToken($request->query->get('csrftoken'));
        // The API function is called.
        if (ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'deactivate', ['dudid' => $dudid])) {
            // Success
            $request->getSession()->getFlashBag()->add('status', $this->__('Done! Saved your changes.'));
        }

        return new RedirectResponse($this->get('router')->generate('zikulaprofilemodule_admin_view', [], RouterInterface::ABSOLUTE_URL));
    }
}
