{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="config" size="small"}
    <h3>{gt text='User account panel settings'}</h3>
</div>
<form class="z-form" action="{modurl modname=$module type='admin' func='updateconfig'}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
        <input type="hidden" id="csrftoken" name="csrftoken" value="{insert name="csrftoken"}" />
        <fieldset>
            <legend>{gt text='Registered users list settings'}</legend>
            <div class="z-formrow">
                <label for="profile_viewregdate">{gt text="Display the user's registration date"}</label>
                {if $modvars.$module.viewregdate|default:0 eq 1}
                <input id="profile_viewregdate" name="viewregdate" type="checkbox" value="1" checked="checked" />
                {else}
                <input id="profile_viewregdate" name="viewregdate" type="checkbox" value="1" />
                {/if}
            </div>
            <div class="z-formrow">
                <label for="profile_memberslistitemsperpage">{gt text="Users per page in 'Registered users list'"}</label>
                <input id="profile_memberslistitemsperpage" type="text" name="memberslistitemsperpage" size="3" value="{$modvars.$module.memberslistitemsperpage|safetext}" />
            </div>
            <div class="z-formrow">
                <label for="profile_onlinemembersitemsperpage">{gt text="Users per page in 'Users currently on-line' page"}</label>
                <input id="profile_onlinemembersitemsperpage" type="text" name="onlinemembersitemsperpage" size="3" value="{$modvars.$module.onlinemembersitemsperpage|safetext}" />
            </div>
            <div class="z-formrow">
                <label for="profile_recentmembersitemsperpage">{gt text="Users per page in 'Recent registrations' page"}</label>
                <input id="profile_recentmembersitemsperpage" type="text" name="recentmembersitemsperpage" size="3" value="{$modvars.$module.recentmembersitemsperpage|safetext}" />
            </div>
            <div class="z-formrow">
                <label for="profile_filterunverified">{gt text="Filter unverified users from 'Registered users list'"}</label>
                <div id="profile_filterunverified">
                    {if $modvars.$module.filterunverified eq 1}
                    <input id="filterunverified1" type="radio" name="filterunverified" value="1" checked="checked" />
                    <label for="filterunverified1">{gt text='Yes'}</label>
                    <input id="filterunverified0" type="radio" name="filterunverified" value="0" />
                    <label for="filterunverified0">{gt text='No'}</label>
                    {else}
                    <input id="filterunverified1" type="radio" name="filterunverified" value="1" />
                    <label for="filterunverified1">{gt text='Yes'}</label>
                    <input id="filterunverified0" type="radio" name="filterunverified" value="0" checked="checked" />
                    <label for="filterunverified0">{gt text='No'}</label>
                    {/if}
                </div>
            </div>
        </fieldset>
        <fieldset>
            <legend>{gt text='User registration form settings'}</legend>
            <p class="z-informationmsg">{gt text="The personal info items that you activate below will be displayed in the user registration form if the 'Users' module is configured to display personal info items during user registration, and if the 'Profile' module is specified in the 'General settings manager' as the module to provide the site's user profile management functionality. Personal info items that are configured as 'Required' will always be displayed in the user registration form."}</p>
            <div class="z-formrow">
                <label for="profile_dudregshow">{gt text='Personal info items to include in user registration form'}</label>
                <div id="profile_dudregshow">
					{foreach from=$fieldsets key="key" item='fieldset'}
						<fieldset class="{$key}">
							<legend>{$fieldset}</legend>
							{foreach from=$dudfields key='key' item='item'}
								{if ($fieldset == $item.prop_fieldset)}
									<div>
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
        <div class="z-formbuttons z-buttons">
            {button src='button_ok.png' set='icons/small' __alt='Save' __title='Save' __text='Save'}
            <a href="{modurl modname=$module type='admin' func='view'}" title="{gt text="Cancel"}">{img modname='core' src='button_cancel.png' set='icons/small' __alt='Cancel' __title='Cancel'} {gt text="Cancel"}</a>
        </div>
    </div>
</form>
{adminfooter}