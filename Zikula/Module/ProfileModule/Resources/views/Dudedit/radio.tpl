<div class="{$class|default:'form-group'}">
    {if $required}
    <p id="advice-required-prop_{$attributename}" class="alert alert-warning" style="display:none">
        {gt text='Sorry! A required personal info item is missing. Please correct and try again.'}
    </p>
    {/if}

    <div class="col-lg-3 control-label">
        <label{if $required} class="required"{/if}>{gt text=$proplabeltext}</label>
    </div>
    <div class="col-lg-9">
        {html_radios name=$field_name values=$listoptions output=$listoutput checked=$value labels=false assign='listoptions'}
        {foreach from=$listoptions item='item'}
        <div class="radio">
            {$item}
        </div>
        {/foreach}
        {if $note neq ''}
        <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-danger {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>