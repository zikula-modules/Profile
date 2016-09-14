// Copyright Zikula Foundation, licensed MIT.

(function($) {
    function showAvatar()
    {
        if ($('#prop_avatar').length > 0 && $('#avatarPreview').length > 0) {
            if ($('#prop_avatar').value() == '') {
                $('#avatarPreview').attr('src' ,'//www.gravatar.com/avatar/b58996c504c5638798eb6b511e6f49af.jpg?d=mm&r=g&s=80&f=1');
            } else {
                $('#avatarPreview').attr('src', Zikula.Config.baseURL + $('#avatarPath').html() + '/' + $('#prop_avatar').val();
            }
        }
    }

    $(document).ready(function() {
        $('#prop_avatar').change(showAvatar);
    });
})(jQuery)
