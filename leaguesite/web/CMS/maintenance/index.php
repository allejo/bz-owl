<?php
	// legacy maintenance code
	// code is deprecated, use the maintenance add-on instead!
	
	// siteinfo class used all the time
	if (!(isset($site)))
	{
		require_once(dirname(dirname(__FILE__)) . '/site.php');
		$site = new site();
	}
	
	// call the new maintenance add-on directly to do the job
	require_once(dirname(dirname(__FILE__)) . '/add-ons/maintenance/maintenance.php');
	$maintenance = new maintenance();
	// done
	
	function update_activity($teamid=false)
	{
		global $maintenance;
		
		
		if (!isset($maintenance))
		{
			require_once(dirname(dirname(__FILE__)) . '/add-ons/maintenance/maintenance.php');
			$maintenance = new maintenance();
		}
		
		// old code does call this function directly with a teamid
		// just pass the call to the new add-on
		$maintenance->updateTeamActivity();
	}
?>
