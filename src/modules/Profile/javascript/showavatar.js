// Copyright 2011 Zikula Foundation.

function showavatar()
{
    if ($('prop_avatar') && $('youravatardisplay')) {
        $('youravatardisplay').src = document.location.pnbaseURL + $('youravatarpath').innerHTML + '/' + $('prop_avatar').options[$('prop_avatar').selectedIndex].value;
    }
}
