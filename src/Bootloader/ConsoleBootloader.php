<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\Bootloader\DependedInterface;
use Spiral\Boot\KernelInterface;
use Spiral\Command\CleanCommand;
use Spiral\Command\PublishCommand;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Append;
use Spiral\Console\CommandLocator;
use Spiral\Console\Console;
use Spiral\Console\ConsoleDispatcher;
use Spiral\Console\LocatorInterface;
use Spiral\Console\Sequence\CallableSequence;
use Spiral\Console\Sequence\CommandSequence;
use Spiral\Core\Container\SingletonInterface;

/**
 * Bootloads console and provides ability to register custom bootload commands.
 */
final class ConsoleBootloader extends Bootloader implements SingletonInterface, DependedInterface
{
    public const SINGLETONS = [
        Console::class          => Console::class,
        LocatorInterface::class => CommandLocator::class
    ];

    /** @var ConfiguratorInterface */
    private $config;

    /**
     * @param ConfiguratorInterface $config
     */
    public function __construct(ConfiguratorInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param KernelInterface   $kernel
     * @param ConsoleDispatcher $console
     */
    public function boot(KernelInterface $kernel, ConsoleDispatcher $console)
    {
        $kernel->addDispatcher($console);

        $this->config->setDefaults('console', [
            'commands'  => [],
            'configure' => [],
            'update'    => []
        ]);

        $this->addCommand(CleanCommand::class);
        $this->addCommand(PublishCommand::class);
    }

    /**
     * @return array
     */
    public function defineDependencies(): array
    {
        return [TokenizerBootloader::class];
    }

    /**
     * @param string $command
     */
    public function addCommand(string $command)
    {
        $this->config->modify(
            'console',
            new Append('commands', null, $command)
        );
    }

    /**
     * @param array|string $sequence
     * @param string       $header
     * @param string       $footer
     * @param array        $options
     */
    public function addConfigureSequence(
        $sequence,
        string $header,
        string $footer = '',
        array $options = []
    ) {
        $this->config->modify(
            'console',
            $this->sequence('configure', $sequence, $header, $footer, $options)
        );
    }

    /**
     * @param array|string $sequence
     * @param string       $header
     * @param string       $footer
     * @param array        $options
     */
    public function addUpdateSequence(
        $sequence,
        string $header,
        string $footer = '',
        array $options = []
    ) {
        $this->config->modify(
            'console',
            $this->sequence('update', $sequence, $header, $footer, $options)
        );
    }

    /**
     * @param string $target
     * @param mixed  $sequence
     * @param string $header
     * @param string $footer
     * @param array  $options
     * @return Append
     */
    private function sequence(
        string $target,
        $sequence,
        string $header,
        string $footer,
        array $options
    ): Append {
        if (is_array($sequence) || $sequence instanceof \Closure) {
            return new Append(
                $target,
                null,
                new CallableSequence($sequence, $options, $header, $footer)
            );
        }

        return new Append(
            $target,
            null,
            new CommandSequence($sequence, $options, $header, $footer)
        );
    }
}