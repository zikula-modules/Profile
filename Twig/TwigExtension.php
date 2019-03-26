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

use Zikula\ProfileModule\Entity\RepositoryInterface\PropertyRepositoryInterface;

class TwigExtension extends \Twig_Extension
{
    /**
     * @var PropertyRepositoryInterface
     */
    protected $propertyRepository;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * TwigExtension constructor.
     *
     * @param PropertyRepositoryInterface $propertyRepository
     * @param string $prefix
     */
    public function __construct(
        PropertyRepositoryInterface $propertyRepository,
        $prefix
    ) {
        $this->propertyRepository = $propertyRepository;
        $this->prefix = $prefix;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('zikulaprofilemodule_sortAttributesByWeight', [$this, 'sortAttributesByWeight'])
        ];
    }

    public function sortAttributesByWeight($attributes)
    {
        $properties = $this->propertyRepository->getIndexedActive();
        $sorter = function ($att1, $att2) use ($properties) {
            if ((0 !== mb_strpos($att1, $this->prefix)) && (0 !== mb_strpos($att2, $this->prefix))) {
                return 0;
            }
            $n1 = mb_substr($att1, mb_strlen($this->prefix) + 1);
            $n2 = mb_substr($att2, mb_strlen($this->prefix) + 1);
            if (!isset($properties[$n1]) || !isset($properties[$n2])) {
                return 0;
            }

            return $properties[$n1]['weight'] > $properties[$n2]['weight'];
        };
        $attributes = $attributes->toArray();
        uksort($attributes, $sorter);

        return $attributes;
    }
}
