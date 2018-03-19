<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Contact Table class.
 *
 * @since  1.0
 */
class DuesTableDue extends JTable
{
	
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(&$db)
	{

		parent::__construct('#__user_dues', 'id', $db);

		//JTableObserverTags::createObserver($this, array('typeAlias' => 'com_dues.due'));
		//JTableObserverContenthistory::createObserver($this, array('typeAlias' => 'com_dues.due'));
	}

	/**
	 * Stores a dues item.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = false)
	{
		// Transform the params field
		if (is_array($this->params))
		{
			$registry = new Registry($this->params);
			$this->params = (string) $registry;
		}

		$date   = JFactory::getDate()->toSql();
		$userId = JFactory::getUser()->id;

		$this->modified = $date;

		if ($this->id)
		{
			// Existing item
			$this->modified_by = $userId;
		}
		else
		{
			// New dues. A due created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!(int) $this->created)
			{
				$this->created = $date;
			}

			if (empty($this->created_by))
			{
				$this->created_by = $userId;
			}
		}

		return parent::store($updateNulls);
	}


	/**
	 * Method to set the sticky state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.  If not set the instance property value is used.
	 * @param   integer  $state   The sticky state. eg. [0 = unsticked, 1 = sticked]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function stick($pks = null, $state = 1, $userId = 0)
	{
		$k = $this->_tbl_key;

		// Sanitize input.
		$pks    = ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array($this->$k);
			}
			// Nothing to set publishing state on, return false.
			else
			{
				$this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Get an instance of the table
		/** @var DuesTableDue $table */
		$table = JTable::getInstance('Due', 'DuesTable');

		// For all keys
		foreach ($pks as $pk)
		{
			// Load the dues
			if (!$table->load($pk))
			{
				$this->setError($table->getError());
			}


			// Change the state
			$table->status = $state;

			$table->modified = JFactory::getDate()->toSql();
			$table->modified_by = $userId;
			if ($state){
				$table->date_paid = JFactory::getDate()->toSql();
			}else{
				$table->date_paid = "0000-00-00";
			}

			// Check the row
			$table->check();

			// Store the row
			if (!$table->store())
			{
				$this->setError($table->getError());
			}
			//api magento call to first check if item exists, if not create, adjust inventory, flush
			$params = JComponentHelper::getParams('com_dues');
			$member = JFactory::getUser($table->user_id);
			$member_name = $member->name;
			$mage_url = htmlspecialchars($params->get('dues_url'), ENT_COMPAT, 'UTF-8');
			$mage_api_key = htmlspecialchars($params->get('dues_api_key'), ENT_COMPAT, 'UTF-8');
			$mage_api_user = htmlspecialchars($params->get('dues_api_user'), ENT_COMPAT, 'UTF-8');
			$mage_loc = htmlspecialchars($params->get('dues_loc'), ENT_COMPAT, 'UTF-8');
			$client_url =  rtrim($mage_url, '/') . '/index.php/api/index/index/wsdl/1/';
			
			$client = new SoapClient($client_url);
			
			$session = $client->login($mage_api_user,$mage_api_key);
			$sku = $table->user_id . '-' . $table->year;
			$cat_result = $client->call($session, 'catalog_category.level', array(null, null, 2));
			
			//check if cat exists
			$cat_exists = false;
			foreach ($cat_result as $r => $result) {
				if($result["name"] == $table->user_id){
					$cat_exists = true;
					$cat_id = $result["category_id"];
				}
			}
			if(!$cat_exists){ //create the category
				$cat_id = $client->call($session, 'catalog_category.create', array(2, array(
					'name' => $table->user_id,
					'is_active' => 1,
					'position' => 1,
					
					'available_sort_by' => 'position',
					'custom_design' => null,
					'custom_apply_to_products' => null,
					'custom_design_from' => null,
					'custom_design_to' => null,
					'custom_layout_update' => null,
					'default_sort_by' => 'position',
					'description' => $table->user_id . ' Dues',
					'display_mode' => null,
					'is_anchor' => 0,
					'landing_page' => null,
					'meta_description' => 'Category meta description',
					'meta_keywords' => 'Category meta keywords',
					'meta_title' => 'Category meta title',
					'page_layout' => 'two_columns_left',
					'url_key' => $table->user_id,
					'include_in_menu' => 1,
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
					'name' => $member_name . ' Dues for ' . $table->year,
					'description' => $member_name . ' Membership Dues for ' . $table->year,
					'weight' => '0',
					'status' => '1',
					'url_key' => $sku,
					//'url_path' => 'product-url-path',
					'visibility' => '4',
					'price' => '250',
					'tax_class_id' => 0,
					'meta_title' => 'Product meta title',
					'meta_keyword' => 'Product meta keyword',
					'meta_description' => 'Product meta description',
					'stock_data' => array(
						'qty' => '1',
						'is_in_stock' => 1,
						'manage_stock' => 1,
						'min_qty' => 0,
						'max_sale_qty' => 1,
						'is_qty_decimal' => 0,
						'backorders' => 0
					)
				)));
			}
			//finally adjust qty based on dues paid or unpaid
			$productId = $sku;
			$inv_qty = $state ? '0' : '1';
			$stockItemData = array(
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

			$result = $client->call(
				$session,
				'product_stock.update',
				array(
					$productId,
					$stockItemData
				)
			);
			
			$client->endSession($session);
			if($mage_loc != ""){
				try{
					array_map('unlink', glob($_SERVER['DOCUMENT_ROOT'] . '/' . trim($mage_loc,'/') . '/var/cache/mage--*/*')); 
				} catch (Exception $e) {
    				unset($e);
				}
				
			}
			
		}

		return count($this->getErrors()) == 0;
	}
	
}
