{pageaddvar name='stylesheet' value='@ZikulaProfileModule/Resources/public/css/ribbon.css'}

{if (isset($userinfo.__ATTRIBUTES__))}
    {array_field array=$userinfo.__ATTRIBUTES__ field='user_information_first_name' returnValue=true assign='user_information_first_name'}
    {array_field array=$userinfo.__ATTRIBUTES__ field='user_information_middle_name' returnValue=true assign='user_information_middle_name'}
    {array_field array=$userinfo.__ATTRIBUTES__ field='user_information_last_name' returnValue=true assign='user_information_last_name'}
    
    {setvar var='full_name' keys='user_information_first_name, user_information_middle_name, user_information_last_name`' values="`$user_information_first_name`, `$user_information_middle_name`, `$user_information_last_name`"}

    {if (!empty($full_name))}
        {setvar var="name" value=' '|implode:$full_name}
    {else}
        {array_field array=$userinfo.__ATTRIBUTES__ field='realname' returnValue=true assign='name'}
    {/if}
{else}
    {assign var='name' value=''}
{/if}

{if (!$name)}
	{assign var='name' value=$uname}
{/if}

{gt text='%s' tag1=$name|@ucwords|safetext assign='templatetitle'}

{include file='User/menu.tpl'}
<div class="ribbon-wrapper-green">
    <div class="ribbon-green">
        {gt text='Profile'}
    </div>
</div>
<div class="panel panel-default" id="profile_wrapper">
    <div class="panel-body profile-information">
        <div class="row user_name">
            <div class="col-sm-3 text-right">
                <strong>{gt text='User Name:'}</strong>
            </div>
            <div class="col-sm-9">
                <span>{$uname|safetext}</span>
            </div>
        </div>
        {if (($modvars.$module.viewregdate|default:1) && ($userinfo.user_regdate != '1970-01-01 00:00:00'))}
            <div class="row registration_date">
                <div class="col-sm-3 text-right">
                    <strong>{gt text='Registration Date:'}</strong>
                </div>
                <div class="col-sm-9">
                    <span>{$userinfo.user_regdate|dateformat:'datebrief'}</span>
                </div>
            </div>
        {/if}
        {if (isset($dudarray.avatar))}
            {if ((empty($dudarray.avatar)) || ($dudarray.avatar == 'blank.gif') || ($dudarray.avatar == 'blank.png') || ($dudarray.avatar == 'gravatar.jpg'))}
                {if ($modvars.ZikulaUsersModule.allowgravatars)}
                    {gravatar email_address=$userinfo.email}
                {/if}
            {else}
                {modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}
                <img class="img-thumbnail" src="{$avatarpath}/{$dudarray.avatar|safetext}" alt="{gt text='Avatar'}" />
            {/if}
        {elseif ($modvars.ZikulaUsersModule.allowgravatars)}
            {gravatar email_address=$userinfo.email}
        {/if}
    </div>
</div>
{foreach from=$fieldsets item='fieldset'}
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3>{$fieldset}</h3>
        </div>
        <div class="panel-body">
            {foreach from=$fields item='item'}
                {if (($fieldset == $item.prop_fieldset) && ($item.prop_attribute_name != 'avatar'))}
                    {duditemdisplay item=$item userinfo=$userinfo}
                {/if}
            {/foreach}
        </div>
    </div>
{/foreach}
