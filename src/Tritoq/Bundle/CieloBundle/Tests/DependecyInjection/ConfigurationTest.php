<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\Tests\DependecyInjection;


use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Tritoq\Bundle\CieloBundle\DependencyInjection\Configuration;

/**
 *
 * Tests the Configuration DI
 *
 *
 * Class ConfigurationTest
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\Tests\DependecyInjection
 * @license   GPL-3.0+
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $valuesValid;

    /**
     * @var array
     */
    private $valuesInvalid;

    /**
     * Assert that two arrays are equal. This helper method will sort the two arrays before comparing them if
     * necessary. This only works for one-dimensional arrays, if you need multi-dimension support, you will
     * have to iterate through the dimensions yourself.
     *
     * @param array $expected the expected array
     * @param array $actual the actual array
     * @param bool  $regard_order whether or not array elements may appear in any order, default is false
     * @param bool  $check_keys whether or not to check the keys in an associative array
     */
    protected function assertArraysEqual(array $expected, array $actual, $regard_order = false, $check_keys = true)
    {
        // check length first
        $this->assertEquals(count($expected), count($actual), 'Failed to assert that two arrays have the same length.');

        // sort arrays if order is irrelevant
        if (!$regard_order) {
            if ($check_keys) {
                $this->assertTrue(ksort($expected), 'Failed to sort array.');
                $this->assertTrue(ksort($actual), 'Failed to sort array.');
            } else {
                $this->assertTrue(sort($expected), 'Failed to sort array.');
                $this->assertTrue(sort($actual), 'Failed to sort array.');
            }
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     *
     */
    public function setUp()
    {
        $fileValid = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'config-example.yml';
        $data = Yaml::parse($fileValid);
        $this->valuesValid = $data;


        $fileInValid = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'config-invalid-example.yml';
        $data = Yaml::parse($fileInValid);
        $this->valuesInvalid = $data;
    }

    /**
     *
     */
    public function testOptions()
    {
        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $this->valuesValid);
    }

    /**
     *
     */
    public function testOptionsWithInvalidFormat()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $this->valuesInvalid);
    }
}
