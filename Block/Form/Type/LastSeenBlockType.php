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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class LastSeenBlockType.
 */
class LastSeenBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', [
                'label'       => __('Number of recent visitors to display'),
                'empty_data'  => 5,
                'scale'       => 0,
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'zikulaprofilemodule_lastseenblock';
    }
}
