<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluSnippetManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function count;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_snippet_manager');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('navigation')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('navigation_title')->isRequired()->end()
                            ->scalarNode('type')->defaultNull()->end()
                            ->scalarNode('list_view_key')->defaultValue('snippets')->end()
                            ->integerNode('order')->defaultValue(0)->end()
                            ->scalarNode('icon')->defaultValue('su-snippet')->end()
                            ->append($this->addChildrenNode())
                            ->append($this->addTabsNode())
                        ->end()
                        ->validate()
                            ->ifTrue(function ($config) {
                                return $this->isInvalidNavigationEntry($config);
                            })
                            ->thenInvalid("The 'type' must be defined when no children are given.")
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addChildrenNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('children');

        $node
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('navigation_title')->isRequired()->end()
                    ->scalarNode('type')->isRequired()->end()
                    ->scalarNode('list_view_key')->defaultValue('snippets')->end()
                    ->integerNode('order')->defaultValue(0)->end()
                    ->scalarNode('icon')->defaultValue('su-snippet')->end()
                    ->append($this->addTabsNode())
                ->end()
            ->end();

        return $node;
    }

    /**
     * Additional tabs shown on a snippet-type's edit view. When "secured" is true
     * (default), each tab registers an EDIT security context
     * ("snippet_manager.<type>_<key>") so it can be enabled per role in the permission
     * management. Two types are supported:
     *  - "form" (default): renders a FormView for the given "form_key".
     *  - "automation": renders the sulu/automation-bundle task list (requires that
     *    bundle to be installed); "form_key" is not used and "tab_title" is optional.
     */
    private function addTabsNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('tabs');

        $node
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->enumNode('type')->values(['form', 'automation'])->defaultValue('form')->end()
                    ->scalarNode('form_key')->defaultNull()->end()
                    ->scalarNode('tab_title')->defaultNull()->end()
                    ->integerNode('tab_order')->defaultValue(45)->end()
                    ->scalarNode('path')->defaultNull()->end()
                    ->booleanNode('secured')->defaultTrue()->end()
                    ->booleanNode('title_visible')->defaultTrue()->end()
                ->end()
                ->validate()
                    ->ifTrue(static function (array $tab): bool {
                        return 'form' === $tab['type'] && (null === $tab['form_key'] || null === $tab['tab_title']);
                    })
                    ->thenInvalid('The "form_key" and "tab_title" options are required for tabs of type "form".')
                ->end()
            ->end();

        return $node;
    }

    private function isInvalidNavigationEntry(array $config): bool
    {
        $hasChildren = isset($config['children']) && count($config['children']) > 0;
        $hasType = isset($config['type']);

        return !$hasChildren && !$hasType;
    }
}
