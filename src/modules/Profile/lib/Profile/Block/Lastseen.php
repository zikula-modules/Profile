<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: lastseen.php 90 2010-01-25 08:31:41Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */

class Profile_Block_Lastseen extends Zikula_Controller_Block
{
    /**
     * initialise block
     *
     * @author       The Zikula Development Team
     */
    public function init()
    {
        // Security
        SecurityUtil::registerPermissionSchema('Profile:LastSeenblock:', 'Block title::');
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
                'text_type'       => $this->__('Recent visitors'),
                'text_type_long'  => $this->__('Show registered users having visited the site recently'),
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
        // Check if the Profile module is available or saving of login dates are disabled
        if (!ModUtil::available('Profile') || !ModUtil::getVar('Users', 'savelastlogindate')) {
            return false;
        }

        // Security check
        if (!SecurityUtil::checkPermission('Profile:LastSeenblock:', "$blockinfo[title]::", ACCESS_READ)) {
            return false;
        }

        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        $this->view->setCaching(false);

        // get last x logged in user id's
        $users = ModUtil::apiFunc('Profile', 'memberslist', 'getall',
                array('sortby'    => 'lastlogin',
                'numitems'  => $vars['amount'],
                'sortorder' => 'DESC'));

        $this->view->assign('users', $users);

        $blockinfo['content'] = $this->view->fetch('profile_block_lastseen.tpl');

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
        if (empty($vars['amount'])) {
            $vars['amount'] = 5;
        }

        // Create output object
        $this->view->setCaching(false);

        // assign the approriate values
        $this->view->assign('amount', $vars['amount'])
                       ->assign('savelastlogindate', ModUtil::getVar('Users', 'savelastlogindate'));

        // Return the output that has been generated by this function
        return $this->view->fetch('profile_block_lastseen_modify.tpl');
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

        // alter the corresponding variable
        $vars['amount'] = (int)FormUtil::getPassedValue('amount', null, 'REQUEST');

        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);

        // clear the block cache
        $this->view->clear_cache('profile_block_lastseen.tpl');

        return $blockinfo;
    }
}