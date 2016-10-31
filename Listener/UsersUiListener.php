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

use ModUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig_Environment;
use UserUtil;
use Zikula\Bundle\HookBundle\Hook\ValidationResponse;
use Zikula\Common\Translator\TranslatorInterface;
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
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Access to the request information.
     *
     * @var Request
     */
    protected $request;

    /**
     * The validation object instance used when validating information entered during an edit phase.
     *
     * @var ValidationResponse
     */
    protected $validation;

    /**
     * Constructor.
     *
     * @param KernelInterface     $kernel       KernelInterface service instance
     * @param TranslatorInterface $translator   TranslatorInterface service instance
     * @param RequestStack        $requestStack RequestStack service instance
     * @param Twig_Environment    $twig         Twig_Environment service instance
     */
    public function __construct(KernelInterface $kernel, TranslatorInterface $translator, RequestStack $requestStack, Twig_Environment $twig)
    {
        $this->kernel = $kernel;
        $this->translator = $translator;
        $this->request = $requestStack->getCurrentRequest();
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [
            'module.users.ui.display_view'                      => ['uiView'],
            'module.users.ui.form_edit.new_user'                => ['uiEdit'],
            'module.users.ui.form_edit.modify_user'             => ['uiEdit'],
            'module.users.ui.form_edit.new_registration'        => ['uiEdit'],
            'module.users.ui.form_edit.modify_registration'     => ['uiEdit'],
            'module.users.ui.validate_edit.new_user'            => ['validateEdit'],
            'module.users.ui.validate_edit.modify_user'         => ['validateEdit'],
            'module.users.ui.validate_edit.new_registration'    => ['validateEdit'],
            'module.users.ui.validate_edit.modify_registration' => ['validateEdit'],
            'module.users.ui.process_edit.new_user'             => ['processEdit'],
            'module.users.ui.process_edit.modify_user'          => ['processEdit'],
            'module.users.ui.process_edit.new_registration'     => ['processEdit'],
            'module.users.ui.process_edit.modify_registration'  => ['processEdit'],
        ];
    }

    /**
     * Render and return profile information for display as part of a hook-like UI event issued from the Users module.
     *
     * @param GenericEvent $event The event that triggered this function call, including the subject of the display request
     *
     * @return void
     */
    public function uiView(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }

        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
        if (!$items) {
            return;
        }

        $user = $event->getSubject();

        $event->data[self::EVENT_KEY] = $this->twig->render('@ZikulaProfileModule/UsersUi/profile_ui_view.html.twig', [
            'dudItems' => $items,
            'userInfo' => $user,
        ]);
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
     *                            profile items should be entered
     *
     * @return void
     */
    public function uiEdit(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }

        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive', ['get' => 'editable']);
        if (!$items) {
            return;
        }

        $fieldSets = [];
        foreach ($items as $propattr => $propdata) {
            $fieldSet = (isset($propdata['prop_fieldset']) && !empty($propdata['prop_fieldset'])) ? $propdata['prop_fieldset'] : $this->translator->__('User Information');
            $items[$propattr]['prop_fieldset'] = $fieldSet;
            $fieldSets[$fieldSet] = $fieldSet;
        }

        // check if there's a user to edit
        // or uses uid=1 to pull the default values from the anonymous user
        $userId = $event->hasArgument('id') ? $event->getArgument('id') : null;
        if (!isset($userId)) {
            $userId = 1;
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

        $errorFields = $this->validation ? $this->validation->getErrors() : [];

        $event->data[self::EVENT_KEY] = $this->twig->render('@ZikulaProfileModule/UsersUi/profile_ui_edit.html.twig', [
            'dudItems'  => $items,
            'dudErrors' => $errorFields,
            'fieldSets' => $fieldSets,
            'userId'    => $userId,
        ]);
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
     *                            created by this function
     *
     * @return void
     */
    public function validateEdit(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }

        if (!$this->request->isMethod('POST')) {
            return;
        }

        $dynadata = $this->request->request->has('dynadata') ? $this->request->request->get('dynadata') : [];
        $this->validation = new ValidationResponse('dynadata', $dynadata);
        $requiredFailures = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'checkrequired', [
            'dynadata' => $dynadata,
        ]);

        $errorCount = 0;

        if ($requiredFailures && $requiredFailures['result']) {
            foreach ($requiredFailures['fields'] as $key => $fieldName) {
                $this->validation->addError($fieldName,
                    $this->translator->__f('The \'%s\' field is required.', ['%s' => $requiredFailures['translatedFields'][$key]])
                );

                $errorCount++;
            }
        }

        if ($errorCount > 0) {
            $this->request->getSession()->getFlashBag()->add('error',
                $this->translator->_fn(
                    'There was a problem with one of the personal information fields.',
                    'There were problems with %d personal information fields.',
                    $errorCount,
                    ['%d' => $errorCount]
                )
            );
        }

        $event->data->set(self::EVENT_KEY, $this->validation);
    }

    /**
     * Respond to a `module.users.ui.process_edit` event to store profile data gathered when editing or creating a user account.
     *
     * Parameters passed in via POST:
     * ------------------------------
     * array dynadata An array containing the profile items to store for the user.
     *
     * @param GenericEvent $event The event that triggered this function call, containing the id of the user for which
     *                            profile information should be stored
     *
     * @return void
     */
    public function processEdit(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }

        if (!$this->request->isMethod('POST')) {
            return;
        }

        if ($this->validation && !$this->validation->hasErrors()) {
            $user = $event->getSubject();
            $dynadata = $this->request->request->has('dynadata') ? $this->request->request->get('dynadata') : [];
            foreach ($dynadata as $dudName => $dudItem) {
                UserUtil::setVar($dudName, $dudItem, $user['uid']);
            }
        }
    }
}
