{if $users}
<ul class="list-group">
    {foreach from=$users item='user'}
        <li class="list-group-item">{$user.uname|profilelinkbyuname} ({$user.lastlogin|dateformat})</li>
    {/foreach}
</ul>
{/if}