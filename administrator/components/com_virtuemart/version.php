<?php
if( !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/**
*
* @version $Id: version.php 7569 2014-01-10 00:03:34Z Milbo $
* @package VirtueMart
* @subpackage core
* @copyright Copyright (C) 2005-2011 VirtueMart Team - All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.org
*/


if( !class_exists( 'vmVersion' ) ) {
	/** Version information */
	class vmVersion {
		/** @var string Product */
		static $PRODUCT = 'VirtueMart';
		/** @var int Release Number */
		static $RELEASE = '2.9.9b';
		/** @var string Development Status */
		static $DEV_STATUS = 'MAYOR';
		/** @var string Codename */
		static $CODENAME = 'DreamIn';
		/** @var string Date */
		static $RELDATE = 'September 04 2014';
		/** @var string Time */
		static $RELTIME = '1339';
		/** @var string Timezone */
		static $RELTZ = 'GMT';
		/** @var string Revision */
		static $REVISION = 'Revision: 8271';
		/** @var string Copyright Text */
		static $COPYRIGHT = 'Copyright (C) 2005-2012 VirtueMart Development Team  - All rights reserved.';
		/** @var string URL */
		static $URL = '<a href="http://virtuemart.net">VirtueMart</a> is a Free Component for Joomla! released under the GNU/GPL2 License.';

		static $shortversion = '';
		static $myVersion = '';

		public function __construct() {

			self::$shortversion = vmVersion::$PRODUCT . " " . vmVersion::$RELEASE . " " . vmVersion::$DEV_STATUS. " ";

			self::$myVersion = self::$shortversion .' '.vmVersion::$REVISION. " [".vmVersion::$CODENAME ."] <br />" . vmVersion::$RELDATE . " "
				. vmVersion::$RELTIME . " " . vmVersion::$RELTZ;
		}
	}
}




// pure php no closing tag