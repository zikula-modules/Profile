{gt text='Users Online' assign='templatetitle'}
{include file='User/menu.tpl'}

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{gt text='User Name'}</th>
                <th>{gt text='Real Name'}</th>
                {if $msgmodule}
                <th>{gt text='Messages'}</th>
                {/if}
                <th>{gt text='Site'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$users item='user'}
            <tr>
                <td><strong>{$user.uname|profilelinkbyuname}</strong></td>
                <td>{$user.__ATTRIBUTES__.realname|default:'&nbsp;'}</td>
                {if $msgmodule}
                <td><a href="{modurl modname=$msgmodule type='user' func='newpm' uid=$user.uid}"><i class="fa fa-envelope-o fa-lg"></i></a></td>
                {/if}
                <td>
                    {if @isset($user.__ATTRIBUTES__.url) and $user.__ATTRIBUTES__.url neq '' and $user.__ATTRIBUTES__.url neq 'http://'}
                    <a href="{$user.__ATTRIBUTES__.url|safetext}"><i class="fa fa-envelope-o fa-lg" title='{$user.__ATTRIBUTES__.url}'></i></a>
                    {else}
                    &nbsp;
                    {/if}
                </td>
            </tr>
            {foreachelse}
            <tr class="warning"><td colspan="4">{gt text='No registered users are currently online.'}</td></tr>
            {/foreach}
        </tbody>
    </table>
</div>