<div class="{$class|default:'form-group'}">
    <label for="prop_{$attributename}" class="col-sm-3 control-label{if $required} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-sm-9">
        <div class="checkbox">
            <input id="prop_{$attributename}" type="checkbox" name="{$field_name}" value="1"{if $value} checked="checked"{/if} />
        </div>
        {if $note neq ''}
        <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-warning {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>