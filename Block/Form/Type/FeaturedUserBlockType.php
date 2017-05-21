<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
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
use Zikula\Common\Translator\IdentityTranslator;

class FeaturedUserBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translator = $options['translator'];
        $builder
            ->add('username', TextType::class, [
                'label' => $translator->__('User name'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('fieldstoshow', ChoiceType::class, [
                'label' => $translator->__('Information to show'),
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],
                'expanded' => true,
                'multiple' => true,
                'choices_as_values' => true,
                'choices' => $options['activeProperties'],
                'choice_label' => 'label',
                'choice_value' => 'id'
            ])
            ->add('showregdate', CheckboxType::class, [
                'label' => $translator->__('Show registration date'),
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'activeProperties' => [],
            'translator' => new IdentityTranslator()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'zikulaprofilemodule_featureduserblock';
    }
}
