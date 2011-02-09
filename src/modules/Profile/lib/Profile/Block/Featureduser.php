<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: featureduser.php 90 2010-01-25 08:31:41Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */


class Profile_Block_Featureduser extends Zikula_Controller_Block
{

    /**
     * initialise block
     *
     * @author       The Zikula Development Team
     */
    public function init()
    {
        // Security
        SecurityUtil::registerPermissionSchema('Profile:FeaturedUserblock:', 'Block ID::');
    }

    /**
     * get information on block
     *
     * @author       The Zikula Development Team
     * @return       array       The block information
     */
    public function info()
    {
        return array('module'          => 'Profile',
                'text_type'       => $this->__('Featured user'),
                'text_type_long'  => $this->__('Show featured user'),
                'allow_multiple'  => true,
                'form_content'    => false,
                'form_refresh'    => false,
                'show_preview'    => true,
                'admin_tableless' => true);
    }

    /**
     * display block
     *
     * @author       The Zikula Development Team
     * @param        array       $blockinfo     a blockinfo structure
     * @return       output      the rendered bock
     */
    public function display($blockinfo)
    {
        // Check if the Profile module is available.
        if (!ModUtil::available('Profile')) {
            return false;
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile:FeaturedUserblock:', "$blockinfo[bid]::", ACCESS_READ)) {
            return false;
        }

        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // If there's no user to show, nothing to do
        if (!isset($vars['username']) || empty($vars['username'])) {
            return false;
        }

        // Defaults
        if (!isset($vars['fieldstoshow']) || !is_array($vars['fieldstoshow']) || empty($vars['fieldstoshow'])) {
            $vars['fieldstoshow'] = array();
        }

        if (!isset($vars['showregdate']) || empty($vars['showregdate'])) {
            $vars['showregdate'] = '';
        }

        $userinfo = UserUtil::getVars(UserUtil::getIdFromName($vars['username']));

        // Check if the user is watching its own profile or if he is admin
        $currentuser = UserUtil::getVar('uid');
        $ismember    = ($currentuser >= 2);
        $sameuser    = ($currentuser == $userinfo['uid']);

        $isadmin     = false;
        if (SecurityUtil::checkPermission('Profile::', '::', ACCESS_ADMIN)) {
            $isadmin = true;
        }

        // get all active profile fields
        $activeduds = ModUtil::apiFunc('Profile', 'user', 'getallactive', array('index' => 'prop_label'));

        foreach ($activeduds as $dudlabel => $activedud)
        {
            // check if the attribute is set to be shown in the block
            if (!in_array($activedud['prop_attribute_name'], $vars['fieldstoshow'])) {
                continue;
            }

            // discard empty fields
            if (empty($userinfo['__ATTRIBUTES__'][$activedud['prop_attribute_name']])) {
                continue;
            }

            // check the access to this field
            if ($activedud['prop_viewby'] != 0) {
                // not to everyone, checks members only or higher
                if (!($activedud['prop_viewby'] == 1 && $ismember)) {
                    // lastly check for the same user or admin
                    if (!($activedud['prop_viewby'] == 2 && ($sameuser || $isadmin))) {
                        continue;
                    }
                }
            }

            // add it to the viewable properties
            $dudarray[$dudlabel] = $userinfo['__ATTRIBUTES__'][$activedud['prop_attribute_name']];
        }
        unset($activeduds);

        // build the output
        $this->view->setCache_Id('featured'.$vars['username']);

        $this->view->assign('userinfo',    $userinfo);
        $this->view->assign('showregdate', $vars['showregdate']);
        $this->view->assign('dudarray',    $dudarray);

        $blockinfo['content'] = $this->view->fetch('profile_block_featureduser.tpl');

        return BlockUtil::themeBlock($blockinfo);
    }

    /**
     * modify block settings
     *
     * @author       The Zikula Development Team
     * @param        array       $blockinfo     a blockinfo structure
     * @return       output      the bock form
     */
    public function modify($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // Defaults
        if (!isset($vars['username']) || empty($vars['username'])) {
            $vars['username'] = '';
        }

        if (!isset($vars['fieldstoshow']) || !is_array($vars['fieldstoshow']) || empty($vars['fieldstoshow'])) {
            $vars['fieldstoshow'] = array();
        }

        if (!isset($vars['showregdate']) || empty($vars['showregdate'])) {
            $vars['showregdate'] = '';
        }

        // get all active profile fields
        $activeduds = ModUtil::apiFunc('Profile', 'user', 'getallactive');

        foreach ($activeduds as $attr => $activedud) {
            $dudarray[$attr] = $this->__($activedud['prop_label'], $dom);
        }

        // Create output object
        $this->view->setCaching(false);

        // assign the approriate values
        $this->view->assign('username',     $vars['username']);
        $this->view->assign('showregdate',  $vars['showregdate']);
        $this->view->assign('dudarray',     $dudarray);
        $this->view->assign('fieldstoshow', array_flip($vars['fieldstoshow']));

        // Return the output that has been generated by this function
        return $this->view->fetch('profile_block_featureduser_modify.tpl');
    }

    /**
     * update block settings
     *
     * @author       The Zikula Development Team
     * @param        array       $blockinfo     a blockinfo structure
     * @return       $blockinfo  the modified blockinfo structure
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // alter the corresponding variables
        $vars['username']     = FormUtil::getPassedValue('username', null, 'POST');
        $vars['fieldstoshow'] = FormUtil::getPassedValue('fieldstoshow', null, 'POST');
        $vars['showregdate']  = (bool)FormUtil::getPassedValue('showregdate', null, 'POST');

        if (!isset($vars['fieldstoshow']) || !is_array($vars['fieldstoshow']) || empty($vars['fieldstoshow'])) {
            $vars['fieldstoshow'] = array();
        }

        // validate the passed duds
        if (!empty($vars['fieldstoshow'])) {
            $activeduds = ModUtil::apiFunc('Profile', 'user', 'getallactive');
            $activeduds = array_keys($activeduds);

            foreach ($vars['fieldstoshow'] as $k => $v) {
                if (!in_array($v, $activeduds)) {
                    unset($vars['fieldstoshow'][$k]);
                }
            }
        }

        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);

        // clear the block cache
        $this->view->clear_cache('profile_block_featureduser.tpl');

        return $blockinfo;
    }
}