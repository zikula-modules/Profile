{gt text='Last %s Registered Users' tag1=$recentmembersitemsperpage assign='templatetitle'}

{include file='User/menu.tpl'}

<table class="z-datatable">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th>{gt text='User Name'}</th>
            <th>{gt text='Registration Date'}</th>
            { if @isset($dudarray.realname) }
            <th>{gt text='Real Name'}</th>
            {/if}
            {if $msgmodule}
            <th>{gt text='Messages'}</th>
            {/if}
            { if @isset($dudarray.url) }
            <th>{gt text='Site'}</th>
            {/if}
            {if $adminedit}
            <th>{gt text='Actions'}</th>
            {/if}
        </tr>
    </thead>
    <tbody>
        {foreach from=$users item='user'}
        <tr class="{cycle values='z-odd,z-even'}">
            <td>
                {if $user.onlinestatus eq 1}
                <a href="{modurl modname=$module type='user' func='onlinemembers'}">{img modname='core' src='greenled.png' set='icons/extrasmall' __title='Online' __alt='Online'}</a>
                {else}
                {img modname='core' src='redled.png' set='icons/extrasmall' __title='Offline' __alt='Offline' }
                {/if}
            </td>
            <td><strong>{$user.uname|profilelinkbyuname}</strong></td>
            <td>{$user.user_regdate|dateformat|default:"&nbsp;"}</td>
            { if @isset($dudarray.realname) }
            <td>{$user.__ATTRIBUTES__.realname|default:"&nbsp;"}</td>
            {/if}
            {if $msgmodule}
            <td><a href="{modurl modname=$msgmodule type='user' func='newpm' uid=$user.uid}">{img modname='core' set='icons/extrasmall' src="mail_new.png" __alt='Send Private Message'}</a></td>
            {/if}
            { if @isset($dudarray.url) }
            <td>
                {if $user.__ATTRIBUTES__.url eq ''}
                &nbsp;
                {else}
                <a href="{$user.__ATTRIBUTES__.url|safetext}">{img modname=core set=icons/extrasmall src="agt_internet.png" title=$user.__ATTRIBUTES__.url alt=$user.__ATTRIBUTES__.url}</a>
                {/if}
            </td>
            {/if}
            {if $adminedit}
            <td>
                <a href="{modurl modname='Users' type='admin' func='modify' userid=$user.uid}">{img modname='core' set='icons/extrasmall' src='xedit.png' __alt='Edit'}</a>
                {if $admindelete}
                <a href="{modurl modname='Users' type='admin' func='deleteusers' userid=$user.uid}">{img modname='core' set='icons/extrasmall' src='14_layer_deletelayer.png' __alt='Delete'}</a>
                {/if}
            </td>
            {/if}
        </tr>
        {foreachelse}
        <tr class="z-datatableempty"><td colspan="7">{gt text='No recently registered users found.'}</td></tr>
        {/foreach}
    </tbody>
</table>