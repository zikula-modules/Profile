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

<div id="profile_wrapper">
    {if (isset($dudarray.avatar))}
    	{if (($dudarray.avatar == '') || ($dudarray.avatar == 'blank.gif') || ($dudarray.avatar == 'blank.png'))}
			{gravatar email_address=$userinfo.email f=true}
		{elseif ($dudarray.avatar == 'gravatar.gif')}
			{gravatar email_address=$userinfo.email}
		{else}
			{modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}
			<img src="{$avatarpath}/{$dudarray.avatar|safetext}" alt="" class="profileavatar" />
		{/if}
    {/if}
    <div class="z-form">
        <div class="z-formrow">
            <strong class="z-label">{gt text='User name:'}</strong>
            <span>{$uname|safetext}</span>
        </div>
        {if (($modvars.$module.viewregdate|default:1) && ($userinfo.user_regdate != '1970-01-01 00:00:00'))}
        	<div class="z-formrow">
            	<strong class="z-label">{gt text='Registration date:'}</strong>
				<span>{$userinfo.user_regdate|dateformat:'datebrief'}</span>
			</div>
        {/if}
        {foreach from=$fieldsets item='fieldset'}
    		<h2>{$fieldset}</h2>
			{foreach from=$fields item='item'}
				{if (($fieldset == $item.prop_fieldset) && ($item.prop_attribute_name != 'avatar'))}
					{duditemdisplay item=$item userinfo=$userinfo}
				{/if}
			{/foreach}
		{/foreach}
    </div>
</div>