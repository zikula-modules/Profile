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

namespace Zikula\ProfileModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Zikula\Bundle\FormExtensionBundle\Form\Type\DynamicFieldType;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\SettingsModule\Api\ApiInterface\LocaleApiInterface;

class PropertyType extends AbstractType
{
    use TranslatorTrait;

    /**
     * @var LocaleApiInterface
     */
    private $localeApi;

    public function __construct(
        TranslatorInterface $translator,
        LocaleApiInterface $localeApi
    ) {
        $this->setTranslator($translator);
        $this->localeApi = $localeApi;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', TextType::class, [
                'label' => $this->__('Id'),
                'help' => $this->__('Unique, simple string. No spaces. a-z, 0-9, _ and -'),
                'alert' => [$this->__('Once used, do not change the ID value or all profiles will lose their connection!') => 'warning']
            ])
            ->add('labels', CollectionType::class, [
                'label' => $this->__('Translated labels'),
                'entry_type' => TranslationType::class
            ])
            ->add('fieldInfo', DynamicFieldType::class, [
                'label' => false
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => $this->__('Active'),
                'label_attr' => ['class' => 'switch-custom']
            ])
            ->add('weight', IntegerType::class, [
                'label' => $this->__('Weight'),
                'constraints' => [new GreaterThan(0)],
                'empty_data' => 100
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->__('Save'),
                'icon'  => 'fa-check',
                'attr'  => [
                    'class' => 'btn btn-success'
                ]
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $this->__('Cancel'),
                'icon'  => 'fa-times',
                'attr'  => [
                    'class' => 'btn btn-default'
                ]
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $supportedLocales = $this->localeApi->getSupportedLocales();
            $data = $event->getData();
            $labels = $data['labels'];
            foreach ($supportedLocales as $locale) {
                if (!array_key_exists($locale, $labels)) {
                    $labels[$locale] = $labels['en'] ?? '';
                }
            }
            $data['labels'] = $labels;
            $event->setData($data);
        });
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_property';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PropertyEntity::class
        ]);
    }
}
