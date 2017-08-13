<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Form\Type;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Zikula\Bundle\FormExtensionBundle\Form\Type\LocaleType;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\Event\GenericEvent;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\FormTypesChoices;
use Zikula\ProfileModule\ProfileEvents;
use Zikula\SettingsModule\Api\ApiInterface\LocaleApiInterface;

class PropertyType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LocaleApiInterface
     */
    private $localeApi;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * PropertyType constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param LocaleApiInterface $localeApi
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LocaleApiInterface $localeApi,
        TranslatorInterface $translator
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->localeApi = $localeApi;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', TextType::class, [
                'label' => $this->translator->__('Id'),
                'help' => $this->translator->__('Unique, simple string. No spaces. a-z, 0-9, _ and -'),
                'alert' => ['Once used, do not change the ID value or all profiles will lose their connection!' => 'warning']
            ])
            ->add('labels', CollectionType::class, [
                'label' => $this->translator->__('Translated labels'),
                'entry_type' => TranslationType::class
            ])
            ->add('formType', ChoiceType::class, [
                'label' => $this->translator->__('Field type'),
                'choices' => $this->getChoices(),
                'choices_as_values' => true,
                'placeholder' => $this->translator->__('Select')
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->__('Active'),
            ])
            ->add('weight', IntegerType::class, [
                'constraints' => [new GreaterThan(0)],
                'empty_data' => 100
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->translator->__('Save'),
                'icon'  => 'fa-check',
                'attr'  => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $this->translator->__('Cancel'),
                'icon'  => 'fa-times',
                'attr'  => [
                    'class' => 'btn btn-default',
                ],
            ]);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $supportedLocales = $this->localeApi->getSupportedLocales();
            $data = $event->getData();
            $labels = $data['labels'];
            foreach ($supportedLocales as $locale) {
                if (!array_key_exists($locale, $labels)) {
                    $labels[$locale] = isset($labels['en']) ? $labels['en'] : '';
                }
            }
            $data['labels'] = $labels;
            $event->setData($data);
        });
        $formModifier = function (FormInterface $form, $formType = null) {
            switch ($formType) {
                case ChoiceType::class:
                    $optionsType = ChoiceFormOptionsArrayType::class;
                    break;
                case DateType::class:
                case DateTimeType::class:
                case TimeType::class:
                case BirthdayType::class:
                    $optionsType = DateTimeFormOptionsArrayType::class;
                    break;
                case TextType::class:
                case TextareaType::class:
                    $optionsType = RegexibleFormOptionsArrayType::class;
                    break;
                default:
                    $optionsType = FormOptionsArrayType::class;
            }
            $form->add('formOptions', $optionsType, [
                'label' => $this->translator->__('Field options'),
                'translator' => $this->translator
            ]);
        };
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formType = $data['formType'];
                $formModifier($event->getForm(), $formType);
            }
        );
        $builder->get('formType')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formType = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $formType);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_property';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PropertyEntity::class,
        ]);
    }

    private function getChoices()
    {
        $choices = new FormTypesChoices([
            $this->translator->__('Text Fields') => [
                $this->translator->__('Text') => TextType::class,
                $this->translator->__('Textarea') => TextareaType::class,
                $this->translator->__('Email') => EmailType::class,
                $this->translator->__('Integer') => IntegerType::class,
                $this->translator->__('Money') => MoneyType::class,
                $this->translator->__('Number') => NumberType::class,
                $this->translator->__('Password') => PasswordType::class,
                $this->translator->__('Percent') => PercentType::class,
                $this->translator->__('Url') => UrlType::class,
                $this->translator->__('Range') => RangeType::class,
            ],
            $this->translator->__('Choice Fields') => [
                $this->translator->__('Choice') => ChoiceType::class,
                $this->translator->__('Country') => CountryType::class,
                $this->translator->__('Language') => LanguageType::class,
                $this->translator->__('Locale') => LocaleType::class,
                $this->translator->__('Timezone') => TimezoneType::class,
                $this->translator->__('Currency') => CurrencyType::class,
            ],
            $this->translator->__('Date and Time Fields') => [
                $this->translator->__('Date') => DateType::class,
                $this->translator->__('DateTime') => DateTimeType::class,
                $this->translator->__('Time') => TimeType::class,
                $this->translator->__('Birthday') => BirthdayType::class,
            ],
            $this->translator->__('Other Fields') => [
                $this->translator->__('Checkbox') => CheckboxType::class,
                $this->translator->__('Radio') => RadioType::class,
                $this->translator->__('Avatar') => AvatarType::class,
            ],
        ]);
        $this->eventDispatcher->dispatch(ProfileEvents::FORM_TYPE_CHOICES, new GenericEvent($choices));

        return $choices;
    }
}
