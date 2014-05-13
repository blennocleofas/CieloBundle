<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\Tests\DependecyInjection;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;
use Tritoq\Bundle\CieloBundle\DependencyInjection\TritoqCieloExtension;

class TritoqCieloExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testValidOptions()
    {
        $fileValid = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'config-example.yml';
        $data = Yaml::parse($fileValid);

        $container = new ContainerBuilder();

        $extension = new TritoqCieloExtension();
        $extension->load($data, $container);

    }
}
