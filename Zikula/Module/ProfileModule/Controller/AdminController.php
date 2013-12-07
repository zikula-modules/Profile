<?php
/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\ProfileModule\Controller;

use ModUtil;
use LogUtil;
use SecurityUtil;
use DataUtil;
use System;
use Zikula_View;

/**
 * Administrative UI-oriented operations.
 */
class AdminController extends \Zikula_AbstractController
{
    /**
     * The default entrypoint.
     *
     * @return void
     */
    public function mainAction()
    {
        $this->redirect(ModUtil::url($this->name, 'admin', 'view'), 301);
    }
    
    /**
     * The Profile help page.
     *
     * @return string The rendered template output.
     */
    public function helpAction()
    {
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        return $this->view->fetch('Admin/help.tpl');
    }
    
    /**
     * View all items managed by this module.
     * 
     * @return string The rendered template output.
     */
    public function viewAction()
    {
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        // Get parameters from whatever input we need.
        $startnum = (int) $this->request->query->get('startnum', null);
        $numitems = 20;
        $items = ModUtil::apiFunc($this->name, 'user', 'getall', array('startnum' => $startnum, 'numitems' => $numitems));
        $count = ModUtil::apiFunc($this->name, 'user', 'countitems');
        $csrftoken = SecurityUtil::generateCsrfToken();
        $x = 1;
        $duditems = array();
        foreach ($items as $item) {
            // display the proper icon and link to enable or disable the field
            switch (true) {
                // 0 <= DUD types can't be disabled
                case $item['prop_dtype'] <= 0:
                    $statusval = 1;
                    $status = array('url' => '', 'image' => 'greenled.png', 'title' => $this->__('Required'));
                    break;
                case $item['prop_weight'] != 0:
                    $statusval = 1;
                    $status = array('url' => ModUtil::url($this->name, 'admin', 'deactivate', array('dudid' => $item['prop_id'], 'weight' => $item['prop_weight'], 'csrftoken' => $csrftoken)), 'image' => 'greenled.png', 'title' => $this->__('Deactivate'));
                    break;
                default:
                    $statusval = 0;
                    $status = array('url' => ModUtil::url($this->name, 'admin', 'activate', array('dudid' => $item['prop_id'], 'csrftoken' => $csrftoken)), 'image' => 'redled.png', 'title' => $this->__('Activate'));
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
            $options = array();
            if (SecurityUtil::checkPermission('Profile::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
                $options[] = array('url' => ModUtil::url($this->name, 'admin', 'modify', array('dudid' => $item['prop_id'])), 'image' => 'xedit.png', 'class' => '', 'title' => $this->__('Edit'));
                if ($item['prop_weight'] > 1) {
                    $options[] = array('url' => ModUtil::url($this->name, 'admin', 'decrease_weight', array('dudid' => $item['prop_id'])), 'image' => '2uparrow.png', 'class' => 'profile_up', 'title' => $this->__('Up'));
                }
                if ($x < $count) {
                    $options[] = array('url' => ModUtil::url($this->name, 'admin', 'increase_weight', array('dudid' => $item['prop_id'])), 'image' => '2downarrow.png', 'class' => 'profile_down', 'title' => $this->__('Down'));
                }
                if (SecurityUtil::checkPermission('Profile::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_DELETE) && $item['prop_dtype'] > 0) {
                    $options[] = array('url' => ModUtil::url($this->name, 'admin', 'delete', array('dudid' => $item['prop_id'])), 'image' => '14_layer_deletelayer.png', 'class' => '', 'title' => $this->__('Delete'));
                }
            }
            $item['status'] = $status;
            $item['statusval'] = $statusval;
            $item['options'] = $options;
            $item['dtype'] = $data_type_text;
            $item['prop_fieldset'] = isset($item['prop_fieldset']) && !empty($item['prop_fieldset']) ? $item['prop_fieldset'] : $this->__('User Information');
            $duditems[] = $item;
            $x++;
        }
        $this->view->setCaching(false)->assign('startnum', $startnum)->assign('duditems', $duditems);
        // assign the values for the smarty plugin to produce a pager in case of there
        // being many items to display.
        $this->view->assign('pager', array('numitems' => $count, 'itemsperpage' => $numitems));
        // Return the output that has been generated by this function
        return $this->view->fetch('Admin/view.tpl');
    }
    
    /**
     * Add new dynamic user data item.
     *
     * @return string The rendered template output.
     */
    public function newdudAction()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
        $this->view->assign('displaytypes', array(0 => DataUtil::formatForDisplay($this->__('Text box')), 1 => DataUtil::formatForDisplay($this->__('Text area')), 2 => DataUtil::formatForDisplay($this->__('Checkbox')), 3 => DataUtil::formatForDisplay($this->__('Radio button')), 4 => DataUtil::formatForDisplay($this->__('Dropdown list')), 5 => DataUtil::formatForDisplay($this->__('Date')), 7 => DataUtil::formatForDisplay($this->__('Multiple checkbox set'))));
        $this->view->assign('requiredoptions', array(0 => DataUtil::formatForDisplay($this->__('No')), 1 => DataUtil::formatForDisplay($this->__('Yes'))));
        $this->view->assign('viewbyoptions', array(0 => DataUtil::formatForDisplay($this->__('Everyone')), 1 => DataUtil::formatForDisplay($this->__('Registered users only')), 2 => DataUtil::formatForDisplay($this->__('Admins and account owner only')), 3 => DataUtil::formatForDisplay($this->__('Admins only'))));
        // Return the output that has been generated by this function
        return $this->view->fetch('Admin/new.tpl');
    }
    
    /**
     * Create the dud.
     * 
     * Parameters passed via the $args array or via a POST:
     * ----------------------------------------------------
     * string  label         The name of the item to be created.
     * string  attributename The attribute name of the item to be created.
     * numeric required      0 if not required, 1 if required.
     * numeric viewby        Viewable-by option; 0 thru 3, everyone, registered users, admins and account owners, admin only.
     * numeric displaytype   Display type; 0 thru 7.
     * array   listoptions   If the display type is a list, then the options to display in the list.
     * string  note          Note for the item.
     * string fieldset The fieldset to group the item.
     *
     * @param array $args All parameters passed to this function via an internal call.
     * 
     * @return bool True if item created, false otherwise.
     *
     * @see    Profile_admin_new()
     */
    public function createAction($args)
    {
        $this->checkCsrfToken();
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
        // Get parameters from whatever input we need.
        $label = isset($args['label']) ? $args['label'] : $this->request->request->get('label', null);
        $attrname = isset($args['attributename']) ? $args['attributename'] : $this->request->request->get('attributename', null);
        $required = isset($args['required']) ? $args['required'] : $this->request->request->get('required', null);
        $viewby = isset($args['viewby']) ? $args['viewby'] : $this->request->request->get('viewby', null);
        $displaytype = isset($args['displaytype']) ? $args['displaytype'] : $this->request->request->get('displaytype', null);
        $listoptions = isset($args['listoptions']) ? $args['listoptions'] : $this->request->request->get('listoptions', null);
        $note = isset($args['note']) ? $args['note'] : $this->request->request->get('note', null);
        $fieldset = isset($args['fieldset']) ? $args['fieldset'] : $this->request->request->get('fieldset', null);
        $returnurl = ModUtil::url($this->name, 'admin', 'view');
        // Validates and check if empty or already existing...
        if (empty($label)) {
            return LogUtil::registerError($this->__('Error! The item must have a label. An example of a recommended label is: \'_MYDUDLABEL\'.'), null, $returnurl);
        }
        if (empty($attrname)) {
            return LogUtil::registerError($this->__('Error! The item must have an attribute name. An example of an acceptable name is: \'mydudfield\'.'), null, $returnurl);
        }
        //@todo The check needs to occur for both the label and fieldset.
        //if (ModUtil::apiFunc($this->name, 'user', 'get', array('proplabel' => $label, 'propfieldset' => $fieldset))) {
        //    return LogUtil::registerError($this->__('Error! There is already a label with this naming.'), null, $returnurl);
        //}
        if (ModUtil::apiFunc($this->name, 'user', 'get', array('propattribute' => $attrname))) {
            return LogUtil::registerError($this->__('Error! There is already an attribute name with this naming.'), null, $returnurl);
        }
        $filteredlabel = $label;
        // The API function is called.
        $dudid = ModUtil::apiFunc($this->name, 'admin', 'create', array('label' => $filteredlabel, 'attribute_name' => $attrname, 'required' => $required, 'viewby' => $viewby, 'dtype' => 1, 'displaytype' => $displaytype, 'listoptions' => $listoptions, 'note' => $note, 'fieldset' => $fieldset));
        // The return value of the function is checked here
        if ($dudid != false) {
            // Success
            LogUtil::registerStatus($this->__('Done! Created new personal info item.'));
        }
        // This function generated no output
        return System::redirect($returnurl);
    }
    
    /**
     * Modify a dynamic user data item.
     * 
     * This is a standard function that is called whenever an administrator wishes to modify a current module item.
     * 
     * Parameters passed via the $args array or via GET:
     * -------------------------------------------------
     * int dudid    The id of the item to be modified.
     * int objectid Generic object id maps to dudid if present.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return string The rendered template.
     */
    public function modifyAction($args)
    {
        // Get parameters from whatever input we need.
        $dudid = isset($args['dudid']) ? (int) $args['dudid'] : (int) $this->request->query->get('dudid', null);
        $objectid = isset($args['objectid']) ? (int) $args['objectid'] : (int) $this->request->query->get('objectid', null);
        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $dudid = $objectid;
        }
        // The user API function is called.
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $dudid));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }
        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "{$item['prop_label']}::{$dudid}", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        // backward check to remove any 1.4- forbidden char in listoptions
        $item['prop_listoptions'] = str_replace(Chr(10), '', str_replace(Chr(13), '', $item['prop_listoptions']));
        $item['prop_fieldset'] = isset($item['prop_fieldset']) && !empty($item['prop_fieldset']) ? $item['prop_fieldset'] : $this->__('User Information');
        // Create output object
        $render = Zikula_View::getInstance($this->name, false);
        // Add a hidden variable for the item id.
        $this->view->assign('dudid', $dudid);
        $this->view->assign('displaytypes', array(0 => DataUtil::formatForDisplay($this->__('Text box')), 1 => DataUtil::formatForDisplay($this->__('Text area')), 2 => DataUtil::formatForDisplay($this->__('Checkbox')), 3 => DataUtil::formatForDisplay($this->__('Radio button')), 4 => DataUtil::formatForDisplay($this->__('Dropdown list')), 5 => DataUtil::formatForDisplay($this->__('Date')), 7 => DataUtil::formatForDisplay($this->__('Multiple checkbox set'))));
        $this->view->assign('requiredoptions', array(0 => DataUtil::formatForDisplay($this->__('No')), 1 => DataUtil::formatForDisplay($this->__('Yes'))));
        $this->view->assign('viewbyoptions', array(0 => DataUtil::formatForDisplay($this->__('Everyone')), 1 => DataUtil::formatForDisplay($this->__('Registered users only')), 2 => DataUtil::formatForDisplay($this->__('Admins and account owner only')), 3 => DataUtil::formatForDisplay($this->__('Admins only'))));
        $item['prop_listoptions'] = str_replace('
', '', $item['prop_listoptions']);
        $this->view->assign('item', $item);
        return $this->view->fetch('Admin/modify.tpl');
    }
    
    /**
     * Update the dynamic user data definition.
     * 
     * Parameters passed in the $args array:
     * -------------------------------------
     * int    dudid    The id of the item to be updated.
     * int    objectid Generic object id maps to dudid if present.
     * string label    The name of the item to be updated.
     * string dtype    The data type of the item.
     * int    length   The lenght of item if dtype is string.
     * 
     * @param array $args All parameters passed to this function via an internal call.
     * 
     * @return bool True if update successful, false otherwise.
     *
     * @see    ProfileModify()
     */
    public function updateAction($args)
    {
        $this->checkCsrfToken();
        // Get parameters from whatever input we need.
        $dudid = (int) $this->request->request->get('dudid', isset($args['dudid']) ? $args['dudid'] : null);
        $objectid = (int) $this->request->request->get('objectid', isset($args['objectid']) ? $args['objectid'] : null);
        $label = $this->request->request->get('label', isset($args['label']) ? $args['label'] : null);
        $required = $this->request->request->get('required', isset($args['required']) ? $args['required'] : null);
        $viewby = $this->request->request->get('viewby', isset($args['viewby']) ? $args['viewby'] : null);
        $displaytype = $this->request->request->get('displaytype', isset($args['displaytype']) ? $args['displaytype'] : null);
        $listoptions = $this->request->request->get('listoptions', isset($args['listoptions']) ? $args['listoptions'] : null);
        $note = $this->request->request->get('note', isset($args['note']) ? $args['note'] : null);
        $fieldset = $this->request->request->get('fieldset', isset($args['fieldset']) ? $args['fieldset'] : null);
        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $dudid = $objectid;
        }
        // The return value of the function is checked here
        $parameters = array('dudid' => $dudid, 'required' => $required, 'viewby' => $viewby, 'label' => $label, 'displaytype' => $displaytype, 'listoptions' => str_replace('
', '', $listoptions), 'note' => $note, 'fieldset' => $fieldset);
        if (ModUtil::apiFunc($this->name, 'admin', 'update', $parameters)) {
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }
        // This function generated no output
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }
    
    /**
     * Delete a dud item.
     *
     * Parameters passed via the $args array, or via GET, or via POST:
     * ---------------------------------------------------------------
     * int  dudid        The id of the item to be deleted.
     * int  objectid     Generic object id maps to dudid if present.
     * bool confirmation Confirmation that this item can be deleted.
     * 
     * @param array $args All parameters passed to this function via an internal call.
     * 
     * @return boolean|string If no confirmation then the rendered output of a template to get confirmation; otherwise true if delete successful, false otherwise.
     */
    public function deleteAction($args)
    {
        // Get parameters from whatever input we need.
        $dudid = (int) $this->request->query->get('dudid', $this->request->request->get('dudid', isset($args['dudid']) ? $args['dudid'] : null));
        $objectid = (int) $this->request->query->get('objectid', $this->request->request->get('objectid', isset($args['objectid']) ? $args['objectid'] : null));
        $confirmation = (bool) $this->request->query->get('confirmation', $this->request->request->get('confirmation', isset($args['confirmation']) ? $args['confirmation'] : null));
        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $dudid = $objectid;
        }
        // The user API function is called.
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $dudid));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }
        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "{$item['prop_label']}::{$dudid}", ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }
        // Check for confirmation.
        if (empty($confirmation)) {
            // No confirmation yet - display a suitable form to obtain confirmation
            // of this action from the user
            // Add hidden item id to form
            $this->view->assign('dudid', $dudid);
            // Return the output that has been generated by this function
            return $this->view->fetch('Admin/delete.tpl');
        }
        // If we get here it means that the user has confirmed the action
        // Check CsrfToken
        $this->checkCsrfToken();
        // The API function is called.
        if (ModUtil::apiFunc($this->name, 'admin', 'delete', array('dudid' => $dudid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Deleted the personal info item.'));
        }
        // This function generated no output
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }
    
    /**
     * Increase weight of a dud item in the sorted list.
     *
     * Parameters passed in via GET:
     * -----------------------------
     * int dudid The id of the item to be updated.
     * 
     * @return boolean True if update successful, false otherwise.
     */
    public function increase_weightAction()
    {
        $dudid = (int) $this->request->query->get('dudid', null);
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $dudid));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }
        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        /** @var $prop \Zikula\Module\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('Zikula\Module\ProfileModule\Entity\PropertyEntity', $dudid);
        $prop->incrementWeight();
        $this->entityManager->flush();
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }
    
    /**
     * Decrease weight of a dud item on the sorted list.
     *
     * Parameters passed via GET:
     * --------------------------
     * int dudid The id of the item to be updated.
     * 
     * @return boolean True if update successful, false otherwise.
     */
    public function decrease_weightAction()
    {
        $dudid = (int) $this->request->query->get('dudid', null);
        $item = ModUtil::apiFunc($this->name, 'user', 'get', array('propid' => $dudid));
        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }
        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
        if ($item['prop_weight'] <= 1) {
            return LogUtil::registerError($this->__('Error! You cannot decrease the weight of this account property.'), 404);
        }
        /** @var $prop \Zikula\Module\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('Zikula\Module\ProfileModule\Entity\PropertyEntity', $dudid);
        $prop->decrementWeight();
        $this->entityManager->flush();
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }
    
    /**
     * Process item activation request
     *
     * Parameters passed in the $args array, or via GET:
     * -------------------------------------------------
     * int dudid Id of item activate.
     * 
     * @param array $args All parameters passed to this function via an internal call.
     * 
     * @return boolean True if activation successful, false otherwise.
     */
    public function activateAction($args)
    {
        $this->checkCsrfToken($this->request->query->get('csrftoken'));
        // Get parameters from whatever input we need.
        $dudid = (int) $this->request->query->get('dudid', isset($args['dudid']) ? $args['dudid'] : null);
        // The API function is called.
        if (ModUtil::apiFunc($this->name, 'admin', 'activate', array('dudid' => $dudid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }
        // This function generated no output
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }
    
    /**
     * Process item deactivation request
     *
     * Parameters passed in the $args array, or via GET:
     * -------------------------------------------------
     * int dudid Id of item deactivate
     * 
     * @param array $args All parameters passed to this function via an internal call.
     * 
     * @return boolean True if deactivation successful, false otherwise.
     */
    public function deactivateAction($args)
    {
        $this->checkCsrfToken($this->request->query->get('csrftoken'));
        // Get parameters from whatever input we need.
        $dudid = (int) $this->request->query->get('dudid', isset($args['dudid']) ? $args['dudid'] : null);
        // The API function is called.
        if (ModUtil::apiFunc($this->name, 'admin', 'deactivate', array('dudid' => $dudid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }
        // This function generated no output
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }
    
    /**
     * This is a standard function to modify the configuration parameters of the module.
     *
     * @return string The rendered template output.
     */
    public function modifyconfigAction()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        $items = ModUtil::apiFunc($this->name, 'user', 'getallactive', array('get' => 'editable', 'index' => 'prop_id'));

        $fieldsets = array();
        		
        foreach ($items as $k => $item) {
            $item['prop_fieldset'] = ((isset($item['prop_fieldset'])) && (!empty($item['prop_fieldset']))) ? $item['prop_fieldset'] : $this->__('User Information');
            $items[$k] = (array)$item;
            $fieldsets[$item['prop_fieldset']] = $item['prop_fieldset'];
        }
		
        // Create output object
        // Appending the module configuration to template
        return $this->view->setCaching(false)
                ->add_core_data()
                ->assign('dudfields', $items)
                ->assign('fieldsets', $fieldsets)
                ->fetch('Admin/modifyconfig.tpl');
    }
    
    /**
     * Function that updates the module configuration.
     * 
     * Parameters passed in via POST:
     * ------------------------------
     * boolean viewregdate               If true the user's registration date is displayed; false to supress the registration date.
     * numeric memberslistitemsperpage   The number of members to show per page on the member list.
     * numeric onlinemembersitemsperpage The number of members to show per page on the members online list.
     * numeric recentmembersitemsperpage The number of members to show per page on the recent registered members list.
     * booleam filterunverified          If true, users who have not completed the registration process are not listed; if false, they are listed.
     * array   dudregshow                An array of dud item ids indicating which items to include in the registration form; an empty array to include none.
     * 
     * @return boolean True if update successful, false otherwise.
     * 
     * @see    Profile_admin_modifyconfig()
     */
    public function updateconfigAction()
    {
        $this->checkCsrfToken();
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        // Update module variables.
        $viewregdate = (bool) $this->request->request->get('viewregdate', 0);
        $this->setVar('viewregdate', $viewregdate);
        $memberslistitemsperpage = (int) $this->request->request->get('memberslistitemsperpage', 20);
        $this->setVar('memberslistitemsperpage', $memberslistitemsperpage);
        $onlinemembersitemsperpage = (int) $this->request->request->get('onlinemembersitemsperpage', 20);
        $this->setVar('onlinemembersitemsperpage', $onlinemembersitemsperpage);
        $recentmembersitemsperpage = (int) $this->request->request->get('recentmembersitemsperpage', 10);
        $this->setVar('recentmembersitemsperpage', $recentmembersitemsperpage);
        $filterunverified = (bool) $this->request->request->get('filterunverified', false);
        $this->setVar('filterunverified', $filterunverified);
        $dudregshow = $this->request->request->get('dudregshow', array());
        $this->setVar('dudregshow', $dudregshow);
        // the module configuration has been updated successfuly
        $this->registerStatus($this->__('Done! Saved your settings changes.'));
        // This function generated no output
        return System::redirect(ModUtil::url($this->name, 'admin', 'view'));
    }

}