<?php
/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\ProfileModule\Controller;

use SecurityUtil;
use ModUtil;
use Symfony\Component\HttpFoundation\Request;
use Zikula\Core\Response\Ajax\FatalResponse;
use Zikula\Core\Response\Ajax\ForbiddenResponse;
use Zikula\Core\Response\Ajax\BadDataResponse;
use Zikula\Core\Response\Ajax\AjaxResponse;
use Zikula\Core\Response\Ajax\NotFoundResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route; // used in annotations - do not remove
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method; // used in annotations - do not remove
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/ajax")
 * 
 * AJAX query and response functions.
 * 
 * Class AjaxController
 * @package Zikula\ProfileModule\Controller
 */
class AjaxController extends \Zikula_Controller_AbstractAjax
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
     * @return AjaxResponse|ForbiddenResponse|BadDataResponse
     */
    public function changeprofileweightAction(Request $request)
    {
        $this->checkAjaxToken();
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN)) {

            return new ForbiddenResponse($this->__('Sorry! You do not have authorisation for this module.'));
        }
        $profilelist = $request->get('profilelist', null);
        $startnum = $request->get('startnum', null);
        if ($startnum < 0) {

            return new BadDataResponse(array(), $this->__f('Error! Invalid \'%s\' passed.', 'startnum'));
        }
        // update the items with the new weights
        $props = array();
        $weight = $startnum + 1;
        parse_str($profilelist);
        foreach ($profilelist as $prop_id) {
            if (empty($prop_id)) {
                continue;
            }
            $props[$prop_id] = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $prop_id);
            $props[$prop_id]->setProp_weight($weight);
            $weight++;
        }
        // update the db
        $this->entityManager->flush();

        return new AjaxResponse(array('result' => true));
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
     * @return AjaxResponse|NotFoundResponse|ForbiddenResponse|BadDataResponse
     */
    public function changeprofilestatusAction(Request $request)
    {
        $this->checkAjaxToken();
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN)) {

            return new ForbiddenResponse($this->__('Sorry! You do not have authorisation for this module.'));
        }
        $prop_id = $request->get('dudid', null);
        $oldstatus = (bool)$request->get('oldstatus', null);
        if (!$prop_id) {

            return new NotFoundResponse(array('result' => false));
        }
        // update the item status
        $func = $oldstatus ? 'deactivate' : 'activate';
        $res = ModUtil::apiFunc($this->name, 'admin', $func, array('dudid' => $prop_id));
        if (!$res) {

            return new FatalResponse($this->__('Error! Could not save your changes.'));
        }

        return new AjaxResponse(array('result' => true, 'dudid' => $prop_id, 'newstatus' => !$oldstatus));
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
     * @return AjaxResponse|NotFoundResponse|ForbiddenResponse|BadDataResponse
     */
    public function profilesectionAction(Request $request)
    {
        $this->checkAjaxToken();
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_READ)) {

            return new ForbiddenResponse($this->__('Sorry! You do not have authorisation for this module.'));
        }
        $uid = $request->get('uid', null);
        $name = $request->get('name', null);
        $args = $request->get('args', null);
        if (empty($uid) || !is_numeric($uid) || empty($name)) {

            return new NotFoundResponse(array('result' => false));
        }
        if (empty($args) || !is_array($args)) {
            $args = array();
        }
        // update the item status
        $section = ModUtil::apiFunc($this->name, 'section', $name, array_merge($args, array('uid' => $uid)));
        if (!$section) {

            return new FatalResponse($this->__('Error! Could not load the section.'));
        }
        // build the output
        $this->view->setCaching(false);
        // check the template existence
        $template = "sections/profile_section_{$name}.tpl";
        if (!$this->view->template_exists($template)) {

            return new NotFoundResponse(array('result' => false));
        }
        // assign and render the output
        $this->view->assign('section', $section);

        return new AjaxResponse(array('result' => $this->view->fetch($template, $uid), 'name' => $name, 'uid' => $uid));
    }

}
