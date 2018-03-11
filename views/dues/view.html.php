<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_DUES
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View class for a list of DUES
 *
 * @since  1.6
 */
class DuesViewDues extends JViewLegacy
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  JForm
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * The sidebar markup
	 *
	 * @var  string
	 */
	protected $sidebar;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Preprocess the list of items to find ordering divisions.
		// TODO: Complete the ordering stuff with nested sets
		foreach ($this->items as &$item)
		{
			$item->order_up = true;
			$item->order_dn = true;
		}

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
		}


		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		
		$user  = JFactory::getUser();

		JToolbarHelper::title(JText::_('COM_DUES_MANAGER_DUES'), 'address dues');

		// Add buttons
		if ($user->authorise('core.create', 'com_dues')
			&& $user->authorise('core.edit', 'com_dues')
			&& $user->authorise('core.edit.state', 'com_dues'))
		{
			JToolbarHelper::addNew('due.add');
			JToolbarHelper::publish('due.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('due.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			// Get the toolbar object instance
			$bar = JToolbar::getInstance('toolbar');

			$title = JText::_('COM_DUES_TOOLBAR_BATCH');

			// Instantiate a new JLayoutFile instance and render the batch button
			$layout = new JLayoutFile('toolbar.batch');

			$dhtml = $layout->render(array('title' => $title));
			$bar->appendButton('Custom', $dhtml, 'batch');
			
		}

		if ($this->state->get('filter.published') == -2)
		{
			JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'dues.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($user->authorise('core.edit.state', 'com_dues'))
		{
			JToolbarHelper::trash('dues.trash');
		}

		if ($user->authorise('core.admin', 'com_dues') || $user->authorise('core.options', 'com_dues'))
		{
			JToolbarHelper::preferences('com_dues');
		}

		JToolbarHelper::help('JHELP_COMPONENTS_DUES_DUES');

		JHtmlSidebar::setAction('index.php?option=com_dues');
	}
}
