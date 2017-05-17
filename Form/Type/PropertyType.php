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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zikula\Bundle\FormExtensionBundle\Form\Type\LocaleType;
use Zikula\Common\Translator\IdentityTranslator;
use Zikula\Core\Event\GenericEvent;
use Zikula\ProfileModule\FormTypesChoices;

class PropertyType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * PropertyType constructor.
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translator = $options['translator'];
        $choices = new FormTypesChoices([
            $translator->__('Text') => TextType::class,
            $translator->__('Textarea') => TextareaType::class,
            $translator->__('Checkbox') => CheckboxType::class,
            $translator->__('Email') => EmailType::class,
            $translator->__('Url') => UrlType::class,
            $translator->__('Avatar') => AvatarType::class,
            $translator->__('Radio') => RadioType::class,
            $translator->__('Choice') => ChoiceType::class,
            $translator->__('Timezone') => TimezoneType::class,
            $translator->__('Date') => DateType::class,
            $translator->__('Birthday') => BirthdayType::class,
            $translator->__('Time') => TimeType::class,
            $translator->__('DateTime') => DateTimeType::class,
            $translator->__('Integer') => IntegerType::class,
            $translator->__('Money') => MoneyType::class,
            $translator->__('Number') => NumberType::class,
            $translator->__('Percent') => PercentType::class,
            $translator->__('Range') => RangeType::class,
            $translator->__('Country') => CountryType::class,
            $translator->__('Language') => LanguageType::class,
            $translator->__('Locale') => LocaleType::class,
            $translator->__('Currency') => CurrencyType::class,
        ]);
        $this->eventDispatcher->dispatch('test', new GenericEvent($choices));

        $builder
            ->add('id', TextType::class)
            ->add('label', TextType::class)
            ->add('formType', ChoiceType::class, [
                'choices' => $choices,
                'choices_as_values' => true
            ])
            ->add('formOptions', CollectionType::class, [
                'entry_options' => ['required' => false]
            ])
            ->add('active', CheckboxType::class)
            ->add('save', SubmitType::class, [
                'label' => $translator->__('Save'),
                'icon'  => 'fa-check',
                'attr'  => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $translator->__('Cancel'),
                'icon'  => 'fa-times',
                'attr'  => [
                    'class' => 'btn btn-default',
                ],
            ]);
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
            'translator' => new IdentityTranslator(),
        ]);
    }
}
