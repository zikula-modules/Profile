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

/**
 * "Last Seen" block.
 */
class LastseenBlock extends \Zikula_Controller_AbstractBlock
{
    /**
     * Initialise the block.
     *
     * @return void
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema($this->name.':LastSeenblock:', 'Block title::');
    }

    /**
     * Get information on the block.
     *
     * @return array The block information.
     */
    public function info()
    {
        return [
            'module' => $this->name,
            'text_type' => $this->__('Recent visitors'),
            'text_type_long' => $this->__('Show registered users having visited the site recently'),
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
        // Check if the Profile module is available or saving of login dates are disabled
        if (!ModUtil::available($this->name)) {
            return false;
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.':LastSeenblock:', "{$blockinfo['title']}::", ACCESS_READ)) {
            return false;
        }

        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        $this->view->setCaching(false);
        // get last x logged in user id's
        $users = ModUtil::apiFunc($this->name, 'memberslist', 'getall', ['sortby' => 'lastlogin', 'numitems' => $vars['amount'], 'sortorder' => 'DESC']);
        $this->view->assign('users', $users);
        $blockinfo['content'] = $this->view->fetch('Block/lastseen.tpl');

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
        if (empty($vars['amount'])) {
            $vars['amount'] = 5;
        }
        // Create output object
        $this->view->setCaching(false);
        // assign the approriate values
        $this->view->assign('amount', $vars['amount']);

        // Return the output that has been generated by this function
        return $this->view->fetch('Block/lastseen_modify.tpl');
    }

    /**
     * Update block settings.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return array The modified blockinfo structure.
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // alter the corresponding variable
        $vars['amount'] = (int)$this->request->request->get('amount', 0);
        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
        // clear the block cache
        $this->view->clear_cache('Block/lastseen.tpl');

        return $blockinfo;
    }
}
