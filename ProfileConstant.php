<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule;

class ProfileConstant
{
    /**
     * Event called when creating the choices array for profile properties
     * Subject is instance of \Zikula\ProfileModule\FormTypesChoices
     */
    const GET_FORM_TYPES_EVENT = 'profile_get_form_types';

    /**
     * the form data prefix
     */
    const FORM_BLOCK_PREFIX = 'zikulaprofilemodule_editprofile';

    /**
     * the name of the attribute to obtain a realname. requires prefix + ':'
     */
    const ATTRIBUTE_NAME_DISPLAY_NAME = 'realname';

    /**
     * Module variable key for the avatar image path.
     */
    const MODVAR_AVATAR_IMAGE_PATH = 'avatarpath';

    /**
     * Default value for the avatar image path.
     */
    const DEFAULT_AVATAR_IMAGE_PATH = 'images/avatar';

    /**
     * Module variable key for the flag indicating whether gravatars are allowed or not.
     */
    const MODVAR_GRAVATARS_ENABLED = 'allowgravatars';

    /**
     * Default value for the flag indicating whether gravatars are allowed or not.
     */
    const DEFAULT_GRAVATARS_ENABLED = true;

    /**
     * Module variable key for the file name containing the generic gravatar image.
     */
    const MODVAR_GRAVATAR_IMAGE = 'gravatarimage';

    /**
     * Default value for the file name containing the generic gravatar image.
     */
    const DEFAULT_GRAVATAR_IMAGE = 'gravatar.jpg';
}
