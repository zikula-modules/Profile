<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Listener;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Twig\Environment;
use Zikula\Bundle\FormExtensionBundle\Event\FormTypeChoiceEvent;
use Zikula\Bundle\HookBundle\Hook\ValidationResponse;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\Event\GenericEvent;
use Zikula\ProfileModule\Form\ProfileTypeFactory;
use Zikula\ProfileModule\Form\Type\AvatarType;
use Zikula\ProfileModule\Helper\UploadHelper;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Event\UserFormAwareEvent;
use Zikula\UsersModule\Event\UserFormDataEvent;
use Zikula\UsersModule\UserEvents;

/**
 * Hook-like event handlers for basic profile data.
 */
class UsersUiListener implements EventSubscriberInterface
{
    /**
     * The area name that this handler processes.
     */
    public const EVENT_KEY = 'module.profile.users_ui_handler';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var ProfileTypeFactory
     */
    private $formFactory;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var UploadHelper
     */
    protected $uploadHelper;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * The validation object instance used when validating information entered during an edit phase.
     *
     * @var ValidationResponse
     */
    protected $validation;

    public function __construct(
        TranslatorInterface $translator,
        UserRepositoryInterface $userRepository,
        ProfileTypeFactory $factory,
        Environment $twig,
        RegistryInterface $registry,
        UploadHelper $uploadHelper,
        string $prefix
    ) {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->formFactory = $factory;
        $this->twig = $twig;
        $this->doctrine = $registry;
        $this->uploadHelper = $uploadHelper;
        $this->prefix = $prefix;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::DISPLAY_VIEW => ['uiView'],
            UserEvents::EDIT_FORM => ['amendForm'],
            UserEvents::EDIT_FORM_HANDLE => ['editFormHandler'],
            FormTypeChoiceEvent::NAME => ['formTypeChoices']
        ];
    }

    /**
     * Render and return profile information for display as part of a hook-like UI event issued from the Users module.
     */
    public function uiView(GenericEvent $event): void
    {
        $event->data[self::EVENT_KEY] = $this->twig->render('@ZikulaProfileModule/Hook/display.html.twig', [
            'prefix' => $this->prefix,
            'user' => $event->getSubject(),
        ]);
    }

    public function amendForm(UserFormAwareEvent $event): void
    {
        $user = $event->getFormData();
        $uid = !empty($user['uid']) ? $user['uid'] : Constant::USER_ID_ANONYMOUS;
        $userEntity = $this->userRepository->find($uid);
        $attributes = $userEntity->getAttributes() ?? [];
        $profileForm = $this->formFactory->createForm($attributes, false);
        $event
            ->formAdd($profileForm)
            ->addTemplate('@ZikulaProfileModule/Hook/edit.html.twig')
        ;
    }

    public function editFormHandler(UserFormDataEvent $event): void
    {
        $userEntity = $event->getUserEntity();
        $formData = $event->getFormData(ProfileConstant::FORM_BLOCK_PREFIX);
        foreach ($formData as $key => $value) {
            if (!empty($value)) {
                if ($value instanceof UploadedFile) {
                    $value = $this->uploadHelper->handleUpload($value, $userEntity->getUid());
                }
                $userEntity->setAttribute($key, $value);
            } elseif (false === mb_strpos($key, 'avatar')) {
                $userEntity->delAttribute($key);
            }
        }
        $this->doctrine->getManager()->flush();
    }

    public function formTypeChoices(FormTypeChoiceEvent $event): void
    {
        $choices = $event->getChoices();

        $groupName = $this->translator->__('Other Fields', 'zikula');
        if (!isset($choices[$groupName])) {
            $choices[$groupName] = [];
        }

        $groupChoices = $choices[$groupName];
        $groupChoices[$this->translator->__('Avatar')] = AvatarType::class;
        $choices[$groupName] = $groupChoices;

        $event->setChoices($choices);
    }
}
