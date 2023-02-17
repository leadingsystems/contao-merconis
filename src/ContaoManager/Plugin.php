<?php

namespace LeadingSystems\MerconisBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Plugin for the Contao Manager.
 *
 * @author Leading Systems GmbH
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getBundles(ParserInterface $parser)
	{
		return [
			BundleConfig::create('LeadingSystems\MerconisBundle\LeadingSystemsMerconisBundle')
				->setLoadAfter(
					[
						'Contao\CoreBundle\ContaoCoreBundle',
						'LeadingSystems\HelpersBundle\LeadingSystemsHelpersBundle',
						'LeadingSystems\LSJS4CBundle\LeadingSystemsLSJS4CBundle',
						'LeadingSystems\LSJS4CBundle\LeadingSystemsLSCSS4CBundle',
						'LeadingSystems\ApiBundle\LeadingSystemsApiBundle',
						'LeadingSystems\CajaxBundle\LeadingSystemsCajaxBundle',
						'LeadingSystems\LanguageSelectorBundle\LeadingSystemsLanguageSelectorBundle',
						'LeadingSystems\DataCollectorBundle\LeadingSystemsDataCollectorBundle'
					]
				)
				->setReplace(['zzz_merconis'])
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
	{
		return $resolver
			->resolve(__DIR__ . '/../Resources/config/routing.yml')
			->load(__DIR__ . '/../Resources/config/routing.yml');
	}
}
