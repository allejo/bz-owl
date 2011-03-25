<?php
	// handle database related data and functions
	class database
	{
		private $connection;
		private $pdo;
		
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
			// TODO: implement it
		}
		
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
			if (isset($this->pdo))
			{
				$this->SQL('USE `' . $db . '`');
			} else
			{
				// choose database
				if (!(mysql_select_db($db, $this->connection)))
				{
					die('<p>Could not select database!<p>');
				}
			}
			return true;
		}
		
		function SQL($query, $file=false, $errorUserMSG='')
		{
			global $tmpl;
			
			if ($this->getDebugSQL() && isset($tmpl))
			{
				$tmpl->addMSG('executing query: '. $query . $tmpl->return_self_closing_tag('br'));
			}
			
			$result = $this->pdo->query($query);
			
			if (!$result)
			{
				// print out the raw error in debug mode
				if ($this->getDebugSQL())
				{
					echo'<p>Query ' . htmlent($query) . ' is probably not valid SQL.</p>' . "\n";
					echo mysql_error();
				}
				
				// log the error
				if ($file !== false)
				{
					$this->logError($file, $query . $this->quote(mysql_error()));
				} else
				{
					$this->logError($query . $this->quote(mysql_error()));
				}
				
				if (strlen($errorUserMSG) > 0)
				{
					$tmpl->done($errorUserMSG);
				}
				
				$tmpl->done('Error: Could not process query.');
			}
			
			return $result;
		}
		
		
		function execute(PDOStatement &$query, $inputParameters)
		{
			if (!is_array($inputParameters))
			{
				$inputParameters = array($inputParameters);
			}
			
			$result = $query->execute($inputParameters);
			return $result;
		}
		
		function prepare($query)
		{
			return $this->pdo->prepare($query);;
		}
		
		function fetchRow(PDOStatement $queryResult)
		{
			$queryResult->errorInfo();
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
		
		function errorInfo(PDOStatement $queryResult)
		{
			return $queryResult->errorInfo();
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
