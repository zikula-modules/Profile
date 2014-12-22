<div class="form-group">
    <label class="col-lg-3 control-label" for="profile_featured_username">{gt text='User name'}</label>
    <div class="col-lg-9">
        <input class="form-control" id="profile_featured_username" type="text" name="username" value="{$username|safehtml}" maxlength="255" />
    </div>
</div>

<div class="form-group">
    <label class="col-lg-3 control-label" for="profile_block_fields">{gt text='Information to show'}</label>
    <div class="col-lg-9" id="profile_block_fields">
        {foreach from=$dudarray key='dud_label' item='dud_display'}
        <div class="checkbox">
            <label for="featured_field_{$dud_label|safetext}">
                <input id="featured_field_{$dud_label|safetext}" type="checkbox" name="fieldstoshow[]" value="{$dud_label|safetext}"{if isset($fieldstoshow.$dud_label)} checked="checked"{/if} />
                {$dud_display|safetext}
            </label>
        </div>
        {/foreach}
    </div>
</div>

<div class="form-group">
    <label class="col-lg-3 control-label" for="profile_featured_regdate">{gt text='Show registration date'}</label>
    <div class="col-lg-9">
        <div class="checkbox">
            <input id="profile_featured_regdate" type="checkbox" name="showregdate" value="1"{if $showregdate} checked="checked"{/if} />
        </div>
    </div>
</div>
