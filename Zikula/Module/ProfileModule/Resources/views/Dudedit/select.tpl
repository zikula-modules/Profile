{modgetvar module='Zikula\Module\UsersModule\Constant::MODNAME'|constant name='Zikula\Module\UsersModule\Constant::MODVAR_AVATAR_IMAGE_PATH'|constant assign='avatarpath'}

<div class="{$class|default:'form-group'}{if $error} has-error{/if}">
    {if $required}
        <p id="advice-required-prop_{$attributename}" class="alert alert-warning" style="display:none">
        {gt text='Sorry! A required personal info item is missing. Please correct and try again.'}
    </p>
    {/if}

    <div class="col-lg-3 control-label">
        <label for="prop_{$attributename}"{if $required} class="required"{/if}>{gt text=$proplabeltext}</label>
    </div>
    <div class="col-lg-9">
        {gt text='Select' assign='gt'}
        {if (($attributename == 'country') || (strpos($attributename, '_country') !== false))}
            {if $required}{assign value="form-control required" var="class"}{else}{assign value="form-control" var="class"}{/if}
            {selector_countries allText=$gt allValue='' class=$class id="prop_`$attributename`" name="dynadata[`$attributename`]" selectedValue=$value}
        {else}
            <select id="prop_{$attributename}" name="{$field_name}{if ($selectmultiple)}[]{/if}"{$selectmultiple} class="form-control{if $required} required{/if}">
                <option label="{gt text='Select'}" value="">{gt text='Select'}</option>
                {html_options id=$attributename values=$listoptions output=$listoutput selected=$value}
            </select>
        {/if}

        {if $attributename eq 'avatar'}
        <p id="youravatarcontainer">
            <span id="youravatarpath" style="display:none">{$avatarpath}</span>{gt text=$proplabeltext assign='avatarlabeltext'}
            <img class="img-thumbnail" id="youravatardisplay" src="{$avatarpath}/{$value}" alt="{$avatarlabeltext}" />
        </p>
        {/if}
        {if $note}
            <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-danger {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>

{if $attributename eq 'avatar'}
{ajaxheader modname=$module filename='showavatar.js'}
<script type="text/javascript">
    // <![CDATA[
    Event.observe($('prop_avatar'), 'change', showavatar);
    // ]]>
</script>
{/if}