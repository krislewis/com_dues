<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of contact records.
 *
 * @since  1.6
 */
class DuesModelDues extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'name', 'ul.name',
				'year', 'a.year',
				'user_id', 'a.user_id',
				'published', 'a.published',
				'status', 'a.status',
				'date_paid', 'a.date_paid',
				'ordering', 'a.ordering',
				'ul.name', 'linked_user'
			);


		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = 'ul.name', $direction = 'asc')
	{
		$app = JFactory::getApplication();

		$forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		// Adjust the context to support forced languages.
		if ($forcedLanguage)
		{
			$this->context .= '.' . $forcedLanguage;
		}

		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string'));
		$this->setState('filter.status', $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string'));
		$this->setState('filter.level', $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', null, 'int'));

		// List state information.
		parent::populateState($ordering, $direction);

		// Force a language.
		if (!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.status');
		$id .= ':' . $this->getState('filter.level');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();

		// Select the required fields from the table.
		$query->select(
			$db->quoteName(
				explode(', ', $this->getState(
					'list.select',
					'a.id, a.status, a.date_paid, a.user_id' .
					', a.published, a.year'
					)
				)
			)
		);
		$query->from($db->quoteName('#__user_dues', 'a'));

		// Join over the users for the linked user.
		$query->select(
				array(
					$db->quoteName('ul.name', 'linked_user'),
					$db->quoteName('ul.email')
				)
			)
			->join(
				'LEFT',
				$db->quoteName('#__users', 'ul') . ' ON ' . $db->quoteName('ul.id') . ' = ' . $db->quoteName('a.user_id')
			);


		// Filter by access level.
		if ($access = $this->getState('filter.status'))
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $access);
		}


		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.published') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(' . $db->quoteName('a.published') . ' = 0 OR ' . $db->quoteName('a.published') . ' = 1)');
		}

		// Filter by search in name.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where(
					'(' . $db->quoteName('ul.name') . ' LIKE ' . $search . 'OR' . $db->quoteName('a.year') . 'LIKE' . $search . ')'
				);
			}
		}

	

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.user_id');
		$orderDirn = $this->state->get('list.direction', 'asc');


		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}
	/**
	 * Add the entered URLs into the database
	 *
	 * @param   array  $batch_urls  Array of URLs to enter into the database
	 *
	 * @return bool
	 */
	public function batchProcess($batch_year)
	{
		include_once(JPATH_ADMINISTRATOR . '/components/com_dues/credentials.php');
		function getActiveMembers()
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			// Select the required fields from the table.
			$query->select($db->quoteName('a.user_id'));
			$query->from($db->quoteName('#__comprofiler', 'a'));
			$query->where('(' . $db->quoteName('a.cb_memberlevel') . ' != "Non-Member User" AND ' . $db->quoteName('a.cb_memberstatus') . ' = "Active")');
			$db->setQuery($query);
			return $db->loadColumn();
		}
		function getBatchYearDues($batch_year)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			// Select the required fields from the table.
			$query->select($db->quoteName('a.user_id'));
			$query->from($db->quoteName('#__user_dues', 'a'));
			$query->where($db->quoteName('a.year') . ' =' . (int)$batch_year);
			$db->setQuery($query);
			return $db->loadColumn();
		}
		$ActiveMembers = getActiveMembers();
		$BatchYearDues = getBatchYearDues($batch_year);
		
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		$columns = array(
			$db->quoteName('user_id'),
			$db->quoteName('year'),
			$db->quoteName('status'),
			$db->quoteName('created'),
			$db->quoteName('created_by'),
			$db->quoteName('published')
		);

		$query->columns($columns);
		$sessionId = $proxy->login($mage_api_user,$mage_api_key);
		foreach ($ActiveMembers as $ActiveMember)
		{
			if(!in_array($ActiveMember, $BatchYearDues)){//Make sure dues year+member doesn't already exist
				//Magento API call to check for category and create if not exist, then add item to it
				
				$query->insert($db->quoteName('#__user_dues'), false)
					->values(
						$db->quote($ActiveMember) . ', ' . $db->quote($batch_year) . ' ,' . $db->quote('0') . ', ' . 
						$db->quote(JFactory::getDate()->toSql()) . ', ' . $db->quote($user->id) . ', 1'
					);
			}
		}

		$db->setQuery($query);
		$db->execute();

		return true;
	}
}
