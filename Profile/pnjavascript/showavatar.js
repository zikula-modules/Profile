/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.com
 * @version $Id: showavatar.js 364 2009-11-23 08:41:31Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */

function showavatar()
{
    if ($('prop_avatar') && $('youravatardisplay')) {
        $('youravatardisplay').src = document.location.pnbaseURL + $('youravatarpath').innerHTML + '/' + $('prop_avatar').options[$('prop_avatar').selectedIndex].value;
    }
}
