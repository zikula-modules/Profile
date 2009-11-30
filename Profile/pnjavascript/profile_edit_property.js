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

// required value, actived flag
var backup_required = [null, false];

function profile_modifyconfig_init()
{
    Event.observe('profile_displaytype', 'change', profile_displaytype_onchange, false);

    profile_displaytype_onchange();

    // initialized the backup of the required selector
    backup_required[0] = $F('profile_required');
    if ($('profile_displaytype').value == '2') {
    	$('profile_required').value = "0";
    	$('profile_required').disable();
    	backup_required[1] = true;
    }
}

function profile_displaytype_onchange()
{
    // recover the backup value if enabled
    if (backup_required[1] == true) {
    	backup_required[1] = false;
    	$('profile_required').value = backup_required[0];
    	$('profile_required').enable();
    }

    var state = 0;

    // checkbox
    if ($('profile_displaytype').value == '2') {
        backup_required[0] = $F('profile_required');
        backup_required[1] = true;
        $('profile_required').value = "0";
        $('profile_required').disable();
        state += 2;
    }
    // radio
    if ($('profile_displaytype').value == '3') {
        state += 4;
    }
    // dropdown
    if ($('profile_displaytype').value == '4') {
        state += 8;
    }
    // multibox
    if ($('profile_displaytype').value == '7') {
        state += 16;
    }

    $('profile_help_type2').hide();
    $('profile_help_type3').hide();
    $('profile_help_type4').hide();
    $('profile_help_type7').hide();
    $('profile_warn_ids').hide();
    // needs to show the list_content textarea
    if (state > 0) {
    	$('profile_content_wrapper').show();
    	// check which type help should be shown
    	if (state&2) {
    		// checkbox
    		$('profile_help_type2').show();
    	} else if (state&4) {
    		// radio
    		$('profile_help_type3').show();
    		$('profile_warn_ids').show();
    	} else if (state&8) {
    		// dropdown
    		$('profile_help_type4').show();
    		$('profile_warn_ids').show();
    	} else if (state&16) {
    		// multibox
    		$('profile_help_type7').show();
    	}
    } else {
    	$('profile_content_wrapper').hide();
    }
}
