<?php
	// handle database related data and functions
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
		
		function quote($string)
		{
			return $this->pdo->quote($string);
		}
		
		function free(PDOStatement $queryResult)
		{
			// might be needed to execute next statement
			// depending on database driver
			$queryResult->closeCursor();
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
			
			return $result;
		}
		
		
		function execute(PDOStatement &$query, $inputParameters)
		{
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
						$query->bindValue($param, $value[0], $value[1]);
						unset($inputParameters[$param]);	// works correctly with foreach
					}
				}
				if (empty($inputParameters))
				{
					$inputParameters = NULL;
				}
			}
			
			$result = $query->execute($inputParameters);
			
			if (!$result)
			{
				$error = $query->errorInfo();
	 			$this->logError('SQLSTATE error code: ' . $error[0]
 								. ', driver error code: ' . $error[1]
 								. "\n" . 'driver error message: ' . $error[2]);
			}
			
			
			return $result;
		}
		
		function prepare($query)
		{
			$result = $this->pdo->prepare($query);
			if ($result === false)
			{
				$error = $this->pdo->errorInfo();
	 			$this->logError('SQLSTATE error code: ' . $error[0]
 								. ', driver error code: ' . $error[1]
 								. "\n" . 'driver error message: ' . $error[2]
 								. "\n" . 'preparing query failed, query was ' . $query);
			}
			
			return $result;
		}
		
		function fetchRow(PDOStatement $queryResult)
		{
			return $queryResult->fetch();
		}
		
		function fetchAll(PDOStatement $queryResult)
		{
			return $queryResult->fetchAll();
		}
		
		function rowCount(PDOStatement $queryResult)
		{
			return $queryResult->rowCount();
		}
		
		function exec($query)
		{
			// executes $query and returns number of result rows
			// do not use on SELECT queries
			return $this->pdo->exec($query);
		}
		
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