<?php
	class country
	{
		private $id;
		private $name;
		private $flagfile;
		
		public function __construct($id = 1)
		{
			$this->id = (int) $id;
		}
		
		public function getName()
		{
			global $db;
			
			if (isset($this->name))
			{
				return $this->name;
			}
			
			$query = $db->prepare('SELECT `name` FROM `countries` WHERE `id`=:id LIMIT 1');
			// the id can be of any data type and of any length
			// just assume it's a 50 characters long string
			$db->execute($query, array(':id' => array($this->id, PDO::PARAM_INT)));
			
			if ($row = $db->fetchRow($query))
			{
				$this->name = $row['name'];
			}
			$db->free($query);
			
			return $this->name;
		}
		
		public function getFlag()
		{
		
			global $db;
			
			if (isset($this->country))
			{
				return strlen($this->flagfile) > 0 ? ('../Flags/' . $this->flagfile) : false;
			}
			
			$query = $db->prepare('SELECT `flagfile` FROM `countries` WHERE `id`=:id LIMIT 1');
			// the id can be of any data type and of any length
			// just assume it's a 50 characters long string
			$db->execute($query, array(':id' => array($this->id, PDO::PARAM_INT)));
			
			if ($row = $db->fetchRow($query))
			{
				$this->flagfile = $row['flagfile'];
			}
			$db->free($query);
			
			return strlen($this->flagfile) > 0 ? ('../Flags/' . $this->flagfile) : false;
		}
	}
?>
