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

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ChoiceFormOptionsArrayType extends FormOptionsArrayType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('multiple', CheckboxType::class, [
                'label' => $this->translator->__('Multiple'),
                'required' => false,
            ])
            ->add('expanded', CheckboxType::class, [
                'label' => $this->translator->__('Expanded'),
                'required' => false,
            ])
            ->add('choices', TextType::class, [
                'label' => $this->translator->__('Choices'),
                'help' => $this->translator->__('A comma-delineated list.'),
                'required' => false,
            ])
            ->add('choices_as_values', HiddenType::class, [ // not needed in Core-2.0
                'data' => true
            ])
        ;
    }
}
