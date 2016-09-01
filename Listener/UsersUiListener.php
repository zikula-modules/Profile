<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Listener;

use DataUtil;
use ModUtil;
use UserUtil;
use Zikula\ProfileModule\Constant as ProfileConstant;
use Zikula\Core\Hook\ValidationResponse;
use Zikula_View;
use ZLanguage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Zikula\Core\Event\GenericEvent;

/**
 * Hook-like event handlers for basic profile data.
 */
class UsersUiListener implements EventSubscriberInterface
{
    /**
     * The area name that this handler processes.
     */
    const EVENT_KEY = 'module.profile.users_ui_handler';

    /**
     * The language domain for ZLanguage i18n.
     *
     * @var string|null
     */
    protected $domain = null;

    /**
     * Access to a Zikula_View instance for the Profile module.
     *
     * @var Zikula_View
     */
    protected $view;

    /**
     * Access to the request information.
     *
     * @var \Zikula_Request_Http
     */
    protected $request;

    /**
     * The validation object instance used when validating information entered during an edit phase.
     *
     * @var ValidationResponse
     */
    protected $validation;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->domain = ZLanguage::getModuleDomain(ProfileConstant::MODNAME);
    }

    public function getView()
    {
        if (!$this->view) {
            $this->view = Zikula_View::getInstance(ProfileConstant::MODNAME);
        }
        return $this->view;
    }

    public static function getSubscribedEvents()
    {
        return [
            'module.users.ui.display_view' => ['uiView'],
            'module.users.ui.form_edit.new_user' => ['uiEdit'],
            'module.users.ui.form_edit.modify_user' => ['uiEdit'],
            'module.users.ui.form_edit.new_registration' => ['uiEdit'],
            'module.users.ui.form_edit.modify_registration' => ['uiEdit'],
            'module.users.ui.validate_edit.new_user' => ['validateEdit'],
            'module.users.ui.validate_edit.modify_user' => ['validateEdit'],
            'module.users.ui.validate_edit.new_registration' => ['validateEdit'],
            'module.users.ui.validate_edit.modify_registration' => ['validateEdit'],
            'module.users.ui.process_edit.new_user' => ['processEdit'],
            'module.users.ui.process_edit.modify_user' => ['processEdit'],
            'module.users.ui.process_edit.new_registration' => ['processEdit'],
            'module.users.ui.process_edit.modify_registration' => ['processEdit']
        ];
    }

    /**
     * Render and return profile information for display as part of a hook-like UI event issued from the Users module.
     *
     * @param GenericEvent $event The event that triggered this function call, including the subject of the display request.
     *
     * @return void
     */
    public function uiView(GenericEvent $event)
    {
        $items = ModUtil::apiFunc(ProfileConstant::MODNAME, 'user', 'getallactive');
        // The return value of the function is checked here
        if ($items) {
            $user = $event->getSubject();
            // Create output object
            $this->getView()
                ->setCaching(false)
                ->assign('duditems', $items)
                ->assign('userinfo', $user);
            // Return the dynamic data rows
            $event->data[self::EVENT_KEY] = $this->getView()->fetch('profile_profile_ui_view.tpl');
        }
    }

    /**
     * Render form elements for display that allow a user to enter profile information for a user account as part of a
     * Users module hook-like UI event.
     *
     * Parameters passed in via POST:
     * ------------------------------
     * array dynadata If reentering the editing phase after validation errors, an array containing the profile items to
     *                  store for the user; otherwise not provided.
     *
     * @param GenericEvent $event The event that triggered this function call, including the id of the user for which
     *                            profile items should be entered.
     *
     * @return void
     */
    public function uiEdit(GenericEvent $event)
    {
        $items = ModUtil::apiFunc(ProfileConstant::MODNAME, 'user', 'getallactive', ['get' => 'editable']);
        // The return value of the function is checked here
        if ($items) {
            $fieldsets = [];
            foreach ($items as $propattr => $propdata) {
                $items[$propattr]['prop_fieldset'] = ((isset($items[$propattr]['prop_fieldset'])) && (!empty($items[$propattr]['prop_fieldset']))) ? $items[$propattr]['prop_fieldset'] : __('User Information', $this->domain);
                $fieldsets[DataUtil::formatPermalink($items[$propattr]['prop_fieldset'])] = $items[$propattr]['prop_fieldset'];
            }
            // check if there's a user to edit
            // or uses uid=1 to pull the default values from the anonymous user
            $userid = $event->hasArgument('id') ? $event->getArgument('id') : null;
            if (!isset($userid)) {
                $userid = 1;
            }
            // Get the dynamic data that might have been posted
            if ($this->request->isMethod('POST') && $this->request->request->has('dynadata')) {
                $dynadata = $this->request->request->get('dynadata');
            } else {
                $dynadata = [];
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
                $errorFields = [];
            }
            $this->getView()
                ->setCaching(false)
                ->assign('duderrors', $errorFields)
                ->assign('duditems', $items)
                ->assign('fieldsets', $fieldsets)
                ->assign('userid', $userid);
            $content = $this->getView()->fetch('profile_profile_ui_edit.tpl');
            $event->data[self::EVENT_KEY] = $content;
        }
    }

    /**
     * Validate profile information entered for a user as part of the hook-like user UI events.
     *
     * Parameters passed in via POST:
     * ------------------------------
     * array dynadata An array containing the profile items to store for the user.
     *
     * @param GenericEvent $event The event that triggered this function call, including the id of the user for which
     *                            profile data was entered, and a collection in which to store the validation object
     *                            created by this function.
     *
     * @return void
     */
    public function validateEdit(GenericEvent $event)
    {

        if ($this->request->isMethod('POST')) {
            $dynadata = $this->request->request->has('dynadata') ? $this->request->request->get('dynadata') : [];
            $this->validation = new ValidationResponse('dynadata', $dynadata);
            $requiredFailures = ModUtil::apiFunc(ProfileConstant::MODNAME, 'user', 'checkrequired', [
                'dynadata' => $dynadata
            ]);
            
            $errorCount = 0;
    
            if (($requiredFailures) && ($requiredFailures['result'])) {
                foreach ($requiredFailures['fields'] as $key => $fieldName) {
                    $this->validation->addError($fieldName, __f(
                        'The \'%1$s\' field is required.',
                        [$requiredFailures['translatedFields'][$key]],
                        $this->domain)
                    );

                    $errorCount++;
                }
            }

            if ($errorCount > 0) {
                $this->request
                    ->getSession()
                    ->getFlashBag()
                    ->add('error', _fn(
                        'There was a problem with one of the personal information fields.',
                        'There were problems with %d personal information fields.',
                        $errorCount,
                        [$errorCount],
                        $this->domain)
                    );
            }

            $event->data->set(self::EVENT_KEY, $this->validation);
        }

    }

    /**
     * Respond to a `module.users.ui.process_edit` event to store profile data gathered when editing or creating a user account.
     *
     * Parameters passed in via POST:
     * ------------------------------
     * array dynadata An array containing the profile items to store for the user.
     *
     * @param GenericEvent $event The event that triggered this function call, containing the id of the user for which
     *                            profile information should be stored.
     *
     * @return void
     */
    public function processEdit(GenericEvent $event)
    {
        if ($this->request->isMethod('POST')) {
            if ($this->validation && !$this->validation->hasErrors()) {
                $user = $event->getSubject();
                $dynadata = $this->request->request->has('dynadata') ? $this->request->request->get('dynadata') : [];
                foreach ($dynadata as $dudName => $dudItem) {
                    UserUtil::setVar($dudName, $dudItem, $user['uid']);
                }
            }
        }
    }

}
