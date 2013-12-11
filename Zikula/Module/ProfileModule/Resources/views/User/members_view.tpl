{gt text='Registered Users' assign='templatetitle'}

{include file='User/menu.tpl'}

<form id="profile-search" class="form-horizontal" action="{modurl modname=$module type='user' func='viewmembers'}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
        <input type="hidden" id="csrftoken" name="csrftoken" value="{insert name="csrftoken"}" />
        <div class="well">
            <div class="form-group">
                <div class="col-lg-3 control-label">
                    <label for="profile_letter" class="profile_letter">{gt text='Search'}</label>
                </div>
                <div class="col-lg-9">
                    <span class="col-lg-4"><input class="form-control" id="profile_letter" type="text" name="letter" value="" maxlength="50" /></span>
                    <span class="col-lg-8"><input class="btn btn-success" type="submit" value="{gt text='Submit'}" /></span>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-9">
                    <div class="radio">
                        <label for="profile_nickname">
                            <input id="profile_nickname" type="radio" name="searchby" value="uname" checked="checked" />
                            {gt text='Search in User Names'}
                        </label>
                    </div>
                    {if isset($dudarray.realname)}
                    <div class="radio">
                        <label for="profile_realname">
                            <input id="profile_realname" type="radio" name="searchby" value="{$dudarray.realname}" />
                            {gt text='Search in Real Names'}
                        </label>
                    </div>
                    {/if}
                    {if isset($dudarray.url)}
                    <div class="radio">
                        <label for="profile_url">
                            <input id="profile_url" type="radio" name="searchby" value="{$dudarray.url}" />
                            {gt text='Search in Site'}
                        </label>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
    <div id="profile-alphafilter" class="text-center">
        {pagerabc posvar="letter" forwardvars='sortby' printempty=true}
    </div>
</form>

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{gt text='User Name'}</th>
                {if isset($dudarray.realname)}
                <th>{gt text='Real name'}</th>
                {/if}
                {if $msgmodule}
                <th>{gt text='Messages'}</th>
                {/if}
                {if isset($dudarray.url)}
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
                {if isset($dudarray.realname)}
                <td>{if isset($user.attributes) && isset($user.attributes.realname)}{$user.attributes.realname|safetext|default:"&nbsp;"}{else}&nbsp;{/if}</td>
                {/if}
                {if $msgmodule}
                <td><a href="{modurl modname=$msgmodule type='user' func='newpm' uid=$user.uid}"><i class="fa fa-envelope-o fa-lg"></i></a></td>
                {/if}
                {if isset($dudarray.url)}
                <td>
                    {if !(isset($user.attributes) && isset($user.attributes.url)) || ($user.attributes.url == '')}
                    &nbsp;
                    {else}
                    <a href="{$user.attributes.url|safetext}" rel="nofollow"><i class="fa fa-globe fa-lg" title='{$user.attributes.url}'></i></a>
                    {/if}
                </td>
                {/if}
                {if $adminedit}
                <td>
                    <a href="{modurl modname=Users type=admin func=modify userid=$user.uid}"><i class="fa fa-pencil fa-lg"></i></a>
                    {if $admindelete}
                    <a href="{modurl modname=Users type=admin func=deleteusers userid=$user.uid}"><i class="fa fa-trash-o fa-lg text-danger"></i></a>
                    {/if}
                </td>
                {/if}
            </tr>
            {foreachelse}
            <tr class="danger"><td colspan="6">{gt text='No users found.'}</td></tr>
            {/foreach}
        </tbody>
    </table>
</div>

{pager rowcount=$pager.numitems limit=$pager.itemsperpage posvar='startnum' shift=1}

<h3>{gt text='Statistics'}</h3>
<ul id="profile_status">
    <li><strong>{gt text='Registered:'} </strong>{$memberslistreg|safetext}</li>
    <li><strong>{gt text='Online:'} </strong><a href="{modurl modname=$module type='user' func='onlinemembers'}">{$memberslistonline}</a></li>
    <li><strong>{gt text='Newest User:'} </strong><a href="{modurl modname=$module type='user' func='view' uname=$memberslistnewest}">{$memberslistnewest}</a></li>
</ul>