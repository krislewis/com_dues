<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_dues
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

JLoader::register('DuesHelper', JPATH_ADMINISTRATOR . '/components/com_dues/helpers/dues.php');

/**
 * dues HTML helper class.
 *
 * @since  1.6
 */
abstract class JHtmlDues
{


	public static function paid($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states = array(
			1 => array(
				'sticky_unpublish',
				'COM_DUES_DUES_PAID',
				'COM_DUES_DUES_HTML_PAY_DUES',
				'COM_DUES_DUES_PAID',
				true,
				'publish',
				'publish'
			),
			0 => array(
				'sticky_publish',
				'COM_DUES_DUES_UNPAID',
				'COM_DUES_DUES_HTML_UNPAID_DUES',
				'COM_DUES_DUES_UNPAID',
				true,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'dues.', $enabled, true, $checkbox);
	}
}