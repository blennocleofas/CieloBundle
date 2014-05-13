<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

/**
 *
 * Carrega e Gerencia as configurações do Bundle
 *
 * Leia mais em {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * Class TritoqCieloExtension
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\DependencyInjection
 * @license   GPL-3.0+
 */
class TritoqCieloExtension extends Extension
{
    /**
     *
     * Carrega as configurações para a DI
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        // Adiciona o parametro para a DI

        $container->setParameter('tritoq.cielo.configuration', $config);
    }
}
