<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnajax.php 366 2009-11-23 16:19:37Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
*/

/**
 * Section to show the latest comments of an user
 *
 * @author Mateo Tibaquira
 * @param  integer   numitems   number of comments to show
 * @return array of comments
 */
function Profile_sectionapi_ezcomments($args)
{
    // assures the number of items to retrieve
    if (!isset($args['numitems']) || empty($args['numitems'])) {
        $args['numitems'] = 5;
    }
    // only approved comments
    $args['status'] = 0;

    return pnModAPIFunc('EZComments', 'user', 'getall', $args);
}
