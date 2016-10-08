<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Controller;

use ModUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Response\Ajax\FatalResponse;
use Zikula\Core\Response\Ajax\BadDataResponse;
use Zikula\Core\Response\Ajax\AjaxResponse;
use Zikula\Core\Response\Ajax\NotFoundResponse;
use Zikula\Core\Controller\AbstractController;

/**
 * Class AjaxController
 * @Route("/ajax")
 */
class AjaxController extends AbstractController
{
    /**
     * @Route("/changeweight", options={"expose"=true})
     * 
     * Change the weight of a profile item.
     *
     * @param Request $request
     *
     * Parameters passed in via POST, or via GET:
     * ------------------------------------------
     * array   profilelist An array of dud item ids for which the weight should be changed.
     * numeric startnum    The desired weight of the first item in the list minus 1 (e.g., if the weight of the first item should be 3 then startnum contains 2)
     *
     * @return AjaxResponse|BadDataResponse
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     */
    public function changeprofileweightAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $profilelist = $request->get('profilelist', null);
        $startnum = $request->get('startnum', null);
        if ($startnum < 0) {
            return new BadDataResponse([], $this->__f('Error! Invalid \'%s\' passed.', ['%s' => 'startnum']));
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');

        // update the items with the new weights
        $props = [];
        $weight = $startnum + 1;
        parse_str($profilelist);
        foreach ($profilelist as $prop_id) {
            if (empty($prop_id)) {
                continue;
            }
            $props[$prop_id] = $entityManager->find('ZikulaProfileModule:PropertyEntity', $prop_id);
            $props[$prop_id]->setProp_weight($weight);
            $weight++;
        }
        // update the db
        $entityManager->flush();

        return new AjaxResponse(['result' => true]);
    }

    /**
     * @Route("/changestatus", options={"expose"=true})
     *
     * Change the status of a profile item.
     *
     * @param Request $request
     *
     * Parameters passed in via POST, or via GET:
     * ------------------------------------------
     * numeric dudid     Id of the property to update.
     * boolean oldstatus True to activate or false to deactivate the item.
     *
     * @return AjaxResponse|NotFoundResponse|BadDataResponse
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     */
    public function changeprofilestatusAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $prop_id = $request->get('dudid', null);
        $oldstatus = (bool)$request->get('oldstatus', null);
        if (!$prop_id) {

            return new NotFoundResponse(['result' => false]);
        }
        // update the item status
        $func = $oldstatus ? 'deactivate' : 'activate';
        $res = ModUtil::apiFunc('ZikulaProfileModule', 'admin', $func, ['dudid' => $prop_id]);
        if (!$res) {
            return new FatalResponse($this->__('Error! Could not save your changes.'));
        }

        return new AjaxResponse(['result' => true, 'dudid' => $prop_id, 'newstatus' => !$oldstatus]);
    }

    /**
     * @Route("/section", options={"expose"=true})
     *
     * Get a profile section for a user.
     *
     * @param Request $request
     *
     * Parameters passed in via POST, or via GET:
     * ------------------------------------------
     * numeric uid  Id of the user to query.
     * string  name Name of the section to retrieve.
     * array   args Optional arguments to the API.
     *
     * @return AjaxResponse|NotFoundResponse|BadDataResponse
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     */
    public function profilesectionAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_READ)) {
            throw new AccessDeniedException();
        }

        $uid = $request->get('uid', null);
        $name = $request->get('name', null);
        $args = $request->get('args', null);
        if (empty($uid) || !is_numeric($uid) || empty($name)) {
            return new NotFoundResponse(['result' => false]);
        }
        if (empty($args) || !is_array($args)) {
            $args = [];
        }

        // update the item status
        $section = ModUtil::apiFunc('ZikulaProfileModule', 'section', $name, array_merge($args, ['uid' => $uid]));
        if (!$section) {
            return new FatalResponse($this->__('Error! Could not load the section.'));
        }

        // build the output
        $view = \Zikula_View::getInstance('ZikulaProfileModule', \Zikula_View::CACHE_DISABLED);

        // check the template existence
        $template = 'sections/profile_section_' . $name . '.tpl';
        if (!$view->template_exists($template)) {
            return new NotFoundResponse(['result' => false]);
        }

        // assign and render the output
        $view->assign('section', $section);

        return new AjaxResponse([
            'result' => $view->fetch($template, $uid),
            'name' => $name,
            'uid' => $uid
        ]);
    }
}
