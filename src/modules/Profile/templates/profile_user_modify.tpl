{gt text='Edit personal info' assign='templatetitle'}
{ajaxheader validation=true}

{include file='profile_user_menu.htm'}

<form id="modifyprofileform" class="z-form" action="{modurl modname='Profile' type='user' func='update'}" method="post" enctype="application/x-www-form-urlencoded">
    <fieldset>
        <legend>{$templatetitle}</legend>
        <p>{gt text="Items marked with an asterisk ('*') are required entries."}</p>
        <input type="hidden" name="authid" value="{insert name='generateauthkey' module='Profile'}" />
        {foreach from=$duditems item=item}
        {duditemmodify item=$item}
        {/foreach}
    </fieldset>
    <div class="z-formbuttons z-buttons">
        {button src='button_ok.gif' set='icons/small' __alt='Save' __title='Save' __text='Save'}
        <a href="{modurl modname='Profile' func='view'}" title="{gt text="Cancel"}">{img modname='core' src='button_cancel.gif' set='icons/small' __alt='Cancel' __title='Cancel'} {gt text="Cancel"}</a>
    </div>
</form>

<script type="text/javascript">
    // <![CDATA[
    var valid = new Validation('modifyprofileform');
    // ]]>
</script>
