<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\Http;

use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Component;
use Spiral\Core\Container;

class MiddlewarePipe extends Component
{
    /**
     * Container.
     *
     * @invisible
     * @var Container
     */
    protected $container = null;

    /**
     * Set of middleware layers builded to handle incoming Request and return Response. Middleware
     * can be represented as class, string (DI) or array (callable method).
     *
     * @var array|MiddlewareInterface[]
     */
    protected $middleware = array();

    /**
     * Final endpoint has to be called, this is "the deepest" part of pipeline. It's not necessary
     * that this endpoint will be called at all, as one of middleware layers can stop processing.
     *
     * @var callable
     */
    protected $target = null;

    /**
     * Middleware Pipeline used by HttpDispatchers to pass request thought middleware(s) and receive
     * filtered result. Pipeline can be used outside dispatcher in routes, modules and controllers.
     *
     * @param Container             $container
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(Container $container, array $middleware = array())
    {
        $this->container = $container;
        $this->middleware = $middleware;
    }

    /**
     * Add new middleware to end of chain. Middleware can be represented as class, string (DI) or
     * array (callable method). Use can use closures to specify middleware. Every middleware will
     * receive 3 parameters, Request, next closure and context.
     *
     * @param mixed $middleware
     * @return static
     */
    public function add($middleware)
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Every pipeline should have specified target to generate "deepest" response instance or other
     * response data (depends on context). Target should always be specified.
     *
     * @param callable $target
     * @return static
     */
    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Run pipeline chain with specified input request and context. Response type depends on target
     * method and middleware logic.
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    public function run(ServerRequestInterface $request)
    {
        return $this->next(0, $request);
    }

    /**
     * Internal method used to jump between middleware layers.
     *
     * @param int                    $position
     * @param ServerRequestInterface $request
     * @return mixed
     */
    protected function next($position = 0, $request = null)
    {
        $next = function ($contextInput = null) use ($position, $request)
        {
            return $this->next(++$position, $contextInput ?: $request);
        };

        if (!isset($this->middleware[$position]))
        {
            if ($this->target instanceof \Closure)
            {
                $reflection = new \ReflectionFunction($this->target);

                $arguments = array();
                if (!empty($request))
                {
                    $arguments['request'] = $request;
                }

                return $reflection->invokeArgs(
                    $this->container->resolveArguments($reflection, $arguments)
                );
            }

            return call_user_func($this->target, $request);
        }

        /**
         * @var callable $middleware
         */
        $middleware = $this->middleware[$position];
        $middleware = is_string($middleware)
            ? $this->container->get($middleware)
            : $middleware;

        return $middleware($request, $next);
    }
}