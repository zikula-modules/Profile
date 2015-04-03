<div class="row">
    <div class="col-sm-3 text-right">
        <strong>{gt text=$item.prop_label}:</strong>
    </div>
    <div class="col-sm-9">
        {foreach from=$output item='outputrow'}
        <span>{$outputrow}</span>
        {/foreach}
    </div>
</div>
