{adminheader}
<h3>
    <span class="fa fa-wrench"></span>&nbsp;{gt text="Settings"}
</h3>
<form id="modifyconfig" class="form-horizontal" role="form" action="{modurl modname=$module type='admin' func='updateconfig'}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
        <input type="hidden" id="csrftoken" name="csrftoken" value="{insert name="csrftoken"}" />
        <fieldset>
            <legend>{gt text='Registered users list settings'}</legend>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_viewregdate">{gt text="Display the user's registration date"}</label>
                </div>
                <div class="col-lg-9">
                    <div class="checkbox">
                        <input id="profile_viewregdate" name="viewregdate" type="checkbox" value="1"{if $modvars.$module.viewregdate|default:0 eq 1} checked="checked"{/if} />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_memberslistitemsperpage">{gt text="Users per page in 'Registered users list'"}</label>
                </div>
                <div class="col-lg-9">
                    <input class="form-control" id="profile_memberslistitemsperpage" type="text" name="memberslistitemsperpage" value="{$modvars.$module.memberslistitemsperpage|safetext}" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_onlinemembersitemsperpage">{gt text="Users per page in 'Users currently on-line' page"}</label>
                </div>
                <div class="col-lg-9">
                    <input class="form-control" id="profile_onlinemembersitemsperpage" type="text" name="onlinemembersitemsperpage" value="{$modvars.$module.onlinemembersitemsperpage|safetext}" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_recentmembersitemsperpage">{gt text="Users per page in 'Recent registrations' page"}</label>
                </div>
                <div class="col-lg-9">
                    <input class="form-control" id="profile_recentmembersitemsperpage" type="text" name="recentmembersitemsperpage" value="{$modvars.$module.recentmembersitemsperpage|safetext}" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_filterunverified">{gt text="Filter unverified users from 'Registered users list'"}</label>
                </div>
                <div class="col-lg-9">
                    <div id="profile_filterunverified">
                        <label class="checkbox-inline"><input id="filterunverified1" type="radio" name="filterunverified" value="1"{if $modvars.$module.filterunverified eq 1} checked="checked"{/if} /> {gt text='Yes'}</label>
                        <label class="checkbox-inline"><input id="filterunverified0" type="radio" name="filterunverified" value="0"{if $modvars.$module.filterunverified eq 0} checked="checked"{/if} /> {gt text='No'}</label>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <legend>{gt text='User registration form settings'}</legend>
            <p class="alert alert-info">{gt text="The personal info items that you activate below will be displayed in the user registration form if the 'Users' module is configured to display personal info items during user registration, and if the 'Profile' module is specified in the 'General settings manager' as the module to provide the site's user profile management functionality. Personal info items that are configured as 'Required' will always be displayed in the user registration form."}</p>
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_dudregshow">{gt text='Personal info items to include in user registration form'}</label>
                </div>
                <div class="col-lg-9">
					{foreach from=$fieldsets key="key" item='fieldset'}
                    <fieldset class="{$key}">
                        <legend>{$fieldset}</legend>
                        {foreach from=$dudfields key='key' item='item'}
                            {if ($fieldset == $item.prop_fieldset)}
                                <div class="checkbox">
                                    {if ($item.prop_required)}
                                        <input id="profile_dudregshow_{$item.prop_attribute_name|safetext}" type="hidden" name="dudregshow[]" value="{$item.prop_attribute_name|safetext}" />
                                        <input id="profile_dudregshow_{$item.prop_attribute_name|safetext}_placeholder" type="checkbox" name="dudregshow_placeholder[]" value="{$item.prop_attribute_name|safetext}" checked="checked" disabled="disabled" />
                                    {else}
                                        {if (in_array($item.prop_attribute_name, $modvars.$module.dudregshow))}
                                            <input id="profile_dudregshow_{$item.prop_attribute_name|safetext}" type="checkbox" name="dudregshow[]" value="{$item.prop_attribute_name|safetext}" checked="checked" />
                                        {else}
                                            <input id="profile_dudregshow_{$item.prop_attribute_name|safetext}" type="checkbox" name="dudregshow[]" value="{$item.prop_attribute_name|safetext}" />
                                        {/if}
                                    {/if}
                                    <label for="profile_dudregshow_{$item.prop_attribute_name|safetext}">{gt text=$item.prop_label|safetext}</label>
                                </div>
                            {/if}
                        {/foreach}
                    </fieldset>
					{/foreach}
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