<?php

namespace LeadingSystems\MerconisBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Configures the bundle.
 *
 * @author Leading Systems GmbH
 */
class LeadingSystemsMerconisExtension extends Extension implements PrependExtensionInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__ . '/../Resources/config')
		);

		$loader->load('services.yml');
	}

    /**
     * Prepend configuration for contao-cache so its extension can create pools/handlers.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $file = __DIR__ . '/../Resources/config/contao_cache.yaml';
        if (!is_file($file)) {
            return;
        }
        try {
            $parsed = Yaml::parseFile($file);
            if (is_array($parsed) && isset($parsed['leading_systems_contao_cache']) && is_array($parsed['leading_systems_contao_cache'])) {
                $container->prependExtensionConfig('leading_systems_contao_cache', $parsed['leading_systems_contao_cache']);
            }
        } catch (\Throwable $e) {
            // ignore; config remains optional
        }
    }
}
