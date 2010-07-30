<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadmin.php 108 2010-02-08 06:39:56Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @author Mark West
 */

class Profile_Controller_Admin extends Zikula_Controller
{
    /**
     * The main administration function
     *
     * @return string HTML string
     */
    public function main()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        // Return the output
        return $this->view->fetch('profile_admin_main.htm');;
    }

    /**
     * The Profile help page
     *
     * @return string HTML string
     */
    public function help()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        // Return the output
        return $this->view->fetch('profile_admin_help.htm');;
    }

    /**
     * View all items held by this module
     * @author Mark West
     * @return string HTML string
     */
    public function view()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        // Get parameters from whatever input we need.
        $startnum = (int)FormUtil::getPassedValue('startnum', null, 'GET');
        $numitems = 20;

        // The user API function is called.
        $items = ModUtil::apiFunc('Profile', 'user', 'getall',
                array('startnum' => $startnum,
                'numitems' => $numitems));

        $count  = ModUtil::apiFunc('Profile', 'user', 'countitems');
        $authid = SecurityUtil::generateAuthKey();

        $x = 1;
        $duditems = array();
        foreach ($items as $item)
        {
            // display the proper icom and link to enable or disable the field
            switch (true)
            {
                // 0 <= DUD types can't be disabled
                case ($item['prop_dtype'] <= 0):
                    $statusval = 1;
                    $status = array('url' => '',
                            'image' => 'greenled.gif',  'title' => $this->__('Required'));
                    break;

                case ($item['prop_weight'] <> 0):
                    $statusval = 1;
                    $status = array('url'   => ModUtil::url('Profile', 'admin', 'deactivate',
                            array('dudid'    => $item['prop_id'],
                            'weight'   => $item['prop_weight'],
                            'authid'   => $authid)),
                            'image' => 'greenled.gif',
                            'title' => $this->__('Deactivate'));
                    break;

                default:
                    $statusval = 0;
                    $status = array('url'   => ModUtil::url('Profile', 'admin', 'activate',
                            array('dudid'    => $item['prop_id'],
                            'authid'   => $authid)),
                            'image' => 'redled.gif',
                            'title' => $this->__('Activate'));
            }

            // analizes the DUD type
            switch ($item['prop_dtype'])
            {
                case '-2': // non-editable field
                    $data_type_text = $this->__('Not editable field');
                    break;

                case '-1': // Third party (non-editable)
                    $data_type_text = $this->__('Third-party (not editable)');
                    break;

                case '0': // Third party (mandatory)
                    $data_type_text = $this->__('Third-party') . ($item['prop_required'] ? ', '.$this->__('Required') : '');
                    break;

                default:
                case '1': // Normal property
                    $data_type_text = $this->__('Normal') . ($item['prop_required'] ? ', '.$this->__('Required') : '');
                    break;

                case '2': // Third party (normal field)
                    $data_type_text = $this->__('Third-party') . ($item['prop_required'] ? ', '.$this->__('Required') : '');
                    break;
            }

            // Options for the item.
            $options = array();
            if (SecurityUtil::checkPermission('Profile::item', "$item[prop_label]::$item[prop_id]", ACCESS_EDIT))
            {
                $options[] = array('url' => ModUtil::url('Profile', 'admin', 'modify', array('dudid' => $item['prop_id'])),
                        'image' => 'xedit.gif',
                        'class' => '',
                        'title' => $this->__('Edit'));

                if ($item['prop_weight'] > 1) {
                    $options[] = array('url' => ModUtil::url('Profile', 'admin', 'decrease_weight', array('dudid' => $item['prop_id'])),
                            'image' => '2uparrow.gif',
                            'class' => 'profile_up',
                            'title' => $this->__('Up'));
                }

                if ($x < $count) {
                    $options[] = array('url' => ModUtil::url('Profile', 'admin', 'increase_weight', array('dudid' => $item['prop_id'])),
                            'image' => '2downarrow.gif',
                            'class' => 'profile_down',
                            'title' => $this->__('Down'));
                }

                if (SecurityUtil::checkPermission('Profile::item', "$item[prop_label]::$item[prop_id]", ACCESS_DELETE) && $item['prop_dtype'] > 0) {
                    $options[] = array('url' => ModUtil::url('Profile', 'admin', 'delete', array('dudid' => $item['prop_id'])),
                            'image' => '14_layer_deletelayer.gif',
                            'class' => '',
                            'title' => $this->__('Delete'));
                }
            }

            $item['status']    = $status;
            $item['statusval'] = $statusval;
            $item['options']   = $options;
            $item['dtype']     = $data_type_text;
            $duditems[] = $item;
            $x++;
        }

        $this->view->setCaching(false)
                       ->assign('startnum', $startnum)
                       ->assign('duditems', $duditems);

        // assign the values for the smarty plugin to produce a pager in case of there
        // being many items to display.
        $this->view->assign('pager', array('numitems'     => $count,
                'itemsperpage' => $numitems));

        // Return the output that has been generated by this function
        return $this->view->fetch('profile_admin_view.htm');
    }

    /**
     * Add new dynamic user data item
     *
     * @author Mark West
     * @return string HTML string
     */
    public function newdud()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }

        $this->view->assign('displaytypes',     array(0 => DataUtil::formatForDisplay($this->__('Text box')),
                1 => DataUtil::formatForDisplay($this->__('Text area')),
                2 => DataUtil::formatForDisplay($this->__('Checkbox')),
                3 => DataUtil::formatForDisplay($this->__('Radio button')),
                4 => DataUtil::formatForDisplay($this->__('Dropdown list')),
                5 => DataUtil::formatForDisplay($this->__('Date')),
                7 => DataUtil::formatForDisplay($this->__('Multiple checkbox set'))));

        $this->view->assign('requiredoptions',  array(0 => DataUtil::formatForDisplay($this->__('No')),
                1 => DataUtil::formatForDisplay($this->__('Yes'))));

        $this->view->assign('viewbyoptions',    array(0 => DataUtil::formatForDisplay($this->__('Everyone')),
                1 => DataUtil::formatForDisplay($this->__('Registered users only')),
                2 => DataUtil::formatForDisplay($this->__('Admins and account owner only')),
                3 => DataUtil::formatForDisplay($this->__('Admins only'))));

        // Return the output that has been generated by this function
        return $this->view->fetch('profile_admin_new.htm');
    }

    /**
     * Function that executes the creation
     *
     * @author Mark West
     * @see Profile_admin_new()
     * @param string 'label' the name of the item to be created
     * @param string 'dtype' the data type of the item to be created
     * @return bool true if item created, false otherwise
     */
    public function create($args)
    {
        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Profile', 'admin', 'view'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }

        // Get parameters from whatever input we need.
        $label       = isset($args['label'])         ? $args['label']         : FormUtil::getPassedValue('label', null, 'POST');
        $attrname    = isset($args['attributename']) ? $args['attributename'] : FormUtil::getPassedValue('attributename', null, 'POST');
        $required    = isset($args['required'])      ? $args['required']      : FormUtil::getPassedValue('required', null, 'POST');
        $viewby      = isset($args['viewby'])        ? $args['viewby']        : FormUtil::getPassedValue('viewby', null, 'POST');
        $displaytype = isset($args['displaytype'])   ? $args['displaytype']   : FormUtil::getPassedValue('displaytype', null, 'POST');
        $listoptions = isset($args['listoptions'])   ? $args['listoptions']   : FormUtil::getPassedValue('listoptions', null, 'POST');
        $note        = isset($args['note'])          ? $args['note']          : FormUtil::getPassedValue('note', null, 'POST');

        $returnurl = ModUtil::url('Profile', 'admin', 'view');

        // Validates and check if empty or already existing...
        if (empty($label)) {
            return LogUtil::registerError($this->__("Error! The personal info item must have a label. An example of a recommended label is: '_MYDUDLABEL'."), null, $returnurl);
        }

        if (empty($attrname)) {
            return LogUtil::registerError($this->__("Error! The personal info item must have an attribute name. An example of an acceptable name is: 'mydudfield'."), null, $returnurl);
        }

        if (ModUtil::apiFunc('Profile', 'user', 'get', array('proplabel' => $label))) {
            return LogUtil::registerError($this->__('Error! There is already an personal info item label with this naming.'), null, $returnurl);
        }

        if (ModUtil::apiFunc('Profile', 'user', 'get', array('propattribute' => $attrname))) {
            return LogUtil::registerError($this->__('Error! There is already an attribute name with this naming.'), null, $returnurl);
        }

        $permalinkssep = System::getVar('shorturlsseparator');
        $filteredlabel = str_replace($permalinkssep, '', DataUtil::formatPermalink($label));
        if ($label != $filteredlabel) {
            LogUtil::registerStatus($this->__('Warning! The personal info item label has been accepted, but was filtered and altered to ensure it contains no special characters or spaces in its naming.'), null, $returnurl);
        }

        // The API function is called.
        $dudid = ModUtil::apiFunc('Profile', 'admin', 'create',
                array('label'          => $filteredlabel,
                'attribute_name' => $attrname,
                'required'       => $required,
                'viewby'         => $viewby,
                'dtype'          => 1,
                'displaytype'    => $displaytype,
                'listoptions'    => $listoptions,
                'note'           => $note));

        // The return value of the function is checked here
        if ($dudid != false) {
            // Success
            LogUtil::registerStatus($this->__('Done! Created new personal info item.'));
        }

        // This function generated no output
        return System::redirect($returnurl);
    }

    /**
     * Modify a dynamic user data item
     * This is a standard function that is called whenever an administrator
     * wishes to modify a current module item
     * @author Mark West
     * @param int 'dudid' the id of the item to be modified
     * @param int 'objectid' generic object id maps to dudid if present
     * @return string HTML string
     */
    public function modify($args)
    {
        // Get parameters from whatever input we need.
        $dudid    = isset($args['dudid'])    ? (int)$args['dudid']    : (int)FormUtil::getPassedValue('dudid',    null, 'GET');
        $objectid = isset($args['objectid']) ? (int)$args['objectid'] : (int)FormUtil::getPassedValue('objectid', null, 'GET');

        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $dudid = $objectid;
        }

        // The user API function is called.
        $item = ModUtil::apiFunc('Profile', 'user', 'get', array('propid' => $dudid));

        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "$item[prop_label]::$dudid", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        // backward check to remove any 1.4- forbidden char in listoptions
        $item['prop_listoptions'] = str_replace(Chr(10), '', str_replace(Chr(13), '', $item['prop_listoptions']));

        // Create output object
        $render = & Zikula_View::getInstance('Profile', false);

        // Add a hidden variable for the item id.
        $this->view->assign('dudid', $dudid);

        $this->view->assign('displaytypes',     array(0 => DataUtil::formatForDisplay($this->__('Text box')),
                1 => DataUtil::formatForDisplay($this->__('Text area')),
                2 => DataUtil::formatForDisplay($this->__('Checkbox')),
                3 => DataUtil::formatForDisplay($this->__('Radio button')),
                4 => DataUtil::formatForDisplay($this->__('Dropdown list')),
                5 => DataUtil::formatForDisplay($this->__('Date')),
                7 => DataUtil::formatForDisplay($this->__('Multiple checkbox set'))));

        $this->view->assign('requiredoptions',  array(0 => DataUtil::formatForDisplay($this->__('No')),
                1 => DataUtil::formatForDisplay($this->__('Yes'))));

        $this->view->assign('viewbyoptions',    array(0 => DataUtil::formatForDisplay($this->__('Everyone')),
                1 => DataUtil::formatForDisplay($this->__('Registered users only')),
                2 => DataUtil::formatForDisplay($this->__('Admins and account owner only')),
                3 => DataUtil::formatForDisplay($this->__('Admins only'))));

        $item['prop_listoptions'] = str_replace("\n", '', $item['prop_listoptions']);

        $this->view->assign('item', $item);

        // Return the output that has been generated by this function
        return $this->view->fetch('profile_admin_modify.htm');
    }

    /**
     * Function that executes the update
     *
     * @author Mark West
     * @see ProfileModify()
     * @param int 'dudid' the id of the item to be updated
     * @param int 'objectid' generic object id maps to dudid if present
     * @param string 'label' the name of the item to be updated
     * @param string 'dtype' the data type of the item
     * @param int 'length' the lenght of item if dtype is string
     * @return bool true if update successful, false otherwise
     */
    public function update($args)
    {
        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Profile', 'admin', 'view'));
        }

        // Get parameters from whatever input we need.
        $dudid       = (int)FormUtil::getPassedValue('dudid',    (isset($args['dudid']) ? $args['dudid'] : null), 'POST');
        $objectid    = (int)FormUtil::getPassedValue('objectid', (isset($args['objectid']) ? $args['objectid'] : null), 'POST');
        $label       = FormUtil::getPassedValue('label',         (isset($args['label']) ? $args['label'] : null), 'POST');
        $required    = FormUtil::getPassedValue('required',      (isset($args['required']) ? $args['required'] : null), 'POST');
        $viewby      = FormUtil::getPassedValue('viewby',        (isset($args['viewby']) ? $args['viewby'] : null), 'POST');
        //$dtype       = FormUtil::getPassedValue('dtype',         (isset($args['dtype']) ? $args['dtype'] : null), 'POST');
        $displaytype = FormUtil::getPassedValue('displaytype',   (isset($args['displaytype']) ? $args['displaytype'] : null), 'POST');
        $listoptions = FormUtil::getPassedValue('listoptions',   (isset($args['listoptions']) ? $args['listoptions'] : null), 'POST');
        $note        = FormUtil::getPassedValue('note',          (isset($args['note']) ? $args['note'] : null), 'POST');

        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $dudid = $objectid;
        }

        // The return value of the function is checked here
        if (ModUtil::apiFunc('Profile', 'admin', 'update',
        array('dudid'       => $dudid,
        'required'    => $required,
        'viewby'      => $viewby,
        'label'       => $label,
        'displaytype' => $displaytype,
        'listoptions' => str_replace("\n", "", $listoptions),
        'note'        => $note))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }

    /**
     * delete item
     *
     * @author Mark West
     * @param int 'dudid' the id of the item to be deleted
     * @param int 'objectid' generic object id maps to dudid if present
     * @param bool 'confirmation' confirmation that this item can be deleted
     * @return mixed HTML string if no confirmation, true if delete successful, false otherwise
     */
    public function delete($args)
    {
        // Get parameters from whatever input we need.
        $dudid        =  (int)FormUtil::getPassedValue('dudid',        (isset($args['dudid']) ? $args['dudid'] : null), 'GETPOST');
        $objectid     =  (int)FormUtil::getPassedValue('objectid',     (isset($args['objectid']) ? $args['objectid'] : null), 'GETPOST');
        $confirmation = (bool)FormUtil::getPassedValue('confirmation', (isset($args['confirmation']) ? $args['confirmation'] : null), 'GETPOST');

        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $dudid = $objectid;
        }

        // The user API function is called.
        $item = ModUtil::apiFunc('Profile', 'user', 'get', array('propid' => $dudid));

        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "$item[prop_label]::$dudid", ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }

        // Check for confirmation.
        if (empty($confirmation)) {
            // No confirmation yet - display a suitable form to obtain confirmation
            // of this action from the user

            // Add hidden item id to form
            $this->view->assign('dudid', $dudid);

            // Return the output that has been generated by this function
            return $this->view->fetch('profile_admin_delete.htm');
        }

        // If we get here it means that the user has confirmed the action

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Profile', 'admin', 'view'));
        }

        // The API function is called.
        if (ModUtil::apiFunc('Profile', 'admin', 'delete', array('dudid' => $dudid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Deleted the personal info item.'));
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }

    /**
     * Increase weight
     *
     * @author Mark West
     * @param  int 'dudid' the id of the item to be updated
     * @return bool true if update successful, false otherwise
     */
    public function increase_weight($args)
    {
        $dudid = (int)FormUtil::getPassedValue('dudid', null, 'GET');
        $item = ModUtil::apiFunc('Profile', 'user', 'get', array('propid' => $dudid));

        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "$item[prop_label]::$item[prop_id]", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        $res = DBUtil::incrementObjectFieldByID('user_property', 'prop_weight', $dudid, 'prop_id');

        // The return value of the function is checked here
        if ($res) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }

        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }

    /**
     * Decrease weight
     *
     * @author Mark West
     * @param  int 'dudid' the id of the item to be updated
     * @return bool true if update successful, false otherwise
     */
    public function decrease_weight($var)
    {
        $dudid = (int)FormUtil::getPassedValue('dudid', null, 'GET');
        $item = ModUtil::apiFunc('Profile', 'user', 'get', array('propid' => $dudid));

        if ($item == false) {
            return LogUtil::registerError($this->__('Error! No such personal info item found.'), 404);
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile::item', "$item[prop_label]::$item[prop_id]", ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        if ($item['prop_weight'] <= 1) {
            return LogUtil::registerError($this->__('Error! You cannot decrease the weight of this account property.'), 404);
        }

        $res = DBUtil::incrementObjectFieldByID('user_property', 'prop_weight', $dudid, 'prop_id', -1);

        // The return value of the function is checked here
        if ($res) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }

        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }

    /**
     * Process item activation request
     * @author Mark West
     * @param int 'dudid' id of item activate
     * @return bool true if activation successful, false otherwise
     * @todo remove passing of weight parameter; can be got from API
     */
    public function activate($args)
    {
        // Get parameters from whatever input we need.
        $dudid  = (int)FormUtil::getPassedValue('dudid', (isset($args['dudid']) ? $args['dudid'] : null), 'GET');

        // The API function is called.
        if (ModUtil::apiFunc('Profile', 'admin', 'activate', array('dudid' => $dudid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }

    /**
     * Process item deactivation request
     * @author Mark West
     * @param int 'dudid' id of item deactivate
     * @param int 'weight' current weight of item
     * @return bool true if deactivation successful, false otherwise
     * @todo remove passing of weight parameter; can be got from API
     */
    public function deactivate($args)
    {
        // Get parameters from whatever input we need.
        $dudid  = (int)FormUtil::getPassedValue('dudid',  (isset($args['dudid']) ? $args['dudid'] : null), 'GET');

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Profile', 'admin', 'view'));
        }

        // The API function is called.
        if (ModUtil::apiFunc('Profile', 'admin', 'deactivate', array('dudid' => $dudid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved your changes.'));
        }

        // Let any other modules know that the modules configuration has been updated
        $this->callHooks('module','updateconfig','Profile', array('module' => 'Profile'));

        // This function generated no output
        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }

    /**
     * This is a standard function to modify the configuration parameters of the
     * module
     * @author Mark West
     * @return string HTML string
     */
    public function modifyconfig()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        $items = ModUtil::apiFunc('Profile', 'user', 'getallactive', array('get' => 'editable', 'index' => 'prop_id'));

        foreach ($items as $k => $item) {
            if ($item['prop_required']) {
                unset($items[$k]);
                continue;
            }
            $items[$k] = $item['prop_label'];
        }

        // Create output object
        // Appending the module configuration to template
        $this->view->setCaching(false)
                        ->add_core_data()
                        ->assign('dudfields', $items);

        // Return the output that has been generated by this function
        return $this->view->fetch('profile_admin_modifyconfig.htm');
    }

    /**
     * Function that updates the module configuration
     *
     * @author Mark West
     * @see Profile_admin_modifyconfig()
     * @return bool true if update successful, false otherwise
     */
    public function updateconfig()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Profile', 'admin', 'view'));
        }

        // Update module variables.
        $viewregdate = (bool)FormUtil::getPassedValue('viewregdate', 0, 'POST');
        $this->setVar('viewregdate', $viewregdate);


        $memberslistitemsperpage = (int)FormUtil::getPassedValue('memberslistitemsperpage', 20, 'POST');
        $this->setVar('memberslistitemsperpage', $memberslistitemsperpage);

        $onlinemembersitemsperpage = (int)FormUtil::getPassedValue('onlinemembersitemsperpage', 20, 'POST');
        $this->setVar('onlinemembersitemsperpage', $onlinemembersitemsperpage);

        $recentmembersitemsperpage = (int)FormUtil::getPassedValue('recentmembersitemsperpage', 10, 'POST');
        $this->setVar('recentmembersitemsperpage', $recentmembersitemsperpage);

        $filterunverified = (bool)FormUtil::getPassedValue('filterunverified', false, 'POST');
        $this->setVar('filterunverified', $filterunverified);


        $dudtextdisplaytags = (bool)FormUtil::getPassedValue('dudtextdisplaytags', 0, 'POST');
        $this->setVar('dudtextdisplaytags', $dudtextdisplaytags);


        $dudregshow = FormUtil::getPassedValue('dudregshow', array(), 'POST');
        $this->setVar('dudregshow', $dudregshow);

        // Let any other modules know that the modules configuration has been updated
        $this->callHooks('module', 'updateconfig', 'Profile', array('module' => 'Profile'));

        // the module configuration has been updated successfuly
        $this->registerStatus($this->__('Done! Saved your settings changes.'));

        // This function generated no output
        return System::redirect(ModUtil::url('Profile', 'admin', 'view'));
    }
}