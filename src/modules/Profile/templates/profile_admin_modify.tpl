{ajaxheader modname='Profile' filename='profile_edit_property.js' nobehaviour=true noscriptaculous=true}
{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="edit" size="small"}
    <h3>{gt text='Edit personal info item'}</h3>
</div>

<form class="z-form" action="{modurl modname='Profile' type='admin' func='update'}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
        <input type="hidden" id="csrftoken" name="csrftoken" value="{insert name="csrftoken"}" />
        <input type="hidden" name="dudid" value="{$dudid}" />
        <fieldset>
            <legend>{gt text='Personal info item'}</legend>
            <div class="z-formnote z-warningmsg">{gt text="Notice: No special characters or spaces are allowed in the personal info item's label or attribute name."}</div>
            <div class="z-formrow">
                <label for="profile_label">{gt text='Label'}</label>
                <input id="profile_label" name="label" type="text" size="20" maxlength="255" value="{$item.prop_label|safetext}" />

                {modurl modname='Profile' type='admin' func='help' fqurl=true assign='helpurl'}
                <div class="z-formnote z-informationmsg">{gt text='Check the <strong><a href="%s">help page</a></strong> to get more information about labels and translatable stuff.' tag1=$helpurl|safetext}</div>
            </div>
            <div class="z-formrow">
                <label for="profile_attributename">{gt text='Attribute name'}</label>
                <span id="profile_attributename">{$item.prop_attribute_name}</span>
            </div>
            <div class="z-formrow">
                <label for="profile_required">{gt text="Make this a 'Required' item"}</label>
                <select id="profile_required" name="required">
                    {html_options options=$requiredoptions selected=$item.prop_required}
                </select>
            </div>
            <div class="z-formrow">
                <label for="profile_viewby">{gt text='Visibility'}</label>
                <select id="profile_viewby" name="viewby">
                    {html_options options=$viewbyoptions selected=$item.prop_viewby}
                </select>
            </div>
            <input type="hidden" name="dtype" value="{$item.prop_dtype|safetext}" />
            <div class="z-formrow">
                <label for="profile_displaytype">{gt text='Type of control to display'}</label>
                <select id="profile_displaytype" name="displaytype">
                    {html_options options=$displaytypes selected=$item.prop_displaytype}
                </select>
            </div>
            <div class="z-formrow" id="profile_content_wrapper">
                <label for="profile_listoptions">{gt text='Content'}</label>
                <textarea id="profile_listoptions" cols="50" rows="5" name="listoptions">{$item.prop_listoptions|safetext}</textarea>

                <p class="z-formnote z-informationmsg" id="profile_help_type2">{gt text="Notice: Precede output options by '@@'. Example: '@@No@@Yes', '@@Disabled@@Enabled'. The order is important. If you want to have a different label in the edit form, you can use the following format: 'EditLabel@@DisplayNo@@DisplayYes'. All the values are translatable."}</p>
                <p class="z-formnote z-informationmsg" id="profile_help_type3">{gt text="Notice: Use the following format for each option: '@@label@id'. Example: '@@radio option 1@id1@@radio option 2@id2@@radio option 3@id3'. The options are translatable."}</p>
                <p class="z-formnote z-informationmsg" id="profile_help_type4">{gt text="Notice: Use the following format for each option: '@@label@id'. Example for a simple list: '@@option 1@id1@@option 2@id2@@option 3@id3'. Example for a multiple checkbox set: '1@@option 1@id1@@option 2@id2@@option 3@id3'. The options are translatable."}</p>
                <p class="z-formnote z-informationmsg" id="profile_help_type5">{gt text="Notice: You can specify the date format to use, either via Zikula core templating variables ('datelong', 'datebrief', 'datestring', 'datestring2', 'datetimebrief', 'datetimelong', 'timebrief' or 'timelong'), or via a custom format such as '%b %d of %Y', possibly using PHP options (see the <a href=\"http://www.php.net/manual/en/function.strftime.php\">PHP documentation</a> for more information). The format is translatable."}</p>
                <p class="z-formnote z-informationmsg" id="profile_help_type7">{gt text="Notice: Use the following format for each option: 'id,label;'. Example: 'id1,label1;id2,label2;id3,label3'. Each property should be separated with a semicolon (';'). The ID and label of each property should be separated by a comma (','). The labels are translatable."}</p>

                <div class="z-formnote z-warningmsg" id="profile_warn_ids">
                    {gt text="Warning! If you want to edit the ID of an option without losing associated user data in the database, its label must not be renamed simultaneously. Also, do not assign IDs and labels the same naming. You are recommended to give each option an unique ID and a unique name, and to try to avoid renaming an ID once it has been created."}<br />
                    {gt text="Notice: Entering an ID is optional. If you do not specify an ID, the option position will be used for the ID (starting from zero), instead of the user-defined ID you can enter here. You are recommended to choose an ID that is unique, and to avoid subsequently modifying it."}
                </div>
            </div>
            <div class="z-formrow">
                <label for="profile_note">{gt text='Notice to display with personal info item'}</label>
                <textarea id="profile_note" cols="50" rows="2" name="note">{$item.prop_note|safetext}</textarea>
            </div>
        </fieldset>

        <div class="z-formbuttons z-buttons">
            {button src='button_ok.png' set='icons/small' __alt='Save' __title='Save' __text='Save'}
            <a href="{modurl modname='Profile' type='admin' func='view'}" title="{gt text="Cancel"}">{img modname='core' src='button_cancel.png' set='icons/small' __alt='Cancel' __title='Cancel'} {gt text="Cancel"}</a>
        </div>
    </div>
</form>
{adminfooter}
