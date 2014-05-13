<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Tritoq\Payment\Cielo\AnaliseRisco;
use Tritoq\Payment\Cielo\Loja;
use Tritoq\Payment\Cielo\Transacao;

/**
 *
 * Arvore de configuração do Builder
 *
 *
 * Class Configuration
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\DependencyInjection
 * @license   GPL-3.0+
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        // TODO: Implement getConfigTreeBuilder() method.
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tritoq_cielo');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        /*
         *  Carrega as informações estáticas para autenticação ao Web service da Cielo
         */


        $supportedEnvironments = array(Loja::AMBIENTE_TESTE, Loja::AMBIENTE_PRODUCAO);
        $supportedAuthMethods = array(
            Transacao::AUTORIZAR_AUTENTICADA_NAO_AUTENTICADA,
            Transacao::AUTORIZAR_NAO_AUTORIZAR,
            Transacao::AUTORIZAR_SEM_AUTENTICACAO,
            Transacao::AUTORIZAR_SOMENTE_AUTENTICADA
        );

        $supportedTypeAnalysis = array(
            AnaliseRisco::ACAO_CAPTURAR,
            AnaliseRisco::ACAO_DESFAZER,
            AnaliseRisco::ACAO_MANUAL_POSTERIOR
        );

        $rootNode

            ->children()

            ->arrayNode('loja')
            ->children()
            // Nome da Loja
            ->scalarNode('nome')
            ->cannotBeEmpty()
            ->isRequired()
            ->end()

            // Tipo de ambiente
            ->scalarNode('ambiente')
            ->cannotBeEmpty()
            ->isRequired()
            ->validate()
            ->ifNotInArray($supportedEnvironments)
            ->thenInvalid('Ambiente não suportado')
            ->end()
            ->end()

            // URL de Retorno
            ->scalarNode('url_retorno')
            ->cannotBeEmpty()
            ->isRequired()
            ->end()

            // Número da Loja junto a Cielo
            ->scalarNode('numero_loja')->defaultValue(Loja::LOJA_NUMERO_AMBIENTE_TESTE)->end()

            // Chave de autentição ao Webservice da Cielo
            ->scalarNode('chave')->defaultValue(Loja::LOJA_CHAVE_AMBIENTE_TESTE)->end()
            ->end()
            ->end()

            ->arrayNode('transacao')
            ->children()
            // Como autorizar a transação
            ->integerNode('autorizar')
            ->cannotBeEmpty()
            ->isRequired()
            ->validate()
            ->ifNotInArray($supportedAuthMethods)
            ->thenInvalid('Opção autorizar inválida')
            ->end()
            ->end()
            // Captura automática
            ->booleanNode('capturar')->isRequired()->end()
            ->end()
            ->end()

            ->arrayNode('pedido')
            ->children()
            // Idioma
            ->scalarNode('idioma')->defaultValue('PT')->end()
            ->end()
            ->end()

            ->arrayNode('analise_risco')
            ->children()
            // Analise de Risco
            ->booleanNode('ativo')->defaultValue(false)->end()
            ->booleanNode('afs')->defaultValue(true)->end()

            ->scalarNode('alto_risco')
            ->defaultValue(AnaliseRisco::ACAO_MANUAL_POSTERIOR)
            ->validate()
            ->ifNotInArray($supportedTypeAnalysis)
            ->thenInvalid('Ação para análise inválida')
            ->end()
            ->end()

            ->scalarNode('medio_risco')
            ->defaultValue(AnaliseRisco::ACAO_MANUAL_POSTERIOR)
            ->validate()
            ->ifNotInArray($supportedTypeAnalysis)
            ->thenInvalid('Ação para análise inválida')
            ->end()
            ->end()
            ->scalarNode('baixo_risco')
            ->defaultValue(AnaliseRisco::ACAO_CAPTURAR)
            ->validate()
            ->ifNotInArray($supportedTypeAnalysis)
            ->thenInvalid('Ação para análise inválida')
            ->end()
            ->end()
            ->scalarNode('erro_dados')
            ->defaultValue(AnaliseRisco::ACAO_MANUAL_POSTERIOR)
            ->validate()
            ->ifNotInArray($supportedTypeAnalysis)
            ->thenInvalid('Ação para análise inválida')
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
