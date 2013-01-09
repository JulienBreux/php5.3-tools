<?php
/**
 * Tools by Julien Breux <julien.breux@gmail.com>
 */

namespace Breux\Loader;

/**
 * Loader
 */
class Loader
{
	/** @var array Paths */
	protected $paths = array();

	/** @var array Prefixes */
	protected $prefixes = array();

	/**
	 * Add path
	 *
	 * @param string $path
	 *
	 * @return Loader
	 */
	public function addPath($path)
	{
		$this->paths[] = $path;

		return $this;
	}

	/**
	 * Add paths
	 *
	 * @param array $paths
	 *
	 * @return Loader
	 */
	public function addPaths(array $paths)
	{
		foreach ($paths as $path)
		{
			$this->addPath($path);
		}

		return $this;
	}

	/**
	 * Get paths
	 *
	 * @return array
	 */
	public function getPaths()
	{
		return $this->paths;
	}

	/**
	 * Remove path
	 *
	 * @param string $path
	 *
	 * @return Loader
	 * @throws Exception\InvalidArgumentException
	 */
	public function removePath($path)
	{
		if (array_key_exists($path, $this->paths))
		{
			throw new Exception\InvalidArgumentException("Unable to remove path. '$path' not found.");
		}

		unset($this->paths[$path]);

		return $this;
	}

	/**
	 * Add prefix
	 *
	 * @param string $prefix
	 * @param array $paths
	 *
	 * @return Loader
	 */
	public function addPrefix($prefix, $paths)
	{
        if (isset($this->prefixes[$prefix]))
		{
			$this->prefixes[$prefix] = array_merge($this->prefixes[$prefix], (array)$paths);
		}
		else
		{
			$this->prefixes[$prefix] = (array)$paths;
		}

		return $this;
	}

	/**
	 * Set prefixes
	 *
	 * @param array $prefixes
	 *
	 * @return Loader
	 */
	public function addPrefixes(array $prefixes)
	{
		foreach ($prefixes as $prefix => $paths)
		{
			$this->addPrefix($prefix, $paths);
		}

		return $this;
	}

	/**
	 * Get prefixes
	 *
	 * @return array
	 */
	public function getPrefixes()
	{
		return $this->prefixes;
	}

	/**
	 * Remove prefix
	 *
	 * @param string $prefix
	 *
	 * @return Loader
	 * @throws Exception\InvalidArgumentException
	 */
	public function removePrefix($prefix)
	{
		if (array_key_exists($prefix, $this->prefixes))
		{
			throw new Exception\InvalidArgumentException("Unable to remove prefix. '$prefix' not found.");
		}

		unset($this->prefixes[$prefix]);

		return $this;
	}

	/**
	 * Register loader
	 *
	 * @param callback $callback
	 * @param bool $prepend
	 *
	 * @return bool
	 */
	public function register($callback = NULL, $prepend = FALSE)
	{
		if (is_null($callback))
		{
			$callback = array($this, 'load');
		}

		return \spl_autoload_register($callback, true, $prepend);
	}

	/**
	 * Default loader
	 *
	 * @see Symfony 2 - ClassLoader
	 *
	 * @param string $class
	 *
	 * @return bool
	 */
	public function load($class = '')
	{
		// Remove first \
		if ($class[0] == '\\')
		{
			$class = substr($class, 1);
		}

		// Replace namespace separator by directory separator
		if (($pos = strrpos($class, '\\')) !== false)
		{
			$classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos)).DIRECTORY_SEPARATOR;
			$className = substr($class, $pos + 1);
		}
		// Use for PEAR-like class name
		else
		{
			$classPath = null;
			$className = $class;
		}

		$classPath .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		// PEAR-like require
		foreach ($this->prefixes as $prefix => $paths)
		{
			if (strpos($class, $prefix) === 0)
			{
				foreach ($paths as $path)
				{
					if (file_exists($path.DIRECTORY_SEPARATOR.$classPath))
					{
						$file = $path.DIRECTORY_SEPARATOR.$classPath;
					}
				}
			}
		}

		// Default require
		foreach ($this->paths as $path)
		{
			if (file_exists($path.DIRECTORY_SEPARATOR.$classPath))
			{
				$file = $path.DIRECTORY_SEPARATOR.$classPath;
			}
		}

		if (isset($file))
		{
			require $file;

			return true;
		}

		return false;
	}
}
