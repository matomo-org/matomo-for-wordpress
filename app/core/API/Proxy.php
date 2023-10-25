<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\API;

use Exception;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Context;
use Piwik\Piwik;
use Piwik\Plugin\API;
use Piwik\Plugin\Manager;
use ReflectionClass;
use ReflectionMethod;

// prevent upgrade error eg from Matomo 3.x to Matomo 4.x. Refs https://github.com/matomo-org/matomo/pull/16468
// the `false` is important otherwise it would fail and try to load the proxy.php file again.
if (!class_exists('Piwik\API\NoDefaultValue', false)) {

    // phpcs:ignoreFile PSR1.Classes.ClassDeclaration.MultipleClasses
    class NoDefaultValue
    {
    }
}

/**
 * Proxy is a singleton that has the knowledge of every method available, their parameters
 * and default values.
 * Proxy receives all the API calls requests via call() and forwards them to the right
 * object, with the parameters in the right order.
 *
 * It will also log the performance of API calls (time spent, parameter values, etc.) if logger available
 */
class Proxy
{
    // array of already registered plugins names
    protected $alreadyRegistered = array();

    protected $metadataArray = array();
    private $hideIgnoredFunctions = true;

    // when a parameter doesn't have a default value we use this
    private $noDefaultValue;

    public function __construct()
    {
        $this->noDefaultValue = new NoDefaultValue();
    }

    public static function getInstance()
    {
        return StaticContainer::get(self::class);
    }

    /**
     * Returns array containing reflection meta data for all the loaded classes
     * eg. number of parameters, method names, etc.
     *
     * @return array
     */
    public function getMetadata()
    {
        ksort($this->metadataArray);
        return $this->metadataArray;
    }

    /**
     * Registers the API information of a given module.
     *
     * The module to be registered must be
     * - a singleton (providing a getInstance() method)
     * - the API file must be located in plugins/ModuleName/API.php
     *   for example plugins/Referrers/API.php
     *
     * The method will introspect the methods, their parameters, etc.
     *
     * @param string $className ModuleName eg. "API"
     */
    public function registerClass($className)
    {
        if (isset($this->alreadyRegistered[$className])) {
            return;
        }

        $this->includeApiFile($className);
        $this->checkClassIsSingleton($className);

        $rClass = new ReflectionClass($className);
        if (!$this->shouldHideAPIMethod($rClass->getDocComment())) {
            foreach ($rClass->getMethods() as $method) {
                $this->loadMethodMetadata($className, $method);
            }

            $this->setDocumentation($rClass, $className);
            $this->alreadyRegistered[$className] = true;
        }
    }

    /**
     * Will be displayed in the API page
     *
     * @param ReflectionClass $rClass Instance of ReflectionClass
     * @param string $className Name of the class
     */
    private function setDocumentation($rClass, $className)
    {
        // Doc comment
        $doc = $rClass->getDocComment();
        $doc = str_replace(" * " . PHP_EOL, "<br>", $doc);

        // boldify the first line only if there is more than one line, otherwise too much bold
        if (substr_count($doc, '<br>') > 1) {
            $firstLineBreak = strpos($doc, "<br>");
            $doc = "<div class='apiFirstLine'>" . substr($doc, 0, $firstLineBreak) . "</div>" . substr($doc, $firstLineBreak + strlen("<br>"));
        }
        $doc = preg_replace("/(@package)[a-z _A-Z]*/", "", $doc);
        $doc = preg_replace("/(@method).*/", "", $doc);
        $doc = str_replace(array("\t", "\n", "/**", "*/", " * ", " *", "  ", "\t*", "  *  @package"), " ", $doc);

        // replace 'foo' and `bar` and "foobar" with code blocks... much magic
        $doc = preg_replace('/`(.*?)`/', '<code>$1</code>', $doc);
        $this->metadataArray[$className]['__documentation'] = $doc;
    }

    /**
     * Returns number of classes already loaded
     * @return int
     */
    public function getCountRegisteredClasses()
    {
        return count($this->alreadyRegistered);
    }

    /**
     * Will execute $className->$methodName($parametersValues)
     * If any error is detected (wrong number of parameters, method not found, class not found, etc.)
     * it will throw an exception
     *
     * It also logs the API calls, with the parameters values, the returned value, the performance, etc.
     * You can enable logging in config/global.ini.php (log_api_call)
     *
     * @param string $className The class name (eg. API)
     * @param string $methodName The method name
     * @param array $parametersRequest The parameters pairs (name=>value)
     *
     * @return mixed|null
     * @throws Exception|\Piwik\NoAccessException
     */
    public function call($className, $methodName, $parametersRequest)
    {
        // Temporarily sets the Request array to this API call context
        return Context::executeWithQueryParameters($parametersRequest, function () use ($className, $methodName, $parametersRequest) {
            $this->registerClass($className);

            $request = new \Piwik\Request($parametersRequest);

            /**
             * instantiate the object
             * @var API $object
             */
            $object = $className::getInstance();

            // check method exists
            $this->checkMethodExists($className, $methodName);

            // get the list of parameters required by the method
            $parameterNamesDefaultValuesAndTypes = $this->getParametersListWithTypes($className, $methodName);

            // load parameters in the right order, etc.
            if ($object->usesAutoSanitizeInputParams() && !$this->usesUnsanitizedInputParams($className, $methodName)) {
                $finalParameters = $this->getSanitizedRequestParametersArray($parameterNamesDefaultValuesAndTypes, $request->getParameters());
            } else {
                $finalParameters = $this->getRequestParametersArray($parameterNamesDefaultValuesAndTypes, $request);
            }

            // allow plugins to manipulate the value
            $pluginName = $this->getModuleNameFromClassName($className);

            $returnedValue = null;

            /**
             * Triggered before an API request is dispatched.
             *
             * This event can be used to modify the arguments passed to one or more API methods.
             *
             * **Example**
             *
             *     Piwik::addAction('API.Request.dispatch', function (&$parameters, $pluginName, $methodName) {
             *         if ($pluginName == 'Actions') {
             *             if ($methodName == 'getPageUrls') {
             *                 // ... do something ...
             *             } else {
             *                 // ... do something else ...
             *             }
             *         }
             *     });
             *
             * @param array &$finalParameters List of parameters that will be passed to the API method.
             * @param string $pluginName The name of the plugin the API method belongs to.
             * @param string $methodName The name of the API method that will be called.
             */
            Piwik::postEvent('API.Request.dispatch', array(&$finalParameters, $pluginName, $methodName));

            /**
             * Triggered before an API request is dispatched.
             *
             * This event exists for convenience and is triggered directly after the {@hook API.Request.dispatch}
             * event is triggered. It can be used to modify the arguments passed to a **single** API method.
             *
             * _Note: This is can be accomplished with the {@hook API.Request.dispatch} event as well, however
             * event handlers for that event will have to do more work._
             *
             * **Example**
             *
             *     Piwik::addAction('API.Actions.getPageUrls', function (&$parameters) {
             *         // force use of a single website. for some reason.
             *         $parameters['idSite'] = 1;
             *     });
             *
             * @param array &$finalParameters List of parameters that will be passed to the API method.
             */
            Piwik::postEvent(sprintf('API.%s.%s', $pluginName, $methodName), array(&$finalParameters));

            /**
             * Triggered before an API request is dispatched.
             *
             * Use this event to intercept an API request and execute your own code instead. If you set
             * `$returnedValue` in a handler for this event, the original API method will not be executed,
             * and the result will be what you set in the event handler.
             *
             * @param mixed &$returnedValue Set this to set the result and preempt normal API invocation.
             * @param array &$finalParameters List of parameters that will be passed to the API method.
             * @param string $pluginName The name of the plugin the API method belongs to.
             * @param string $methodName The name of the API method that will be called.
             * @param array $parametersRequest The query parameters for this request.
             */
            Piwik::postEvent('API.Request.intercept', [&$returnedValue, $finalParameters, $pluginName, $methodName, $parametersRequest]);

            $apiParametersInCorrectOrder = array();

            foreach ($parameterNamesDefaultValuesAndTypes as $name => $parameter) {
                if (isset($finalParameters[$name]) || array_key_exists($name, $finalParameters)) {
                    $apiParametersInCorrectOrder[] = $finalParameters[$name];
                }
            }

            // call the method if a hook hasn't already set an output variable
            if ($returnedValue === null) {
                $returnedValue = call_user_func_array(array($object, $methodName), $apiParametersInCorrectOrder);
            }

            $endHookParams = array(
                &$returnedValue,
                array('className'  => $className,
                    'module'     => $pluginName,
                    'action'     => $methodName,
                    'parameters' => $finalParameters)
            );

            /**
             * Triggered directly after an API request is dispatched.
             *
             * This event exists for convenience and is triggered immediately before the
             * {@hook API.Request.dispatch.end} event. It can be used to modify the output of a **single**
             * API method.
             *
             * _Note: This can be accomplished with the {@hook API.Request.dispatch.end} event as well,
             * however event handlers for that event will have to do more work._
             *
             * **Example**
             *
             *     // append (0 hits) to the end of row labels whose row has 0 hits
             *     Piwik::addAction('API.Actions.getPageUrls', function (&$returnValue, $info)) {
             *         $returnValue->filter('ColumnCallbackReplace', 'label', function ($label, $hits) {
             *             if ($hits === 0) {
             *                 return $label . " (0 hits)";
             *             } else {
             *                 return $label;
             *             }
             *         }, null, array('nb_hits'));
             *     }
             *
             * @param mixed &$returnedValue The API method's return value. Can be an object, such as a
             *                              {@link Piwik\DataTable DataTable} instance.
             *                              could be a {@link Piwik\DataTable DataTable}.
             * @param array $extraInfo An array holding information regarding the API request. Will
             *                         contain the following data:
             *
             *                         - **className**: The namespace-d class name of the API instance
             *                                          that's being called.
             *                         - **module**: The name of the plugin the API request was
             *                                       dispatched to.
             *                         - **action**: The name of the API method that was executed.
             *                         - **parameters**: The array of parameters passed to the API
             *                                           method.
             */
            Piwik::postEvent(sprintf('API.%s.%s.end', $pluginName, $methodName), $endHookParams);

            /**
             * Triggered directly after an API request is dispatched.
             *
             * This event can be used to modify the output of any API method.
             *
             * **Example**
             *
             *     // append (0 hits) to the end of row labels whose row has 0 hits for any report that has the 'nb_hits' metric
             *     Piwik::addAction('API.Actions.getPageUrls.end', function (&$returnValue, $info)) {
             *         // don't process non-DataTable reports and reports that don't have the nb_hits column
             *         if (!($returnValue instanceof DataTableInterface)
             *             || in_array('nb_hits', $returnValue->getColumns())
             *         ) {
             *             return;
             *         }
             *
             *         $returnValue->filter('ColumnCallbackReplace', 'label', function ($label, $hits) {
             *             if ($hits === 0) {
             *                 return $label . " (0 hits)";
             *             } else {
             *                 return $label;
             *             }
             *         }, null, array('nb_hits'));
             *     }
             *
             * @param mixed &$returnedValue The API method's return value. Can be an object, such as a
             *                              {@link Piwik\DataTable DataTable} instance.
             * @param array $extraInfo An array holding information regarding the API request. Will
             *                         contain the following data:
             *
             *                         - **className**: The namespace-d class name of the API instance
             *                                          that's being called.
             *                         - **module**: The name of the plugin the API request was
             *                                       dispatched to.
             *                         - **action**: The name of the API method that was executed.
             *                         - **parameters**: The array of parameters passed to the API
             *                                           method.
             */
            Piwik::postEvent('API.Request.dispatch.end', $endHookParams);

            return $returnedValue;
        });
    }

    /**
     * Returns the parameters names and default values for the method $name
     * of the class $class
     *
     * @param string $class The class name
     * @param string $name The method name
     * @return array  Format array(
     *                            'testParameter' => null, // no default value
     *                            'life'          => 42, // default value = 42
     *                            'date'          => 'yesterday',
     *                       );
     */
    public function getParametersList($class, $name)
    {
        return array_combine(
            array_keys($this->metadataArray[$class][$name]['parameters']),
            array_column($this->metadataArray[$class][$name]['parameters'], 'default')
        );
    }

    /**
     * Returns the parameters names, default values and types for the method $name
     * of the class $class
     *
     * @param string $class The class name
     * @param string $name The method name
     * @return array  Format array(
     *                            'testParameter' => ['default' => null, 'type' => null], // no default value
     *                            'life'          => ['default' => 42, 'type' => 'int'], // default value 42, type hint is int
     *                       );
     */
    public function getParametersListWithTypes($class, $name)
    {
        return $this->metadataArray[$class][$name]['parameters'];
    }

    /**
     * Check if given method name is deprecated or not.
     */
    public function isDeprecatedMethod($class, $methodName)
    {
        return $this->metadataArray[$class][$methodName]['isDeprecated'] ?? false;
    }

    /**
     * Check if given method uses unsanitized input parameters.
     */
    public function usesUnsanitizedInputParams($class, $methodName)
    {
        return $this->metadataArray[$class][$methodName]['unsanitizedInputParams'] ?? false;
    }

    /**
     * Returns the 'moduleName' part of '\\Piwik\\Plugins\\moduleName\\API'
     *
     * @param string $className "API"
     * @return string "Referrers"
     */
    public function getModuleNameFromClassName($className)
    {
        return str_replace(array('\\Piwik\\Plugins\\', '\\API'), '', $className);
    }

    public function isExistingApiAction($pluginName, $apiAction)
    {
        $namespacedApiClassName = "\\Piwik\\Plugins\\$pluginName\\API";
        $api = $namespacedApiClassName::getInstance();

        return method_exists($api, $apiAction);
    }

    public function buildApiActionName($pluginName, $apiAction)
    {
        return sprintf("%s.%s", $pluginName, $apiAction);
    }

    /**
     * Sets whether to hide '@ignore'd functions from method metadata or not.
     *
     * @param bool $hideIgnoredFunctions
     */
    public function setHideIgnoredFunctions($hideIgnoredFunctions)
    {
        $this->hideIgnoredFunctions = $hideIgnoredFunctions;

        // make sure metadata gets reloaded
        $this->alreadyRegistered = array();
        $this->metadataArray = array();
    }

    /**
     * Returns an array containing the *sanitized* values of the parameters to pass to the method to call
     *
     * @param array $requiredParameters array of (parameter name, default value)
     * @param array $parametersRequest
     * @throws Exception
     * @return array values to pass to the function call
     */
    private function getSanitizedRequestParametersArray($requiredParameters, $parametersRequest)
    {
        $finalParameters = [];
        foreach ($requiredParameters as $name => $parameter) {
            try {
                $defaultValue = $parameter['default'];
                $type = $parameter['type'];
                $request = new \Piwik\Request($parametersRequest);

                if (in_array($name, ['segment', 'password', 'passwordConfirmation']) && !empty($parametersRequest[$name])) {
                    // special handling for some parameters:
                    // segment: we do not want to sanitize user input as it would break the segment encoding
                    // password / passwordConfirmation: sanitizing this parameters might change special chars in passwords, breaking login and confirmation boxes
                    $requestValue = ($parametersRequest[$name]);
                } elseif ($defaultValue instanceof NoDefaultValue) {
                    if ($type === 'bool') {
                        $requestValue = $request->getBoolParameter($name);
                    } else {
                        $requestValue = Common::getRequestVar($name, null, $type, $parametersRequest);
                    }
                } else {
                    try {
                        if ($type === 'bool') {
                            $requestValue = $request->getBoolParameter($name, $defaultValue);
                        } else {
                            $requestValue = Common::getRequestVar($name, $defaultValue, $type, $parametersRequest);
                        }
                    } catch (Exception $e) {
                        // Special case: empty parameter in the URL, should return the empty string
                        if (isset($parametersRequest[$name])
                            && $parametersRequest[$name] === ''
                        ) {
                            $requestValue = '';
                        } else {
                            $requestValue = $defaultValue;
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Exception(Piwik::translate('General_PleaseSpecifyValue', array($name)));
            }
            $finalParameters[$name] = $requestValue;
        }
        return $finalParameters;
    }

    /**
     * Returns an array containing the values of the parameters to pass to the method to call
     *
     * @param array $requiredParameters array of (parameter name, default value)
     * @param \Piwik\Request $request
     * @throws Exception
     * @return array values to pass to the function call
     */
    private function getRequestParametersArray($requiredParameters, \Piwik\Request $request): array
    {
        $finalParameters = [];
        foreach ($requiredParameters as $name => $parameter) {
            try {
                $defaultValue = $parameter['default'];
                $type = $parameter['type'] ?? '';
                $requestValue = null;

                switch (strtolower($type)) {
                    case 'bool':
                        $method = 'getBoolParameter';
                        break;
                    case 'int':
                        $method = 'getIntegerParameter';
                        break;
                    case 'string':
                        $method = 'getStringParameter';
                        break;
                    case 'float':
                        $method = 'getFloatParameter';
                        break;
                    case 'array':
                        $method = 'getArrayParameter';
                        break;
                    default:
                        $method = 'getParameter';
                }

                if ($defaultValue instanceof NoDefaultValue) {
                    $requestValue = $request->$method($name);
                } elseif ($defaultValue === null) {
                    try {
                        $requestValue = $request->$method($name);
                    } catch (\InvalidArgumentException $e) {
                        $requestValue = null;
                    }
                } else {
                    $requestValue = $request->$method($name, $defaultValue);
                }
            } catch (Exception $e) {
                throw new Exception(Piwik::translate('General_PleaseSpecifyValue', [$name]));
            }
            $finalParameters[$name] = $requestValue;
        }
        return $finalParameters;
    }

    /**
     * Includes the class API by looking up plugins/xxx/API.php
     *
     * @param string $fileName api class name eg. "API"
     * @throws Exception
     */
    private function includeApiFile($fileName)
    {
        $module = self::getModuleNameFromClassName($fileName);
        $path = Manager::getPluginDirectory($module) . '/API.php';

        if (is_readable($path)) {
            require_once $path; // prefixed by PIWIK_INCLUDE_PATH
        } else {
            throw new Exception("API module $module not found.");
        }
    }

    /**
     * @param string $class name of a class
     * @param ReflectionMethod $method instance of ReflectionMethod
     */
    private function loadMethodMetadata($class, $method)
    {
        if (!$this->checkIfMethodIsAvailable($method)) {
            return;
        }
        $name = $method->getName();
        $parameters = $method->getParameters();
        $docComment = $method->getDocComment();

        $aParameters = array();
        foreach ($parameters as $parameter) {
            $nameVariable = $parameter->getName();

            $defaultValue = $this->noDefaultValue;
            if ($parameter->isDefaultValueAvailable()) {
                $defaultValue = $parameter->getDefaultValue();
            }

            $type = $parameter->getType();

            // In case no default value is defined in the method, but the type hint allows null, we assume null as default value
            if ($type && $type->allowsNull() && $defaultValue === $this->noDefaultValue) {
                $defaultValue = null;
            }

            $aParameters[$nameVariable] = [
                'default' => $defaultValue,
                'type' => ($type && $type->isBuiltin()) ? $type->getName() : null,
                'allowsNull' => $type ? $type->allowsNull() : $defaultValue === null,
            ];
        }
        $this->metadataArray[$class][$name]['parameters'] = $aParameters;
        $this->metadataArray[$class][$name]['numberOfRequiredParameters'] = $method->getNumberOfRequiredParameters();
        $this->metadataArray[$class][$name]['isDeprecated'] = false !== strstr($docComment, '@deprecated');
        $this->metadataArray[$class][$name]['unsanitizedInputParams'] = false !== strstr($docComment, '@unsanitized');
    }

    /**
     * Checks that the method exists in the class
     *
     * @param string $className The class name
     * @param string $methodName The method name
     * @throws Exception If the method is not found
     */
    private function checkMethodExists($className, $methodName)
    {
        if (!$this->isMethodAvailable($className, $methodName)) {
            throw new Exception(Piwik::translate('General_ExceptionMethodNotFound', array($methodName, $className)));
        }
    }

    /**
     * @param $docComment
     * @return bool
     */
    public function shouldHideAPIMethod($docComment)
    {
        $hideLine = strstr($docComment, '@hide');

        if ($hideLine === false) {
            return false;
        }

        $hideLine = trim($hideLine);
        $hideLine .= ' ';

        $token = trim(strtok($hideLine, " "), "\n");

        $hide = false;

        if (!empty($token)) {
            /**
             * This event exists for checking whether a Plugin API class or a Plugin API method tagged
             * with a `@hideXYZ` should be hidden in the API listing.
             *
             * @param bool &$hide whether to hide APIs tagged with $token should be displayed.
             */
            Piwik::postEvent(sprintf('API.DocumentationGenerator.%s', $token), array(&$hide));
        }

        return $hide;
    }

    /**
     * @param ReflectionMethod $method
     * @return bool
     */
    protected function checkIfMethodIsAvailable(ReflectionMethod $method)
    {
        if (!$method->isPublic() || $method->isConstructor() || $method->getName() === 'getInstance') {
            return false;
        }

        if ($this->hideIgnoredFunctions && false !== strstr($method->getDocComment(), '@ignore')) {
            return false;
        }

        if ($this->shouldHideAPIMethod($method->getDocComment())) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the method is found in the API of the given class name.
     *
     * @param string $className The class name
     * @param string $methodName The method name
     * @return bool
     */
    private function isMethodAvailable($className, $methodName)
    {
        return isset($this->metadataArray[$className][$methodName]);
    }

    /**
     * Checks that the class is a Singleton (presence of the getInstance() method)
     *
     * @param string $className The class name
     * @throws Exception If the class is not a Singleton
     */
    private function checkClassIsSingleton($className)
    {
        if (!method_exists($className, "getInstance")) {
            throw new Exception("$className that provide an API must be Singleton and have a 'public static function getInstance()' method.");
        }
    }
}
