<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class StorePresenter  extends \App\Presenters\BasePresenter {
    
    
    public $newId = NULL;


    
    /** @persistent */
    public $page;
    
    /** @persistent */
    public $filter;     
    
    /** @persistent */
    public $filterColumn;            

    /** @persistent */
    public $filterValue;                

    /** @persistent */
    public $sortKey;        

    /** @persistent */
    public $sortOrder;      

    /**
    * @inject
    * @var \App\Model\StoreDocsManager
    */
    public $StoreDocsManager;    

    
    /**
    * @inject
    * @var \App\Model\DocumentsManager
    */
    public $DocumentsManager;            
            
    
    /**
    * @inject
    * @var \App\Model\StoreMoveManager
    */
    public $StoreMoveManager;        

    /**
    * @inject
    * @var \App\Model\StoreManager
    */
    public $StoreManager;            
    
    /**
    * @inject
    * @var \App\Model\StoreOutManager
    */
    public $StoreOutManager;                
    
    /**
    * @inject
    * @var \App\Model\StorageManager
    */
    public $StorageManager;            
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;            
    
    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;        
    
    /**
    * @inject
    * @var \App\Model\RatesVatManager
    */
    public $RatesVatManager;

    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $PriceListManager;    
    
    /**
    * @inject
    * @var \App\Model\PriceListPartnerManager
    */
    public $PriceListPartnerManager;        

    
    
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
        

   public function actionCreateDoc() {
		/*array( 'cl_company_id', 
		 * 'doc_date', 
		 * 'cl_partners_name', 
		 * 'currency_code', 
		 * 'doc_title', 
		 * 'storage_name', 
		 * 'doc_type' => array('store_in','store_out')
		 */
	   ////do action budeme předávat všechny následující hodnoty včetně api_key, pomocí kterého budeme provádět autentizaci uživatele
	   $dataApi = array('cl_company_id' => 63,
						'doc_date' => new \Nette\Utils\DateTime,
						'cl_partners_name' => '2H C.S. s.r.o.',
						'currency_code' => 'CZK',
						'doc_title' => 'výdej ze skladu',
						'storage_name' => '01',
						'doc_type' => 'store_out');
		$this->StoreDocsManager->ApiCreateDoc($dataApi);
		
		$this->terminate();
   }	


   public function actionGiveOut() {
	   //array('identification','quantity','price','storage_name')
	   $dataApi = array('cl_company_id' => 63,
						'identification' => '01-000001',
						'quantity' => 1,
						'price' => NULL,
						'storage_name' => '01',
						'description' => 'poznamka k polozce',
						'cl_store_docs_id' => 60);
		$this->StoreManager->ApiGiveOut($dataApi);
		$this->terminate();
   }



    
}
