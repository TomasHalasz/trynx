<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListBonds management.
 */
class PriceListBondsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_bonds';
	
	public function getQuantity($pricelist_bonds_id, $amount){
	    $data = $this->findBy(array('id' => $pricelist_bonds_id))->fetch();
	    if ($data){
            //21.01.2018 - there will be recalculation according to units
            $newQuantity = $data->quantity*$amount;
	    	return $newQuantity;
	    }else{
	    	return 0;
	    }
	}
	
	public function getDiscount($pricelist_bonds_id){
	    $data = $this->findBy(['id' => $pricelist_bonds_id])->fetch();
	    if ($data){
            //21.01.2018 - there will be recalculation according to units
            $discount = $data->discount;
            return $discount;
	    }else{
		    return 0;
	    }
	}

    public function getBondData($oneBond, Nette\Database\Table\ActiveRow $tmpItem)
    {
        //bdump(array_key_exists('cl_store_docs_id', $tmpItem->toArray()));
        $newItem = [];
        //bdump($tmpItem);
        //bdump(isset($tmpItem['cl_invoice_items_back_id']));
        bdump($tmpItem->toArray());
        if ((array_key_exists('cl_store_docs_id', $tmpItem->toArray()) && $tmpItem->cl_store_docs['doc_type'] == 0)) {
            $quantityName = 's_in';
            if ($tmpItem->cl_store_docs->cl_company->platce_dph == 1){
                $newItem['price_in']            = $oneBond->cl_pricelist['price_s'];
                $newItem['price_in_vat']        = $oneBond->cl_pricelist['price_s'] * (1 + ($oneBond->cl_pricelist['vat'] / 100));
            }else{
                $newItem['price_in_vat']    = $oneBond->cl_pricelist['price_s'];
            }
            $newItem['price_e2']            = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpItem[$quantityName]);
            $newItem['vat']                 = $oneBond->cl_pricelist->vat;
            $newItem['price_e2_vat']        = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpItem[$quantityName]);

        }elseif (array_key_exists('cl_store_docs_id', $tmpItem->toArray()) && $tmpItem->cl_store_docs['doc_type'] == 1) {
            $quantityName = 's_out';
            $newItem['price_e2']            = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpItem[$quantityName]);
            $newItem['vat']                 = $oneBond->cl_pricelist->vat;
            $newItem['price_e2_vat']        = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpItem[$quantityName]);

        }elseif (array_key_exists('cl_delivery_note_in_id', $tmpItem->toArray()) && !array_key_exists('cl_invoice_items_back_id', $tmpItem->toArray())) {
            $newItem['price_in']            = $oneBond->cl_pricelist['price_s'];
            if ($tmpItem->cl_delivery_note_in->cl_company->platce_dph == 1){
                $newItem['price_in_vat']        = $oneBond->cl_pricelist['price_s'] * (1 + ($oneBond->cl_pricelist['vat'] / 100));
            }else{
                $newItem['price_in_vat']    = $oneBond->cl_pricelist['price_s'];
            }
            $quantityName = 'quantity';
            $newItem['price_e2']            = ($newItem['price_in']  * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpItem[$quantityName]);
            $newItem['vat']                 = $oneBond->cl_pricelist->vat;
            $newItem['price_e2_vat']        = $newItem['price_in']  * ((1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpItem[$quantityName]) * (1 + ($oneBond->cl_pricelist['vat'] / 100)));
            $newItem['price_e_type']        = $tmpItem->price_e_type;
            $newItem['item_label']          = $oneBond->cl_pricelist->item_label;
            $newItem['units']               = $oneBond->cl_pricelist->unit;
            bdump($newItem);
        }else{
            $quantityName = 'quantity';
            $newItem['price_e_type']        = $tmpItem->price_e_type;
            $newItem['item_label']          = $oneBond->cl_pricelist->item_label;
            $newItem['units']               = $oneBond->cl_pricelist->unit;
            $newItem['price_e2']            = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpItem[$quantityName]);
            $newItem['vat']                 = $oneBond->cl_pricelist->vat;
            $newItem['price_e2_vat']        = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpItem[$quantityName]);
        }
        $newItem['item_order']          = $tmpItem->item_order + 1;
        $newItem['cl_pricelist_id']     = $oneBond->cl_pricelist_id;
        $newItem['cl_storage_id']       = $tmpItem->cl_storage_id;
        $newItem[$quantityName]         = $oneBond->quantity * (($oneBond->multiply == 1) ? $tmpItem[$quantityName] : 1 ); //$tmpItem->quantity;

        $newItem['price_s']             = $oneBond->cl_pricelist->price_s;
        $newItem['price_e']             = $oneBond->cl_pricelist->price;
        $newItem['discount']            = $oneBond->discount;

        $newItem['cl_parent_bond_id']   = $tmpItem->id;
        return $newItem;

    }


}

