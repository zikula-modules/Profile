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

namespace Zikula\ProfileModule\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Zikula\Bundle\FormExtensionBundle\DynamicFieldInterface;
use Zikula\Core\Doctrine\EntityAccess;

/**
 * @ORM\Entity(repositoryClass="Zikula\ProfileModule\Entity\Repository\PropertyRepository")
 * @ORM\Table(name="user_property")
 * @UniqueEntity("id")
 */
class PropertyEntity extends EntityAccess implements DynamicFieldInterface
{
    /**
     * Note this value is NOT auto-generated and must be manually created!
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     * @Assert\Regex("/^[a-zA-Z0-9\-\_]+$/")
     * @var string
     */
    private $id;

    /**
     * @ORM\Column(type="array")
     * @Assert\NotNull()
     * @var string
     */
    private $labels = [];

    /**
     * @ORM\Column(type="text")
     * @Assert\Length(min="0", max="255", allowEmptyString="false")
     * @var string
     */
    private $formType = '';

    /**
     * @ORM\Column(type="array")
     * @Assert\NotNull()
     * @var array
     */
    private $formOptions = [];

    /**
     * @ORM\Column(type="integer")
     * @Assert\GreaterThan(0)
     * @var int
     */
    private $weight = 0;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $active = true;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getLabel(string $locale = '', string $default = 'en'): string
    {
        if (!empty($locale) && isset($this->labels[$locale])) {
            return $this->labels[$locale];
        }
        if (!empty($default) && isset($this->labels[$default])) {
            return $this->labels[$default];
        }
        $values = array_values($this->labels);

        return !empty($values[0]) ? array_shift($values) : $this->id;
    }

    /**
     * @param string[] $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function setFormType(string $formType): void
    {
        $this->formType = $formType;
    }

    public function getFormOptions(): array
    {
        if (!isset($this->formOptions['required'])) {
            $this->formOptions['required'] = false;
        }

        return $this->formOptions;
    }

    public function setFormOptions(array $formOptions): void
    {
        $this->formOptions = $formOptions;
    }

    public function getFieldInfo(): array
    {
        return [
            'formType' => $this->getFormType(),
            'formOptions' => $this->getFormOptions()
        ];
    }

    public function setFieldInfo(array $fieldInfo): void
    {
        $this->setFormType($fieldInfo['formType']);
        $this->setFormOptions($fieldInfo['formOptions']);
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function incrementWeight(): void
    {
        $this->weight++;
    }

    public function decrementWeight(): void
    {
        $this->weight--;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    public function getName(): string
    {
        return $this->getId();
    }

    public function getPrefix(): string
    {
        return 'zpmpp';
    }

    public function getGroupNames(): array
    {
        return [];
    }
}
