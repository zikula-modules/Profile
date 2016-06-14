{ajaxheader modname='ZikulaProfileModule' filename='Profile.UI.Edit.js' noscriptaculous=true effects=true}
{foreach from=$fieldsets key='key' item='fieldset'}
    {capture name='capture_fieldset' assign='capture_fieldset'}
        <fieldset class="{$key}">
    	    <legend>{$fieldset}</legend>
            {foreach from=$duditems item='item' key='itemlabel'}
    		    {if ($fieldset == $item.prop_fieldset)}
    		        {capture name='capture_fields' assign='capture_fields'}
                        {duditemmodify item=$item uid=$userid error=$duderrors.$itemlabel|default:''}
    			    {/capture}
    			    {if ($capture_fields|trim != '')}
                        {$capture_fields}
                    {/if}
                {/if}
            {/foreach}
        </fieldset>
    {/capture}
    {if ($capture_fields|trim != '')}
        {$capture_fieldset}
    {/if}
{/foreach}
