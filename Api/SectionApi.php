<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Api;

use ModUtil;

/**
 * Profile section api.
 */
class SectionApi extends \Zikula_AbstractApi
{
    /**
     * Section to show the latest articles of a user.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * numeric uid      The user account id of the user for whom to return comments.
     * numeric numitems Number of comments to show.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return array An array of articles.
     */
    public function news($args)
    {
        // validates an the uid parameter
        if (!isset($args['uid']) || empty($args['uid'])) {
            return false;
        }
        // assures the number of items to retrieve
        if (!isset($args['numitems']) || empty($args['numitems'])) {
            $args['numitems'] = 5;
        }

        // show only published articles
        $args['status'] = 0;
        // exclude future articles
        $args['filterbydate'] = true;

        // removes unallowed parameters
        if (isset($args['from'])) {
            unset($args['from']);
        }
        if (isset($args['to'])) {
            unset($args['to']);
        }
        if (isset($args['query'])) {
            unset($args['query']);
        }

        return ModUtil::apiFunc('News', 'user', 'getall', $args);
    }

    /**
     * Section to show the latest comments of a user.
     *
     * Parameters passed in the $args array:
     * -------------------------------------
     * numeric uid      The user account id of the user for whom to return comments.
     * numeric numitems Number of comments to show.
     * 
     * @param array $args All parameters passed to this function.
     * 
     * @return array An array of comments.
     */
    public function ezcomments($args)
    {
        // validates an the uid parameter
        if (!isset($args['uid']) || empty($args['uid'])) {
            return false;
        }
        // assures the number of items to retrieve
        if (!isset($args['numitems']) || empty($args['numitems'])) {
            $args['numitems'] = 5;
        }

        // show only approved comments
        $args['status'] = 0;

        return ModUtil::apiFunc('EZComments', 'user', 'getall', $args);
    }
}
