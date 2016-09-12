<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Smarty function to display a section of the user profile.
 *
 * Example
 * {profilesection name='ezcomments'}
 *
 * Parameters passed in via the $params array:
 * -------------------------------------------
 * numeric uid  The user account id of the user for which this profile section should be displayed.
 * string  name Section name to render.
 *
 * @param array $params All parameters passed to this section from the template.
 * @param Zikula_View $view Reference to the Zikula_View object.
 *
 * @return string|boolean The rendered section; empty string if the section is not defined; false if error.
 */
function smarty_function_profilesection($params, Zikula_View $view)
{
    // validation
    if (!isset($params['name']) || empty($params['name'])) {
        $view->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', ['profilesection', 'name']));
        return false;
    }
    if (!isset($params['uid']) || empty($params['uid'])) {
        $params['uid'] = $view->get_template_vars('uid');
        if (empty($params['uid'])) {
            $view->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', ['profilesection', 'uid']));
            return false;
        }
    }

    $params['name'] = strtolower($params['name']);

    // extract the items to list
    $section = ModUtil::apiFunc('ZikulaProfileModule', 'section', $params['name'], $params);
    if ($section === false) {
        return '';
    }

    // build the output
    $view->setCaching(Zikula_View::CACHE_DISABLED);

    // check the template existance
    $template = 'sections/profile_section_' . $params['name'] . '.tpl';

    if (!$view->template_exists($template)) {
        return '';
    }

    // assign and render the output
    $view->assign('section', $section);

    return $view->fetch($template, $params['uid']);
}
