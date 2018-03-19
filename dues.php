<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_dues'))
{
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}
$params = JComponentHelper::getParams('com_dues');
$mage_url = htmlspecialchars($params->get('dues_url'), ENT_COMPAT, 'UTF-8');
$mage_api_key = htmlspecialchars($params->get('dues_api_key'), ENT_COMPAT, 'UTF-8');
$mage_api_user = htmlspecialchars($params->get('dues_api_user'), ENT_COMPAT, 'UTF-8');
$controller = JControllerLegacy::getInstance('dues');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
