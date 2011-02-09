{array_field_isset array=$userinfo.__ATTRIBUTES__ field='realname' returnValue=true assign='name'}
{if !$name}{assign var='name' value=$uname}{/if}
{gt text="Personal info for %s" tag1=$name|@ucwords|safetext assign='templatetitle'}

{include file='profile_user_menu.tpl'}

<div id="profile_wrapper">
    {if isset($dudarray.avatar)}
    {if $dudarray.avatar eq '' or $dudarray.avatar eq 'blank.gif'}
    {img modname='core' src='personal.gif' set='icons/large' class='profileavatar'}
    {else}
    {modgetvar module='Users' name='avatarpath' assign='avatarpath'}
    <img src="{$avatarpath}/{$dudarray.avatar|safetext}" alt="" class="profileavatar" />
    {/if}
    {/if}

    <div class="z-form">
        <div class="z-formrow">
            <strong class="z-label">{gt text='User name:'}</strong>
            <span>{$uname|safetext}</span>
        </div>
        {foreach from=$fields item='field'}
        {if $field.prop_attribute_name neq 'avatar'}
        {duditemdisplay item=$field userinfo=$userinfo}
        {/if}
        {/foreach}
        {if $pncore.Profile.viewregdate|default:1 and $userinfo.user_regdate neq '1970-01-01 00:00:00'}
        <div class="z-formrow">
            <strong class="z-label">{gt text='Registration date:'}</strong>
            <span>{$userinfo.user_regdate|dateformat:'datebrief'}</span>
        </div>
        {/if}
    </div>

    {*modurl modname='Profile' func='view' uid=$userinfo.uid assign='returnurl'*}
    {*notifydisplayhooks eventname='profile.hook.general.ui.view' subject=$userinfo id=$userinfo.uid returnurl=$returnurl*}
</div>