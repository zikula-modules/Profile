{if $usersonline}
<ul>
    {foreach item='user' from=$usersonline}
    <li>
        {$user.uname|userprofilelink:'':'':$maxLength}
        {modurl modname=$msgmodule func='inbox' assign="messageslink"}
        {if $msgmodule AND $user.uid eq $uid}
        (<a href="{$messageslink|safehtml}" title="{gt text='unread'}">{$messages.unread}</a> | <a href="{$messageslink|safehtml}" title="{gt text='total'}">{$messages.totalin}</a>)
        {elseif $msgmodule}
        <a href="{modurl modname=$msgmodule func='newpm' uid=$user.uid}" title="{gt text='Send private message'}&nbsp;{$user.uname|safehtml}">{img modname='core' set='icons/extrasmall' src='mail_new.gif' __alt='Send private message' style='vertical-align:middle; margin-left:2px;'}</a>
        {/if}
    </li>
    {/foreach}
</ul>
{/if}
<p>
    {if $anononline eq 0}
    {gt text='%s registered user' plural='%s registered users' count=$membonline tag1=$membonline assign='blockstring'}
    {gt text='%s on-line.' tag1=$blockstring}
    {elseif $membonline eq 0}
    {gt text='%s anonymous guest' plural='%s anonymous guests' count=$anononline tag1=$anononline assign='blockstring'}
    {gt text='%s on-line.' tag1=$blockstring}
    {else}
    {gt text='%s registered user' plural='%s registered users' count=$membonline tag1=$membonline assign='nummeb'}
    {gt text='%s anonymous guest' plural='%s anonymous guests' count=$anononline tag1=$anononline assign='numanon'}
    {gt text='%1$s and %2$s online.' tag1=$nummeb tag2=$numanon}
    {/if}
</p>
