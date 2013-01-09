<?php
/**
 * Tools by Julien Breux <julien.breux@gmail.com>
 */

namespace Breux\Database;

class Factory
{
	/** @const string Default instance name */
	const DEFAULT_INSTANCE_NAME = 'master';

	/** @var array Database instances */
	protected static $instances = array();

	/** @var array Database configurations */
	protected static $configurations = array();

	/**
	 * Get database instance
	 *
	 * @param string $instanceName Instance name
	 *
	 * @return Database instance
	 */
	public static function getDatabase($instanceName = self::DEFAULT_INSTANCE_NAME)
	{
		if (!isset(self::$instances[$instanceName]) || !(self::$instances[$instanceName] instanceof Database))
		{
			$configuration = self::getConfiguration($instanceName);
			self::$instances[$instanceName] = new Database($configuration);
		}

		return self::$instances[$instanceName];
	}

	/**
	 * Register configurations
	 *
	 * @param array $configurations
	 */
	public static function registerConfigurations(array $configurations)
	{
		foreach ($configurations as $configuration)
		{
			self::registerConfiguration($configuration);
		}
	}

	/**
	 * Register configuration
	 *
	 * @param Configuration $configuration
	 */
	public static function registerConfiguration(Configuration $configuration)
	{
		self::$configurations[$configuration->getInstanceName()] = $configuration;
	}

	/**
	 * Get configuration form instance name
	 *
	 * @param string $instanceName Instance name
	 *
	 * @return mixed
	 * @throws Exceptions\RuntimeException
	 */
	public static function getConfiguration($instanceName)
	{
		if (!array_key_exists($instanceName, self::$configurations))
		{
			throw new Exceptions\RuntimeException('Database configuration error: Unable to find "'.$instanceName.'".');
		}

		return self::$configurations[$instanceName];
	}
}
