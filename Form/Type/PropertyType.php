<?php

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
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\SettingsModule\Api\ApiInterface\LocaleApiInterface;

class PropertyType extends AbstractType
{
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
     *
     * @param LocaleApiInterface $localeApi
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LocaleApiInterface $localeApi,
        TranslatorInterface $translator
    ) {
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
                'alert' => [$this->translator->__('Once used, do not change the ID value or all profiles will lose their connection!') => 'warning']
            ])
            ->add('labels', CollectionType::class, [
                'label' => $this->translator->__('Translated labels'),
                'entry_type' => TranslationType::class
            ])
            ->add('fieldInfo', DynamicFieldType::class, [
                'label' => false
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->__('Active'),
            ])
            ->add('weight', IntegerType::class, [
                'label' => $this->translator->__('Weight'),
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
            ])
        ;
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
}
