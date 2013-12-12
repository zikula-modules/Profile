<div class="{$class|default:'form-group'}">
    <div class="col-lg-3 control-label">
        <label for="prop_{$attributename}"{if $required} class="required"{/if}>{gt text=$proplabeltext}</label>
    </div>
    <div class="col-lg-9">
        <div id="prop_{$attributename}">
            {html_checkboxes name="dynadata[`$attributename`]" labels=true options=$fields selected=$value assign='fields'}
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
