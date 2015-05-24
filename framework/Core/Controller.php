<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Core;

use Spiral\Core\Dispatcher\ClientException;
use Spiral\Components;

/**
 * @property Core                                     $core
 * @property Components\Http\HttpDispatcher           $http
 * @property Components\Console\ConsoleDispatcher     $console
 * @property Loader                                   $loader
 * @property Components\Modules\ModuleManager         $modules
 * @property Components\Files\FileManager             $file
 * @property Components\Debug\Debugger                $debug
 * @property Components\Tokenizer\Tokenizer           $tokenizer
 * @property Components\Cache\CacheManager            $cache
 * @property Components\I18n\Translator               $i18n
 * @property Components\View\ViewManager              $view
 * @property Components\Redis\RedisManager            $redis
 * @property Components\Encrypter\Encrypter           $encrypter
 * @property Components\Image\ImageManager            $image
 * @property Components\Storage\StorageManager        $storage
 * @property Components\DBAL\DatabaseManager          $dbal
 * @property Components\ODM\ODM                       $odm
 * @property Components\ORM\ORM                       $orm
 *
 *
 * @property \Psr\Http\Message\ServerRequestInterface $request
 * @property Components\Http\Cookies\CookieManager    $cookies
 * @property Components\Session\SessionStore          $session
 * @property Components\Http\Router\Router            $router
 */
class Controller extends Component implements ControllerInterface
{
    /**
     * Default action to run. This action will be performed if dispatcher didn't specified another
     * action to run.
     *
     * @var string
     */
    protected $defaultAction = 'index';

    /**
     * Action prefix will be assigned to every provided action. Useful when you need methods like
     * "new", "list" and etc.
     *
     * @var string
     */
    protected $actionPrefix = '';

    /**
     * Last set of parameters passed to callAction method,
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * Method executed before controller action beign called. Should return nothing to let controller
     * execute action itself. Any returned result will prevent action execution and will be returned
     * from callAction.
     *
     * @param string $method    Method name (normalized via reflection).
     * @param array  $arguments Method arguments.
     * @return mixed
     */
    protected function beforeAction($method, array $arguments)
    {
        return null;
    }

    /**
     * Method executed after controller action beign called. Original or altered result should be
     * returned.
     *
     * @param string $method    Method name (normalized via reflection).
     * @param array  $arguments Method arguments.
     * @param mixed  $result    Method result (plain output not included).
     * @return mixed
     */
    protected function afterAction($method, array $arguments, $result)
    {
        return $result;
    }

    /**
     * Performing controller action. This method should either return response object or string, or
     * any other type supported by specified dispatcher. This method can be overwritten in child
     * controller to force some specific Response or modify output from every controller action.
     *
     * @param string $action     Method name.
     * @param array  $parameters Set of parameters to populate controller method.
     * @return mixed
     * @throws ClientException
     */
    public function callAction($action = '', array $parameters = array())
    {
        if (empty($action))
        {
            $action = $this->defaultAction;
        }

        $action = $this->actionPrefix . $action;
        if (!method_exists($this, $action))
        {
            throw new ClientException(ClientException::NOT_FOUND);
        }

        $reflection = new \ReflectionMethod($this, $action);

        if (
            $reflection->isStatic()
            || !$reflection->isPublic()
            || !$reflection->isUserDefined()
            || $reflection->getDeclaringClass()->getName() == __CLASS__
        )
        {
            throw new ClientException(ClientException::NOT_FOUND, "Action is not allowed");
        }

        $this->parameters = $parameters;

        //Getting set of arguments should be sent to requested method
        $arguments = $this->core->resolveArguments($reflection, $parameters, true);

        foreach ($reflection->getParameters() as $parameter)
        {
            if (!array_key_exists($parameter->getPosition(), $arguments) && !$parameter->isOptional())
            {
                throw new ClientException(
                    ClientException::BAD_DATA, "Missing parameter '{$parameter->name}'"
                );
            }
        }

        $action = $reflection->getName();

        if (($result = $this->beforeAction($action, $arguments)) !== null)
        {
            //Got filtered.
            return $result;
        }

        benchmark(get_called_class(), $action);
        $result = $reflection->invokeArgs($this, $arguments);
        benchmark(get_called_class(), $action);

        return $this->afterAction($action, $arguments, $result);
    }

    /**
     * An alias for Container::getInstance()->get() method to retrieve components by their alias.
     *
     * @param string $name Binding or component name/alias.
     * @return Component
     */
    public function __get($name)
    {
        return Container::getInstance()->get($name);
    }
}