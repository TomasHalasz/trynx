<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PartnersAccount management.
 */
class PartnersAccountManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_partners_account';

    public function updateAccounts($ucet, $partnerId, $clCurrenciesId)
    {
        foreach ($ucet as $key => $one){
            if (isset($one['standardniUcet'])) {
                $lcPredcisli = isset($one['standardniUcet']['predcisli']) ? $one['standardniUcet']['predcisli'] . '-' : '';
                $lcUcet = $lcPredcisli . $one['standardniUcet']['cislo'];
                $lcBank = $one['standardniUcet']['kodBanky'];
                $tmpUcet = $this->findAll()->where('cl_partners_book_id = ? AND account_code = ? AND  bank_code = ?', $partnerId, $lcUcet, $lcBank)->fetch();
                if (!$tmpUcet) {
                    $maxRow = $this->findAll()->where('cl_partners_book_id = ?', $partnerId)->max('item_order') + 1;
                    $maxRow = is_null($maxRow) ? 0 : $maxRow;
                    $this->insert(['cl_partners_book_id' => $partnerId,
                        'cl_currencies_id' => $clCurrenciesId,
                        'account_code' => $lcUcet,
                        'bank_code' => $lcBank,
                        'item_order' => $maxRow,
                        'date_from' => $one['datumZverejneni']]);
                }
            }
        }
    }


}

