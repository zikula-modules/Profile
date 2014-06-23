<?php

/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

use Zikula\Module\ProfileModule\Constant as ProfileConstant;

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * Example:
 *
 * {gravatar email_address='user@example.com'}
 *
 * @see http://gravatar.com/site/implement/images/php/
 *
 * Parameters passed in via the $params array:
 * -------------------------------------------
 * string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * string $email_address The email address.
 * bool $f Force default image. Defaults to FALSE.
 * bool $img TRUE to return a complete IMG tag, FALSE for just the URL.
 * string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * int $s Size in pixels. Defaults to 80px. [ 1 - 2048 ]
 *
 * @param array $params All attributes passed to this function from the template.
 * @param Zikula_View $view Reference to the Zikula_View object.
 *
 * @return string Containing either just a URL or a complete image tag.
 */
function smarty_function_gravatar(array $params = array(), Zikula_View $view)
{
    
    $dom = ZLanguage::getModuleDomain(ProfileConstant::MODNAME);

    $params['d'] = (isset($params['d'])) ? (string)$params['d'] : 'mm';
    $params['email_address'] = (isset($params['email_address'])) ? (string)$params['email_address'] : 'user@example.com';
    $params['f'] = (isset($params['f'])) ? (bool)$params['f'] : false;
    $params['img'] = (isset($params['img'])) ? (bool)$params['img'] : true;
    $params['r'] = (isset($params['r'])) ? (string)$params['r'] : 'g';
    $params['s'] = (isset($params['s'])) ? (int)$params['s'] : 80;

    $result = (System::serverGetVar('HTTPS', 'off') != 'off') ? 'https://secure.gravatar.com/avatar/' : 'http://www.gravatar.com/avatar/';
    $result .= md5(strtolower(trim($params['email_address']))).'.jpg';
    $result .= '?d='.$params['d'].'&amp;r='.$params['r'].'&amp;s='.$params['s'];
    $result .= ($params['f']) ? '&amp;f='.$params['f'] : '';

    if ($params['img']) {
        $result = '<img src="'.$result.'" class="img-thumbnail" alt="'.__('Avatar', $dom).'" />';
    }

    return $result;

}
