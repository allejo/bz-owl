<?php
	class pmSystemPM
	{
		function displayRecipient(&$recipient, $key, array &$values)
		{
			global $config;
			global $tmpl;
			global $db;
			
			
			// $values: array($fromTeam, $queryTeamName, $queryPlayerName, $n)
			$recipientID = intval($recipient);
			$recipient = array();
			if ($values[0])
			{
				$db->execute($values[1], $recipientID);
				$recipientName = $db->fetchAll($values[1]);
				$db->free($values[1]);
				
				if (count($recipientName) < 1)
				{
					$recipientName['0']['name'] = 'ERROR: Could not find out team name';
				}
				$recipient['link'] = ($config->getValue('baseaddress') . 'Teams/?profile='
									  . htmlent($recipientID));
			} else
			{
				$db->execute($values[2], $recipientID);
				$recipientName = $db->fetchAll($values[2]);
				$db->free($values[2]);
				
				if (count($recipientName) < 1)
				{
					$recipientName['0']['name'] = 'ERROR: Could not find out user name';
				}
				
				$recipient['link'] = ($config->getValue('baseaddress') . 'Players/?profile='
									  . htmlent($recipientID));
			}
			
			$recipient['name'] = $recipientName['0']['name'];
			$recipient['seperator'] = $values[3] > $key ? true : false;
		}
		
		function randomKeyMatch(&$confirmed)
		{
			global $site;
			
			$randomKeyValue = '';
			$randomKeyName = '';
			
			if (isset($_POST['key_name']))
			{
				$randomKeyName = html_entity_decode($_POST['key_name']);
				
				if (isset($_POST[$randomKeyName]))
				{
					$randomKeyValue = html_entity_decode($_POST[$randomKeyName]);
				}
			}
			
			return $randomkeysmatch = $site->validateKey($randomKeyName, $randomKeyValue);
		}
	}
?>
