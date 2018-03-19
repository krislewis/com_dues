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

	/** 
	 * Mage soap api functionality
	 * 
	 * 
	 * 
	*/
	public static function mageUpdate($user_id, $year, $state = 0, $mage_flush = TRUE)
	{
		//api magento call to first check if item exists, if not create, adjust inventory, flush
		$inv_qty = $state ? '0' : '1';
		$params = JComponentHelper::getParams('com_dues');
		$member = JFactory::getUser($user_id);
		$member_name = $member->name;
		$mage_url = htmlspecialchars($params->get('dues_url'), ENT_COMPAT, 'UTF-8');
		$mage_api_key = htmlspecialchars($params->get('dues_api_key'), ENT_COMPAT, 'UTF-8');
		$mage_api_user = htmlspecialchars($params->get('dues_api_user'), ENT_COMPAT, 'UTF-8');
		$mage_loc = htmlspecialchars($params->get('dues_loc'), ENT_COMPAT, 'UTF-8');
		$client_url =  rtrim($mage_url, '/') . '/api/soap/?wsdl' ;
		$stockItemData = array( //set stock up for update
			'qty' => $inv_qty,
			'is_in_stock' => 1,
			'manage_stock' => 1,
			'use_config_manage_stock' => 0,
			'min_qty' => 0,
			'use_config_min_qty' => 0,
			'min_sale_qty' => 1,
			'use_config_min_sale_qty' => 0,
			'max_sale_qty' => 1,
			'use_config_max_sale_qty' => 0,
			'is_qty_decimal' => 0,
			'backorders' => 0,
			'use_config_backorders' => 0,
			'notify_stock_qty' => 10,
			'use_config_notify_stock_qty' => 0
		);
		
		$client = new SoapClient($client_url);
		
		$session = $client->login($mage_api_user,$mage_api_key);
		$sku = $user_id . '-' . $year;
		$cat_result = $client->call($session, 'catalog_category.level', array(null, null, 2));
		
		//check if cat exists
		$cat_exists = false;
		foreach ($cat_result as $r => $result) {
			if($result["name"] == $user_id){
				$cat_exists = true;
				$cat_id = $result["category_id"];
			}
		}
		if(!$cat_exists){ //create the category
			$cat_id = $client->call($session, 'catalog_category.create', array(2, array(
				'name' => $user_id,
				'is_active' => 1,
				'position' => 1,
				'available_sort_by' => 'position',
				'custom_design' => null,
				'custom_apply_to_products' => null,
				'custom_design_from' => null,
				'custom_design_to' => null,
				'custom_layout_update' => null,
				'default_sort_by' => 'position',
				'description' => $user_id . ' Dues',
				'display_mode' => null,
				'is_anchor' => 0,
				'landing_page' => null,
				'meta_description' => 'Category meta description',
				'meta_keywords' => 'Category meta keywords',
				'meta_title' => 'Category meta title',
				'page_layout' => 'two_columns_left',
				'url_key' => $user_id,
				'include_in_menu' => 0
			)));
		}
		//check if item exists in category
		$item_check = $client->call($session, 'catalog_category.assignedProducts', $cat_id);
		$item_exists = false;
		foreach ($item_check as $k => $inventory_item) {
			if($inventory_item['sku'] == $sku){
				$item_exists = true;
			}
		}
		if(!$item_exists){//item doesn't exist, create it
			// get attribute set
			$attributeSets = $client->call($session, 'product_attribute_set.list');
			$attributeSet = current($attributeSets);


			$result = $client->call($session, 'catalog_product.create', array('simple', $attributeSet['set_id'], $sku, array(
				'categories' => array($cat_id),
				'websites' => array(1),
				'name' => $member_name . ' Dues for ' . $year,
				'weight' => '0',
				'status' => '1',
				'url_key' => $sku,
				'visibility' => '4',
				'price' => '250',
				'tax_class_id' => 0,
				'meta_title' => $member_name,
				'meta_description' => $member_name,
				'stock_data' => $stockItemData
			)));
		}else{
			//finally adjust qty based on dues paid or unpaid if cat and item already exist
			$productId = $sku;

			$result = $client->call(
				$session,
				'product_stock.update',
				array(
					$productId,
					$stockItemData
				)
			);
		}
		$client->endSession($session); 
		if($mage_loc != "" and $mage_flush){
			try{
				array_map('unlink', glob($_SERVER['DOCUMENT_ROOT'] . '/' . trim($mage_loc,'/') . '/var/cache/mage--*/*')); 
			} catch (Exception $e) {
				JError::raiseWarning(500, $e->getMessage());
			}
			
		}
		return $result;
	}
}
