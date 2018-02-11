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
 * View to edit a contact.
 *
 * @since  1.6
 */
class DuesViewDue extends JViewLegacy
{
	/**
	 * The JForm object
	 *
	 * @var  JForm
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		

		$this->addToolbar();

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
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user       = JFactory::getUser();
		$userId     = $user->id;
		$isNew      = ($this->item->id == 0);
		
	
		JToolbarHelper::title($isNew ? JText::_('COM_DUES_MANAGER_DUES_NEW') : JText::_('COM_DUES_MANAGER_DUES_EDIT'), 'address dues');

		// Build the actions for new and existing records.
		if ($isNew)
		{
			// For new records, check the create permission.
			if ($isNew)
			{
				JToolbarHelper::apply('due.apply');
				JToolbarHelper::save('due.save');
				JToolbarHelper::save2new('due.save2new');
			}

			JToolbarHelper::cancel('due.cancel');
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			$itemEditable = $this->item->created_by == $userId;

			// Can't save the record if it's checked out and editable
			if (!$checkedOut && $itemEditable)
			{
				JToolbarHelper::apply('due.apply');
				JToolbarHelper::save('due.save');
				JToolbarHelper::save2new('due.save2new');
				
			}


			if (JComponentHelper::isEnabled('com_contenthistory') && $itemEditable)
			{
				JToolbarHelper::versions('com_dues.due', $this->item->id);
			}

			JToolbarHelper::cancel('due.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		JToolbarHelper::help('JHELP_COMPONENTS_DUES_DUES_EDIT');
	}
}
