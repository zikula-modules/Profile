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

namespace Zikula\ProfileModule\Block\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;

class FeaturedUserBlockType extends AbstractType
{
    use TranslatorTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => $this->trans('User name'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('fieldstoshow', ChoiceType::class, [
                'label' => $this->trans('Information to show'),
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],
                'expanded' => true,
                'multiple' => true,
                'choices' => $options['activeProperties'],
                'choice_label' => 'label',
                'choice_value' => 'id'
            ])
            ->add('showregdate', CheckboxType::class, [
                'label' => $this->trans('Show registration date'),
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'activeProperties' => []
        ]);
    }

    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_featureduserblock';
    }
}
