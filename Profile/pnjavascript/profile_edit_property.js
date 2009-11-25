/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.com
 * @version $Id: profile_edit_property.js 367 2009-11-23 22:16:28Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */

Event.observe(window, 'load', profile_modifyconfig_init, false);

function profile_modifyconfig_init()
{
    Event.observe('profile_dtype', 'change', profile_dtype_onchange, false);

    profile_dtype_onchange();
}

function profile_dtype_onchange()
{
    if ($('profile_dtype').value == '1') {
        $('profile_length_container').show();
    } else {
    	$('profile_length_container').hide();
    }
}
