<?php

namespace LeadingSystems\MerconisBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class LeadingSystemsMerconisExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $file = __DIR__ . '/../Resources/config/contao_cache.yaml';
        if (!is_file($file)) {
            return;
        }

        $parsed = Yaml::parseFile($file);
        if (is_array($parsed) && isset($parsed['leading_systems_contao_cache'])) {
            $container->prependExtensionConfig('leading_systems_contao_cache', $parsed['leading_systems_contao_cache']);
        }
    }

	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);

		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__ . '/../Resources/config')
		);

		$loader->load('services.yml');

		// Set parameters from config tree (with defaults applied by Configuration)
		$container->setParameter('merconis.search.input_mapping.enabled', (bool)($config['search']['input_mapping']['enabled'] ?? true));
		$container->setParameter('merconis.search.input_mapping.apply_in_elasticsearch', (bool)($config['search']['input_mapping']['apply_in_elasticsearch'] ?? false));
		$container->setParameter('merconis.search.server.adapter', (string)($config['search']['server']['adapter'] ?? 'DirectMySQL'));
	}
}
