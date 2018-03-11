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

/**
 * Dues list controller class.
 *
 * @since  1.6
 */
class DuesControllerDues extends JControllerAdmin
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
		parent::__construct($config);
		$this->registerTask('sticky_unpublish', 'sticky_publish');

	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  Array of configuration parameters.
	 *
	 * @return  JModelLegacy
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Due', $prefix = 'DuesModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
		/**
	 * Stick items
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function sticky_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('sticky_publish' => 1, 'sticky_unpublish' => 0);
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JError::raiseWarning(500, JText::_('COM_DUES_NO_DUES_SELECTED'));
		}
		else
		{
			// Get the model.
			/** @var DuesModelDues $model */
			$model = $this->getModel();
			
			// Change the state of the records.
			if (!$model->stick($ids, $value))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				if ($value == 1)
				{
					$ntext = 'COM_DUES_N_DUES_STUCK';
				}
				else
				{
					$ntext = 'COM_DUES_N_DUES_UNSTUCK';
				}

				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_dues&view=dues');
	}
	/**
	 * Executes the batch process to add URLs to the database
	 *
	 * @return  void
	 */
	public function batch()
	{
		$batch_year_request = $this->input->post->get('batch_year', '', 'INT');
		

		// Set default message on error - overwrite if successful
		$this->setMessage(JText::_('COM_DUES_NO_ITEM_ADDED'), 'error');

		if (!empty($batch_year_request))
		{
			$model = $this->getModel('Dues');

			// Execute the batch process
			if ($model->batchProcess($batch_year_request))
			{
				$this->setMessage(JText::_('COM_DUES_ITEMS_ADDED'));
			}
		}

		$this->setRedirect('index.php?option=com_dues');
	}
}
