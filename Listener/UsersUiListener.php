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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Zikula\Bundle\CoreBundle\Event\GenericEvent;
use Zikula\Bundle\FormExtensionBundle\Event\FormTypeChoiceEvent;
use Zikula\Bundle\HookBundle\Hook\ValidationResponse;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;
use Zikula\ProfileModule\Form\ProfileTypeFactory;
use Zikula\ProfileModule\Form\Type\AvatarType;
use Zikula\ProfileModule\Helper\UploadHelper;
use Zikula\ProfileModule\ProfileConstant;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Event\UserFormPostCreatedEvent;
use Zikula\UsersModule\Event\UserFormPostValidatedEvent;
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
     * @var PropertyRepositoryInterface
     */
    private $propertyRepository;

    /**
     * @var ProfileTypeFactory
     */
    private $formFactory;

    /**
     * @var Environment
     */
    protected $twig;

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
        PropertyRepositoryInterface $propertyRepository,
        ProfileTypeFactory $factory,
        Environment $twig,
        UploadHelper $uploadHelper,
        string $prefix
    ) {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->propertyRepository = $propertyRepository;
        $this->formFactory = $factory;
        $this->twig = $twig;
        $this->uploadHelper = $uploadHelper;
        $this->prefix = $prefix;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::DISPLAY_VIEW => ['uiView'],
            UserFormPostCreatedEvent::class => ['amendForm'],
            UserFormPostValidatedEvent::class => ['editFormHandler'],
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
            'activeProperties' => $this->propertyRepository->getDynamicFieldsSpecification()
        ]);
    }

    public function amendForm(UserFormPostCreatedEvent $event): void
    {
        $user = $event->getFormData();
        $uid = !empty($user['uid']) ? $user['uid'] : Constant::USER_ID_ANONYMOUS;
        $userEntity = $this->userRepository->find($uid);
        $attributes = $userEntity->getAttributes() ?? [];

        // unpack json values (e.g. array for multi-valued options)
        foreach ($attributes as $key => $attribute) {
            $value = $attribute->getValue();
            if (is_string($value) && is_array(json_decode($value, true)) && JSON_ERROR_NONE === json_last_error()) {
                $attribute->setValue(json_decode($value, true));
            }
        }

        $profileForm = $this->formFactory->createForm($attributes, false);
        $event
            ->formAdd($profileForm)
            ->addTemplate('@ZikulaProfileModule/Hook/edit.html.twig')
        ;
    }

    public function editFormHandler(UserFormPostValidatedEvent $event): void
    {
        $userEntity = $event->getUser();
        $formData = $event->getFormData(ProfileConstant::FORM_BLOCK_PREFIX);
        foreach ($formData as $key => $value) {
            if (!empty($value)) {
                if ($value instanceof UploadedFile) {
                    $value = $this->uploadHelper->handleUpload($value, $userEntity->getUid());
                } elseif (is_array($value)) {
                    // pack multi-valued options into json
                    $value = json_encode($value);
                }
                $userEntity->setAttribute($key, $value);
            } elseif (false === mb_strpos($key, 'avatar')) {
                $userEntity->delAttribute($key);
            }
        }

        // we do not call flush here on purpose because maybe
        // other modules need to care for certain things before
        // the Users module calls flush after all listeners finished
    }

    public function formTypeChoices(FormTypeChoiceEvent $event): void
    {
        $choices = $event->getChoices();

        $groupName = $this->translator->trans('Other Fields', [], 'zikula');
        if (!isset($choices[$groupName])) {
            $choices[$groupName] = [];
        }

        $groupChoices = $choices[$groupName];
        $groupChoices[$this->translator->trans('Avatar')] = AvatarType::class;
        $choices[$groupName] = $groupChoices;

        $event->setChoices($choices);
    }
}
