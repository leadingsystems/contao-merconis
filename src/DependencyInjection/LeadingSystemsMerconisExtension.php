<?php

namespace LeadingSystems\MerconisBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class LeadingSystemsMerconisExtension extends Extension
{
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
	}
}
