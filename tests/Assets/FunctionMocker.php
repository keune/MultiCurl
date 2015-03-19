<?php

namespace BluePsyduckTests\MultiCurl\Assets;

use PHPUnit_Framework_TestCase;

/**
 * The FunctionMocker is able to mock plain PHP functions.
 *
 * The trick is to redefine the functions within the namespace where they get called, so that the mocked function is
 * preferred the PHP internal function on calling. Therefor each function mock is coupled to a concrete namespace.
 *
 * @author Marcel <marcel@mania-community.de>
 */
class FunctionMocker {
    /**
     * The test case instance.
     * @var \PHPUnit_Framework_TestCase
     */
    protected $testCase;

    /**
     * The namespace to mock the function into.
     * @var string
     */
    protected $namespace;

    /**
     * The functions to mock.
     * @var array
     */
    protected $functions = array();

    /**
     * The mocked functions.
     * @var array
     */
    private static $mockedFunctions = array();

    /**
     * The created mock objects.
     * @var array
     */
    private static $currentMocks = array();

    /**
     * Sets the test case instance.
     * @param \PHPUnit_Framework_TestCase $testCase
     * @return $this Implementing fluent interface.
     */
    public function setTestCase(PHPUnit_Framework_TestCase $testCase) {
        $this->testCase = $testCase;
        return $this;
    }

    /**
     * Sets the namespace to mock the function into.
     * @param string $namespace
     * @return $this Implementing fluent interface.
     */
    public function setNamespace($namespace) {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Sets the functions to mock.
     * @param array $functions
     * @return $this Implementing fluent interface.
     */
    public function setFunctions($functions) {
        $this->functions = $functions;
        return $this;
    }

    /**
     * Returns the mock object.
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getMock() {
        $mock = $this->testCase->getMockBuilder('stdClass')
                               ->setMethods($this->functions)
                               ->setMockClassName('BluePsyduckTests_MultiCurl_Assets_FunctionMocker_' . uniqid())
                               ->getMock();

        foreach ($this->functions as $function) {
            if (!isset(self::$mockedFunctions[$this->namespace][$function])) {
                $code = $this->generateCode($this->namespace, $function);
                eval($code);
                self::$mockedFunctions[$this->namespace][$function] = $function;
            }
        }

        self::$currentMocks[$this->namespace] = $mock;
        return $mock;
    }

    /**
     * generates the code for mocking a function.
     * @param string $namespace The namespace to mock the function into.
     * @param string $function The function name.
     * @return string The PHP code.
     */
    protected function generateCode($namespace, $function) {
        return <<<EOT
namespace $namespace {
    use BluePsyduckTests\MultiCurl\Assets\FunctionMocker;
    function $function() {
        return FunctionMocker::invokeMockedFunction('$namespace', '$function', func_get_args());
    }
}
EOT;
    }

    /**
     * Invokes a mocked function.
     * @param string $namespace The namespace the function has been mocked into.
     * @param string $function The function name.
     * @param array $parameters The parameters to be passed to the function.
     * @return mixed The result of the function call.
     */
    public static function invokeMockedFunction($namespace, $function, $parameters = array()) {
        $callback = null;
        if (isset(self::$currentMocks[$namespace])) {
            $callback = array(self::$currentMocks[$namespace], $function);
        }
        if (!is_callable($callback)) {
            $callback = $function;
        }
        return call_user_func_array($callback, $parameters);
    }

    /**
     * Resets any saved mocks.
     */
    public static function resetCurrentMocks() {
        self::$currentMocks = array();
    }
}