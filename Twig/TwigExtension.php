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

namespace Zikula\ProfileModule\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;
use Zikula\UsersModule\Entity\UserAttributeEntity;

class TwigExtension extends AbstractExtension
{
    /**
     * @var PropertyRepositoryInterface
     */
    protected $propertyRepository;

    /**
     * @var PropertyEntity[]
     */
    protected $properties;

    /**
     * @var string
     */
    protected $prefix;

    public function __construct(
        PropertyRepositoryInterface $propertyRepository,
        string $prefix
    ) {
        $this->propertyRepository = $propertyRepository;
        $this->properties = null;
        $this->prefix = $prefix;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('zikulaprofilemodule_sortAttributesByWeight', [$this, 'sortAttributesByWeight']),
            new TwigFilter('zikulaprofilemodule_formatPropertyForDisplay', [$this, 'formatPropertyForDisplay'])
        ];
    }

    public function sortAttributesByWeight(iterable $attributes): iterable
    {
        if (null === $this->properties) {
            $this->properties = $this->propertyRepository->getIndexedActive();
        }
        $properties = $this->properties;
        $sorter = function ($att1, $att2) use ($properties) {
            if ((0 !== mb_strpos($att1, $this->prefix)) && (0 !== mb_strpos($att2, $this->prefix))) {
                return 0;
            }
            $n1 = mb_substr($att1, mb_strlen($this->prefix) + 1);
            $n2 = mb_substr($att2, mb_strlen($this->prefix) + 1);
            if (!isset($properties[$n1], $properties[$n2])) {
                return 0;
            }

            return $properties[$n1]['weight'] > $properties[$n2]['weight'];
        };
        $attributes = $attributes->toArray();
        uksort($attributes, $sorter);

        return $attributes;
    }

    public function formatPropertyForDisplay(UserAttributeEntity $attribute): string
    {
        $value = $attribute->getValue();
        if (empty($value)) {
            return $value;
        }

        if (null === $this->properties) {
            $this->properties = $this->propertyRepository->getIndexedActive();
        }

        $attributeName = $attribute->getName();

        foreach ($this->properties as $property) {
            if ($attributeName !== $this->prefix . ':' . $property['id']) {
                continue;
            }
            if ('Symfony\Component\Form\Extension\Core\Type\ChoiceType' == $property['formType']) {
                if (isset($property['formOptions']['multiple']) && 1 == $property['formOptions']['multiple']) {
                    $values = json_decode($value, true);
                    $labels = [];
                    $choices = array_flip($property['formOptions']['choices']);
                    foreach ($values as $choiceId) {
                        $labels[] = $choices[$choiceId];
                    }
                    $value = implode(', ', $labels);
                }
            }
        }

        return $value;
    }
}
