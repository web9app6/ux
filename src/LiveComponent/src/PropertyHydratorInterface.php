<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Symfony\UX\LiveComponent\Exception\UnsupportedHydrationException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
interface PropertyHydratorInterface
{
    /**
     * @param mixed $value
     *
     * @return scalar|null|array
     *
     * @throws UnsupportedHydrationException If unable to dehydrate.
     */
    public function dehydrate($value);

    /**
     * @param scalar|null|array $value
     *
     * @return mixed
     *
     * @throws UnsupportedHydrationException If unable to dehydrate.
     */
    public function hydrate(string $type, $value);
}
