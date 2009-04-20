<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Jason Lefkowitz <jason.lefkowitz@changetowin.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'Atom Feed' for the 'jl_atom' extension.
 *
 * @author	Jason Lefkowitz <jason.lefkowitz@changetowin.org>
 * @package	TYPO3
 * @subpackage	tx_jlatom
 */
class tx_jlatom_pi1 extends tslib_pibase {
	var $prefixId = 'tx_jlatom_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_jlatom_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'jl_atom';	// The extension key.
	var $pi_checkCHash = TRUE;
	
	var $pluginMode = '';
	
		/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
			
		$this -> conf = $conf;
		$this -> pi_setPiVarDefaults();
		$this -> pi_loadLL();
		$this -> pi_initPIflexForm();
		
		debug($this->cObj->data);
		
		$templateFile = $this->cObj->fileResource('EXT:jl_atom/res/atom1.tmpl');		
		$templateItem = $this->cObj->getSubpart($templateFile, "###ENTRY###");	
		
		if (isset($this->conf['feedConfig.']['limit']) && is_numeric($this->conf['feedConfig.']['limit'])) {
		
			$entriesLimit = $this->conf['feedConfig.']['limit'];
			
		} else {
		
			$entriesLimit = 6;
			
		}
		
		$entryList = "";
		
		$entriesRetrieved = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			"uid,title,url,tx_realurl_pathsegment,SYS_LASTCHANGED,tstamp,crdate",
			"pages",
			"pid=23 AND hidden=0",
			"",
			"crdate DESC",
			$entriesLimit	
		);
		
		$entryCounter = 1;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($entriesRetrieved)) {
		
			if ($this->conf['feedConfig.']['realUrl'] == 1) {
			
				$speakingUrlRetrieved = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					"pagepath",
					"tx_realurl_pathcache",
					"page_id=" . $row['uid']			
				);
		
				$speakingUrlAssoc = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($speakingUrlRetrieved);
				$entryUrl = $this->conf['feedConfig.']['baseUrl'] . $speakingUrlAssoc['pagepath'] . ".html";
			
			} else {
			
				$entryUrl = $this->conf['feedConfig.']['baseUrl'] . 'index.php?id=' . $row['uid'];
				
			} 
			
			$entryFields = array(
      
				'###ENTRY_TITLE###' => htmlspecialchars($row['title']), 
				'###ENTRY_URI###'	=> $entryUrl,
				'###ENTRY_ID###'	=> $entryUrl,
				'###ENTRY_UPDATED###' => $this->convertUnixTimeToRFC3339($row['crdate']),
				'###ENTRY_PUBLISHED###' => $this->convertUnixTimeToRFC3339($row['crdate'])				
			);
			
			$entryFilled = $this->cObj->substituteMarkerArrayCached($templateItem, $entryFields);
			$entryList .= $entryFilled;

			if ($entryCounter == 1) {
			
				$feedUpdated = $this->convertUnixTimeToRFC3339($row['crdate']);
				
			}
			
			$entryCounter++;
		
		}
		

		$markersToReplace = array(	'###FEED_UPDATED###' => $feedUpdated,
											'###FEED_ID###' => $this->conf['feedConfig.']['baseUrl'],
											'###FEED_ICON###' => $this->conf['feedConfig.']['feedIcon'],
											'###FEED_TITLE###' => $this->conf['feedConfig.']['title'],
											'###FEED_GENERATOR###' => $this->conf['feedConfig.']['generator'],
											'###FEED_AUTHOR_NAME###' => $this->conf['feedConfig.']['authorName'],
											'###FEED_AUTHOR_EMAIL###' => $this->conf['feedConfig.']['authorEmail']);
		
		$subpartsToReplace = array("###ENTRIES###" => $entryList);	
		
		$atomFeed = $this->cObj->substituteMarkerArrayCached($templateFile, $markersToReplace, $subpartsToReplace);

		return($atomFeed);
		
	}
	
	
	function convertUnixTimeToRFC3339($timestamp) {
	
		$convertedTime = "";
		
		$convertedTime = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
	
		return $convertedTime;
	
	}

	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/jl_atom/pi1/class.tx_jlatom_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/jl_atom/pi1/class.tx_jlatom_pi1.php']);
}

?>