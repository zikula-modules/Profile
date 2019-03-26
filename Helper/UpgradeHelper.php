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
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\Form\Type\AvatarType;
use Zikula\UsersModule\Entity\UserAttributeEntity;

class UpgradeHelper
{
    /**
     * @var string
     */
    private $systemTimezone;

    /**
     * @var array
     */
    private $offsetMap = [];

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
     *
     * @param TranslatorInterface $translator
     * @param VariableApiInterface $variableApi
     */
    public function __construct(
        TranslatorInterface $translator,
        VariableApiInterface $variableApi
    ) {
        $this->setTranslator($translator);
        $this->systemTimezone = $variableApi->getSystemVar('timezone');
        $this->createOffsetMap();
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param UserAttributeEntity $attribute
     * @param string $prefix
     * @return mixed
     */
    public function getModifiedAttributeValue(UserAttributeEntity $attribute, $prefix)
    {
        $value = $attribute->getValue();
        if ($prefix . ':timezone' === $attribute->getName()) {
            $value = isset($this->offsetMap[$value]) ? $this->offsetMap[$value] : $this->systemTimezone;
        }

        return $value;
    }

    /**
     * @param array $property
     * @param string $locale
     * @return PropertyEntity
     */
    public function mergeToNewProperty(array $property, $locale = 'en')
    {
        $property['validation'] = unserialize($property['validation']);
        $newProperty = new PropertyEntity();
        $newProperty->setId($property['attributename']);
        $newProperty->setWeight($property['weight']);
        $newProperty->setActive($property['weight'] > 0);
        $newProperty->setLabels([$locale => $this->__(/** @Ignore */$property['label'])]);
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
                $newProperty->setId('timezone');
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
        if ('_country' === mb_substr($property['attributename'], -8)) {
            $newProperty->setFormType(CountryType::class);
        }
    }

    private function setFormOptions(PropertyEntity $newProperty, array $property)
    {
        $options = [];
        if (true === $property['validation']['required'] || 1 === $property['validation']['required'] || !empty($property['validation']['required'])) {
            $options['required'] = true;
        }
        if (!empty($property['validation']['pattern'])) {
            $options['constraints'] = [new Regex($property['validation']['pattern'])];
        }
        if (!empty($property['validation']['note'])) {
            $options['help'] = $property['validation']['note'];
        }
        // this does not migrate 'viewby' which should be handled in permissions by property id
        switch ($newProperty->getFormType()) {
            case AvatarType::class:
                $options['label'] = $this->__('Avatar');
                break;
            case ChoiceType::class:
                $listOptions = explode('@@', $property['validation']['listoptions'], 2);
                $options['multiple'] = $listOptions[0];
                $options['choices'] = $this->generateChoices($property['validation']['listoptions']);
                if (7 === $property['validation']['displaytype']) {
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
            $value = $itemParts[1] ?? $id;
            $display = !empty($itemParts[0]) ? $this->__(/** @Ignore */$itemParts[0]) : $id;
            $choices[$display] = $value;
        }

        return $choices;
    }

    private function getDateFormatFromAlias($format)
    {
        switch (trim(mb_strtolower($format))) {
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

    private function createOffsetMap()
    {
        $identifiers = \DateTimeZone::listIdentifiers();
        foreach ($identifiers as $name) {
            $now = new \DateTime(null, new \DateTimeZone($name));
            $offsetValue = $now->getOffset() / 3600;
            if (!isset($this->offsetMap[$offsetValue])) {
                $this->offsetMap[$offsetValue] = $name;
            }
        }
    }
}
