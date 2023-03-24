<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Tracy\Debugger;
use Exception;

/**
 * Prices management.
 */
class PricesManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_prices';

	/** @var \App\Model\PricesGroupsManager*/
	public $PricesGroupsManager;				

	/** @var \App\Model\StoreMoveManager*/
	public $StoreMoveManager;				

	/** @var \App\Model\PriceListManager*/
	public $PriceListManager;

    /** @var \App\Model\PriceListPartnerManager*/
    public $PriceListPartnerManager;

    /** @var \App\Model\PriceListPartnerGroupManager*/
    public $PriceListPartnerGroupManager;


    /**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					PricesGroupsManager $PricesGroupsManager, StoreMoveManager $StoreMoveManager, PriceListManager $PriceListManager, PriceListPartnerManager $PriceListPartnerManager,
                    PriceListPartnerGroupManager $PriceListPartnerGroupManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->PricesGroupsManager            = $PricesGroupsManager;
	      $this->StoreMoveManager               = $StoreMoveManager;
	      $this->PriceListManager               = $PriceListManager;
          $this->PriceListPartnerManager        = $PriceListPartnerManager;
          $this->PriceListPartnerGroupManager   = $PriceListPartnerGroupManager;
	  }    		
	
	
	public function getPrice($cl_partners_book,  $cl_pricelist_id, $cl_currencies_id, $cl_storage_id = NULL)
	{
        $cl_prices_groups_id = $cl_partners_book->cl_prices_groups_id;
	    $arrDataCustom = ['price' => 0, 'price_vat' => 0, 'cl_currencies_id' => NULL];
        $arrDataGroup = ['price' => 0, 'price_vat' => 0];

        $tmpPriceList = $this->PriceListManager->find($cl_pricelist_id);
        if ($cl_partners_book->pricelist_partner) {
            //29072020 - own pricelist is set, search for individual price in cl_pricelist_partner or correction of price in cl_pricelist_partner_group
            $tmpData = $this->PriceListPartnerManager->findAll()->where('cl_pricelist_id = ? AND cl_partners_book_id = ? AND cl_currencies_id = ?', $cl_pricelist_id, $cl_partners_book->id, $cl_currencies_id)->fetch();
            if ($tmpData){
                $arrDataCustom['price']     = $tmpData->price;
                $arrDataCustom['price_vat'] = $tmpData->price_vat;
                //28.11.2020 - discount by % individual
                if ($tmpData->price_change != 0){
                    $arrDataCustom['price'] = $tmpPriceList->price - ($tmpPriceList->price * ($tmpData->price_change / 100));
                    $arrDataCustom['price_vat'] = $tmpPriceList->price_vat - ($tmpPriceList->price_vat * ($tmpData->price_change / 100));
                }
                $arrDataCustom['cl_currencies_id'] = $tmpData['cl_currencies_id'];
            }

            if (!is_null($tmpPriceList->cl_pricelist_group_id)) {
                $tmpData = $this->PriceListPartnerGroupManager->findAll()->where('cl_pricelist_group_id = ? AND cl_partners_book_id = ?', $tmpPriceList->cl_pricelist_group_id, $cl_partners_book->id)->fetch();
                if ($tmpData) {
                    $arrDataGroup['price'] = $tmpPriceList->price - ($tmpPriceList->price * ($tmpData->price_change / 100));
                    $arrDataGroup['price_vat'] = $tmpPriceList->price_vat - ($tmpPriceList->price_vat * ($tmpData->price_change / 100));

                    //if ($priceS > 0 && $priceS < $arrDataGroup['price'])
                    //28.11.2020 - use surcharge only when is filled
                    if ($tmpData->price_surcharge != 0){
                        $priceS = $tmpPriceList->price_s + ($tmpPriceList->price_s * $tmpData->price_surcharge / 100);
                        $priceSvat = $priceS * (1 + ($tmpPriceList->vat / 100));
                        $arrDataGroup['price']      = $priceS;
                        $arrDataGroup['price_vat']  = $priceSvat;
                    }
                    //bdump($arrDataGroup);
                }
            }
            if ($arrDataGroup['price'] > 0 && ($arrDataGroup['price'] < $arrDataCustom['price'] || $arrDataCustom['price'] == 0))
            {
                $arrDataCustom['price'] = $arrDataGroup['price'];
                $arrDataCustom['price_vat'] = $arrDataGroup['price_vat'];
            }
        }
        //if (count($arrData) == 0) {
            if (!is_null($cl_prices_groups_id)) {
                $tmpDataGroup = $this->PricesGroupsManager->find($cl_prices_groups_id);

                $tmpData = $this->findAll()->where('cl_prices_groups_id = ? AND cl_pricelist_id = ? AND cl_currencies_id = ?', $cl_prices_groups_id, $cl_pricelist_id, $cl_currencies_id)->fetch();

                if ($tmpData) {
                    $arrData['price'] = $tmpData->price;
                    $arrData['price_vat'] = $tmpData->price_vat;
                } else {
                    //
                    //14.02.2020 - pricelist is not set for this item and currencie we use standard price
                    //pricelist is not set for this item and currencie we have to return
                    //standard price from cl_pricelist
                   // if ($tmpPriceList = $this->PriceListManager->find($cl_pricelist_id)) {
                        $arrData['price'] = $tmpPriceList->price;
                        $arrData['price_vat'] = $tmpPriceList->price_vat;
                        //$arrData['price_vat'] = $arrData['price']  * (1 + ($tmpPriceList->vat / 100));
                   // }

                }

                if ($tmpDataGroup->price_surcharge > 0) {
                    //price_surcharge is greater then 0, we work with store price
                    //we must find actual store_move which will be used for sale
                    //
                    if (!is_null($cl_storage_id)) {
                        $arrCond = ['cl_store_move.cl_pricelist_id' => $cl_pricelist_id,
                            'cl_store_move.cl_storage_id' => $cl_storage_id];
                    } else {
                        $arrCond = ['cl_store_move.cl_pricelist_id' => $cl_pricelist_id];
                    }
                    //if ($lastVap = $this->StoreMoveManager->findBy($arrCond)
                    //    ->order('cl_store_docs.doc_date DESC, cl_store_move.id DESC')
                    //    ->limit(1)
                    //    ->fetch()) {
                        //$arrData['price'] = $lastVap->price_vap * (1 + ($tmpDataGroup->price_surcharge / 100));
                        $arrData['price'] = $tmpPriceList->price_s + ($tmpPriceList->price_s * ($tmpDataGroup->price_surcharge / 100));
                        $arrData['price_vat'] = $arrData['price'] * (1 + ($tmpPriceList->vat / 100));

                    //}
                } elseif ($tmpDataGroup->price_change <> 0) {
                    //if ($tmpPriceList = $this->PriceListManager->find($cl_pricelist_id)) {
                        $arrData['price'] = $tmpPriceList->price - ($tmpPriceList->price * ($tmpDataGroup->price_change / 100));
                        $arrData['price_vat'] = $arrData['price'] * (1 + ($tmpPriceList->vat / 100));
                    //}
                }
            } else {
                //08.07.2019 - if there is no cl_prices_groups_id set we find first record without cl_prices_groups_id for right currency
                $tmpData = $this->findAll()->where('cl_prices_groups_id IS NULL AND cl_pricelist_id = ? AND cl_currencies_id = ?', $cl_pricelist_id, $cl_currencies_id)->fetch();
                if ($tmpData) {
                    $arrData['price'] = $tmpData->price;
                    $arrData['price_vat'] = $tmpData->price_vat;
                } else {
                    //08.07.2019 - otherwise we use standard price
                    //pricelist is not set for this item and currencie we have to return
                    //standard price from cl_pricelist
                    //if ($tmpPriceList = $this->PriceListManager->find($cl_pricelist_id)) {
                        $arrData['price'] = $tmpPriceList->price;
                        $arrData['price_vat'] = $tmpPriceList->price_vat;
                        //$arrData['price_vat'] = $arrData['price']  * (1 + ($tmpPriceList->vat / 100));
                   // }
                }

            }
       // }
        bdump($arrData);
        bdump($arrDataCustom);
        if ($arrDataCustom['price'] > 0 && ($arrDataCustom['price'] < $arrData['price'] || $arrData['price'] == 0)) {
            $arrData['price'] = $arrDataCustom['price'];
            $arrData['price_vat'] = $arrDataCustom['price_vat'];
            $arrData['cl_currencies_id'] = $arrDataCustom['cl_currencies_id'];
        }
        bdump($arrData);
        bdump($arrDataCustom);
        if ($tmpPriceList['price_vat'] > 0) {
            $discount = round((1 - $arrData['price_vat'] / $tmpPriceList['price_vat']) * 100, 2);
        }else{
            $discount = 0;
        }
        //bdump($arrData,'pred');
        $arrData['price_e2']        = $arrData['price'];
        $arrData['price_e2_vat']    = $arrData['price_vat'];
        if ((!isset($arrData['cl_currencies_id']) || is_null($arrData['cl_currencies_id'])) || ($arrData['cl_currencies_id'] == $tmpPriceList->cl_currencies_id) ){
            $arrData['price']       = $tmpPriceList->price;
            $arrData['price_vat']   = $tmpPriceList->price_vat;
            $arrData['discount']    = $discount;
        }else{
           // $arrData['price']           = $arrData['price'];
           // $arrData['price_vat']       = $arrData['price_vat'];
            $arrData['discount']    = 0;
        }
        //$arrData['cl_currencies_id'] = $arrData['cl_currencies_id'];
        //bdump($arrData);
	    return $arrData;
	}
	//getPrice($tmpData->cl_prices_groups_id,$arrPrice['id'],$tmpData->cl_currencies_id))
}

