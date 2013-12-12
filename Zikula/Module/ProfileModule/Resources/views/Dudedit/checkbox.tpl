<div class="{$class|default:'form-group'}">
    <div class="col-lg-3 control-label">
        <label for="prop_{$attributename}"{if $required} class="required"{/if}>{gt text=$proplabeltext}</label>
    </div>
    <div class="col-lg-9">
        <div class="checkbox">
            <input id="prop_{$attributename}" type="checkbox" name="dynadata[{$attributename}]" value="1"{if $value} checked="checked"{/if} />
        </div>
        {if $note neq ''}
        <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-warning {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>
