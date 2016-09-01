<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Block;

use SecurityUtil;
use ModUtil;
use BlockUtil;
use UserUtil;

/**
 * "Featured User" block.
 */
class FeatureduserBlock extends \Zikula_Controller_AbstractBlock
{
    /**
     * Initialise the block.
     *
     * @return void
     */
    public function init()
    {
        // Security
        SecurityUtil::registerPermissionSchema($this->name.':FeaturedUserblock:', 'Block ID::');
    }

    /**
     * Return block information.
     *
     * @return array The block information.
     */
    public function info()
    {
        return [
            'module' => $this->name,
            'text_type' => $this->__('Featured user'),
            'text_type_long' => $this->__('Show featured user'),
            'allow_multiple' => true,
            'form_content' => false,
            'form_refresh' => false,
            'show_preview' => true,
            'admin_tableless' => true
        ];
    }

    /**
     * Display the block.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return string The rendered block.
     */
    public function display($blockinfo)
    {
        // Check if the Profile module is available.
        if (!ModUtil::available($this->name)) {
            return false;
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.':FeaturedUserblock:', "{$blockinfo['bid']}::", ACCESS_READ)) {
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
            $vars['fieldstoshow'] = [];
        }
        if (!isset($vars['showregdate']) || empty($vars['showregdate'])) {
            $vars['showregdate'] = '';
        }
        $userinfo = UserUtil::getVars(UserUtil::getIdFromName($vars['username']));
        // Check if the user is watching its own profile or if he is admin
        $currentuser = UserUtil::getVar('uid');
        $ismember = $currentuser >= 2;
        $sameuser = $currentuser == $userinfo['uid'];
        $isadmin = false;
        if (SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN)) {
            $isadmin = true;
        }
        // get all active profile fields
        $dudarray = [];
        $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive', ['index' => 'prop_label']);
        foreach ($activeduds as $dudlabel => $activedud) {
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
        $this->view->setCacheId('featured' . $vars['username']);
        $this->view->assign('userinfo', $userinfo)
                   ->assign('showregdate', $vars['showregdate'])
                   ->assign('dudarray', $dudarray);
        $blockinfo['content'] = $this->view->fetch('Block/featureduser.tpl');

        return BlockUtil::themeBlock($blockinfo);
    }

    /**
     * Modify block settings.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return string The rendered block form.
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
            $vars['fieldstoshow'] = [];
        }
        if (!isset($vars['showregdate']) || empty($vars['showregdate'])) {
            $vars['showregdate'] = '';
        }
        // get all active profile fields
        $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive');
        $dudarray = [];
        foreach ($activeduds as $attr => $activedud) {
            $dudarray[$attr] = $this->__($activedud['prop_label']);
        }
        // Create output object
        $this->view->setCaching(false);
        // assign the approriate values
        $this->view->assign('username', $vars['username'])
                   ->assign('showregdate', $vars['showregdate'])
                   ->assign('dudarray', $dudarray)
                   ->assign('fieldstoshow', array_flip($vars['fieldstoshow']));

        // Return the output that has been generated by this function
        return $this->view->fetch('Block/featureduser_modify.tpl');
    }

    /**
     * Update block settings.
     *
     * Parameters passed in via POST:
     * ------------------------------
     * string  username     The user name of the featured user.
     * array   fieldstoshow An array of dud item labels corresponding to the information to display in the block.
     * boolean showregdate  True to show the featured user's registration date, false to supress it.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return array The modified blockinfo structure.
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // alter the corresponding variables
        $vars['username'] = $this->request->request->get('username', null);
        $vars['fieldstoshow'] = $this->request->request->get('fieldstoshow', null);
        $vars['showregdate'] = (bool)$this->request->request->get('showregdate', null);
        if (!isset($vars['fieldstoshow']) || !is_array($vars['fieldstoshow']) || empty($vars['fieldstoshow'])) {
            $vars['fieldstoshow'] = [];
        }
        // validate the passed duds
        if (!empty($vars['fieldstoshow'])) {
            $activeduds = ModUtil::apiFunc($this->name, 'user', 'getallactive');
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
        $this->view->clear_cache('Block/featureduser.tpl');

        return $blockinfo;
    }
}
