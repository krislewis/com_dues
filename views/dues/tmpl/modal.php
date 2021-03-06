<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$app = JFactory::getApplication();

if ($app->isClient('site'))
{
	JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
}

JLoader::register('DuesHelperRoute', JPATH_ROOT . '/components/com_dues/helpers/route.php');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.core');
JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
JHtml::_('bootstrap.popover', '.hasPopover', array('placement' => 'bottom'));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.polyfill', array('event'), 'lt IE 9');
JHtml::_('script', 'com_dues/admin-dues-modal.min.js', array('version' => 'auto', 'relative' => true));

// Special case for the search field tooltip.
$searchFilterDesc = $this->filterForm->getFieldAttribute('search', 'description', null, 'filter');
JHtml::_('bootstrap.tooltip', '#filter_search', array('title' => JText::_($searchFilterDesc), 'placement' => 'bottom'));

$function  = $app->input->getCmd('function', 'jSelectDues');
$editor    = $app->input->getCmd('editor', '');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$onclick   = $this->escape($function);

if (!empty($editor))
{
	// This view is used also in com_menus. Load the xtd script only if the editor is set!
	JFactory::getDocument()->addScriptOptions('xtd-contacts', array('editor' => $editor));
	$onclick = "jSelectDues";
}
?>
<div class="container-popup">

	<form action="<?php echo JRoute::_('index.php?option=com_dues&view=dues&layout=modal&tmpl=component&function=' . $function . '&' . JSession::getFormToken() . '=1'); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">

		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped table-condensed">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th width="1%" class="nowrap center">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" style="min-width:55px" class="nowrap center">
							<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap">
							<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.paid', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_LINKED_USER_LABEL', 'ul.name', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_DUES_FIELD_LINKED_USER_LABEL', 'a.year', $listDirn, $listOrder); ?>
						</th>
						

						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="6">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php
				$iconStates = array(
					-2 => 'icon-trash',
					0  => 'icon-unpublish',
					1  => 'icon-publish',
					2  => 'icon-archive',
				);
				?>
				<?php foreach ($this->items as $i => $item) : ?>
				<?php 
						$lang = '';
					
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center">
							<span class="<?php echo $iconStates[$this->escape($item->published)]; ?>" aria-hidden="true"></span>
						</td>
						<td>
							<a class="select-link" href="javascript:void(0)" data-function="<?php echo $this->escape($onclick); ?>" data-id="<?php echo $item->id; ?>" data-title="<?php echo $this->escape(addslashes($item->name)); ?>">
								<?php echo $this->escape($item->name); ?>
							</a>
							<?php echo $this->escape($item->name); ?></a>
							<div class="small">
								<?php echo JText::_('JCATEGORY') . ': ' . $this->escape($item->category_title); ?>
							</div>
						</td>
						<td>
							<?php if (!empty($item->linked_user)) : ?>
								<?php echo $item->linked_user; ?>
							<?php endif; ?>
						</td>
						
						<td class="small hidden-phone">
							<?php echo JLayoutHelper::render('joomla.content.language', $item); ?>
						</td>
						<td align="center">
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="forcedLanguage" value="<?php echo $app->input->get('forcedLanguage', '', 'CMD'); ?>" />
		<?php echo JHtml::_('form.token'); ?>

	</form>
</div>
