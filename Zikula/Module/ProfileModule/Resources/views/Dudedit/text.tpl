<div class="{$class|default:'form-group'}">
    <label for="prop_{$attributename}" class="col-lg-3 control-label{if ($required)} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-lg-9">
        <input id="prop_{$attributename}" class="form-control" name="{$field_name}" type="text" value="{$value}"{if (isset($item.prop_pattern))} pattern="{$item.prop_pattern}"{/if}{if ($required)} required="required"{/if} />
        {if ($note)}
        <em class="help-block">{$note}</em>
        {/if}
    </div>
</div>