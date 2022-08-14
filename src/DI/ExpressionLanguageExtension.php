<?php declare(strict_types = 1);

namespace WebChemistry\ExpressionLanguage\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExpressionLanguageExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'cache' => Expect::anyOf(Expect::string(), Expect::type(Statement::class))->nullable()->default(
				class_exists(FilesystemAdapter::class) ? FilesystemAdapter::class : null
			),
		]);
	}

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('expression.language'))
			->setFactory(ExpressionLanguage::class, [$this->createCache()]);
	}

	private function createCache(): ?Statement
	{
		/** @var stdClass $config */
		$config = $this->getConfig();

		if ($config->cache instanceof Statement) {
			return $config->cache;
		}

		if ($config->cache === FilesystemAdapter::class) {
			return new Statement(
				FilesystemAdapter::class,
				[
					'namespace' => 'Symfony.ExpressionLanguage',
					'directory' => $this->getContainerBuilder()->parameters['tempDir'] . '/cache',
				]
			);
		}

		return is_string($config->cache) ? new Statement($config->cache) : null;
	}

}
