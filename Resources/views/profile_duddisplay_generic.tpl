<div class="row">
    <div class="col-md-4 text-right">
        <strong>{gt text=$item.prop_label}:</strong>
    </div>
    <div class="col-md-8">
        {foreach from=$output item='outputrow'}
        <span>{$outputrow}</span>
        {/foreach}
    </div>
</div>
