<?php
namespace App\ApplicationModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;
use Nette\Utils\DateTime;

class HelpdeskBillingPresenter extends \App\Presenters\BaseAppPresenter {

    public $myReadOnly,$txtSearch = NULL;

    /** @persistent */
    public $id;       
	
    public $dateFrom = NULL, $dateTo = NULL;
	
    const
	    DEFAULT_STATE = 'Czech Republic';
	    

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;    	

    /**
    * @inject
    * @var \App\Model\PartnersEventManager
    */
    public $PartnersEventManager;
	
    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;         	

    /**
    * @inject
    * @var \App\Model\InvoiceManager
    */
    public $InvoiceManager;    
    
  
    /**
    * @inject
    * @var \App\Model\InvoiceItemsManager
    */
    public $InvoiceItemsManager;            

    /**
    * @inject
    * @var \App\Model\InvoiceTypesManager
    */
    public $InvoiceTypesManager;                

    /**
    * @inject
    * @var \App\Model\InvoicePaymentsManager
    */
    public $InvoicePaymentsManager;        

    /**
    * @inject
    * @var \App\Model\CommissionItemsManager
    */
    public $CommissionItemsManager;        
    
    /**
    * @inject
    * @var \App\Model\CommissionManager
    */
    public $CommissionManager;


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Helpdesk']);
    }

    public function actionDefault()
    {

    }
    
    public function renderDefault() {
				
	//dump($this->txtSearch);
	$this->template->modal = FALSE;
	if ($this->dateFrom == NULL)
	{
	    $this->dateFrom = new \Nette\Utils\DateTime;
	    $dtmm = $this->dateFrom->format('m');
	    $dtmy = $this->dateFrom->format('Y');
 	    $this->dateFrom->fromParts($dtmy, $dtmm, '01');
	}
	if ($this->dateTo == NULL)
	{
	    $this->dateTo = new \Nette\Utils\DateTime;
	}		
		
	$this['search']->setValues(array('dateFrom' => $this->dateFrom->format('d.m.Y'), 'dateTo' => $this->dateTo->format('d.m.Y')));
	
	$this->template->partnersEvent = $this->PartnersEventManager->findAll()->
					where('(cl_invoice_id IS NULL AND cl_commission_id IS NULL) AND cl_partners_event_id IS NULL')->
					where('finished = 1')->
					order('cl_partners_book_id,date_rcv');
	$this->template->settings = $this->settings;
	$this->template->userId = $this->user->id;
	$arrSums =  $this->PartnersEventManager->findAll()->
					where('(cl_invoice_id IS NULL AND cl_commission_id IS NULL) AND cl_partners_event_id IS NULL')->
					where('finished = 1')->
					select('(SUM(work_time/60)*hour_tax) AS celkem, SUM(work_time/60) AS celkem_hours, cl_partners_book_id')->
					group('cl_partners_book_id');
	
	$this->template->arrSums = $arrSums->fetchPairs('cl_partners_book_id','celkem');
	$this->template->arrSumsHours = $arrSums->fetchPairs('cl_partners_book_id','celkem_hours');	
		
    }
	
    public function renderEdit($id = NULL) {
				
	$this->id = $id;
		

    }	
	

    protected function createComponentSearch($name)
    {	
	    $form = new Form($this, $name);
	    //$form->setMethod('POST');
           $form->addText('dateFrom', $this->translator->translate('Období_od:'), 20, 20)
		    	->setAttribute('class','form-control input-sm datepicker')
			->setAttribute('placeholder',$this->translator->translate('Datum_od'));
           
	   $form->addText('dateTo', $this->translator->translate('Období_do:'), 20, 20)
		    	->setAttribute('class','form-control input-sm datepicker')
			->setAttribute('placeholder','Datum do');
				
	   
	    $form->addSubmit('send', $this->translator->translate('Nastavit_období'))->setAttribute('class','btn btn-sm btn-primary');
		
	    $form->addSubmit('back', $this->translator->translate('Zrušit'))
		    ->setAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'searchBack');	    	    
		
	    $form->onSuccess[] = array($this, 'searchSubmitted');
	    return $form;
    }

    public function searchBack()
    {	    
		$this->txtSearch = "";
		$this->redirect('this');
    }		

    public function searchSubmitted(Form $form)
    {
	    $data=$form->values;	
	    if ($form['send']->isSubmittedBy())
	    {    
		$this->dateFrom = DateTime::from(strtotime($data['dateFrom'])); 	
		$this->dateTo = DateTime::from(strtotime($data['dateTo'])); 	
		//dump($this->dateFrom);
		//$this->dateFrom = $data['dateFrom']; 	
		//$this->dateTo = $data['dateTo'];
		
	    }
	    //$this->redirect(this');
	}	
		    
	
    public function handleSelectOne($data)
    {
	$data2 = json_decode($data);
	//$retVal=$this->PartnersEventManager->findAll()->where(array('id' => $data2))->update(array('selected' => 1));
	//dump($data2);
	//$this->sendResponse(new Nette\Utils\Json\JsonResponse(['retVal' => $retVal]));
	//$this->terminate();
	if ($data2[0]->state == TRUE)
	{
	    $arrUpdate = array('selected' => $this->user->id);
	}else{
	    $arrUpdate = array('selected' => NULL);	 
	}
	
	$retVal = $this->PartnersEventManager->findAll()->where(array('id' => $data2[0]->id))->update($arrUpdate);
	$this->redrawControl('datatable');
	
    }
    
    public function handleSelectAll($data)
    {
	$data2 = json_decode($data);
	foreach ($data2 as $one)
	{
	    if ($one->state == TRUE)
	    {
		$arrUpdate = array('selected' => $this->user->id);
	    }else{
		$arrUpdate = array('selected' => NULL);	 
	    }

	    $retVal = $this->PartnersEventManager->findAll()->where(array('id' => $one->id))->update($arrUpdate);
	    
	}
	$this->redrawControl('datatable');
	
    }    
	
    public function handleCreateInvoice()
    {
		//if ($tmpData = $this->DataManager->find($this->id))
		//{
			if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?',1)->fetch())
			{
				$tmpInvoiceType = $tmpInvoiceType->id;
			} else {
				$tmpInvoiceType = NULL;
			}
			//default values for invoice
			$defDueDate = new \Nette\Utils\DateTime;
			$arrInvoice = array();
			$arrInvoice['cl_currencies_id'] =  $this->settings->cl_currencies_id;
			$arrInvoice['currency_rate'] = $this->settings->cl_currencies->fix_rate;

			//find partner if are selected events only for one partner
			$tmpPartners = $this->PartnersEventManager->findAll()->
					where(array('selected' => $this->user->id))->
					select('cl_partners_book_id,SUM(1) AS pocet')->
					group('cl_partners_book_id');
			if ($tmpPartners->count() > 0)
			{

				if ($tmpPartners->count() == 1)
				{
				    $tmpPartner = $tmpPartners->fetch();
				}else{
				    $tmpPartner = NULL;
				}

				if (!is_null($tmpPartner))
				{
				    //there are events only for one partner, we can set partner, due date, payment
				    $arrInvoice['cl_partners_book_id'] = $tmpPartner->cl_partners_book_id;
				    //settings for concrete partner
				    if ($tmpPartner->cl_partners_book->due_date > 0)
				    {
					    $strModify = '+'.$tmpPartner->cl_partners_book->due_date.' day';
				    } else {
					    $strModify = '+'.$this->settings->due_date.' day';
				    }

				    if (isset($tmpPartner->cl_partners_book->cl_payment_types_id))
				    {
					    $clPayment = $tmpPartner->cl_partners_book->cl_payment_types_id;
					    $spec_symb = $tmpPartner->cl_partners_book->spec_symb;
				    } else {
					    $clPayment = $this->settings->cl_payment_types_id;
					    $spec_symb = "";	    
				    }	    

				    $arrInvoice['spec_symb'] = $spec_symb;
				}else{
				    $clPayment = NULL;
				    $strModify = '+'.$this->settings->due_date.' day';
				}
				$arrInvoice['cl_payment_types_id'] = $clPayment;			
				$arrInvoice['due_date'] = $defDueDate->modify($strModify);

				//$arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
				//$arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
				//$arrInvoice['currency_rate'] = $tmpData->currency_rate;	    
				//$arrInvoice['cl_commission_id'] = $tmpData->id;						
				//$arrInvoice['price_e_type'] = $tmpData->price_e_type;
				$arrInvoice['price_e_type'] = $this->settings->price_e_type;
				$arrInvoice['inv_date'] = new \Nette\Utils\DateTime;
				$arrInvoice['vat_date'] = new \Nette\Utils\DateTime;

				$arrInvoice['konst_symb'] = $this->settings->konst_symb;
				$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
				//$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;

				$arrInvoice['header_show'] = $this->settings->header_show;
				$arrInvoice['footer_show'] = $this->settings->footer_show;
				$arrInvoice['header_txt'] = $this->settings->header_txt;
				$arrInvoice['footer_txt'] = $this->settings->footer_txt;	    



				//create invoice
				//new number
				$nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
				$arrInvoice['inv_number'] = $nSeries['number'];
				$arrInvoice['cl_number_series_id'] = $nSeries['id'];
				$arrInvoice['var_symb'] = preg_replace('/\D/', '', $arrInvoice['inv_number']);				
				$tmpStatus = 'invoice';		
				if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
					$arrInvoice['cl_status_id'] = $nStatus->id;		

				$row = $this->InvoiceManager->insert($arrInvoice);
				//$this->PartnersEventManager->update(array('id' => $this->id, 'cl_invoice_id' => $row->id));
				$invoiceId = $row->id;


				//now content of invoice
				//at first, delete old content
				//next insert new content
				$this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId))->delete();
				$tmpItems = $this->PartnersEventManager->findAll()->
						where(array('selected' => $this->user->id, 'finished' => 1));

				$lastOrder = 1;
				foreach($tmpItems as $one)
				{
					$newItem = array();
					$newItem['cl_invoice_id'] = $invoiceId;
					$newItem['cl_partners_event_id'] = $one->id;
					$newItem['item_order'] = $lastOrder;

					$newItem['item_label'] = "[" . $one->event_number . "] " . $one->work_label;
					if (is_null($tmpPartner))
					{
					    $newItem['item_label'] = "[" . $one->event_number . "] " . $one->cl_partners_book->company . " / " . $one->work_label;					    
					}else{
					    $newItem['item_label'] = "[" . $one->event_number . "] " . $one->work_label;
					}					
					$newItem['quantity'] = round($one->work_time/60,2);		
					$newItem['units'] = 'hod.';		
					$newItem['price_s'] = 0;
					$newItem['discount'] = 0;					
					if ($this->settings->platce_dph == 1)
					{
					    //default VAT from settings
					    $newItem['vat'] = $this->settings->hd_vat;						
					}else{
					    $newItem['vat'] = 0;
					}
					//VAT payer and unit price with VAT
					if ($this->settings->price_e_type == 0 || $this->settings->platce_dph == 0)  
					{
					    $newItem['price_e'] = $one->hour_tax;		
					}else{
					    $calcVat = round(($one->hour_tax) * ($newItem['vat']/100), 2);
					    $newItem['price_e'] = $one->hour_tax + $calcVat;
					}
					$newItem['price_e2'] = round($one->work_time/60,2) * $one->hour_tax;				
					$newItem['price_e2_vat'] = $newItem['price_e2'] + round((($one->work_time/60) * $one->hour_tax) * ($newItem['vat']/100), 2);

					$newItem['price_e_type'] = $this->settings->price_e_type;
					$this->InvoiceItemsManager->insert($newItem);
					$lastOrder++ ;

					$this->PartnersEventManager->update(array('id' => $one->id, 'cl_invoice_id' => $invoiceId, 'selected' => NULL));				
				}

				//InvoicePresenter::updateSum($invoiceId,$this);
				$this->InvoiceManager->updateInvoiceSum($invoiceId);

				$this->flashMessage($this->translator->translate('Změny_byly_uloženy,_faktura_byla_vytvořena.'), 'success');
				//$this->redirect('Invoice:edit', $invoiceId);
				$this->redirect('Invoice:edit', array('id' => $invoiceId));
			//}	
			}else{
				$this->flashMessage($this->translator->translate('Nebyly_vybrány_žádné_události_k_fakturaci.'), 'danger');
				$this->redirect('this');			    
			}
    }
	
    
    
    
    public function handleCreateCommission()
    {

		//if ($tmpData = $this->DataManager->find($this->id))
		//{

			//default values for commission
			$defDate = new \Nette\Utils\DateTime;
			$arrCommission = array();
			$arrCommission['cl_currencies_id'] =  $this->settings->cl_currencies_id;
			$arrCommission['currency_rate'] = $this->settings->cl_currencies->fix_rate;
			$arrCommission['vat'] = $this->settings->hd_vat;

			//find partner if are selected events only for one partner
			$tmpPartners = $this->PartnersEventManager->findAll()->
					where(array('selected' => $this->user->id))->
					select('cl_partners_book_id,SUM(1) AS pocet')->
					group('cl_partners_book_id');
			if ($tmpPartners->count() > 0)
			{

				if ($tmpPartners->count() == 1)
				{
				    $tmpPartner = $tmpPartners->fetch();
				}else{
				    $tmpPartner = NULL;
				}

				if (!is_null($tmpPartner))
				{
				    //there are events only for one partner, we can set partner, due date, payment
				    $arrCommission['cl_partners_book_id'] = $tmpPartner->cl_partners_book_id;
				    //settings for concrete partner
				    if ($tmpPartner->cl_partners_book->due_date > 0)
				    {
					    $strModify = '+'.$tmpPartner->cl_partners_book->due_date.' day';
				    } else {
					    $strModify = '+'.$this->settings->due_date.' day';
				    }

				}else{
				}

				$arrCommission['price_e_type'] = $this->settings->price_e_type;
				$arrCommission['cm_date'] = new \Nette\Utils\DateTime;

				$arrCommission['header_show'] = $this->settings->header_show_cm;
				$arrCommission['header_txt'] = $this->settings->header_txt_cm;

				//create commission
				//new number
				$nSeries = $this->NumberSeriesManager->getNewNumber('commission');
				$arrCommission['cm_number'] = $nSeries['number'];
				$arrCommission['cl_number_series_id'] = $nSeries['id'];
				$tmpStatus = 'commission';		
				if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
				{
					$arrCommission['cl_status_id'] = $nStatus->id;		
				}

				$row = $this->CommissionManager->insert($arrCommission);
				$commissionId = $row->id;

				//now content of commission
				//at first, delete old content
				//next insert new content
				$this->CommissionItemsManager->findBy(array('cl_commission_id' => $commissionId))->delete();
				$tmpItems = $this->PartnersEventManager->findAll()->
						where(array('selected' => $this->user->id, 'finished' => 1));

				$lastOrder = 1;
				foreach($tmpItems as $one)
				{
					$newItem = array();
					$newItem['cl_commission_id'] = $commissionId;
					$newItem['cl_partners_event_id'] = $one->id;
					$newItem['item_order'] = $lastOrder;

					//$newItem['item_label'] = "[" . $one->event_number . "] " . $one->work_label;
					if (is_null($tmpPartner))
					{
					    $newItem['work_label'] = "[" . $one->event_number . "] " . $one->cl_partners_book->company . " / " . $one->work_label;					    
					}else{
					    $newItem['work_label'] = "[" . $one->event_number . "] " . $one->work_label;
					}
					$newItem['work_time'] = round($one->work_time/60,2);		
					$newItem['work_rate'] = $one->hour_tax;		
					$newItem['work_date_s'] = $one->date_rcv;		
					$newItem['work_time_s'] = date_format($one->date_rcv,'H:i');
					$newItem['work_date_e'] = $one->date_rcv->modify('+'.$one->work_time.' minute');
					$newItem['work_time_e'] = date_format($newItem['work_date_e'],'H:i');					
					$newItem['cl_users_id'] = $one->cl_users_id;
					$this->CommissionWorkManager->insert($newItem);
					$lastOrder++ ;

					$this->PartnersEventManager->update(array('id' => $one->id, 'cl_commission_id' => $commissionId, 'selected' => NULL));				
				}

				$this->CommissionManager->updateSum($commissionId);

				$this->flashMessage($this->translator->translate('Změny_byly_uloženy,_zakázka_byla_vytvořena.'), 'success');
				$this->redirect('Commission:edit', array('id' => $commissionId));
			//}	
			}else{
				$this->flashMessage($this->translator->translate('Nebyly_vybrány_žádné_události_k_vytvoření_zakázky.'), 'danger');
				$this->redirect('this');			    
			}	
	
	
    }    
    
	
}
