<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Cash management.
 */
class CashManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_cash';

    /** @var Nette\Database\Context */
    public $InvoiceTypesManager;

    /** @var Nette\Database\Context */
    public $NumberSeriesManager;

    /** @var Nette\Database\Context */
    public $PairedDocsManager;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                InvoiceTypesManager $InvoiceTypesManager, NumberSeriesManager $NumberSeriesManager,
                                PairedDocsManager $PairedDocsManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->InvoiceTypesManager = $InvoiceTypesManager;
        $this->NumberSeriesManager = $NumberSeriesManager;
        $this->PairedDocsManager   = $PairedDocsManager;
    }


    public function makeCash($data){
        //if we are making new cl_cash record, first find cl_invoice_types_id and get correct number
        if ($data['cash'] >= 0 ) {
            $cashType = 'cash_in';
            $tmpTypeId = NULL;
            $tmpType = $this->InvoiceTypesManager->findAll()->where('inv_type = ? AND default_type = 1', 5 )->fetch();
            if ($tmpType)
                $tmpTypeId =$tmpType->id;

        }else {
            $cashType = 'cash_out';
            $tmpTypeId = NULL;
            $tmpType = $this->InvoiceTypesManager->findAll()->where('inv_type = ? AND default_type = 1', 6 )->fetch();
            if ($tmpType)
                $tmpTypeId =$tmpType->id;
        }


        if (is_null($data['cl_cash_id'])){

            if (isset($data['cl_number_series_id']) && !is_null($data['cl_number_series_id'])) {
                $nSeries = $this->NumberSeriesManager->getNewNumber($cashType, $data['cl_number_series_id']);
            }else{
                $nSeries = $this->NumberSeriesManager->getNewNumber($cashType);
            }
            //bdump($nSeries);
            //die;
            $data['cl_number_series_id'] = $nSeries['id'];
            $data['cash_number']         = $nSeries['number'];
            $data['cl_invoice_types_id'] = $tmpTypeId;
            unset($data['cl_cash_id']);
            $newRow = $this->insert($data);

            $docKey = NULL;
            $docId  = NULL;
            if (isset($data['cl_sale_id'])) {
                $docKey = 'cl_sale_id';
                $docId  = $data['cl_sale_id'];
            }
            if (isset($data['cl_invoice_id'])){
                $docKey = 'cl_invoice_id';
                $docId  = $data['cl_invoice_id'];
            }
            if (isset($data['cl_delivery_note_id'])){
                $docKey = 'cl_delivery_note_id';
                $docId  = $data['cl_delivery_note_id'];
            }
            if (isset($data['cl_invoice_arrived_id'])){
                $docKey = 'cl_invoice_arrived_id';
                $docId  = $data['cl_invoice_arrived_id'];
            }
            if (isset($data['cl_transport_id'])){
                $docKey = 'cl_transport_id';
                $docId  = $data['cl_transport_id'];
            }
            if (!is_null($docKey) && !is_null($docId)) {
                //create pairedocs record with created cl_store_docs_id
                $this->PairedDocsManager->insertOrUpdate(array('cl_cash_id' => $newRow, $docKey => $docId));
            }

        }else{
            $data['id'] = $data['cl_cash_id'];
            unset($data['cl_cash_id']);
            $this->update($data);
            $newRow = $data['id'] ;
        }

        return $newRow;
    }

	
}

