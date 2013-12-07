{modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}

<div class="{$class|default:'z-formrow'}">
    {if $required}
    <p id="advice-required-prop_{$attributename}" class="custom-advice z-formnote" style="display:none">
        {gt text='Sorry! A required personal info item is missing. Please correct and try again.'}
    </p>
    {/if}

    <label for="prop_{$attributename}">{gt text=$proplabeltext}{if $required}<span class="z-form-mandatory-flag">{gt text='*'}</span>{/if}</label>
    {gt text='Select' assign='gt'}
    {if (($attributename == 'country') || (strpos($attributename, '_country') !== false))}
        {if ($error)}
    	    {selector_countries allText=$gt allValue='' class='z-form-error' id="prop_`$attributename`" name="dynadata[`$attributename`]" selectedValue=$value}
        {elseif ($required)}
    	    {selector_countries allText=$gt allValue='' class='required' id="prop_`$attributename`" name="dynadata[`$attributename`]" selectedValue=$value}
    	{else}
    	    {selector_countries allText=$gt allValue='' id="prop_`$attributename`" name="dynadata[`$attributename`]" selectedValue=$value}
    	{/if}
    {else}
    	<select id="prop_{$attributename}" name="dynadata[{$attributename}]{if ($selectmultiple)}[]{/if}"{$selectmultiple} class="{if ($required)}required{/if} {if ($error)}z-form-error{/if}">
    		<option label="{gt text='Select'}" value="">{gt text='Select'}</option>
			{html_options id=$attributename values=$listoptions output=$listoutput selected=$value}
		</select>
    {/if}

    {if $attributename eq 'avatar'}
    <p id="youravatarcontainer" class="z-formnote">
        <span id="youravatarpath" style="display:none">{$avatarpath}</span>{gt text=$proplabeltext assign='avatarlabeltext'}
        <img id="youravatardisplay" src="{$avatarpath}/{$value}" alt="{$avatarlabeltext}" />
    </p>
    {/if}
    {if $note}
    <em class="z-sub z-formnote">{$note}</em>
    {/if}
    <p id="prop_{$attributename}_error" class="z-formnote z-errormsg {if !$error}z-hide{/if}">{if $error}{$error}{/if}</p>
</div>

{if $attributename eq 'avatar'}
{ajaxheader modname=$module filename='showavatar.js'}
<script type="text/javascript">
    // <![CDATA[
    Event.observe($('prop_avatar'), 'change', showavatar);
    // ]]>
</script>
{/if}
