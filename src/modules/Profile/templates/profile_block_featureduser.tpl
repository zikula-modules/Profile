{modgetvar module='Users' name='avatarpath' assign='avatarpath'}

<div class="profile-block-featureduser">
    <h5>{$userinfo.uname|userprofilelink}</h5>
    <p>
        {if @isset($userinfo.__ATTRIBUTES__.avatar) and $userinfo.__ATTRIBUTES__.avatar neq '' and $userinfo.__ATTRIBUTES__.avatar neq 'blank.gif'}
        {$userinfo.uname|userprofilelink:'':"`$avatarpath`/`$userinfo.__ATTRIBUTES__.avatar`"}
        {else}
        {img modname='core' src='personal.gif' set='icons/large' assign='profileicon'}
        {$userinfo.uname|userprofilelink:'profileicon':$profileicon}
        {/if}
    </p>
    {foreach from=$dudarray key='dudlabel' item='dudvalue'}
        <p><strong>{gt text=$dudlabel}</strong><br />{$dudvalue|safehtml}</p>
    {/foreach}
    {if $showregdate}
        <span>{gt text='Registered on %s' tag1=$userinfo.user_regdate|dateformat:'datebrief'}</span>
    {/if}
</div>