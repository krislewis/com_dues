<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';


if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_dues&task=dues.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'duesList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_dues'); ?>" method="post" name="adminForm" id="adminForm">
	
	<div id="j-main-container">

		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<div class="clearfix"></div>
		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
			<table class="table table-striped" id="duesList">
				<thead>
					<tr>

						<th width="1%" class="nowrap center">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th class="nowrap center">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_PAID', 'a.paid', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_LINKED_USER_LABEL', 'ul.name', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_YEAR', 'a.year', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_DATE_PAID', 'a.date_paid', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_LINK', '', $listDirn, $listOrder); ?>
						</th>						
						<th style="min-width:55px" class="nowrap center">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_STATUS', 'a.published', $listDirn, $listOrder); ?>
						</th>
						

						

						<th class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
						
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="10">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php 
				$params = JComponentHelper::getParams('com_dues');
				$mage_url = htmlspecialchars($params->get('dues_url'), ENT_COMPAT, 'UTF-8');
				?>
				<?php foreach ($this->items as $i => $item) :
					$canEdit   = $user->authorise('core.edit',       'com_dues');
					$canChange = $user->authorise('core.edit.state', 'com_dues');
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<div class="btn-group">
							<?php echo JHtml::_('dues.paid', $item->status, $i, $canChange); ?>

						</td>
						<td class="break-word">
							<?php if ($canEdit) : ?>
								<a href="<?php echo JRoute::_('index.php?option=com_dues&task=link.edit&id=' . $item->id); ?>"><?php echo $this->escape($item->linked_user); ?></a>
								
							<?php endif; ?>
						</td>
						
						<td class="small break-word hidden-phone hidden-tablet">
							<?php echo $this->escape($item->year); ?>
						</td>
						<td class="small break-word hidden-phone hidden-tablet">
							<?php echo $this->escape($item->date_paid); ?>
						</td>
						<td class="small break-word hidden-phone hidden-tablet">
							<a href="<?php echo rtrim($mage_url, '/') . '/' . $this->escape($item->user_id) . '/' . $this->escape($item->user_id) . '-' . $this->escape($item->year) . '.html'; ?>">
								<?php echo rtrim($mage_url, '/') . '/' . $this->escape($item->user_id) . '/' . $this->escape($item->user_id) . '-' . $this->escape($item->year) . '.html'; ?>
							</a>
						</td>
						<td class="center">
							<div class="btn-group">
								<?php echo JHtml::_('jgrid.published', $item->published, $i, 'dues.',$canChange); ?>
								<?php // Create dropdown items and render the dropdown list.
								if ($canChange)
								{
									JHtml::_('actionsdropdown.' . ((int) $item->published === 2 ? 'un' : '') . 'archive', 'cb' . $i, 'dues');
									JHtml::_('actionsdropdown.' . ((int) $item->published === -2 ? 'un' : '') . 'trash', 'cb' . $i, 'dues');
									echo JHtml::_('actionsdropdown.render', $this->escape($item->id));
								}
								?>
							</div>
						</td>
						
						<td class="small break-word hidden-phone hidden-tablet">
							<?php echo $this->escape($item->id); ?>
						</td>
						
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>


		<?php // Load the batch processing form if user is allowed ?>
			<?php if ($user->authorise('core.create', 'com_dues')
				&& $user->authorise('core.edit', 'com_dues')
				&& $user->authorise('core.edit.state', 'com_dues')) : ?>
				<?php echo JHtml::_(
					'bootstrap.renderModal',
					'collapseModal',
					array(
						'title'  => JText::_('COM_DUES_BATCH_OPTIONS'),
						'footer' => $this->loadTemplate('batch_footer'),
					),
					$this->loadTemplate('batch_body')
				); ?>
			<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
