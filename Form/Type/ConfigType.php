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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ProfileModule\ProfileConstant;

class ConfigType extends AbstractType
{
    use TranslatorTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('viewregdate', CheckboxType::class, [
                'label' => $this->__('Display the user\'s registration date'),
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false
            ])
            ->add('memberslistitemsperpage', IntegerType::class, [
                'constraints' => [new Range(['min' => 10, 'max' => 999])],
                'label' => $this->__('Users per page in \'Registered users list\''),
                'attr' => [
                    'maxlength' => 3
                ]
            ])
            ->add('onlinemembersitemsperpage', IntegerType::class, [
                'constraints' => [new Range(['min' => 10, 'max' => 999])],
                'label' => $this->__('Users per page in \'Users currently on-line\' page'),
                'attr' => [
                    'maxlength' => 3
                ]
            ])
            ->add('recentmembersitemsperpage', IntegerType::class, [
                'constraints' => [new Range(['min' => 10, 'max' => 999])],
                'label' => $this->__('Users per page in \'Recent registrations\' page'),
                'attr' => [
                    'maxlength' => 3
                ]
            ])
            ->add('activeminutes', IntegerType::class, [
                'constraints' => [new Range(['min' => 1, 'max' => 99])],
                'label' => $this->__('Minutes a user is considered online'),
                'attr' => [
                    'maxlength' => 2
                ]
            ])
            ->add('filterunverified', CheckboxType::class, [
                'label' => $this->__('Filter unverified users from \'Registered users list\''),
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false
            ])
            ->add(ProfileConstant::MODVAR_AVATAR_IMAGE_PATH, TextType::class, [
                'label' => $this->__('Path to user\'s avatar images'),
                'constraints' => [
                    new NotBlank(),
                    new Type('string')
                ]
            ])
            ->add(ProfileConstant::MODVAR_GRAVATARS_ENABLED, ChoiceType::class, [
                'label' => $this->__('Allow usage of Gravatar'),
                'label_attr' => ['class' => 'radio-custom'],
                'choices' => [
                    $this->__('Yes') => 1,
                    $this->__('No') => 0
                ],
                'expanded' => true
            ])
            ->add(ProfileConstant::MODVAR_GRAVATAR_IMAGE, TextType::class, [
                'label' => $this->__('Default avatar image (used as fallback)'),
                'constraints' => [
                    new NotBlank(),
                    new Type('string')
                ]
            ])
            ->add('allowUploads', CheckboxType::class, [
                'label' => $this->__('Allow uploading custom avatar images'),
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false
            ])
            ->add('shrinkLargeImages', CheckboxType::class, [
                'label' => $this->__('Shrink large images to maximum dimensions'),
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false
            ])
            ->add('maxSize', IntegerType::class, [
                'label' => $this->__('Max. avatar filesize'),
                'input_group' => ['right' => $this->__('bytes')]
            ])
            ->add('maxWidth', IntegerType::class, [
                'label' => $this->__('Max. width'),
                'attr' => [
                    'maxlength' => 4
                ],
                'input_group' => ['right' => $this->__('pixels')]
            ])
            ->add('maxHeight', IntegerType::class, [
                'label' => $this->__('Max. height'),
                'attr' => [
                    'maxlength' => 4
                ],
                'input_group' => ['right' => $this->__('pixels')]
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->__('Save'),
                'icon' => 'fa-check',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => $this->__('Cancel'),
                'icon' => 'fa-times',
                'attr' => [
                    'class' => 'btn btn-default',
                ],
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_config';
    }
}
