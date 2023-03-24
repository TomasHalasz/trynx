<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListPartnerGroup management.
 */
class PriceListPartnerGroupManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_partner_group';
	public $PriceListGroupManager;


    /** @var App\Model\StoreMoveManager */
    public $StoreMoveManager;

    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                \App\Model\StoreMoveManager $StoreMoveManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->StoreMoveManager = $StoreMoveManager;
    }

    public function autoFill($cl_partners_book_id) : int
    {
        $count = 0;
        $data = $this->StoreMoveManager->findAll()->select('cl_pricelist.cl_pricelist_group_id')->
                                                    where('cl_store_docs.cl_partners_book_id = ? AND cl_store_docs.doc_type = 1', $cl_partners_book_id)->
                                                    where('cl_store_docs.doc_date <= NOW() AND cl_store_docs.doc_date >= DATE_SUB(NOW(), INTERVAL 2 MONTH)');
        $i = $this->findAll()->where('cl_partners_book_id = ?', $cl_partners_book_id)->max('item_order') + 1;
        foreach($data as $key => $one){
            $arrData = array();
            $arrData['item_order']              = $i++;
            $arrData['cl_partners_book_id']     = $cl_partners_book_id;
            $arrData['cl_pricelist_group_id']   = $one['cl_pricelist_group_id'];

            $tmpData = $this->findAll()->where('cl_partners_book_id = ? AND cl_pricelist_group_id = ?', $cl_partners_book_id, $one->cl_pricelist_group_id)->fetch();
            if ($tmpData){

            }else {
                $this->insert($arrData);
                $count++;
            }

        }
        return $count;
    }


}

