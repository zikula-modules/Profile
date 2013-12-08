{gt text='Edit profile' assign='templatetitle'}
{ajaxheader validation=true}

{include file='User/menu.tpl'}

<form id="modifyprofileform" class="z-form" action="{modurl modname=$module type='user' func='update'}" method="post" enctype="application/x-www-form-urlencoded">
	<input type="hidden" id="csrftoken" name="csrftoken" value="{insert name="csrftoken"}" />
	<p>{gt text="Items marked with an asterisk (*) are required entries."}</p>
	{foreach from=$fieldsets key="key" item='fieldset'}
    	<fieldset class="{$key}">
        	<legend>{$fieldset}</legend>
			{foreach from=$duditems item='item'}
				{if ($fieldset == $item.prop_fieldset)}
					{duditemmodify item=$item}
				{/if}
			{/foreach}
		</fieldset>
    {/foreach}
    <div class="z-formbuttons z-buttons">
        {button src='button_ok.png' set='icons/small' __alt='Save' __title='Save' __text='Save'}
        <a href="{modurl modname=$module type='user' func='view'}" title="{gt text="Cancel"}">{img modname='core' src='button_cancel.png' set='icons/small' __alt='Cancel' __title='Cancel'} {gt text="Cancel"}</a>
    </div>
</form>

<script type="text/javascript">
    // <![CDATA[
    var valid = new Validation('modifyprofileform');
    // ]]>
</script>
