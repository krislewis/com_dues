<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Contact component helper.
 *
 * @since  1.6
 */
class DuesHelper extends JHelperContent
{
	
	/**
	 * Adds Count Items for Category Manager.
	 *
	 * @param   stdClass[]  &$items  The dues category objects
	 *
	 * @return  stdClass[]
	 *
	 * @since   3.5
	 */
	public static function countItems(&$items)
	{
		$db = JFactory::getDbo();

		foreach ($items as $item)
		{
			$item->count_trashed = 0;
			$item->count_archived = 0;
			$item->count_unpublished = 0;
			$item->count_published = 0;
			$query = $db->getQuery(true);
			$query->select('status AS state, count(*) AS count')
				->from($db->qn('#__user_dues'))
				->group('status');
			$db->setQuery($query);
			$dues = $db->loadObjectList();

			foreach ($dues as $due)
			{
				if ($due->state == 1)
				{
					$item->count_published = $due->count;
				}

				if ($due->state == 0)
				{
					$item->count_unpublished = $due->count;
				}

				if ($due->state == 2)
				{
					$item->count_archived = $due->count;
				}

				if ($due->state == -2)
				{
					$item->count_trashed = $due->count;
				}
			}
		}

		return $items;
	}


	/**
	 * Returns a valid section for contacts. If it is not valid then null
	 * is returned.
	 *
	 * @param   string  $section  The section to get the mapping for
	 * @param   object  $item     optional item object
	 *
	 * @return  string|null  The new section
	 *
	 * @since   3.7.0
	 */
	public static function validateSection($section, $item)
	{
		if (JFactory::getApplication()->isClient('site') && $section == 'dues' && $item instanceof JForm)
		{
			// The dues form needs to be the mail section
			$section = 'mail';
		}

		if (JFactory::getApplication()->isClient('site') && $section == 'category')
		{
			// The contact form needs to be the mail section
			$section = 'contact';
		}

		if ($section != 'mail' && $section != 'dues')
		{
			// We don't know other sections
			return null;
		}

		return $section;
	}

	/**
	 * Returns valid contexts
	 *
	 * @return  array
	 *
	 * @since   3.7.0
	 */
	public static function getContexts()
	{
		JFactory::getLanguage()->load('com_dues', JPATH_ADMINISTRATOR);

		$contexts = array(
			'com_dues.dues'    => JText::_('COM_DUES_FIELDS_CONTEXT_DUES'),
			'com_dues.mail'       => JText::_('COM_DUES_FIELDS_CONTEXT_MAIL'),
		);

		return $contexts;
	}
}
