<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set ('allow_url_fopen', 0);
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	require_once '../CMS/siteinfo.php';
	$site = new siteinfo();
	
	$display_page_title = 'Official match servers';
	require '../CMS/index.inc';
	
	echo '<div class="static_page_box">' . "\n";
	if (!($logged_in && (isset($_SESSION['allow_watch_servertracker'])) && ($_SESSION['allow_watch_servertracker'])))
	{
		echo '<p>You need to be logged in in order to view this page.</p>' . "\n";
		$site->dieAndEndPage();
	}
	$use_internal_db = true;
	
	require 'list.php';
	
	$connection = $site->loudless_pconnect_to_db();
	
	if (isset($_GET['server']))
	{
		echo '<a class="button" href="./">overview</a>' . "\n";
		$server = urldecode($_GET['server']);
		formatbzfquery_last($server, $connection);
	} else
	{
		formatbzfquery("bzf.guleague.org:5154", $connection);
		
		formatbzfquery("bzf.guleague.org:5155", $connection);
		
		formatbzfquery("bzf.guleague.org:5156", $connection);
		
		formatbzfquery("bzf.guleague.org:5160", $connection);
		
		formatbzfquery("bzf.guleague.org:5161", $connection);
		
		formatbzfquery("bzf.guleague.org:5157", $connection);
		
		formatbzfquery("bzf.guleague.org:5158", $connection);
		
		formatbzfquery("brad.guleague.org:5158", $connection);
		
//		formatbzfquery("longdon.guleague.org:5158", $connection);
//      
//		formatbzfquery("destroyer.guleague.org:5157", $connection);
//		
//		formatbzfquery("destroyer.guleague.org:5158", $connection);
//		
		formatbzfquery("dub.guleague.org:59997", $connection);
		
//		formatbzfquery("trb.guleague.org:5158", $connection);
		
		formatbzfquery("brl.arpa.net:5158", $connection);
//		
//		formatbzfquery("fairserve.bzflag.net:5157", $connection);
//		
//		formatbzfquery("fairserve.bzflag.net:5158", $connection);
//		
		formatbzfquery("quol.guleague.org:5157", $connection);
		
		formatbzfquery("quol.guleague.org:5158", $connection);
		
		formatbzfquery_last("bzflag.enuffsaid.co.nz:5151", $connection);
	}
?>

</div>
</div>
</body>
</html><?php
	// GU-spezifisch!
	//require_once '../../../CMS/siteinfo.php';
	//
	//$object = new siteinfo();
	@mysql_close($connection);
	unset($connection);
	$connection = $object->loudless_connect_to_db();
	if (!$connection)
	{
		// HTML oben schon beendet
		die('no connection to database');
	}
	
	if (!$use_internal_db)
	{
		// only execute once per day to avoid overhead
		date_default_timezone_set($site->used_timezone());
		
		$heute = date("d.m.y");
		$file = 'maintenance.txt';
		
		if (is_writable($file)) {
			
			// we open $filename in "attachemt" - mode.
			// pointer is at end of the file
			// there $somecontent will be saved later using fwrite()
			if (!$handle = fopen($file, "r")) {
				print "Can not open file $file";
				exit;
			}
		} else {
			print "File $file is not writeable";
		}
		
		// read first 10 chars
		$text = fread($handle, 10);
		
		// is DB info current?
		if (strcasecmp($text, $heute) == 0)
		{
			// nothing to do
			die();
		}
		
		// select database
		mysql_select_db("playerlist", $connection);
		
		// delete database data if information not current
		// expensive operation
		$query = 'TRUNCATE teams';
		$result = mysql_query($query, $connection);
		if (!$result)
		{
			print mysql_error();
			die("<br>\nQuery $query is not valid SQL.");
		}
		$query = 'TRUNCATE players';
		$result = mysql_query($query, $connection);
		if (!$result)
		{
			print mysql_error();
			die("<br>\nQuery $query is not valid SQL.");
		}
		
		$row = 1; // number of rows
		// create a new cURL resource
		$ch = curl_init();
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, 'http://gu.bzleague.com/rss/export2.php');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// grab URL and pass it to the browser
		$output = curl_exec($ch);
		
		// close cURL resource, and free up system resources
		curl_close($ch);
		$handleRSS = $output;
		
		//	$handleRSS = fopen ("http://gu.bzleague.com/rss/export2.php","r"); // Datei zum Lesen oeffnen
		//	if (!$handle)
		//	{
		//		die('Could not connect to league website');
		//	}
		
		preg_match_all('/(TE:(.*))|(PL:(.*))/', $handleRSS, $handleRSS);
		$handleRSS = $handleRSS[0];
		//    $handleRSS = str_getcsv ($handleRSS, "\n");
		//    print_r($handleRSS);
		
		//	while ( ($data = str_getcsv ($handleRSS, ",")) !== FALSE )
		foreach ($handleRSS as $dataRow)
		{ // Daten werden aus der Datei in ein Feld $data gelesen
			
			//        preg_match_all('/((.*),(.*))|((.*),(.*),(.*))/' , $dataRow, $data);
			$data = explode(',', $dataRow, 4);
			//        $data = $data[0];
			//        $data = str_getcsv ($dataRow, ',');
			
			
			
			$num = count ($data); // Felder im Array $data werden gezaehlt
			$row++; // increment number of arrays
			
			if ($num == 2)
			{
				$data[1] = ltrim($data[1]);
			}
			if ($num > 2)
			{
				$data[2] = ltrim($data[2]);
			}
			//		print_r($data);
			
			// teams
			if ($num == 2)
			{
				$teamid = mysql_real_escape_string((int) (str_replace('TE: ', '', $data[0])));
				$name = '"' . mysql_real_escape_string(htmlentities($data[1])) . '"';
				
				$query = 'INSERT INTO teams (teamid, name) Values(' . $teamid . ',' . $name . ')';
				$result = mysql_query($query, $connection);
				
				if (!$result)
				{
					print mysql_error();
					die("<br>\nQuery $query is not valid SQL.");
				}
			}
			
			// players
			if ($num == 3)
			{
				$teamid = '"' . mysql_real_escape_string((int) (str_replace('PL: ', '', $data[0]))) . '"';
				
				$name = '"' . mysql_real_escape_string(htmlentities($data[2])) . '"';
				
				$query = 'INSERT INTO players (teamid, name) Values(' . $teamid . ', ' . $name . ')';
				$result = mysql_query($query, $connection);
				
				if (!$result)
				{
					print mysql_error();
					die("<br>\nQuery $query is not valid SQL.");
				}
			}
		}
		
		if (!(strcasecmp($text, $heute) == 0))
		{
			// delete content
			if (!fclose($handle)) {
				print "Can not close file $file";
				exit;
			}
			if (!$handle = fopen($file, 'w')) {
				print "Can not open file $file";
				exit;
			}
			if (!fwrite($handle, $heute)) {
				print "Can not write to file $file";
				exit;
			}
			@fclose($handle);
		}
	}
	//	fclose ($handleRSS);
?>