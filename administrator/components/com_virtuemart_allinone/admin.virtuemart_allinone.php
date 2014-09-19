<?php
/**
 *
 * @version $Id$
 * @package VirtueMart
 * @author Max Milbers
 * @subpackage All In One
 * @copyright Copyright (C) 2014 VirtueMart Team - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

defined('_JEXEC') or die();

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists( 'VmConfig' )) {
	$path = JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php';
	if(file_exists($path)){
		require($path);
		VmConfig::loadConfig();
	} else {
		$app = JFactory::getApplication();
		$app->enqueueMessage('VirtueMart Core is not installed, please install VirtueMart again, or uninstall the AIO component by the joomla extension manager');
		return false;
	}
}

$task = vRequest::getCmd('task');
if($task=='updateDatabase'){
	vRequest::vmCheckToken('Invalid Token, in ' . $task);
	$app = JFactory::getApplication();

	$user = JFactory::getUser();
	if(!($user->authorise('core.admin'))){
		$msg = 'Forget IT';
		$app->redirect('index.php?option=com_virtuemart_allinone', $msg);
	} else {
		if(!class_exists('com_virtuemart_allinoneInstallerScript')) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart_allinone'.DS.'script.vmallinone.php');
		$updater = new com_virtuemart_allinoneInstallerScript();
		$updater->vmInstall();
		$app->redirect('index.php?option=com_virtuemart_allinone', 'Database updated');
	}

}

?>
<script type="text/javascript">
<!--
function confirmation(message, destnUrl) {
	var answer = confirm(message);
	if (answer) {
		window.location = destnUrl;
	}
}
//-->
</script>

<table>
<tr>
	<td>
<?php LiveUpdate::handleRequest(); ?>
	</td>
</tr>
<tr>
<td align="center">
<?php
$jlang = JFactory::getLanguage();
		$jlang->load('com_virtuemart', JPATH_ADMINISTRATOR, 'en-GB', true); // Load English (British)
		$jlang->load('com_virtuemart', JPATH_ADMINISTRATOR, $jlang->getDefault(), true); // Load the site's default language
		$jlang->load('com_virtuemart', JPATH_ADMINISTRATOR, null, true); // Load the currently selected language

		?>
<?php $link=JROUTE::_('index.php?option=com_virtuemart_allinone&task=updateDatabase&'.JSession::getFormToken().'=1' ); ?>
	    <button onclick="javascript:confirmation('<?php echo addslashes( JText::_('COM_VIRTUEMART_UPDATE_VMPLUGINTABLES') ); ?>', '<?php echo $link; ?>');">

            <?php echo JText::_('COM_VIRTUEMART_UPDATE_VMPLUGINTABLES'); ?>
		</button>
	</td>
    </tr>
</table>

<?php


class LiveUpdate
{
	/**
	 * Loads the translation strings -- this is an internal function, called automatically
	 */
	private static function loadLanguage()
	{
		// Load translations
		$basePath = dirname(__FILE__);
		$jlang = JFactory::getLanguage();
		$jlang->load('liveupdate', $basePath, 'en-GB', true); // Load English (British)
		$jlang->load('liveupdate', $basePath, $jlang->getDefault(), true); // Load the site's default language
		$jlang->load('liveupdate', $basePath, null, true); // Load the currently selected language
	}

	/**
	 * Handles requests to the "liveupdate" view which is used to display
	 * update information and perform the live updates
	 */
	public static function handleRequest()
	{
		// Load language strings
		self::loadLanguage();

		// Load the controller and let it run the show
		require_once dirname(__FILE__).'/classes/controller.php';
		$controller = new LiveUpdateController();
		$controller->execute(vRequest::getCmd('task','overview'));
		$controller->redirect();
	}

	/**
	 * Returns update information about your extension, based on your configuration settings
	 * @return stdClass
	 */
	public static function getUpdateInformation($force = false)
	{
		require_once dirname(__FILE__).'/classes/updatefetch.php';
		$update = new LiveUpdateFetch();
		$info = $update->getUpdateInformation($force);
		$hasUpdates = $update->hasUpdates();
		$info->hasUpdates = $hasUpdates;

		$config = LiveUpdateConfig::getInstance();
		$extInfo = $config->getExtensionInformation();

		$info->extInfo = (object)$extInfo;

		return $info;
	}

	public static function getIcon($config=array())
	{
		// Load language strings
		self::loadLanguage();

		$defaultConfig = array(
			'option'			=> vRequest::getCmd('option',''),
			'view'				=> 'liveupdate',
			'mediaurl'			=> JURI::base().'components/'.vRequest::getCmd('option','').'/liveupdate/assets/'
		);
		$c = array_merge($defaultConfig, $config);

		$url = 'index.php?option='.$c['option'].'&view='.$c['view'];
		$img = $c['mediaurl'];

		$updateInfo = self::getUpdateInformation();
		if(!$updateInfo->supported) {
			// Unsupported
			$class = 'liveupdate-icon-notsupported';
			$img .= 'nosupport-32.png';
			$lbl = JText::_('LIVEUPDATE_ICON_UNSUPPORTED');
		} elseif($updateInfo->stuck) {
			// Stuck
			$class = 'liveupdate-icon-crashed';
			$img .= 'nosupport-32.png';
			$lbl = JText::_('LIVEUPDATE_ICON_CRASHED');
		} elseif($updateInfo->hasUpdates) {
			// Has updates
			$class = 'liveupdate-icon-updates';
			$img .= 'update-32.png';
			$lbl = JText::_('LIVEUPDATE_ICON_UPDATES');
		} else {
			// Already in the latest release
			$class = 'liveupdate-icon-noupdates';
			$img .= 'current-32.png';
			$lbl = JText::_('LIVEUPDATE_ICON_CURRENT');
		}

		return '<div class="icon"><a href="'.$url.'">'.
			'<div><img src="'.$img.'" width="32" height="32" border="0" align="middle" style="float: none" /></div>'.
			'<span class="'.$class.'">'.$lbl.'</span></a></div>';
	}
}