<?php
/**
 * Class LiveUpdate
 * dummy file to avoid fatal errors since we removed the liveupdate
 */
defined('_JEXEC') or die();

class LiveUpdate{
	public static function getUpdateInformation($force = false) {
		$versionCheck = new stdClass();
		$versionCheck->hasUpdates = false;
		return $versionCheck;
	}

}