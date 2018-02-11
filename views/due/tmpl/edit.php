<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_dues
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

JFactory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'link.cancel' || document.formvalidator.isValid(document.getElementById('link-form')))
		{
			Joomla.submitform(task, document.getElementById('link-form'));
		}
	};
");
?>

<form action="<?php echo JRoute::_('index.php?option=com_dues&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="link-form" class="form-validate form-horizontal">
	<fieldset>
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'basic')); ?>

			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'basic', empty($this->item->id) ? JText::_('COM_REDIRECT_NEW_LINK') : JText::sprintf('COM_REDIRECT_EDIT_LINK', $this->item->id)); ?>
				<?php echo $this->form->renderField('year'); ?>
				<?php echo $this->form->renderField('name'); ?>
				<?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('status'); ?>
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('created'); ?>
				
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</fieldset>
</form>
