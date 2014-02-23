<?php
	class configSystem
	{
		function __construct($title, $path)
		{
			global $config;
			global $tmpl;
			global $user;
			
			// set a cookie to test if client accepts cookies
			$output_buffer = '';
			ob_start();
			
			@setcookie('cookies', 'allowed', 0, $config->getValue('basepath') . 'Config/', $config->getValue('domain'), 0);
			
			$theme = '';
			if (isset($_GET['theme']))
			{
				$theme = $_GET['theme'];
				
				// clean theme name: numbers, a-Z and space are allowed
				if (!preg_match('/^[0-9A-Za-z ]+$/', $theme))
				{
					$theme = '';
				}
				
				// check if theme stylesheet file does exist
				if (!file_exists(dirname(dirname(dirname(dirname(__FILE__)))) .'/themes/'
								 . htmlspecialchars($theme) . '/'
								 . htmlspecialchars($theme) . '.css'))
				{
					$theme = '';
				}
				
			}
			
			if (strlen($theme) > 0)
			{
				if ($tmpl->existsTemplate('Config', $theme))
				{
					$user->saveTheme($theme);
				}
			}
			
			$output_buffer .= ob_get_contents();
			ob_end_clean();
			// write output buffer
			echo $output_buffer;
			
			
			// read out installed themes instead of defining a fixed list in source code
			
			// first scan the files in the themes directory
			$themes = scandir(dirname(dirname(dirname(__FILE__))) . '/themes/');
			foreach ($themes as $i => $curFile)
			{
				// remove entry from array if it's no folder
				if (!is_dir(dirname(dirname(dirname(__FILE__))) . '/themes/' . $curFile))
				{
					unset($themes[$i]);
					continue;
				}
				
				// filter reserved directory names
				switch ($curFile)
				{
					case (strcasecmp('.', $curFile) === 0):
						unset($themes[$i]);
						break;
						
					case (strcasecmp('..', $curFile) === 0):
						unset($themes[$i]);
						break;
						
					case (strcasecmp('.svn', $curFile) === 0):
						unset($themes[$i]);
						break;
				}
				
				// filter themes with no stylesheet
				if (isset($themes[$i])
					&& !file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/themes/' . $curFile . '/' . $curFile . '.css'))
				{
					unset($themes[$i]);
				}
				
				// filter unfinished themes if it has been turned off in config (default)
				// or if it is not the currently chosen theme
				if (isset($themes[$i]) && !$config->getValue('config.themes.showUnfinished')
					&& !(strcasecmp($curFile, $theme) === 0)
					&& file_exists(dirname(dirname(dirname(__FILE__))) . '/themes/' . $curFile . '/unfinished'))
				{
					unset($themes[$i]);
				}
			}
			unset($curFile);
			
			
			if (!$tmpl->setTemplate('Config', $theme))
			{
				$tmpl->noTemplateFound();
			}
			
			if (strlen($theme) > 0)
			{
				$tmpl->assign('curTheme', $theme);
			} else
			{
				$tmpl->assign('curTheme', $user->getTheme());
			}
			
			// presentation logic in template
			// so just pass the result from business logic
			$tmpl->assign('themes', $themes);
			
			
			$tmpl->display();
		}
	}
?>
