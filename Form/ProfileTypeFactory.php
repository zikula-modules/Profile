<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Form;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;

class ProfileTypeFactory
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PropertyRepositoryInterface
     */
    private $propertyRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $prefix;

    /**
     * PropertyTypeFactory constructor.
     * @param FormFactoryInterface $formFactory
     * @param PropertyRepositoryInterface $propertyRepository
     * @param TranslatorInterface $translator
     * @param string $prefix
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PropertyRepositoryInterface $propertyRepository,
        TranslatorInterface $translator,
        $prefix
    ) {
        $this->formFactory = $formFactory;
        $this->propertyRepository = $propertyRepository;
        $this->translator = $translator;
        $this->prefix = $prefix;
    }

    /**
     * @param PersistentCollection $attributes
     * @param bool $includeButtons
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createForm(PersistentCollection $attributes, $includeButtons = true)
    {
        $attributeValues = [];
        foreach ($attributes as $attribute) {
            if (0 === strpos($attribute->getName(), $this->prefix)) {
                $attributeValues[$attribute->getName()] = $attribute->getValue();
            }
        }
        /** @var PropertyEntity[] $properties */
        $properties = $this->propertyRepository->findBy(['active' => true], ['weight' => 'ASC']);
        $formBuilder = $this->formFactory->createNamedBuilder('zikulaprofilemodule_editprofile', FormType::class, $attributeValues);
        foreach ($properties as $property) {
            $child = $this->prefix . ':' .$property->getId();
            $options = $property->getFormOptions();
            $options['label'] = isset($options['label']) ? $options['label'] : $property->getLabel();//$attributes->get($child)->getExtra();
            $formBuilder->add($child, $property->getFormType(), $options);
        }
        if ($includeButtons) {
            $formBuilder->add('save', SubmitType::class, [
                'label' => $this->translator->__('Save'),
                'icon' => 'fa-check',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ]);
            $formBuilder->add('cancel', SubmitType::class, [
                'label' => $this->translator->__('Cancel'),
                'icon' => 'fa-times',
                'attr' => [
                    'class' => 'btn btn-default',
                ],
            ]);
        }

        return $formBuilder->getForm();
    }
}
