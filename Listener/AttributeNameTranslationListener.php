<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Zikula\ProfileModule\Entity\PropertyEntity;
use Zikula\UsersModule\Entity\UserAttributeEntity;

class AttributeNameTranslationListener implements EventSubscriber
{
    /**
     * @var array
     */
    private $translations = [];

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * @var string
     */
    private $prefix = 'zpmpp';

    /**
     * AttributeNameTranslationListener constructor.
     * @param string $locale
     * @param string $prefix
     */
    public function __construct($locale, $prefix)
    {
        $this->locale = $locale;
        $this->prefix = $prefix . ':';
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        if ($entity instanceof UserAttributeEntity) {
            $name = $entity->getName();
            if (!isset($this->translations[$this->locale][$name])) {
                $this->translations[$this->locale][$name] = $name;
                if (0 === strpos($name, $this->prefix)) {
                    try {
                        $property = $entityManager->find(PropertyEntity::class, substr($name, strlen($this->prefix)));
                        $this->translations[$this->locale][$name] = isset($property) ? $property->getLabel($this->locale) : $name;
                    } catch (\Exception $e) {
                        // listener fails during upgrade. silently fail
                    }
                }
            }
            $entity->setExtra($this->translations[$this->locale][$name]);
        }
    }
}
