// Copyright Zikula Foundation, licensed MIT.

(function($) {
    // required value, actived flag
    var backup_required = [null, false];

    function initDudEditing()
    {
        $('#profile_displaytype').change(onDisplayTypeChange);

        onDisplayTypeChange();

        // initialised the backup of the required selector
        backup_required[0] = $('#profile_required').val();
        if ($('#profile_displaytype').val() == '2' || $('#profile_displaytype').val() == '7') {
            $('#profile_required').val('0');
            $('#profile_required').prop('disabled', true);
            backup_required[1] = true;
        }
    }

    function onDisplayTypeChange()
    {
        // recover the backup value if enabled
        if (backup_required[1] == true) {
            backup_required[1] = false;
            $('#profile_required').val(backup_required[0]);
            $('#profile_required').prop('disabled', false);
        }

        var state = 0;
        var displayType = $('#profile_displaytype').val();

        // disable the required for checkbox and multiple checkbox
        if (displayType == '2' || displayType == '7') {
            backup_required[0] = $('#profile_required').val();
            backup_required[1] = true;
            $('#profile_required').val('0');
            $('#profile_required').prop('disabled', true);
        }

        // checkbox
        if (displayType == '2') {
            state += 1;
        }
        // radio
        if (displayType == '3') {
            state += 2;
        }
        // dropdown
        if (displayType == '4') {
            state += 4;
        }
        // date
        if (displayType == '5') {
            state += 8;
        }
        // multibox
        if (displayType == '7') {
            state += 32;
        }

        $('#profile_help_type2, #profile_help_type3').addClass('hidden');
        $('#profile_help_type4, #profile_help_type5').addClass('hidden');
        $('#profile_help_type7, #profile_warn_ids').addClass('hidden');

        // needs to show the list_content textarea
        if (state > 0) {
            $('#profile_content_wrapper').removeClass('hidden');
            // check which type help should be shown
            if (state & 1) {
                // checkbox
                $('#profile_help_type2').removeClass('hidden');
            } else if (state & 2) {
                // radio
                $('#profile_help_type3, #profile_warn_ids').removeClass('hidden');
            } else if (state & 4) {
                // dropdown
                $('#profile_help_type4, #profile_warn_ids').removeClass('hidden');
            } else if (state & 8) {
                // date
                $('#profile_help_type5').removeClass('hidden');
            } else if (state & 32) {
                // multibox
                $('#profile_help_type7').removeClass('hidden');
            }
        } else {
            $('#profile_content_wrapper').addClass('hidden');
        }
    }

    $(document).ready(initDudEditing);
})(jQuery)
