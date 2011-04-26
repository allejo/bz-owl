<?php
	require_once dirname(__FILE__) . '/List.php';
	class pmDelete extends pmDisplay
	{
		function __construct($folder, $id)
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
		
		
		function delete($folder, $id)
		{
			global $tmpl;
			global $user;
			global $db;
			
			
			// TODO: cascading constraints in the database should make
			// some of these queries unnecessary

			// just delete it from the user's private message table
			$query = $db->prepare('DELETE FROM `pmsystem.msg.users`'
					. ' WHERE `msgid`=:msgid AND `userid`=:uid AND `folder`=:folder');
			$params = array(':msgid' => array($id, PDO::PARAM_INT),
							':uid' => array($user->getID(), PDO::PARAM_INT),
							':folder' => array($folder, PDO::PARAM_STR));
			$db->execute($query, $params);
			
			$query = $db->prepare('SELECT `msgid` FROM `pmsystem.msg.users`'
								. ' WHERE `msgid`=:msgid AND `userid`=:uid');
			$params = array(':msgid' => array($id, PDO::PARAM_INT),
							':uid' => array($user->getID(), PDO::PARAM_INT));
			$db->execute($query, $params);
			$row = $db->fetchRow($query);
			$db->free($query);
			
			// delete message from user's recipients list if it is now gone from both mailboxes
			if ($row === false)
			{
				$query = $db->prepare('DELETE FROM `pmsystem.msg.recipients.users`'
									. ' WHERE `msgid`=:msgid AND `userid`=:uid');
				// current value of $params is correct
				$db->execute($query, $params);
			}
			
			
			// check for message usage
			$query = $db->prepare('SELECT `msgid` FROM `pmsystem.msg.users`'
								  . ' WHERE `msgid`=:msgid LIMIT 1');
			$params = array(':msgid' => array($id, PDO::PARAM_INT));
			$db->execute($query, $params);
			$row = $db->fetchRow($query);
			$db->free($query);
			
			// delete message from storage and teams if no one has the message in mailbox anymore
			if ($row === false)
			{
				// these two queries could be combined if their columns had the same name
				$query = $db->prepare('DELETE FROM `pmsystem.msg.storage` WHERE `id`=:msgid');
				// current value of $params is correct
				$db->execute($query, $params);
				$query = $db->prepare('DELETE FROM `pmsystem.msg.recipients.teams` WHERE `msgid`=:msgid');
				// current value of $params is correct
				$db->execute($query, $params);
			}
			
			$tmpl->setTemplate('PMDelete');
			$tmpl->assign('title', 'Deleted ' . $tmpl->getTemplateVars('title'));
			$tmpl->assign('curFolder', $folder);
			$tmpl->assign('pmDeleted', true);	// FIXME: report any failures
		}
		
		
		function preview($folder, $id)
		{
			global $tmpl;
			
			
			parent::showMail($folder, $id);
			
			$tmpl->setTemplate('PMDelete');
			$tmpl->assign('showPreview', true);
			$tmpl->assign('title', 'Delete ' . $tmpl->getTemplateVars('title'));
		}
		
		
		function sanityCheck(&$confirmed)
		{
			// we do not need to know if user owns this message
			// because delete operation can not delete message
			// from different user
			
			if ($confirmed < 0 || $confirmed > 1)
			{
				$confirmed = 0;
			}
		}
	}
?>
