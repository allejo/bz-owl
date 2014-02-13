<?php
	require_once dirname(__FILE__) . '/pmDisplay.php';
	class pmSystemDelete extends pmSystemDisplay
	{
		public function __construct($folder, $id)
		{
			// get confirmation step info
			$confirmed = isset($_POST['confirmationStep']) ? $_POST['confirmationStep'] : 0;
			// run sanity checks
			$this->sanityCheck($confirmed);
			
			switch ($confirmed)
			{
				case 1:
					// delete message
					$this->delete($folder, $id);
					break;
				
				default:
					// display preview
					$this->preview($folder, $id);
					break;
			}
		}
		
		
		protected function delete($folder, $id)
		{
			global $tmpl;
			global $db;
			
			
			// TODO: cascading constraints in the database should make
			// some of these queries unnecessary

			// just delete it from the user's private message table
			$query = $db->prepare('DELETE FROM `pmsystem_msg_users`'
					. ' WHERE `msgid`=:msgid AND `userid`=:uid AND `folder`=:folder');
			$params = array(':msgid' => array($id, PDO::PARAM_INT),
							':uid' => array(user::getCurrentUserId(), PDO::PARAM_INT),
							':folder' => array($folder, PDO::PARAM_STR));
			$db->execute($query, $params);
			
			// check for message usage
			$query = $db->prepare('SELECT `msgid` FROM `pmsystem_msg_users`'
								  . ' WHERE `msgid`=:msgid LIMIT 1');
			$params = array(':msgid' => array($id, PDO::PARAM_INT));
			$db->execute($query, $params);
			$row = $db->fetchRow($query);
			$db->free($query);
			
			// delete stored message and recipients if no one has the message in mailbox anymore
			if ($row === false)
			{
				$query = $db->prepare('DELETE FROM `pmsystem_msg_storage` WHERE `id`=:msgid');
				// current value of $params is correct
				$db->execute($query, $params);

				$query = $db->prepare('DELETE FROM `pmsystem_msg_recipients_users` WHERE `msgid`=:msgid');
				// current value of $params is correct
				$db->execute($query, $params);

				$query = $db->prepare('DELETE FROM `pmsystem_msg_recipients_teams` WHERE `msgid`=:msgid');
				// current value of $params is correct
				$db->execute($query, $params);
			}
			
			$tmpl->setTemplate('PMDelete');
			$tmpl->assign('title', 'Deleted Mail #' . $id);
			$tmpl->assign('curFolder', $folder);
			$tmpl->assign('pmDeleted', true);	// FIXME: report any failures
		}
		
		
		function preview($folder, $id)
		{
			global $site;
			global $tmpl;
			
			
			parent::showMail($folder, $id);
			
			$tmpl->setTemplate('PMDelete');
			$tmpl->assign('showPreview', true);
			$tmpl->assign('title', 'Delete ' . $tmpl->getTemplateVars('title'));
			
			// protected against cross site injection attempts
			$randomKeyName = 'pmDelete_' . microtime();
			// convert some special chars to underscores
			$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
			$randomkeyValue = $site->setKey($randomKeyName);
			$tmpl->assign('keyName', $randomKeyName);
			$tmpl->assign('keyValue', htmlent($randomkeyValue));
		}
		
		private function randomKeyMatch()
		{
			global $site;
			
			
			$randomKeyValue = '';
			$randomKeyName = '';
			
			if (isset($_POST['key_name']))
			{
				$randomKeyName = htmlent_decode($_POST['key_name']);
				
				if (isset($_POST[$randomKeyName]))
				{
					$randomKeyValue = htmlent_decode($_POST[$randomKeyName]);
				}
			}
			
			return $randomkeysmatch = $site->validateKey($randomKeyName, $randomKeyValue);
		}
		
		protected function sanityCheck(&$confirmed)
		{
			// we do not need to know if user owns this message
			// because delete operation can not delete message
			// from different user
			
			if ($confirmed < 0 || $confirmed > 1)
			{
				$confirmed = 0;
			}
			
			if (!$this->randomKeyMatch())
			{
				$confirmed = 0;
			}
		}
	}
?>
