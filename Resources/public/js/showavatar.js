// Copyright 2011 Zikula Foundation.

function showavatar()
{
    if ($('prop_avatar') && $('youravatardisplay')) {
        if ($('prop_avatar').options[$('prop_avatar').selectedIndex].value == '') {
            $('youravatardisplay').src = '//www.gravatar.com/avatar/b58996c504c5638798eb6b511e6f49af.jpg?d=mm&r=g&s=80&f=1';
        } else {
            $('youravatardisplay').src = Zikula.Config.baseURL + $('youravatarpath').innerHTML + '/' + $('prop_avatar').options[$('prop_avatar').selectedIndex].value;
        }
    }
}
