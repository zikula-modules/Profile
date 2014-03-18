<div class="{$class|default:'form-group'}">
    <label for="prop_{$attributename}" class="col-lg-3 control-label{if ($required)} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-lg-9">
        <input id="prop_{$attributename}" class="form-control" name="{$field_name}" type="text" value="{$value}"{if ($required)} required="required" title="{gt text='&quot;%1$s: %2$s&quot; is required.' tag1=$item.prop_fieldset tag2=$item.prop_label}" x-moz-errormessage="{gt text='&quot;%1$s: %2$s&quot; is required.' tag1=$item.prop_fieldset tag2=$item.prop_label}" oninvalid="this.setCustomValidity('{gt text='Please fill out this field.'}');" oninput="this.setCustomValidity('');" onblur="this.checkValidity();"{/if} />
        {if ($note)}
        <em class="help-block">{$note}</em>
        {/if}
    </div>
</div>