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
			
			
			// just delete it from the user's private message table
			$query = $db->prepare('DELETE FROM `pmsystem.msg.users`'
								  . ' WHERE `userid`=:uid AND `msgid`=:msgid LIMIT 1');
			$params = array(':uid' => array($user->getID(), PDO::PARAM_INT),
							':msgid' => array($id, PDO::PARAM_INT));
			$db->execute($query, $params);
			
			
			// check for message usage
			$query = $db->prepare('SELECT `msgid` FROM `pmsystem.msg.users`'
								  . ' WHERE `userid`<>:uid AND `msgid`=:msgid LIMIT 1');
			$db->execute($query, $params);
			
			// delete message from storage if no one has the message in mailbox anymore
			if (($row = $db->fetchRow($query)) === false)
			{
				$query = $db->prepare('DELETE FROM `pmsystem.msg.storage` WHERE `msgid`=:msgid');
				$params = array(':uid' => array($user->getID(), PDO::PARAM_INT));
				$db->execute($query, $params);
			}
			
			$tmpl->setTemplate('PMDelete');
			$tmpl->assign('pmDeleted', true);
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
