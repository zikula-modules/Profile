{gt text='Last %s Registered Users' tag1=$recentmembersitemsperpage assign='templatetitle'}

{include file='User/menu.tpl'}

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{gt text='User Name'}</th>
                <th>{gt text='Registration Date'}</th>
                {if @isset($dudarray.realname)}
                <th>{gt text='Real Name'}</th>
                {/if}
                {if $msgmodule}
                <th>{gt text='Messages'}</th>
                {/if}
                {if @isset($dudarray.url)}
                <th>{gt text='Site'}</th>
                {/if}
                {if $adminedit}
                <th>{gt text='Actions'}</th>
                {/if}
            </tr>
        </thead>
        <tbody>
            {foreach from=$users item='user'}
            <tr>
                <td><strong>{$user.uname|profilelinkbyuname}</strong>&nbsp;&nbsp;
                    {if $user.onlinestatus eq 1}
                        <a href="{modurl modname=$module type='user' func='onlinemembers'}"><span class="label label-success">{gt text='Online'}</span></a>
                    {else}
                        <span class="label label-danger">{gt text='Offline'}</span>
                    {/if}
                </td>
                <td>{$user.user_regdate|dateformat|default:"&nbsp;"}</td>
                {if @isset($dudarray.realname)}
                <td>{$user.attributes.realname|default:"&nbsp;"}</td>
                {/if}
                {if $msgmodule}
                <td><a href="{modurl modname=$msgmodule type='user' func='newpm' uid=$user.uid}"><i class="fa fa-envelope-o fa-lg"></i></a></td>
                {/if}
                {if @isset($dudarray.url)}
                <td>
                    {if $user.attributes.url eq ''}
                    &nbsp;
                    {else}
                    <a href="{$user.attributes.url|safetext}"><i class="fa fa-envelope-o fa-lg" title='{$user.attributes.url}'></i></a>
                    {/if}
                </td>
                {/if}
                {if $adminedit}
                <td>
                    <a href="{modurl modname='Users' type='admin' func='modify' userid=$user.uid}"><i class="fa fa-pencil fa-lg"></i></a>
                    {if $admindelete}
                    <a href="{modurl modname='Users' type='admin' func='deleteusers' userid=$user.uid}"><i class="fa fa-trash-o fa-lg text-danger"></i></a>
                    {/if}
                </td>
                {/if}
            </tr>
            {foreachelse}
            <tr class="warning"><td colspan="7">{gt text='No recently registered users found.'}</td></tr>
            {/foreach}
        </tbody>
    </table>
</div>