<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\Util\LiveControllerAttributesCreator;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class AddLiveAttributesSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        if (!$event->getMetadata()->get('live', false)) {
            // not a live component, skip
            return;
        }

        if ($event->isEmbedded()) {
            throw new \LogicException('Embedded components cannot be live.');
        }

        $metadata = $event->getMetadata();
        $attributes = $this->getLiveAttributes($event->getMountedComponent(), $metadata);
        $variables = $event->getVariables();
        $attributesKey = $metadata->getAttributesVar();

        // the original ComponentAttributes have already been processed and set
        // onto the variables. So, we manually merge our new attributes in and
        // override that variable.
        if (isset($variables[$attributesKey]) && $variables[$attributesKey] instanceof ComponentAttributes) {
            // merge with existing attributes if available
            $attributes = $attributes->defaults($variables[$attributesKey]->all());
        }

        $variables[$attributesKey] = $attributes;

        $event->setVariables($variables);
    }

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }

    public static function getSubscribedServices(): array
    {
        return [
            LiveControllerAttributesCreator::class,
        ];
    }

    private function getLiveAttributes(MountedComponent $mounted, ComponentMetadata $metadata): ComponentAttributes
    {
        $attributesCreator = $this->container->get(LiveControllerAttributesCreator::class);
        \assert($attributesCreator instanceof LiveControllerAttributesCreator);

        $attributes = $attributesCreator->attributesForRendering(
            $mounted,
            $metadata
        );

        return new ComponentAttributes($attributes);
    }
}
