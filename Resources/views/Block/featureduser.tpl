{modgetvar module='Zikula\UsersModule\Constant::MODNAME'|constant name='Zikula\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}

<div class="text-center">
    <h5>{$userinfo.uname|profilelinkbyuname}</h5>
    <p>
        {if isset($userinfo.__ATTRIBUTES__.avatar) and (($userinfo.__ATTRIBUTES__.avatar == '') || ($userinfo.__ATTRIBUTES__.avatar == 'blank.gif') || ($userinfo.__ATTRIBUTES__.avatar == 'blank.png') || ($userinfo.__ATTRIBUTES__.avatar == 'gravatar.gif'))}
            {gravatar email_address=$userinfo.email}
        {elseif isset($userinfo.__ATTRIBUTES__.avatar)}
            {$userinfo.uname|profilelinkbyuname:'':"`$avatarpath`/`$userinfo.__ATTRIBUTES__.avatar`"}
            {img modname='core' src='personal.png' set='icons/large' assign='profileicon'}
            {$userinfo.uname|profilelinkbyuname:'profileicon':$profileicon}
        {else}
            {gravatar email_address=$userinfo.email}
        {/if}
    </p>
    {foreach from=$dudarray key='dudlabel' item='dudvalue'}
        <p><strong>{gt text=$dudlabel}</strong><br />{$dudvalue|safehtml}</p>
    {/foreach}
    {if $showregdate}
        <span>{gt text='Registered on %s' tag1=$userinfo.user_regdate|dateformat:'datebrief'}</span>
    {/if}
</div>
