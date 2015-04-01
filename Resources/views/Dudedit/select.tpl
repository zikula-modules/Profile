{modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}

<div class="{$class|default:'form-group'}">
    <label for="prop_{$attributename}" class="col-sm-3 control-label{if ($required)} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-sm-9">
        {gt text='Select' assign='gt'}
        {gt text='&quot;%1$s: %2$s&quot; is required. Please select an option.' tag1=$item.prop_fieldset tag2=$item.prop_label assign='title'}
        {if (($attributename == 'country') || (strpos($attributename, '_country') !== false))}
            {selector_countries allText=$gt allValue='' class='form-control' id="prop_`$attributename`" name=$field_name selectedValue=$value required=true title=$title}
        {else}
            <select id="prop_{$attributename}" name="{$field_name}{if ($selectmultiple)}[]{/if}"{if ($selectmultiple)} placeholder="{gt text='Select'}" {/if}{$selectmultiple} class="form-control" required="required" title="{gt text='&quot;%1$s: %2$s&quot; is required. Please select an option.' tag1=$item.prop_fieldset tag2=$item.prop_label}" x-moz-errormessage="{gt text='&quot;%1$s: %2$s&quot; is required. Please select an option.' tag1=$item.prop_fieldset tag2=$item.prop_label}" oninvalid="this.setCustomValidity('{gt text='Please select an item in the list.'}');" onchange="this.setCustomValidity('');" onblur="this.checkValidity();">
                {if (!$selectmultiple)}<option label="{gt text='Select'}" value="">{gt text='Select'}</option>{/if}
                {html_options id=$attributename values=$listoptions output=$listoutput selected=$value}
            </select>
        {/if}

        {if ($attributename == 'avatar')}
        <p id="youravatarcontainer">
            <span id="youravatarpath" style="display: none;">{$avatarpath}</span>{gt text=$proplabeltext assign='avatarlabeltext'}
            <img class="img-thumbnail" id="youravatardisplay" src="{$avatarpath}/{$value}" alt="{$avatarlabeltext}" />
        </p>
        {/if}
        {if ($note)}
            <em class="help-block">{$note}</em>
        {/if}
    </div>
</div>

{if ($attributename == 'avatar')}
{ajaxheader modname=$module filename='showavatar.js'}
<script type="text/javascript">
    // <![CDATA[
    Event.observe($('prop_avatar'), 'change', showavatar);
    // ]]>
</script>
{/if}