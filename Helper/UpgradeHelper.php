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
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Regex;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\Form\Type\AvatarType;

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
     * @param TranslatorInterface $translator
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
        $newProperty->setActive($property['weight'] > 0);
        $newProperty->setLabel($this->__(/** @Ignore */$property['label']));
        $this->setFormType($newProperty, $property);
        $this->setFormOptions($newProperty, $property);

        return $newProperty;
    }

    private function setFormType(PropertyEntity $newProperty, array $property)
    {
        $newProperty->setFormType($this->formTypeMap[$property['validation']['displaytype']]);
        switch ($property['attributename']) {
            case 'tzoffset':
                $newProperty->setFormType(TimezoneType::class);
                break;
            case 'avatar':
                $newProperty->setFormType(AvatarType::class);
                break;
            case 'publicemail':
                $newProperty->setFormType(EmailType::class);
                break;
            case 'url':
                $newProperty->setFormType(UrlType::class);
                break;
            case 'country':
                $newProperty->setFormType(CountryType::class);
        }
        if (substr($property['attributename'], -8) == '_country') {
            $newProperty->setFormType(CountryType::class);
        }
    }

    private function setFormOptions(PropertyEntity $newProperty, array $property)
    {
        $options = [];
        $options['required'] = (bool) $property['validation']['required'];
        $options['constraints'] = !empty($property['validation']['pattern']) ? [new Regex($property['validation']['pattern'])] : [];
        $options['help'] = !empty($property['validation']['note']) ? $property['validation']['note'] : '';
        // this does not migrate 'viewby' which should be handled in permissions by property id
        switch ($newProperty->getFormType()) {
            case ChoiceType::class:
                $listOptions = explode('@@', $property['validation']['listoptions'], 2);
                $options['multiple'] = $listOptions[0];
                $options['choices'] = $this->generateChoices($property['validation']['listoptions']);
                $options['choices_as_values'] = true; // @deprecated remove at Core-2.0
                if (7 == $property['validation']['displaytype']) {
                    $options['multiple'] = true;
                    $options['expanded'] = true;
                }
                break;
            case DateType::class:
                $options['format'] = $this->getDateFormatFromAlias($property['validation']['listoptions']);
                break;

        }
        $newProperty->setFormOptions($options);
    }

    private function generateChoices($listOptions)
    {
        $choices = [];
        $list = explode('@@', $listOptions);
        $list = array_splice($list, 1);
        // translate them if needed
        foreach ($list as $id => $listItem) {
            $itemParts = explode('@', $listItem);
            $value = isset($itemParts[1]) ? $itemParts[1] : $id;
            $display = !empty($itemParts[0]) ? $this->__(/** @Ignore */$itemParts[0]) : $id;
            $choices[$display] = $value;
        }

        return $choices;
    }

    private function getDateFormatFromAlias($format)
    {
        switch (trim(strtolower($format))) {
            case 'us':
                return 'F j, Y';
                break;
            case 'db':
                return 'Y-m-d';
                break;
            default:
            case 'eur':
                return 'j F Y';
                break;
        }
    }
}
