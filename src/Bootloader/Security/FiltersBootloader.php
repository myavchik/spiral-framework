<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Bootloader\Security;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\Bootloader\DependedInterface;
use Spiral\Console\LocatorInterface;
use Spiral\Filter\RequestInput;
use Spiral\Filters\FilterLocator;
use Spiral\Filters\FilterMapper;
use Spiral\Filters\InputInterface;
use Spiral\Filters\MapperInterface;

final class FiltersBootloader extends Bootloader implements DependedInterface
{
    const SINGLETONS = [
        MapperInterface::class  => FilterMapper::class,
        LocatorInterface::class => FilterLocator::class,
        InputInterface::class   => RequestInput::class
    ];

    /**
     * @return array
     */
    public function defineDependencies(): array
    {
        return [
            ValidationBootloader::class
        ];
    }
}