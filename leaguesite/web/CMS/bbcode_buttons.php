<?php
	// this file shows the bbcode buttons if there is a bbcode library used
	
	class bbcode_buttons
	{
		function showBBCodeButtons($element_name = 'announcement', $form_number = 0)
		{
			global $site;
			global $config;
			
			if (isset($config))
			{
				// new code framework
				
				// check for bbcode library
				if ($config->getValue('bbcodeLibAvailable'))
				{
					$buttons = array();
					$buttons[] = '<script type="text/javascript" src="' . $config->getValue('baseaddress') . 'js/bbcode_buttons.js"></script>' . "\n";
					$buttons[] = ('<input type="button" name="bold" value="b" '
								  . 'style="font-weight: bold;" '
								  . 'onclick="' . "insert('[b]', '[/b]', '$form_number', '$element_name')" . '" />');
					$buttons[] = ('<input type="button" name="italic" value="i" '
								  . 'style="font-style: italic;" '
								  . 'onclick="' . "insert('[i]', '[/i]', '$form_number', '$element_name')" . '" />');
					$buttons[] = ('<input type="button" name="underline" value="u" '
								  . 'style="text-decoration: underline;" '
								  . 'onclick="' . "insert('[u]', '[/u]', '$form_number', '$element_name')" . '" />');
					$buttons[] = ('<input type="button" name="img" value="img" '
								  . 'onclick="' . "insert('[img]', '[/img]', '$form_number', '$element_name')" . '" />');
					$buttons[] = ('<input type="button" name="url" value="url" '
								  . 'onclick="' . "insert('[url]', '[/url]', '$form_number', '$element_name')" . '" />');
					
					return $buttons;
				}
			} else
			{
				// compatibility mode
				
				// check for bbcode library
				if ($site->bbcode_lib_available())
				{
					echo "\n" . '<script type="text/javascript" src="' . baseaddress() . 'js/bbcode_buttons.js"></script>' . "\n";
					$site->write_self_closing_tag('input type="button" name="bold" value="b" '
												  . 'style="font-weight: bold;" '
												  . 'onclick="' . "insert('[b]', '[/b]', '$form_number', '$element_name')" . '"');
					$site->write_self_closing_tag('input type="button" name="italic" value="i" '
												  . 'style="font-style: italic;" '
												  . 'onclick="' . "insert('[i]', '[/i]', '$form_number', '$element_name')" . '"');
					$site->write_self_closing_tag('input type="button" name="underline" value="u" '
												  . 'style="text-decoration: underline;" '
												  . 'onclick="' . "insert('[u]', '[/u]', '$form_number', '$element_name')" . '"');
					$site->write_self_closing_tag('input type="button" name="img" value="img" '
												  . 'onclick="' . "insert('[img]', '[/img]', '$form_number', '$element_name')" . '"');
					$site->write_self_closing_tag('input type="button" name="url" value="url" '
												  . 'onclick="' . "insert('[url]', '[/url]', '$form_number', '$element_name')" . '"');
				}
			}
		}
	}
?>