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

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Environment;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\Bundle\HookBundle\Hook\ValidationResponse;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\Event\GenericEvent;
use Zikula\ProfileModule\Form\ProfileTypeFactory;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\RegistrationEvents;
use Zikula\UsersModule\UserEvents;

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
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var ProfileTypeFactory
     */
    private $formFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * The validation object instance used when validating information entered during an edit phase.
     *
     * @var ValidationResponse
     */
    protected $validation;

    /**
     * Constructor.
     *
     * @param ZikulaHttpKernelInterface $kernel
     * @param UserRepositoryInterface $userRepository
     * @param ProfileTypeFactory $factory
     * @param TranslatorInterface $translator
     * @param Twig_Environment $twig
     * @param RegistryInterface $registry
     * @param RequestStack $requestStack
     */
    public function __construct(
        ZikulaHttpKernelInterface $kernel,
        UserRepositoryInterface $userRepository,
        ProfileTypeFactory $factory,
        TranslatorInterface $translator,
        Twig_Environment $twig,
        RegistryInterface $registry,
        RequestStack $requestStack
    )
    {
        $this->kernel = $kernel;
        $this->userRepository = $userRepository;
        $this->formFactory = $factory;
        $this->translator = $translator;
        $this->twig = $twig;
        $this->doctrine = $registry;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::DISPLAY_VIEW => ['uiView'],
            UserEvents::NEW_FORM => ['uiEdit'],
            UserEvents::MODIFY_FORM => ['uiEdit'],
            RegistrationEvents::NEW_FORM => ['uiEdit'],
            RegistrationEvents::MODIFY_FORM => ['uiEdit'],
            UserEvents::NEW_VALIDATE => ['validateEdit'],
            UserEvents::MODIFY_VALIDATE => ['validateEdit'],
            RegistrationEvents::NEW_VALIDATE => ['validateEdit'],
            RegistrationEvents::MODIFY_VALIDATE => ['validateEdit'],
            UserEvents::NEW_VALIDATE => ['processEdit'],
            UserEvents::MODIFY_PROCESS => ['processEdit'],
            RegistrationEvents::NEW_PROCESS => ['processEdit'],
            RegistrationEvents::MODIFY_PROCESS => ['processEdit'],
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

        $items = \ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getallactive');
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
     * @param GenericEvent $event The event that triggered this function call, including the id of the user for which
     *                            profile items should be entered
     */
    public function uiEdit(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }
        $user = $event->getSubject();
        $uid = !empty($user['uid']) ? $user['uid'] : Constant::USER_ID_ANONYMOUS;
        $userEntity = $this->userRepository->find($uid);
        $form = $this->formFactory->createForm($userEntity->getAttributes(), false);
        $event->data[self::EVENT_KEY] = $this->twig->render('@ZikulaProfileModule/Hook/edit.html.twig', [
            'user' => $userEntity,
            'form' => $form->createView()
        ]);
    }

    /**
     * Validate profile information entered for a user as part of the hook-like user UI events.
     *
     * @param GenericEvent $event The event that triggered this function call, including the id of the user for which
     *                            profile data was entered, and a collection in which to store the validation object
     *                            created by this function
     */
    public function validateEdit(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }
        $user = $event->getSubject();
        $uid = !empty($user['uid']) ? $user['uid'] : Constant::USER_ID_ANONYMOUS;
        $userEntity = $this->userRepository->find($uid);
        $form = $this->formFactory->createForm($userEntity->getAttributes(), false);
        $form->handleRequest($this->requestStack->getCurrentRequest());
        $this->validation = new ValidationResponse('', $form->getData());
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = $form->getErrors();
            foreach ($errors as $error) {
                $this->validation->addError('', $error->getMessage());
            }
        }

        $event->data->set(self::EVENT_KEY, $this->validation);
    }

    /**
     * Store profile data gathered when editing or creating a user account.
     *
     * @param GenericEvent $event The event that triggered this function call, containing the id of the user for which
     *                            profile information should be stored
     */
    public function processEdit(GenericEvent $event)
    {
        if (null === $this->kernel->getModule('ZikulaProfileModule')) {
            return;
        }
        $user = $event->getSubject();
        $uid = !empty($user['uid']) ? $user['uid'] : Constant::USER_ID_ANONYMOUS;
        $userEntity = $this->userRepository->find($uid);
        $form = $this->formFactory->createForm($userEntity->getAttributes(), false);
        $form->handleRequest($this->requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            $attributes = $form->getData();
            foreach ($attributes as $attribute => $value) {
                if (!empty($value)) {
                    $userEntity->setAttribute($attribute, $value);
                }
            }
            $this->doctrine->getManager()->flush();
        }
    }
}
