<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Partners book workers management.
 */
class PartnersBookWorkersManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_partners_book_workers';
    private $assignArr = array(array('keyname' => 'use_cl_invoice', 'text' => 'Faktury vydané'),
                                array('keyname' => 'use_cl_invoice_arrived', 'text' =>  'Faktury přijaté'),
                                array('keyname' => 'use_cl_offer', 'text' =>  'Nabídky'),
                                array('keyname' => 'use_cl_commission', 'text' =>  'Zakázky'),
                                array('keyname' => 'use_cl_order', 'text' =>  'Objednávky'),
                                array('keyname' => 'use_cl_delivery_note', 'text' =>  'Dodací_listy'),
                                array('keyname' => 'use_cl_partners_event', 'text' =>  'Helpdesk'),
                                );

    /** find and return worker for given keyname
     * @param $cl_partners_book_id
     * @param $keyname
     * @return int
     */
    public function getWorker($cl_partners_book_id, $keyname) : int
    {
        $tmpWorkers = $this->findAll()->where('cl_partners_book_id = ? AND '.$keyname.'= 1', $cl_partners_book_id)->
                            select('id')->
                            order('item_order')->
                            limit(1)->fetch();
        if ($tmpWorkers){
            $retId = $tmpWorkers->id;
        }else{
            $retId = NULL;
        }

        return $retId;
    }

	public function getWorkersGrouped($cl_partners_book_id)
    {
        if ($cl_partners_book_id != NULL) {
            $arrWorkers = array();
            $tmpWorkers = $this->findAll()->where('cl_partners_book_id = ? ', $cl_partners_book_id)->
                                            select('*')->
                                            order('use_cl_invoice,use_cl_invoice_arrived,use_cl_offer,use_cl_commission,use_cl_order,
                                                                                            use_cl_delivery_note,use_cl_partners_event');


                $arrUnassigned = array();
                foreach($tmpWorkers as $key => $one)
                {
                    $unassigned = TRUE;
                    foreach($this->assignArr as $oneArr)
                    {
                        if ($one[$oneArr['keyname']] == 1) {
                            $unassigned = FALSE;
                            $arrWorkers[$oneArr['text']][$one->id] =  $one->worker_name;
                            //break;
                        }
                    }
                    //$unassigned = TRUE;
                    if ($unassigned){
                        $arrUnassigned[$one->id] = $one->worker_name;
                    }

                }
            $arrWorkers['Nezařazení'] = $arrUnassigned;
        }else{
            $arrWorkers = array();
        }

        return $arrWorkers;
    }

    public function getWorkersGroupedOld($cl_partners_book_id)
    {
	if ($cl_partners_book_id != NULL)
	{
	    $arrWorkers = array();
	    $tmpInvoice = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_invoice = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpInvoice){
		$arrWorkers['Faktury vydané']   =  $tmpInvoice;
	    }

	    $tmpInvoiceArrived = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_invoice_arrived = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpInvoiceArrived){
		$arrWorkers['Faktury přijaté']  = $tmpInvoiceArrived;
	    }

	    $tmpOffers = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_offer = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpOffers){
		$arrWorkers['Nabídky']	    = $tmpOffers;	
	    }

	    $tmpCommission = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_commission = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpCommission){
		$arrWorkers['Zakázky']	    = $tmpCommission;
	    }

	    $tmpOrders = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_order = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpOrders){
		$arrWorkers['Objednávky']	    = $tmpOrders;
	    }

	    $tmpDelivery = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_delivery_note = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpDelivery){
		$arrWorkers['Dodací_listy']	    = $tmpDelivery;
	    }

	    $tmpDelivery = $this->findAll()->where('cl_partners_book_id = ? AND use_cl_partners_event = 1', $cl_partners_book_id)->fetchPairs('id','worker_name');
	    if ($tmpDelivery){
		$arrWorkers['Helpdesk']	    = $tmpDelivery;
	    }	

	    $tmpUnassigned = $this->findAll()->where('cl_partners_book_id = ?', $cl_partners_book_id)
						->where('use_cl_invoice_arrived = 0 AND use_cl_delivery_note = 0 AND use_cl_order = 0 AND use_cl_commission = 0 AND use_cl_offer = 0 AND use_cl_invoice_arrived = 0 AND use_cl_invoice = 0')
						->fetchPairs('id','worker_name');
	    if ($tmpUnassigned){
		$arrWorkers['Nezařazení']	    = $tmpUnassigned;
	    }
	}else{
	    $arrWorkers = array();
	}
	
	return $arrWorkers;
    }

	
}

