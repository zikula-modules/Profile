{ajaxheader modname='ZikulaProfileModule' filename='Profile.UI.Edit.js' noscriptaculous=true effects=true}
{foreach from=$fieldsets key="key" item='fieldset'}
	<fieldset class="{$key}">
    	<legend>{$fieldset}</legend>
    	{foreach from=$duditems item='item' key='itemlabel'}
    		{if ($fieldset == $item.prop_fieldset)}
    			{duditemmodify item=$item uid=$userid error=$duderrors.$itemlabel|default:''}
    		{/if}
		{/foreach}
	</fieldset>
{/foreach}