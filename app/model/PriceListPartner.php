<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListPartner management.
 */
class PriceListPartnerManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_partner';
	public $PriceListManager;

    /** @var App\Model\StoreMoveManager */
    public $StoreMoveManager;
	
	public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
				    \App\Model\PriceListManager $PriceListManager, \App\Model\StoreMoveManager $StoreMoveManager)
	{
	    parent::__construct($db, $userManager, $user, $session, $accessor);
	    $this->PriceListManager = $PriceListManager;
	    $this->StoreMoveManager = $StoreMoveManager;


	}    
	
	
	
	public function update($data, $mark = FALSE)
	{
	    parent::update($data, $mark);
	    
	    $this->PriceListManager->updateCoopData($this->find($data['id'])->cl_pricelist_id);
	}


    public function autoFill($cl_partners_book_id) : int
    {
        $count = 0;
        $data = $this->StoreMoveManager->findAll()->where('cl_store_docs.cl_partners_book_id = ? AND cl_store_docs.doc_type = 1', $cl_partners_book_id)->
                                            where('cl_store_docs.doc_date <= NOW() AND cl_store_docs.doc_date >= DATE_SUB(NOW(), INTERVAL 2 MONTH)');
        $i = $this->findAll()->where('cl_partners_book_id = ?', $cl_partners_book_id)->max('item_order') + 1;
        foreach($data as $key => $one){
            $arrData = array();
            $arrData['item_order']       = $i++;
            $arrData['cl_partners_book_id'] = $cl_partners_book_id;
            $arrData['cl_pricelist_id']     = $one['cl_pricelist_id'];
            $arrData['cl_currencies_id']    = $one->cl_store_docs['cl_currencies_id'];
            $arrData['vat']                 = $one['vat'];

            if ($one['price_e2'] > 0) {
                $arrData['price']           = $one['price_e2'] / ($one['s_out'] == 0 ? 1 : $one['s_out']);
                $arrData['price_vat']       = $one['price_e2_vat'] / ($one['s_out'] == 0 ? 1 : $one['s_out']);
            }else{
                $arrData['price']           = $one->cl_pricelist['price'];
                $arrData['price_vat']       = $one->cl_pricelist['price_vat'];
            }

            $tmpData = $this->findAll()->where('cl_partners_book_id = ? AND cl_pricelist_id = ?', $cl_partners_book_id, $one->cl_pricelist_id)->fetch();
            if ($tmpData){

            }else {
                $this->insert($arrData);
                $count++;
            }

        }
        return $count;
    }

    /*** if input array is null, then priceupdate is for all records in cl_pricelist with price_updated = 1
     * 'old_data' => ['price' => $tmpOldData['price'], 'price_vat' => $tmpOldData['price_vat']],
       'new_data' => ['price' => $data['price'], 'price_vat' => $data['price_vat']
       'id' => $data['id']
     * @param $arrData
     * @return void
     */
    public function updatePricelist($arrData = null)
    {
        if (!is_null($arrData)){
            $tmpData = $this->findAll()->where('cl_pricelist_id = ? AND fix_price = 0', $arrData['id']);
            foreach($tmpData as $key => $one){
                if ($one['cl_currencies_id'] != $one->cl_pricelist['cl_currencies_id']){
                    $arrData['new_data']['price']       = $arrData['new_data']['price'] / $one->cl_currencies['fix_rate'];
                    $arrData['new_data']['price_vat']   = $arrData['new_data']['price_vat'] / $one->cl_currencies['fix_rate'];
                }
                $this->update(['id'         => $key,
                               'price'      => $arrData['new_data']['price'],
                               'vat'        => $arrData['new_data']['vat'],
                               'price_vat'  => $arrData['new_data']['price_vat']
                ]);
            }
        }else{
            $tmpData = $this->findAll()->where('cl_pricelist.price_updated = 1 AND fix_price = 0');
            foreach($tmpData as $key => $one){
                if ($one['cl_currencies_id'] != $one->cl_pricelist['cl_currencies_id']){
                    $tmpPrice       = $one->cl_pricelist['price'] / $one->cl_currencies['fix_rate'];
                    $tmpPriceVat    = $one->cl_pricelist['price_vat'] / $one->cl_currencies['fix_rate'];
                }else{
                    $tmpPrice       = $one->cl_pricelist['price'];
                    $tmpPriceVat    = $one->cl_pricelist['price_vat'];
                }
                $this->update(['id'         => $key,
                                'price'      => $tmpPrice,
                                'vat'        => $one->cl_pricelist['vat'],
                                'price_vat'  => $tmpPriceVat
                ]);
            }
            $tmpPriceList = $this->PriceListManager->findAll()->where('price_updated = 1');
            foreach($tmpPriceList as $key => $one){
                $this->PriceListManager->update(['id' => $one['id'], 'price_updated' => 0]);
            }
        }
    }


}

