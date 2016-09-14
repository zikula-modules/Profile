// Copyright Zikula Foundation, licensed MIT.

(function($) {
    function profileInitAdminView()
    {
        Sortable.create('profilelist', { 
            only: 'z-sortable',
            constraint: false,
            onUpdate: onWeightChanged
        });

        $('a.profile_down, a.profile_up').addClass('hidden');
        $('#profilehint').removeClass('hidden');

        $('.z-sortable, .profilelist').each(function (index) { 
            var node = $(this);
            node.css({'cursor': 'move'}); 
            var profileId = node.attr('id').split('_')[1];

            $('#profile_' + profileId).addClass('sortable');

            // add an event to the status link
            var link = $('#profilestatus_' + profileId);
            link.attr({ href: '#', onclick: 'return false;' });
            link.click(function(event) {
                event.preventDefault();

                var prop_id = jQuery(this).attr('id').split('_')[1];
                var status = jQuery(this).attr('class').split('_')[1];
                onStatusChanged(prop_id, status);
            });

            // parse the status and change the inactive li items ids
            var status = link.attr('class').split('_')[1];
            if (status == '0') {
                $('#profile_' + profileId).attr('id', 'profile' + profileId);
            }
        });
    }

    /**
     * Stores the new sort order. This function gets called automatically
     * from the Sortable when a 'drop' action has been detected
     *
     * @return void
     */
    function onWeightChanged()
    {
        $.ajax({
            url: Routing.generate('zikulaprofilemodule_ajax_changeprofileweight'),
            data: {
                startnum: $('#startnum').val(),
                profilelist: Sortable.serialize('profilelist', { 'name': 'profilelist' })
            },
            success: function (result) {
                var odd = true;
                $('#profilelist').children().each(function (index) {
                    if (!$(this).hasClass('profilelistheader')) {
                        $(this).removeClass('z-odd z-even');
                        $(this).addClass(odd == true ? 'z-odd' : 'z-even');
                        odd = !odd;
                    }
                });
            }
        });
    }

    /**
     * Stores the new state when press the led.
     *
     * @param prop_id property ID
     * @param oldstatus current value to switch
     * @return void
     */
    function onStatusChanged(prop_id, oldstatus)
    {
        $.ajax({
            url: Routing.generate('zikulaprofilemodule_ajax_changeprofilestatus'),
            data: {
                oldstatus: oldstatus,
                dudid: prop_id
            },
            success: function (result) {
                var data = result.getData();

                // define item list and status link objects
                var link = $('#profilestatus_' + data.dudid);

                // switch the status in the classname
                var newClass = '';
                if (data.newstatus) {
                    // got active
                    newClass = link.attr('class').replace('0', '1')
                    // update the li item
                    $('#profile' + data.dudid).attr('id', 'profile_' + data.dudid);
                } else {
                    // got inactive
                    newClass = link.attr('class').replace('1', '0')
                    // update the li item
                    $('#profile_' + data.dudid).attr('id', 'profile' + data.dudid);
                }
                link.attr('class', newClass);

                // update the 
                link.children().each(function (index) {
                    var node = $(this);
                    var tagName = node.get(0).tagName.toLowerCase();

                    if (tagName == 'span') {
                        node.text(data.newstatus ? msgProfileStatusActive : msgProfileStatusInactive);
                        if (data.newstatus) {
                            node.attr({
                                title: msgProfileStatusClickTo + ' ' + msgProfileStatusDeactivate,
                                alt: msgProfileStatusDeactivate,
                                class: 'label label-success'
                            });
                        } else {
                            node.attr({
                                title: msgProfileStatusClickTo + ' ' + msgProfileStatusActivate,
                                alt: msgProfileStatusActivate,
                                class: 'label label-danger'
                            });
                        }
                    } else if (tagName == 'strong') {
                        node.text(data.newstatus ? msgProfileStatusDeactivate : msgProfileStatusActivate);
                    }
                });
            }
        });
    }
})(jQuery)
