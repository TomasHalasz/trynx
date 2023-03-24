<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * B2B Order management.
 */
class B2BOrderManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_b2b_order';
    /** @var App\Model\StatusManager */
    public $StatusManager;
    /** @var App\Model\CompaniesManager */
    public $CompaniesManager;
    /** @var App\Model\PartnersBranchManager */
    public $PartnersBranchManager;

    /** @var App\Model\PartnersBookWorkersManager */
    public $PartnersBookWorkersManager;

    /** @var App\Model\PartnersManager */
    public $PartnersManager;


    /** @var App\Model\CommissionManager */
    public $CommissionManager;

    /** @var App\Model\CommissionItemsSelManager */
    public $CommissionItemsSelManager;

    /** @var App\Model\NumberSeriesManager */
    public $NumberSeriesManager;

    /** @var App\Model\PairedDocsManager */
    public $PairedDocsManager;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                StatusManager $statusManager,  CompaniesManager $companiesManager,  PartnersBranchManager $partnersBranchManager,
                                PartnersBookWorkersManager $partnersBookWorkersManager, CommissionItemsSelManager $commissionItemsSelManager,
                                CommissionManager $commissionManager,NumberSeriesManager $numberSeriesManager, PairedDocsManager $pairedDocsManager,
                                PartnersManager $partnersManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->StatusManager = $statusManager;
        $this->CompaniesManager = $companiesManager;
        $this->PartnersBranchManager = $partnersBranchManager;
        $this->PartnersBookWorkersManager = $partnersBookWorkersManager;
        $this->CommissionItemsSelManager = $commissionItemsSelManager;
        $this->CommissionManager = $commissionManager;
        $this->NumberSeriesManager = $numberSeriesManager;
        $this->PairedDocsManager = $pairedDocsManager;
        $this->PartnersManager = $partnersManager;
    }


    /** return actual cl_basket for current user */
    public function getBasket( $cl_partners_book_id, $cl_partners_branch_id)
    {
        if (!is_null($cl_partners_branch_id)){
            $ret = $this->findAll()->where('cl_status.s_new = 1 AND cl_partners_book_id = ? AND cl_partners_branch_id = ?', $cl_partners_book_id, $cl_partners_branch_id)->
                                    limit(1)->fetch();
        }else{
            $ret = $this->findAll()->where('cl_status.s_new = 1 AND cl_partners_book_id = ? ', $cl_partners_book_id)->
                                    limit(1)->fetch();
        }

       if (!$ret){
           $now = new Nette\Utils\DateTime();
           $cl_status_id = $this->StatusManager->findAll()->where('s_new = 1 AND status_use = "b2b_order"')->fetch();
           if (!$cl_status_id){
               $cl_status_id = NULL;
           }
           $tmp = $this->StatusManager->findAll()->limit(1)->fetch();
           $cl_currencies_id = NULL;
           if ($tmp){
               $cl_company_id = $tmp->cl_company_id;
               $tmp = $this->CompaniesManager->find($cl_company_id);
               if ($tmp){
                   $cl_currencies_id = $tmp->cl_currencies_id;
               }
           }
           $tmpPartnersBook = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();
           //default branch
           if (is_null( $cl_partners_branch_id)) {
               $arrBranch       = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $tmpPartnersBook->id)->
                                                    order('item_order')->limit(1)->fetch();
               $arrBranchId = NULL;
               if ($arrBranch){
                   $arrBranchId = $arrBranch['id'];
               }
           }else{
               $arrBranchId = $cl_partners_branch_id;
           }


           $this->insert(array('date' => $now,
                                'cl_currencies_id' => $cl_currencies_id,
                                'cl_status_id' => $cl_status_id,
                                'cl_partners_branch_id' => $arrBranchId,
                                'cl_users_id' => $tmpPartnersBook['cl_users_id'],
                                'cl_partners_book_id' => $tmpPartnersBook['id']));
           if (!is_null($cl_partners_branch_id)) {
               $ret = $this->findAll()->where('cl_status.s_new = 1 AND cl_partners_book_id = ? AND cl_partners_branch_id = ?', $cl_partners_book_id, $cl_partners_branch_id)->limit(1)->fetch();
           }else{
               $ret = $this->findAll()->where('cl_status.s_new = 1 AND cl_partners_book_id = ?', $cl_partners_book_id)->limit(1)->fetch();
           }
       }
       return $ret;
    }

    public function setWork($id){
        $cl_status_id = $this->StatusManager->findAll()->where('s_work = 1 AND status_use = "b2b_order"')->fetch();
        if ($cl_status_id){
           $this->update(array('id' => $id, 'cl_status_id' => $cl_status_id->id));
        }

    }

	public function updateBasket($id){
        $tmpData = $this->findAll()->select('SUM(:cl_b2b_order_items.price_e2_vat) AS price_e2_vat,
                                                    SUM(:cl_b2b_order_items.price_e2) AS price_e2, COUNT(:cl_b2b_order_items.id) AS item_count')->
                                where('cl_b2b_order.id = ?', $id)->fetch();
        if ($tmpData){
            $this->update(array('id' => $id,
                                'price_e2_vat' => $tmpData['price_e2_vat'],
                                'price_e2' => $tmpData['price_e2'],
                                'item_count' => $tmpData['item_count']));
        }
    }


    public function sendBasket($id){
        $tmpB2B = $this->findAll()->where('id = ?', $id)->fetch();

        $data = array();
        $data['cl_partners_book_id']            = $tmpB2B->cl_partners_book_id;
        $data['cl_users_id']                    = $tmpB2B->cl_users_id;
        $data['cl_partners_branch_id']          = $tmpB2B->cl_partners_branch_id;
        $data['cl_payment_types_id']            = $tmpB2B->cl_payment_types_id;
        $data['cl_partners_book_workers_id']    = $tmpB2B->cl_partners_book_workers_id;
        $data['cl_currencies_id']               = $tmpB2B->cl_currencies_id;
        if ($tmpB2B->currency_rate == 0)
            $data['currency_rate']              = 1;
        else
            $data['currency_rate']                  = $tmpB2B->currency_rate;

        $data['cm_title']                       = $tmpB2B->description_txt;
        $data['description_txt2']               = 'B2B objednávka';
        $today = new \Nette\Utils\DateTime;
        $data['cm_date']                        = $today;

        $numberSeries = array('use' => 'commission', 'table_key' => 'cl_number_series_id', 'table_number' => 'cm_number');
        $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
        $data[$numberSeries['table_key']]       = $nSeries['id'];
        $data[$numberSeries['table_number']]    = $nSeries['number'];

        $tmpStatus = $numberSeries['use'];
        $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch();
        if ($nStatus) {
            $data['cl_status_id'] = $nStatus->id;
        }

        $commission = $this->CommissionManager->insert($data);
        $this->update(array('id' => $id, 'cl_commission_id' => $commission->id, 'cm_number' => $commission->cm_number));

        $arrDataItems = $tmpB2B->related('cl_b2b_order_items');
        $i = 1;
        foreach ($arrDataItems as $key => $one) {
                $dataItems = $one->toArray();
                unset($dataItems['id']);
                unset($dataItems['cl_b2b_order_id']);
                unset($dataItems['cl_pricelist_macro_id']);
                $dataItems['cl_commission_id'] = $commission->id;
                $dataItems['item_order'] = $i;
                $i++;
                $commissionItem = $this->CommissionItemsSelManager->insert($dataItems);

        }

        $this->CommissionManager->updateSum($commission->id);

        //create pairedocs record
        $this->PairedDocsManager->insertOrUpdate(array('cl_b2b_order_id' => $id, 'cl_commission_id' => $commission->id));



    }

}

