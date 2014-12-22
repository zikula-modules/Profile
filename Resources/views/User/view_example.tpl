{array_field array=$userinfo.__ATTRIBUTES__ field='realname' returnValue=true assign='name'}

{if (!$name)}
    {assign var='name' value=$uname}
{/if}

{gt text="Latest submissions of %s" tag1=$name|@ucwords|safetext assign='templatetitle'}

{include file='User/menu.tpl'}

<div id="profile_wrapper">
    {if (isset($dudarray.avatar))}
        {if ((empty($dudarray.avatar)) || ($dudarray.avatar == 'blank.gif') || ($dudarray.avatar == 'blank.png') || ($dudarray.avatar == 'gravatar.jpg'))}
            {if ($modvars.ZikulaUsersModule.allowgravatars)}
                {gravatar email_address=$userinfo.email}
            {/if}
        {else}
            {modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}
            <img class="img-thumbnail" src="{$avatarpath}/{$dudarray.avatar|safetext}" alt="" />
        {/if}
    {elseif ($modvars.ZikulaUsersModule.allowgravatars)}
        {gravatar email_address=$userinfo.email}
    {/if}
    <div class="z-form">
        <div class="z-formrow">
            {profilesection name='News'}
        </div>
        <div class="z-formrow">
            {profilesection name='EZComments'}
        </div>
    </div>
</div>