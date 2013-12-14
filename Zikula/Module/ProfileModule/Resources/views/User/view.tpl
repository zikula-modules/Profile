{if (isset($userinfo.__ATTRIBUTES__))}
    {array_field array=$userinfo.__ATTRIBUTES__ field='realname' returnValue=true assign='name'}
{else}
    {assign var='name' value=''}
{/if}
{if (!$name)}
	{assign var='name' value=$uname}
{/if}
{gt text="Profile for %s" tag1=$name|@ucwords|safetext assign='templatetitle'}

{include file='User/menu.tpl'}

<div class="panel panel-default" id="profile_wrapper">
    <div class="panel-body profile-information">
    <div class="row">
        <div class="col-md-4 text-right">
            <strong>{gt text='User Name:'}</strong>
        </div>
        <div class="col-md-8">
            <span>{$uname|safetext}</span>
        </div>
    </div>
    {if (($modvars.$module.viewregdate|default:1) && ($userinfo.user_regdate != '1970-01-01 00:00:00'))}
    <div class="row">
        <div class="col-md-4 text-right">
            <strong>{gt text='Registration Date:'}</strong>
        </div>
        <div class="col-md-8">
            <span>{$userinfo.user_regdate|dateformat:'datebrief'}</span>
        </div>
    </div>
    {/if}
    {if (isset($dudarray.avatar))}
        {if (($dudarray.avatar == '') || ($dudarray.avatar == 'blank.gif') || ($dudarray.avatar == 'blank.png') || ($dudarray.avatar == 'gravatar.gif'))}
            {gravatar email_address=$userinfo.email}
        {else}
            {modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}
            <img class="img-thumbnail" src="{$avatarpath}/{$dudarray.avatar|safetext}" alt="" />
        {/if}
    {/if}
    </div>
</div>

{foreach from=$fieldsets item='fieldset'}
    <div class="panel panel-default">
        <div class="panel-heading"><h3>{$fieldset}</h3></div>
        <div class="panel-body">
        {foreach from=$fields item='item'}
            {if (($fieldset == $item.prop_fieldset) && ($item.prop_attribute_name != 'avatar'))}
                {duditemdisplay item=$item userinfo=$userinfo}
            {/if}
        {/foreach}
        </div>
{/foreach}
