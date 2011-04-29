<?php
	// handle database related data and functions
	class database_result
	{
		private $query;
		private $handle;
		
		function __construct(PDOStatement $handle, $query)
		{
			$this->handle = $handle;
			$this->query = $query;
		}
		
		function getHandle()
		{
			return $this->handle;
		}
		
		function getQuery()
		{
			return $this->query;
		}
		
		function setHandle(PDOStatement $handle)
		{
			$this->handle = $handle;
		}
	}
	
	
	class database
	{
		private $connection;
		private $pdo;
		
		function __construct()
		{
			$this->createConnection();
		}
		
		function getConnection()
		{
			return $this->connection;
		}
		
		function createConnection()
		{
			global $site;
			global $config;
			
			try
			{
				$this->pdo = new PDO(
									 'mysql:host='. strval($config->value('dbHost'))
									 . ';dbname=' . strval($config->value('dbName')),
									 strval($config->value('dbUser')),
									 strval($config->value('dbPw')),
									 array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
			}
			catch (PDOException $e)
			{
				if ($config->value('debugSQL'))
				{
					echo 'Connection failed: ' . $e->getMessage();
				} else
				{
					echo 'DB connection failure, see log.';
				}
				
				$this->logError($e->getMessage());
				
			    die();
			}
			
			return $this->pdo;
		}
		
		function logError($error)
		{
			global $config;
			
			
			if (isset($this->pdo))
			{
				// database connection available
				$query = $this->prepare('INSERT INTO `ERROR_LOG` (`msg`) VALUES (?)');
				$this->execute($query, $error);
				
				return;
			}
			
			
			$logfile = strval($config->value('errorLogFile'));
			if (strlen($logfile) > 0 && file_exists($logfile) && is_writable($logfile))
			{
				$handle = @fopen($logfile, 'a');
				if (!@fwrite($handle, $error))
				{
					die('ERROR: Writing into log failed');
				}
			} else
			{
				die('ERROR: DB connection not available and problem with logfile encountered.');
			}
		}
		
		// deprecated function
		// use config->value('debugSQL') instead
		function getDebugSQL()
		{
			global $config;
			
			if (isset($_SESSION['debugSQL']))
			{
				return ($_SESSION['debugSQL']);
			} else
			{
				return $config->value('debugSQL');
			}
		}
		
		/* deprecated function: use prepared instead */
		function quote($string)
		{
			return $this->pdo->quote($string);
		}
		
		function free(database_result $dbResult)
		{
			// might be needed to execute next statement
			// depending on database driver
			$dbResult->getHandle()->closeCursor();
		}
		
		function selectDB($db, $connection=false)
		{
			$this->SQL('USE `' . $db . '`');
			return true;
		}
		
		function SQL($query, $file=false, $errorUserMSG='')
		{
			global $tmpl;
			
/*
			if ($this->getDebugSQL() && isset($tmpl))
			{
				$tmpl->assign('MSG', 'executing query: '. $query . $tmpl->return_self_closing_tag('br'));
			}
*/
			
			$result = $this->pdo->query($query);
			
			if (!$result)
			{
				// print out the raw error in debug mode
				if ($this->getDebugSQL())
				{
					echo'<p>Query ' . htmlent($query) . ' is probably not valid SQL.</p>' . "\n";
				}
				
				// log the error
				if ($file !== false)
				{
					$this->logError($file, $query);
				} else
				{
					$this->logError($query);
				}
				
				if (strlen($errorUserMSG) > 0)
				{
					$tmpl->assign('errorMsg', $errorUserMSG);
					$tmpl->display('NoPerm');
				}
				
				$tmpl->assign('errorMsg', 'Error: Could not process query.');
				$tmpl->display('NoPerm');
			}
			
			return new database_result($result, $query);
		}
		
		
		function execute(database_result &$dbResult, $inputParameters)
		{
			$handle = $dbResult->getHandle();
			
			if (!is_array($inputParameters))
			{
				$inputParameters = array($inputParameters);
			} else
			{
				foreach ($inputParameters as $param => $value)
				{
					// example: $inputParameters[':limit'] = array(1, PDO::PARAM_INT);
					// mixing '?' and named parameters in the same SQL statement probably does not work
					if (is_array($value) and preg_match('/^:[a-z]\w*$/i', $param))
					{
						$handle->bindValue($param, $value[0], $value[1]);
						unset($inputParameters[$param]);	// works correctly with foreach
					}
				}
				if (empty($inputParameters))
				{
					$inputParameters = NULL;
				}
			}
			
			$result = $handle->execute($inputParameters);
			
			if ($result === false)
			{
				$error = $handle->errorInfo();
	 			$this->logError('SQLSTATE error code: ' . $error[0]
 								. ', driver error code: ' . $error[1]
 								. "\n" . 'driver error message: ' . $error[2]
 								. "\n\n" . 'executing prepared statement failed,  query was:'
 								. "\n" . $dbResult->getQuery());
			}
			
			// write PDOStatement Object to $queryArguments so other functions will work on it
			$dbResult->setHandle($handle);
			
			return $result;
		}
		
		function prepare($query)
		{
			$result = $this->pdo->prepare($query);
			// this error catching will only work if PDO::ATTR_EMULATE_PREPARES is false
			// leave it in just in case
			if ($result === false)
			{
				$error = $this->pdo->errorInfo();
	 			$this->logError('SQLSTATE error code: ' . $error[0]
 								. ', driver error code: ' . $error[1]
 								. "\n" . 'driver error message: ' . $error[2]
 								. "\n\n" . 'preparing query failed, query was:'
 								. "\n" . $query);
			}
			
			// keep the original query string to show more accurate error messages
			// on prepared query execution fail
			return (new database_result($result, $query));
		}
		
		function fetchRow(database_result $dbResult)
		{
			return $dbResult->getHandle()->fetch();
		}
		
		function fetchAll(database_result $dbResult)
		{
			return $dbResult->getHandle()->fetchAll();
		}
		
		// do not use on SELECT statements as result may vary there,
		// depending on db engine
		function rowCount(database_result $dbResult)
		{
			return $dbResult->getHandle()->rowCount();
		}
		
		function exec($query)
		{
			// executes $query and returns number of result rows
			// do not use on SELECT queries
			return $this->pdo->exec($query);
		}
		
		/* deprecated function, do only use as last resort */
		function lastInsertId($name=NULL)
		{
			if ($name === NULL)
			{
				return $this->pdo->lastInsertId();
			} else
			{
				return $this->pdo->lastInsertId($name);
			}
		}
	}
?>
