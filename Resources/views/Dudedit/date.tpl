<div class="{$class|default:'form-group'}{if $error} has-error{/if}">
    {if $required}
        <p id="advice-required-prop_{$attributename}" class="alert alert-warning" style="display: none;">
            {gt text='Sorry! A required personal info item is missing. Please correct and try again.'}
        </p>
    {/if}
    <label for="prop_{$attributename}" class="col-sm-3 control-label{if $required} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-sm-9">
        {jquery_datepicker
        defaultdate=$valueDateTime
        displayelement="display_`$attributename`"
        displayformat_datetime=$formats.dt
        displayformat_javascript=$formats.js
        object=$field_object
        displayelement_class='form-control'
        readonly=false
        valuestorageelement=$attributename
        changeMonth='true'
        changeYear='true'}
        {if $note}
            <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-danger {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>