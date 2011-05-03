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
			
			@setcookie('cookies', 'allowed', 0, $config->value('basepath') . 'Config/', $config->value('domain'), 0);
			
			$theme = '';
			if (isset($_GET['theme']))
			{
				$theme = $_GET['theme'];
				
				// clean theme name
				if (!preg_match('/^[0-9A-Za-z]+$/', $theme))
				{
					$theme = '';
				}
				
				// check if theme stylesheet file does exist
				if (!file_exists(dirname(dirname(dirname(dirname(__FILE__)))) .'/themes/'
								 . str_replace(' ', '%20', htmlspecialchars($theme) . '/')
								 . str_replace(' ', '%20', htmlspecialchars($theme) . '.css')))
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
					echo dirname(dirname(dirname(__FILE__))) . '/themes/' . $curFile . '/' . $curFile . '.css' . '<br>';
					unset($themes[$i]);
				}
				
				// filter unfinished themes if debugSQL is turned off
				if (isset($themes[$i]) && !$config->value('debugSQL')
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
			
			
			// find out installed repository version
			// return false on failure
			function repositoryVersion()
			{
				$svn_rev = false;
				
				if (file_exists('../.svn/entries'))
				{
					$handle = fopen('../.svn/entries', 'rb');
					$counter = 1;
					while ($rev = fscanf($handle, "%[a-zA-Z0-9,. ]%[dir]\n%[a-zA-Z0-9,.]"))
					{
						$counter++;
						
						if ($counter > 4)
						{
							// listing some of them
							list($svn_rev) = $rev;
							break;
						}
					}
					fclose($handle);
					unset($counter);
				}
				
				return $svn_rev;
			}
			
			if ($rev = repositoryVersion())
			{
				$tmpl->assign('repositoryVersion', $rev);
			}
			
			$tmpl->display();
		}
	}
?>
