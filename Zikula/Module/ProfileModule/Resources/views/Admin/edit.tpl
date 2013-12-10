{ajaxheader modname=$module filename='profile_edit_property.js' nobehaviour=true noscriptaculous=true}
{adminheader}
<h3>
    {if !empty($item)}
        <span class="fa fa-pencil"></span>&nbsp;{gt text="Edit Field"}
    {else}
        <span class="fa fa-plus-square"></span>&nbsp;{gt text="New Field"}
    {/if}

</h3>

<form class="form-horizontal" action="{modurl modname=$module type='admin' func='modify'}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
        <input type="hidden" id="csrftoken" name="csrftoken" value="{insert name="csrftoken"}" />
        <input type="hidden" name="dudid" value="{$dudid}" />
        <fieldset>
            <legend>{gt text='Item'}</legend>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-9">
                    <div class="alert alert-warning">{gt text="Notice: No special characters or spaces are allowed in the personal info item's label or attribute name."}</div>
                </div>
                <div class="col-lg-3 control-label">
                    <label for="profile_label">{gt text='Label'}</label>
                </div>
                <div class="col-lg-9">
                    <input class="form-control" id="profile_label" name="label" type="text" size="20" maxlength="255" value="{$item.prop_label|default:''|safetext}" />
                    {modurl modname=$module type='admin' func='help' fqurl=true assign='helpurl'}
                    <div class="alert alert-info">{gt text='Check the <strong><a href="%s">help page</a></strong> to get more information about labels and translatable stuff.' tag1=$helpurl|safetext}</div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_attributename">{gt text='Attribute name'}</label>
                </div>
                <div class="col-lg-9">
                    {if !empty($item)}
                        <span id="profile_attributename">{$item.prop_attribute_name|default:''}</span>
                    {else}
                        <input class="form-control" id="profile_attributename" name="attributename" type="text" size="20" maxlength="80" />
                        <div class="alert alert-warning">{gt text="Notice: The attribute name you enter cannot be changed afterwards, so you should choose it carefully."}</div>
                    {/if}
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_required">{gt text="Make this a 'Required' item"}</label>
                </div>
                <div class="col-lg-9">
                    <select class="form-control" id="profile_required" name="required">
                    {html_options options=$requiredoptions selected=$item.prop_required|default:0}
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_viewby">{gt text='Visibility'}</label>
                </div>
                <div class="col-lg-9">
                    <select class="form-control" id="profile_viewby" name="viewby">
                    {html_options options=$viewbyoptions selected=$item.prop_viewby|default:0}
                    </select>
                </div>
            </div>
            <input type="hidden" name="dtype" value="{$item.prop_dtype|default:''|safetext}" />
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_displaytype">{gt text='Type of control to display'}</label>
                </div>
                <div class="col-lg-9">
                    <select class="form-control" id="profile_displaytype" name="displaytype">
                    {html_options options=$displaytypes selected=$item.prop_displaytype|default:0}
                    </select>
                </div>
            </div>
            <div class="form-group" id="profile_content_wrapper">
                <div class="col-lg-3 control-label">
                    <label for="profile_listoptions">{gt text='Content'}</label>
                </div>
                <div class="col-lg-9">
                    <textarea class="form-control" id="profile_listoptions" cols="50" rows="5" name="listoptions">{$item.prop_listoptions|default:''|safetext}</textarea>
    
                    <p class="alert alert-info" id="profile_help_type2">{gt text="Notice: Precede output options by '@@'. Example: '@@No@@Yes', '@@Disabled@@Enabled'. The order is important. If you want to have a different label in the edit form, you can use the following format: 'EditLabel@@DisplayNo@@DisplayYes'. All the values are translatable."}</p>
                    <p class="alert alert-info" id="profile_help_type3">{gt text="Notice: Use the following format for each option: '@@label@id'. Example: '@@radio option 1@id1@@radio option 2@id2@@radio option 3@id3'. The options are translatable."}</p>
                    <p class="alert alert-info" id="profile_help_type4">{gt text="Notice: Use the following format for each option: '@@label@id'. Example for a simple list: '@@option 1@id1@@option 2@id2@@option 3@id3'. Example for a multiple checkbox set: '1@@option 1@id1@@option 2@id2@@option 3@id3'. The options are translatable."}</p>
                    <p class="alert alert-info" id="profile_help_type5">{gt text="Notice: You can specify the date format to use, either via Zikula core templating variables ('datelong', 'datebrief', 'datestring', 'datestring2', 'datetimebrief', 'datetimelong', 'timebrief' or 'timelong'), or via a custom format such as '%b %d of %Y', possibly using PHP options (see the <a href=\"http://www.php.net/manual/en/function.strftime.php\">PHP documentation</a> for more information). The format is translatable."}</p>
                    <p class="alert alert-info" id="profile_help_type7">{gt text="Notice: Use the following format for each option: 'id,label;'. Example: 'id1,label1;id2,label2;id3,label3'. Each property should be separated with a semicolon (';'). The ID and label of each property should be separated by a comma (','). The labels are translatable."}</p>
                </div>

                <div class="alert alert-warning" id="profile_warn_ids">
                    {gt text="Warning! If you want to edit the ID of an option without losing associated user data in the database, its label must not be renamed simultaneously. Also, do not assign IDs and labels the same naming. You are recommended to give each option an unique ID and a unique name, and to try to avoid renaming an ID once it has been created."}<br />
                    {gt text="Notice: Entering an ID is optional. If you do not specify an ID, the option position will be used for the ID (starting from zero), instead of the user-defined ID you can enter here. You are recommended to choose an ID that is unique, and to avoid subsequently modifying it."}
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_note">{gt text='Notice to display with personal info item'}</label>
                </div>
                <div class="col-lg-9">
                    <textarea class="form-control" id="profile_note" cols="50" rows="2" name="note">{$item.prop_note|default:''|safetext}</textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_fieldset">{gt text='Fieldset'}</label>
                </div>
                <div class="col-lg-9">
                    <input class="form-control" id="profile_fieldset" name="fieldset" type="text" size="20" maxlength="80" value="{$item.prop_fieldset|default:''|safetext}" placeholder="{gt text='User Information'}" />
                </div>
            </div>
        </fieldset>

        <div class="col-lg-offset-3 col-lg-9">
            <button class="btn btn-success" type="submit" name="Save">{gt text="Save"}</button>
            <a class="btn btn-danger" href="{modurl modname=$module type='admin' func='view'}" title="{gt text="Cancel"}">{gt text="Cancel"}</a>
        </div>
    </div>
</form>
{adminfooter}
