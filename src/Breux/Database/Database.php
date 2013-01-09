<?php
/**
 * Tools by Julien Breux <julien.breux@gmail.com>
 */

namespace Breux\Database;

class Database
{
	/** @var Configuration Configuration object */
	protected $configuration;

	/** @var \Pdo Connection resource */
	protected $resource;

	/** @var \PdoStatement Result */
	protected $result;

	/** @var int Last insert ID */
	protected $lastID;

	/** @var array Queries */
	protected $queries = array();

	/**
	 * Constructor
	 *
	 * @param Configuration $configuration Configuration
	 *
	 * @throws Exceptions\RuntimeException
	 */
	public function __construct(Configuration $configuration)
	{
		$this->setConfiguration($configuration);

		if (!$configuration->getDsn())
		{
			switch ($configuration->getAdapter())
			{
				case 'mysql':
					$dns = $configuration->getAdapter().':dbname='.$configuration->getDatabase().';host='.$configuration->getHost();
					if ($configuration->getPort())
					{
						$dns .= ';port='.$configuration->getPort();
					}
					$configuration->setDsn($dns);
					break;

				case 'sqlite':
					$configuration->setDsn($configuration->getAdapter().':'.$configuration->getDatabase());
					break;

				default:
					throw new Exceptions\RuntimeException('Database adapter error: "'.$configuration->getAdapter().'" not found.');
			}
		}


		$this->connect($configuration->getDsn(), $configuration->getUser(), $configuration->getPassword());
	}

	/**
	 * Connect
	 *
	 * Problem with date.timezone ?
	 * Set date_default_timezone_set to Europe/Paris
	 * (E.g. date_default_timezone_set('Europe/Paris'))
	 *
	 * @param string $dns DSN
	 * @param string $user User
	 * @param string $password Password
	 *
	 * @return bool
	 * @throws Exceptions\RuntimeException
	 */
	public function connect($dns, $user, $password)
	{
		// Connect
		try
		{
			$this->resource = new \PDO($dns, $user, $password);
		}
		catch (\PDOException $e)
		{
			throw new Exceptions\RuntimeException(
				'Database Error: Unable to connect to the database.'.PHP_EOL.$e->getMessage()
			);
		}

		// Enable UTF8
		if (!$this->enableUTF8() && $this->configuration->getDebug())
		{
			throw new Exceptions\RuntimeException(
				'Database Error: Unable to enable UTF8.'
			);
		}
		else
		{
			return true;
		}
	}

	/**
	 * Disconnection
	 */
	public function disconnect()
	{
		$this->resource = NULL;
	}

	/**
	 * Enable UTF-8
	 *
	 * @return bool Success
	 */
	public function enableUTF8()
	{
		return (bool)$this->resource->query("SET NAMES 'utf8'");
	}

	/**
	 * Escape value
	 * (for security reasons)
	 *
	 * @param string $value
	 * @param bool $allowHTML
	 *
	 * @return string Value escaped
	 */
	public function escape($value, $allowHTML = false)
	{
		// Check magic quotes
		if (get_magic_quotes_gpc())
		{
			$value = stripslashes($value);
		}

		// Not numeric
		if (!is_numeric($value))
		{
			// Not allow HTML
			if (!$allowHTML)
			{
				$value = strip_tags($value);
			}
		}

		return $value;
	}

	/**
	 * Get Error Number
	 *
	 * @return int Error Number
	 */
	public function getErrorNumber()
	{
		$error = $this->resource->errorInfo();
		return isset($error[1])
			? $error[1]
			: 0;
	}

	/**
	 * Get Value
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return bool|mixed Value
	 */
	public function getValue($query, array $params = array())
	{
		$row = $this->getRow($query, $params);

		return $row
			? array_shift($row)
			: false;
	}

	/**
	 * Get row
	 *
	 * @todo Test LIMIT 1
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return array|bool Row or error
	 */
	public function getRow($query, array $params = array())
	{
		// Add limit to get just first row
		$query .= ' LIMIT 1';

		// Send query
		$result = $this->query($query, $params);

		return $result
			? $result->fetch(\PDO::FETCH_ASSOC)
			: false;
	}

	/**
	 * Get rows
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return array|bool Rows or error
	 */
	public function getRows($query, array $params = array())
	{
		$rows = array();

		// Send query
		$result = $this->query($query, $params);

		if (!$result)
		{
			return false;
		}

		// Build rows array
		while ($row = $result->fetch(\PDO::FETCH_ASSOC))
		{
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Insert
	 *
	 * @param string $table Table name
	 * @param array $fieldsList List of fields
	 *
	 * @return bool Success
	 */
	public function insert($table, array $fieldsList)
	{
		$params = array();

		// No fields, no query :)
		if (empty($fieldsList))
		{
			return false;
		}

		// Transform simple to multidimensional array
		if (!isset($fieldsList[0]))
		{
			$fieldsList = array($fieldsList);
		}

		// Build query
		$fieldsValues = '';
		foreach ($fieldsList as $index => $fields)
		{
			foreach ($fields as $field => $value) // array_walk not good working :(
			{
				$fieldKey = 'field_'.$index.'_'.$field;
				$fields[$field] = ':'.$fieldKey;
				$params[$fieldKey] = $value;
			}
			$fieldsValues .= '('.implode(', ', $fields).'), ';
		}

		$fieldsValues	= rtrim($fieldsValues, ', ');
		$fieldsKeys		= '`'.implode('`, `', array_keys($fieldsList[0])).'`';
		$query			= 'INSERT INTO `'.$table.'` ('.$fieldsKeys.') VALUES '.$fieldsValues;

		$this->query($query, $params);

		return (bool)$this->getNumberRows();
	}

	/**
	 * Update
	 *
	 * @param string $table Table name
	 * @param array $fields List of fields
	 * @param string $where Condition
	 * @param array $params List of bind params (for preparation query)
	 * @param int $limit Limit of update
	 *
	 * @return bool Success
	 */
	public function update($table, array $fields, $where = '', array $params = array(), $limit = 0)
	{
		// No fields, no query :)
		if (empty($fields))
		{
			return false;
		}

		// Build query
		$query = 'UPDATE `'.$table.'` SET ';

		// Build fields in query
		foreach ($fields as $field => $value)
		{
			$fieldKey = 'field_'.$field;
			$query .= '`'.$field.'` = :'.$fieldKey.', ';
			$params[$fieldKey] = $value;
		}
		$query = rtrim($query, ', ');

		// Build where in query
		if (!empty($where))
		{
			$query .= ' WHERE '.$where;
		}

		// Build limit in query
		if ($limit)
		{
			$query .= ' LIMIT '.(int)$limit;
		}

		$this->query($query, $params);

		return (bool)$this->getNumberRows();
	}

	/**
	 * Delete row(s)
	 *
	 * @param string $table Table name
	 * @param string $where Condition
	 * @param array $params List of bind params (for preparation query)
	 * @param int $limit Limit of deletion
	 *
	 * @return bool Success
	 */
	public function delete($table, $where = '', array $params = array(), $limit = 0)
	{
		// Build query
		$query = 'DELETE FROM `'.$table.'`';

		// Build where in query
		if (!empty($where))
		{
			$query .= ' WHERE '.$where;
		}

		// Build limit in query
		if ($limit)
		{
			$query .= ' LIMIT '.(int)$limit;
		}

		$this->query($query, $params);

		return (bool)$this->getNumberRows();
	}

	/**
	 * Send query
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return mixed Result
	 */
	public function query($query, array $params = array())
	{
		// Debug get start time
		$timeStart = microtime(true);

		// Query execution
		$this->result = $this->resource->prepare($query);
		$this->bindParams($this->result, $params);
		$this->result->execute();

		// Store data for debug
		if ($this->configuration->getDebug())
		{
			$this->queries[] = array(
				'query'			=> $query,
				'interpolate'	=> $this->interpolateQuery($query, $params),
				'time'			=> (microtime(true) - $timeStart),
			);
		}

		// Auto set last ID
		$this->autoSetLastID();

		return $this->result;
	}

	/**
	 * Replaces any parameter placeholders in a query with the value of that
	 * parameter. Useful for debugging. Assumes anonymous parameters from
	 * $params are are in the same order as specified in $query
	 *
	 * @see http://stackoverflow.com/questions/210564/pdo-prepared-statements
	 *
	 * @param string $query The sql query with parameter placeholders
	 * @param array $params The array of substitution parameters
	 *
	 * @return string The interpolated query
	 */
	public function interpolateQuery($query, $params)
	{
		$keys = array();

		$formatValueByType = function($value)
		{
			$sqlKeywords = array('NULL', 'NOW');
			// Type string
			if (is_string($value))
			{
				// Security for no SQL Keywords
				if (!in_array($value, $sqlKeywords, false))
				{
					$value = "'".addslashes($value)."'";
				}
			}
			// Type integer && bool
			elseif (is_int($value) || is_bool($value))
			{
				$value = (int)$value;
			}
			// Type float
			elseif (is_float($value))
			{
				$value = (float)$value;
			}
			// Type NULL
			elseif (is_null($value))
			{
				$value = 'NULL';
			}

			return $value;
		};

		foreach ($params as $key => $value)
		{
			$keys[] = is_string($key)
				? '/:'.$key.'/'
				: '/[?]/';
			$params[$key] = $formatValueByType($value);
		}

		$query = preg_replace($keys, $params, $query, 1, $count);

		return $query;
	}

	/**
	 * Get number of rows
	 *
	 * @return int
	 */
	public function getNumberRows()
	{
		return $this->result
			? $this->result->rowCount()
			: 0;
	}

	/**
	 * Auto set last (inserted) ID
	 */
	public function autoSetLastID()
	{
		$this->lastID = $this->result
			? (int)$this->resource->lastInsertId()
			: 0;
	}

	/**
	 * Get last ID (inserted)
	 *
	 * @return int ID
	 */
	public function getLastID()
	{
		return (int)$this->lastID;
	}

	/**
	 * Get Error Message
	 *
	 * @return string Error
	 */
	public function getErrorMessage()
	{
		$error = $this->resource->errorInfo();
		return $error[0] == '00000'
			? ''
			: $error[2];
	}

	/**
	 * Get queries
	 *
	 * To use, set "debug option" to "true"
	 *
	 * @return array Queries
	 */
	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * Get last query
	 *
	 * To use, set "debug option" to "true"
	 *
	 * @return string Query SQL
	 */
	public function getLastQuery()
	{
		if (!empty($this->queries))
		{
			$lastQuery = end($this->queries);
			return $lastQuery;
		}
		return '';
	}

	/**
	 * Enable debug
	 */
	public function enableDebug()
	{
		$this->configuration->setDebug(TRUE);
	}

	/**
	 * Disable debug
	 */
	public function disableDebug()
	{
		$this->configuration->setDebug(FALSE);
	}

	/**
	 * Return PDO resource
	 *
	 * @return \PdoStatement
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * @param Configuration $configuration
	 */
	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * Bind Params
	 *
	 * @param \PDOStatement $result PDO Statement
	 * @param array $params List of bind params (for preparation query)
	 */
	protected function bindParams($result, $params)
	{
		if (!empty($params))
		{
			foreach ($params as $param => $value)
			{
				if(is_int($value))
				{
					$type = \PDO::PARAM_INT;
				}
				elseif(is_bool($value))
				{
					$type = \PDO::PARAM_BOOL;
				}
				elseif(is_null($value))
				{
					$type = \PDO::PARAM_NULL;
				}
				elseif(is_string($value))
				{
					$type = \PDO::PARAM_STR;
				}
				else
				{
					$type = FALSE;
				}
				$param	= is_int($param)
					? $param + 1
					: ':'.$param;
				$result->bindValue($param, $value, $type);
			}
		}
	}

	/**
	 * Get PDO type of value
	 *
	 * @param $value
	 *
	 * @return bool|int
	 */
	protected function getPDOTypeOfValue($value)
	{
		// Default type (same for float, decimal, double, etc.)
		$type = \PDO::PARAM_STR;

		if(is_int($value))
		{
			$type = \PDO::PARAM_INT;
		}
		elseif(is_bool($value))
		{
			$type = \PDO::PARAM_BOOL;
		}
		elseif(is_null($value))
		{
			$type = \PDO::PARAM_NULL;
		}
		elseif(is_string($value))
		{
			$type = \PDO::PARAM_STR;
		}

		return $type;
	}
}
