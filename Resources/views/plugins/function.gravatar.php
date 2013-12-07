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

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * Example:
 *
 * {gravatar email_address='user@example.com'}
 *
 * @source http://gravatar.com/site/implement/images/php/
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $email_address The email address.
 * @param bool $f Force default image. Defaults to FALSE.
 * @param bool $img TRUE to return a complete IMG tag, FALSE for just the URL.
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param int $s Size in pixels. Defaults to 80px. [ 1 - 2048 ]
 * @return string Containing either just a URL or a complete image tag.
 */
function smarty_function_gravatar(array $params = array(), Zikula_View $view)
{

	$dom = ZLanguage::getModuleDomain('Profile');

	if (!isset($params['email_address'])) {
		return false;
	}
    
    $params['d'] = (isset($params['d'])) ? (string)$params['d'] : 'mm';
    $params['f'] = (isset($params['f'])) ? (bool)$params['f'] : false;
	$params['img'] = (isset($params['img'])) ? (bool)$params['img'] : true;
    $params['r'] = (isset($params['r'])) ? (string)$params['r'] : 'g';
    $params['s'] = (isset($params['s'])) ? (int)$params['s'] : 80;
     
    $result  = (System::serverGetVar('HTTPS', 'off') != 'off') ? 'https://secure.gravatar.com/avatar/' : 'http://www.gravatar.com/avatar/';
    $result .= md5(strtolower(trim($params['email_address']))).'.jpg';
    $result .= '?d='.$params['d'].'&amp;r='.$params['r'].'&amp;s='.$params['s'];
    $result .= ($params['f']) ? '&amp;f='.$params['f'] : '';
    
    if ($params['img']) {
        $result = '<img src="'.$result.'" class="profileavatar" alt="'.__('Avatar', $dom).'" />';
    }
    
    return $result;

}