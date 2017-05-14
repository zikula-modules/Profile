<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Helper;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ProfileModule\Entity\PropertyEntity;

class UpgradeHelper
{
    use TranslatorTrait;

    private $formTypeMap = [
        0 => TextType::class,
        1 => TextareaType::class,
        2 => CheckboxType::class,
        3 => RadioType::class,
        4 => ChoiceType::class,
        5 => DateType::class,
        7 => ChoiceType::class, // multi-checkbox
    ];

    /**
     * UpgradeHelper constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param array $property
     * @return PropertyEntity
     */
    public function mergeToNewProperty(array $property)
    {
        $newProperty = new PropertyEntity();
        $newProperty->setWeight($property['weight']);
        $newProperty->setLabel($this->__(/** @Ignore */$property['label']));
        $newProperty->setFormType($this->formTypeMap[$property['validation']['displaytype']]);
        $newProperty->setFormOptions($this->getFormOptions($property['validation']));

        return $newProperty;
    }

    private function getFormOptions($validation)
    {
        return [];
    }
}
