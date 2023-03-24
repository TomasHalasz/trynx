<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Paymnet Order Items management.
 */
class PaymentOrderItemsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_payment_order_items';

	
}

