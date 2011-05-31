<?php
/**
 * Copyright Zikula Foundation 2011 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Profile
 * @subpackage HookHandler
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * Hook handlers for basic profile data.
 */
class Profile_Listener_ProfileProvider extends Zikula_AbstractEventHandler
{
    /**
     * The area name that this handler processes.
     */
    const EVENT_KEY = 'profile.users.ui.profile';

    /**
     * The common module name.
     *
     * @var string
     */
    protected $name = Profile_Constant::MODNAME;

    /**
     * Access to a Zikula_View instance for the Profile module.
     *
     * @var Zikula_View
     */
    protected $view;

    /**
     * Access to the request information.
     *
     * @var Zikula_Request_Http
     */
    protected $request;

    protected $validation;

    /**
     * Builds an instance of this class.
     *
     * Cannot have parameters.
     */
    public function __construct(Zikula_EventManager $eventManager)
    {
        parent::__construct($eventManager);
        $this->serviceManager = $eventManager->getServiceManager();
        $this->view = Zikula_View::getInstance($this->name);
        $this->request = $this->serviceManager->getService('request');
    }

    public function setupHandlerDefinitions()
    {
        $this->addHandlerDefinition('users.user.display_view', 'uiView');
        $this->addHandlerDefinition('users.user.form_edit', 'uiEdit');
        $this->addHandlerDefinition('users.user.validate_edit', 'validateEdit');
        $this->addHandlerDefinition('users.user.process_edit', 'processEdit');
    }

    public function uiView(Zikula_Event $event)
    {
        $items = ModUtil::apiFunc('Profile', 'user', 'getallactive');

        // The return value of the function is checked here
        if ($items) {
            $user = $event->getSubject();

            // Create output object
            $this->view->setCaching(false)
                    ->assign('duditems', $items)
                    ->assign('userinfo', $user);

            // Return the dynamic data rows
            $event->data[self::EVENT_KEY] = new Zikula_Response_DisplayHook(self::EVENT_KEY, $this->view, 'profile_profile_ui_view.tpl');
        }
    }

    public function uiEdit(Zikula_Event $event)
    {
        $items = ModUtil::apiFunc('Profile', 'user', 'getallactive', array('get' => 'editable'));

        // The return value of the function is checked here
        if ($items) {
            // check if there's a user to edit
            // or uses uid=1 to pull the default values from the annonymous user
            $userid   = $event->hasArg('id') ? $event->getArg('id') : null;

            if (!isset($userid)) {
                $userid = 1;
            }

            // Get the dynamic data that might have been posted
            if ($this->request->isPost() && $this->request->getPost()->has('dynadata')) {
                $dynadata = $this->request->getPost()->get('dynadata');
            } else {
                $dynadata = array();
            }

            // merge this temporary dynadata and the errors into the items array
            foreach ($items as $prop_label => $item) {
                foreach ($dynadata as $propname => $propdata) {
                    if ($item['prop_attribute_name'] == $propname) {
                        $items[$prop_label]['temp_propdata'] = $propdata;
                    }
                }
            }

            if ($this->validation) {
                $errorFields = $this->validation->getErrors();
            } else {
                $errorFields = array();
            }

            $this->view->setCaching(false)
                    ->assign('duderrors', $errorFields)
                    ->assign('duditems', $items)
                    ->assign('userid', $userid);

            $event->data[self::EVENT_KEY] = new Zikula_Response_DisplayHook(self::EVENT_KEY, $this->view, 'profile_profile_ui_edit.tpl');
        }
    }

    public function validateEdit(Zikula_Event $event)
    {
        if ($this->request->isPost()) {
            $dynadata = $this->request->getPost()->has('dynadata') ? $this->request->getPost()->get('dynadata') : array();

            $this->validation = new Zikula_Hook_ValidationResponse('dynadata', $dynadata);
            $requiredFailures = ModUtil::apiFunc('Profile', 'user', 'checkrequired', array('dynadata' => $dynadata));

            $errorCount = 0;
            if ($requiredFailures && $requiredFailures['result']) {
                foreach ($requiredFailures['fields'] as $key => $fieldName) {
                    $this->validation->addError($fieldName, __f('The \'%1$s\' field is required.', array($requiredFailures['translatedFields'][$key]), $this->domain));
                    $errorCount++;
                }
            }

            if ($errorCount > 0) {
                LogUtil::registerError(_fn('There was a problem with one of the personal information fields.', 'There were problems with %1$d personal information fields.', $errorCount, array($errorCount), $this->domain));
            }

            $event->data->set(self::EVENT_KEY, $this->validation);
        }
    }

    public function processEdit(Zikula_Event $event)
    {
        if ($this->request->isPost()) {
            if ($this->validation && !$this->validation->hasErrors()) {
                $user = $event->getSubject();
                $dynadata = $this->request->getPost()->has('dynadata') ? $this->request->getPost()->get('dynadata') : array();

                foreach ($dynadata as $dudName => $dudItem) {
                    UserUtil::setVar($dudName, $dudItem, $user['uid']);
                }
            }
        }
    }
}
