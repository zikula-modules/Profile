<div class="{$class|default:'form-group'}">
    <label for="prop_{$attributename}" class="col-sm-3 control-label{if $required} required{/if}">{gt text=$proplabeltext}</label>
    <div class="col-sm-9">
        <div id="prop_{$attributename}">
            {html_checkboxes name=$field_name labels=true options=$fields selected=$value assign='fields'}
            {foreach from=$fields item='field'}
            <div class="checkbox">
                {$field}
            </div>
            {/foreach}
        </div>

        {if $note neq ''}
        <em class="help-block">{$note}</em>
        {/if}
        <p id="prop_{$attributename}_error" class="alert alert-danger {if !$error}hidden{/if}">{if $error}{$error}{/if}</p>
    </div>
</div>