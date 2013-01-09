<?php
/**
 * Tools by Julien Breux <julien.breux@gmail.com>
 */

namespace Breux\Database;

class Configuration
{
	/** @const string Default instance name */
	const DEFAULT_INSTANCE_NAME = 'master';

	/** @var string Instance name */
	protected $instanceName = self::DEFAULT_INSTANCE_NAME;

	/** @var string Host */
	protected $host = '127.0.0.1';

	/** @var string User */
	protected $user = 'root';

	/** @var string Password */
	protected $password = '';

	/** @var string Database name */
	protected $database = '';

	/** @var string Adapter */
	protected $adapter = 'mysql';

	/** @var int Port */
	protected $port = 3306;

	/** @var string DSN */
	protected $dsn = NULL;

	/** @var bool Debug */
	protected $debug = false;

	/**
	 * Constructor
	 *
	 * @param $instanceName
	 */
	public function __construct($instanceName = self::DEFAULT_INSTANCE_NAME)
	{
		$this->setInstanceName($instanceName);
	}

	/**
	 * @param string $instanceName
	 *
	 * @return Configuration
	 */
	public function setInstanceName($instanceName)
	{
		$this->instanceName = $instanceName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInstanceName()
	{
		return $this->instanceName;
	}

	/**
	 * @param $adapter
	 *
	 * @return Configuration
	 */
	public function setAdapter($adapter)
	{
		$this->adapter = $adapter;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * @param string $database
	 *
	 * @return Configuration
	 */
	public function setDatabase($database)
	{
		$this->database = $database;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * @param boolean $debug
	 *
	 * @return Configuration
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getDebug()
	{
		return $this->debug;
	}

	/**
	 * @param string $dsn
	 *
	 * @return Configuration
	 */
	public function setDsn($dsn)
	{
		$this->dsn = $dsn;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDsn()
	{
		return $this->dsn;
	}

	/**
	 * @param string $host
	 *
	 * @return Configuration
	 */
	public function setHost($host)
	{
		$this->host = $host;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @param int $port
	 *
	 * @return Configuration
	 */
	public function setPort($port)
	{
		$this->port = $port;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @param string $user
	 *
	 * @return Configuration
	 */
	public function setUser($user)
	{
		$this->user = $user;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param string $password
	 *
	 * @return Configuration
	 */
	public function setPassword($password)
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}
}
