<div class="{$class|default:'form-group'}{if $error} has-error{/if}">
    {if $required}
        <p id="advice-required-prop_{$attributename}" class="alert alert-warning" style="display:none">
        {gt text='Sorry! A required personal info item is missing. Please correct and try again.'}
    </p>
    {/if}
    <label for="prop_{$attributename}" class="col-lg-3 control-label{if $required} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-lg-9">
        <input class="form-control{if $required} required{/if}" id="prop_{$attributename}" type="text" name="{$field_name}" value="{$value}" />
        {if $note}
        <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-danger {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>