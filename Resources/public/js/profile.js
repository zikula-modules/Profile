// Copyright Zikula Foundation, licensed MIT.

function profileinit()
{
    Sortable.create('profilelist', { 
        only: 'z-sortable',
        constraint: false,
        onUpdate: profileweightchanged
    });

    $$('a.profile_down').each(function(arrow){arrow.hide();});
    $$('a.profile_up').each(function(arrow){arrow.hide();});
    $('profilehint').show();

    $A(document.getElementsByClassName('z-sortable', 'profilelist')).each(function (node) { 
        node.setStyle({'cursor': 'move'}); 
        var thisprofileid = node.id.split('_')[1];
        Element.addClassName('profile_' + thisprofileid, 'sortable')

        $('profilestatus_'+thisprofileid).setAttribute('href', '#');
        $('profilestatus_'+thisprofileid).setAttribute('onclick', 'return false;');

        // add an event to the status link
        var link = $('profilestatus_'+thisprofileid);
        Event.observe(link, 'click',
            function() {
                var prop_id    = this.id.split('_')[1];
                var thisstatus = this.getAttribute('class').split('_')[1];
                profilestatuschanged(prop_id, thisstatus)
            }
        )

        // parse the status and change the inactive li items ids
        var thisstatus = link.getAttribute('class').split('_')[1];
        if (thisstatus == '0') {
            $('profile_'+thisprofileid).setAttribute('id', 'profile'+thisprofileid);
        }
    });
}

/**
 * Stores the new sort order. This function gets called automatically
 * from the Sortable when a 'drop' action has been detected
 *
 * @params none;
 * @return void;
 */
function profileweightchanged()
{
    var pars = {
        startnum: $F('startnum'),
        profilelist: Sortable.serialize('profilelist', { 'name': 'profilelist' })
    };

    var myAjax = new Zikula.Ajax.Request(
        Routing.generate('zikulaprofilemodule_ajax_changeprofileweight'),
        {
            parameters: pars, 
            onComplete: profileweightchanged_response
        });
}

/**
 * Ajax response function for updating new sort order: cleanup
 *
 * @params none;
 * @return void;
 */
function profileweightchanged_response(req)
{
    if (!req.isSuccess()) {
        Zikula.showajaxerror(req.getMessage());
        return;
    }

    Zikula.recolor('profilelist', 'profilelistheader');
}

/**
 * Stores the new state when press the led.
 *
 * @params prop_id property ID;
 * @params oldstatus current value to switch;
 * @return void;
 */
function profilestatuschanged(prop_id, oldstatus)
{
    var pars = {
        oldstatus: oldstatus,
        dudid: prop_id
    };

    var myAjax = new Zikula.Ajax.Request(
        Routing.generate('zikulaprofilemodule_ajax_changeprofilestatus'),
        {
            parameters: pars, 
            onComplete: profilestatuschanged_response
        });
}

/**
 * Ajax response function for updating new sort order: cleanup
 *
 * @params none;
 * @return void;
 */
function profilestatuschanged_response(req)
{
    if (!req.isSuccess()) {
        Zikula.showajaxerror(req.getMessage());
        return;
    }

    var data = req.getData();

    // define item list and status link objects
    var li   = null;
    var link = $('profilestatus_'+data.dudid);

    // switch the status in the classname
    var newclassname = '';
    if (data.newstatus) {
        // got active
        newclassname = link.getAttribute('class').replace('0', '1')
        // update the li item
        li = $('profile'+data.dudid);
        li.setAttribute('id', 'profile_'+data.dudid);
    } else {
        // got inactive
        newclassname = link.getAttribute('class').replace('1', '0')
        // update the li item
        li = $('profile_'+data.dudid);
        li.setAttribute('id', 'profile'+data.dudid);
    }
    link.setAttribute('class', newclassname);

    // update the 
    $A(link.childElements()).each(
        function (node) {
            if (node.tagName == 'SPAN') {
                if (data.newstatus) {
                    node.title = msgProfileStatusClickTo + ' ' + msgProfileStatusDeactivate;
                    node.alt = msgProfileStatusDeactivate;
                    node.update(msgProfileStatusActive);
                    node.className = 'label label-success';
                } else {
                    node.title = msgProfileStatusClickTo + ' ' + msgProfileStatusActivate;
                    node.alt = msgProfileStatusActivate;
                    node.update(msgProfileStatusInactive);
                    node.className = 'label label-danger';
                }
            } else if (node.tagName == 'STRONG') {
                if (data.newstatus) {
                    node.innerHTML = msgProfileStatusDeactivate;
                } else {
                    node.innerHTML = msgProfileStatusActivate;
                }
            }
        }
    )
}
