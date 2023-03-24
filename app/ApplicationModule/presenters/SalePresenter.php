<?php
namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Caching;

use Nette\Application\UI\Form;
use Exception;
use Nette\Utils\Json;
use Tracy\Debugger;

class SalePresenter extends \App\Presenters\BaseAppPresenter
{
    public $id,$myReadOnly,$numberSeries,$defValues,$id_print, $branchNumberSeriesId, $mainTableName;
    
    /**
    * @inject
    * @var \App\Model\SaleManager
    */
    public $DataManager;    	

    /**
    * @inject
    * @var \App\Model\SaleItemsManager
    */
    public $SaleItemsManager;    	
    
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
    * @var \App\Model\PricesManager
    */
    public $PricesManager;       

    /**
    * @inject
    * @var \App\Model\SaleShortsManager
    */
    public $SaleShortsManager;        

    /**
    * @inject
    * @var \App\Model\StoreDocsManager
    */
    public $StoreDocsManager;         

    /**
    * @inject
    * @var \App\Model\StoreManager
    */
    public $StoreManager;         

    /**
    * @inject
    * @var \App\Model\PairedDocsManager
    */
    public $PairedDocsManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchUsersManager
     */
    public $CompanyBranchUsersManager;

    /**
     * @inject
     * @var \MainServices\EETService
     */
    public $EETService;

    /**
     * @inject
     * @var \App\Model\EETManager
     */
    public $EETManager;
	
	/**
	 * @inject
	 * @var \App\Model\HeadersFootersManager
	 */
	public $HeadersFootersManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Sale']);
    }
	
	public function actionDefault()
    {
        $this->formName = "Prodejna";
        $this->mainTableName = "cl_sale";


        //13.05.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        if ($tmpBranch){
            $tmpBranchDiscount          = $tmpBranch->discount;
            $tmpBranchNumberSeries      = $tmpBranch->cl_number_series_id;
            $tmpBranchPartnersBook      = $tmpBranch->cl_partners_book_id;
            $tmpBranchPartnersStorage   = $tmpBranch->cl_storage_id;
        }else{
            $tmpBranchDiscount          = 0;
            $tmpBranchNumberSeries      = NULL;
            $tmpBranchPartnersBook      = $this->settings->cl_partners_book_id_sale;
            $tmpBranchPartnersStorage   = $this->settings->cl_storage_id_sale;
        }
        $this->branchNumberSeriesId = $tmpBranchNumberSeries;

        //find main sale record for current user
        if (!($tmpSale = $this->DataManager->findAll()->where('cl_users_id = ? AND sale_number IS NULL', $this->user->getId())->fetch()))
        {

            $defValues =    array(  'inv_date'              => new \Nette\Utils\DateTime,
                                    'vat_date'              => new \Nette\Utils\DateTime,
                                    'cl_currencies_id'      =>  $this->settings->cl_currencies_id ,
                                    'currency_rate'         => $this->settings->cl_currencies->fix_rate,
                                    'cl_payment_types_id'   => $this->settings->cl_payment_types_id_sale,
                                    'price_e_type'          => $this->settings->price_e_type,
                                    'cl_partners_book_id'   => $tmpBranchPartnersBook,
                                    'cl_company_branch_id'  => $tmpBranchId,
                                    'discount'              => $tmpBranchDiscount,
                                    'vat_active'            => $this->settings->platce_dph,
                                    'cl_storage_id'         => $tmpBranchPartnersStorage,
                                    'cl_users_id'           => $this->user->getId());

            if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?','sale',1)->fetch())
            {
                $defValues['cl_status_id'] = $nStatus->id;
            }
        //bdump($defValues);
            //if there is no sale record, create one
            $tmpSale = $this->DataManager->insert($defValues);
        }
        $this->id = $tmpSale->id;
        $this->numberSeries = array('use' => 'sale', 'table_key' => 'cl_number_series_id', 'table_number' => 'sale_number');



    }
    
    public function renderDefault($id_print = NULL)
    {
        $this->template->modal          = FALSE;
        $this->myReadOnly               = FALSE;
        $this->template->myReadOnly     = FALSE;
        $this->template->data           = $this->DataManager->find($this->id);
        $this->template->dataItems      = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $this->id);
        $this->template->settings       = $this->settings;
        $this->template->arrInvoiceVat  = $this->getArrInvoiceVat();

        $this->id_print = $id_print;

            //if ($id_print !== NULL)
        //{
            //2. generate PDF
            //$this->generatePDF($id_print);
        //}
    }

    protected function getArrInvoiceVat(){
		$tmpData = $this->DataManager->find($this->id);
		$arrRatesVatValid = $this->RatesVatManager->findAllValid($tmpData->vat_date);
		$arrInvoiceVat = new \Nette\Utils\ArrayHash;
		foreach($arrRatesVatValid as $key => $one)
		{
			if ($tmpData->vat1 == $one['rates'])
			{
				$baseValue      = $tmpData->price_base1;
				$vatValue       =  $tmpData->price_vat1;
				$basePayedValue =  $tmpData->base_payed1;
				$vatPayedValue  =  $tmpData->vat_payed1;
			}elseif ($tmpData->vat2 == $one['rates']){
				$baseValue      = $tmpData->price_base2;
				$vatValue       =  $tmpData->price_vat2;
				$basePayedValue =  $tmpData->base_payed2;		
				$vatPayedValue  =  $tmpData->vat_payed2;
			}elseif ($tmpData->vat3 == $one['rates']){
				$baseValue      = $tmpData->price_base3;
				$vatValue       =  $tmpData->price_vat3;
				$basePayedValue =  $tmpData->base_payed3;		
				$vatPayedValue  =  $tmpData->vat_payed3;
			}  else {
				$baseValue      = 0;
				$vatValue       =  0;
				$basePayedValue =  0;		
				$vatPayedValue  = 0;
			}

			$arrInvoiceVat[$one['rates']] = array('base' => $baseValue, 
							  'vat' => $vatValue, 
							  'payed' => $basePayedValue,
							  'vatpayed' => $vatPayedValue);	    
		}
	return ($arrInvoiceVat);
	
    }    
    
    
    public function createComponentSalelistgrid()
     {
        $tmpParentData = $this->DataManager->find($this->id);
            //dump($this->settings->platce_dph);
            //die;
        if ( $tmpParentData->price_e_type == 1)
        {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        }else{
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }
        $priceRO = !$this->isAllowed($this->name, 'edit', 'price');
        //bdump($priceRO, 'priceRO');
        if ($this->settings->platce_dph == 1)
                            $arrData = array(
                                'cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => 'TRUE'),
                            'item_label' => array($this->translator->translate('Popis'),'format' => 'text','size' => 15, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                          'cl_pricelist_id' => array($this->translator->translate('cenik'), 'format' => 'hidden'),
                          'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 7,'decplaces' => $this->settings->des_mj),
                          'units' => array('','format' => 'text','size' => 5, 'readonly' => $priceRO),
                          'price_e' => array($tmpProdej,'format' => "number",'size' => 10, 'decplaces' => $this->settings->des_cena, 'readonly' => $priceRO),
                          'price_e_type' => array($this->translator->translate('Typ_prodejni_ceny'),'format' => "hidden"),
                          'discount' => array($this->translator->translate('Sleva_%'),'format' => "number",'size' => 6),
                          'price_e2' => array($this->translator->translate('Celkem'),'format' => "number",'size' => 8, 'readonly' => $priceRO),
                          'vat'      => array($this->translator->translate('DPH_%'),'format' => "integer", 'size' => 5, 'roCondition' => 'TRUE'),
                          'price_e2_vat' => array($this->translator->translate('Celkem_s_DPH'),'format' => "number",'size' => 8, 'readonly' => $priceRO));
        else
                            $arrData = array(
                                'cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => 'TRUE'),
                                'item_label' => array($this->translator->translate('Popis'),'format' => 'text','size' => 15, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                          'cl_pricelist_id' => array($this->translator->translate('cenik'), 'format' => 'hidden'),
                          'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 7,'decplaces' => $this->settings->des_mj),
                          'units' => array('','format' => 'text','size' => 5, 'readonly' => $priceRO),
                          'price_e' => array($this->translator->translate('Prodej'),'format' => "number",'size' => 10,'decplaces' => $this->settings->des_cena, 'readonly' => $priceRO),
                          'price_e_type' => array($this->translator->translate('Typ_prodejni_ceny'),'format' => "hidden"),
                          'discount' => array($this->translator->translate('Sleva %'),'format' => "number",'size' => 6),
                          'price_e2' => array($this->translator->translate('Celkem'),'format' => "number",'size' => 8, 'readonly' => $priceRO));




        $control = new Controls\ListgridControl(
                        $this->translator,
                        $this->SaleItemsManager,
                        $arrData,
                        array(),
                        $this->id,
                        array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
                        $this->DataManager,
                        $this->PriceListManager,
                        $this->PriceListPartnerManager,
                        FALSE, //add empty row
                        array(
                         ), //custom links
                        FALSE, //$movableRow
                        NULL, //  $orderColumn
                        FALSE, //$selectMode
                        array('search' => array('cl_price_list.ean','cl_price_list.identification'),
                            'saleShortsManager' => $this->SaleShortsManager), //enable quick search and insert
                        '12px' //fontsize
                        );

        $control->disableSelect2Focus();
        $control->onChange[] = function ()
            {
            $this->updateSum();

            };
        return $control;


     }           
    
   public function UpdateSum()
    {
		$this->DataManager->updateSaleSum($this->id);
		
		$this->redrawControl('baselistArea');
		$this->redrawControl('bscArea');
		$this->redrawControl('bsc-child');

		$this['salelistgrid']->redrawControl('editLines');
		//$this['sumOnDocs']->redrawControl();		
		
		$this->redrawControl('recapitulation');
    }       
    
    /*
     * modify data before addline
     */
    public function beforeAddLine($data)
    {
      //  $this->flashMessage($this->name);
        if ($this->isAllowed($this->name,'write')) {
            $data['price_e_type'] = $this->settings->price_e_type;
            return ($data);
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
            return FALSE;
        }
    }
    
    public function  ListGridInsert($sourceData)
    {
        if ($this->isAllowed($this->name,'write')) {
            $arrPrice = new \Nette\Utils\ArrayHash;
            //if (isset($sourceData['cl_pricelist_id']))
            if (array_key_exists('cl_pricelist_id', $sourceData->toArray())) {
                $arrPrice['id'] = $sourceData['cl_pricelist_id'];
                $sourcePriceData = $this->PriceListManager->find($sourceData->cl_pricelist_id);
            } else {
                $arrPrice['id'] = $sourceData['id'];
                $sourcePriceData = $this->PriceListManager->find($sourceData->id);
            }
            $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];
            ///04.09.2017 - find price if there are defined prices_groups
            $tmpData = $this->DataManager->find($this->id);
            //if ( isset($tmpData['cl_partners_book_id'])
            //&&
            if (!is_null($tmpData['cl_partners_book_id']) && $tmpPrice = $this->PricesManager->getPrice($tmpData->cl_partners_book,
                    $arrPrice['id'],
                    $tmpData->cl_currencies_id,
                    $tmpData->cl_storage_id)) {
                $arrPrice['price'] = $tmpPrice['price'];
                $arrPrice['price_vat'] = $tmpPrice['price_vat'];
                $arrPrice['discount'] = $tmpPrice['discount'];
                $arrPrice['price_e2'] = $tmpPrice['price_e2'];
                $arrPrice['price_e2_vat'] = $tmpPrice['price_e2_vat'];
                $arrPrice['cl_currencies_id'] = $tmpPrice['cl_currencies_id'];
            } else {
                $arrPrice['price'] = $sourceData->price;
                $arrPrice['price_vat'] = $sourceData->price_vat;
                $arrPrice['discount'] = 0;
                $arrPrice['price_e2'] = $sourceData->price;
                $arrPrice['price_e2_vat'] = $sourceData->price_vat;
               // $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
            }
            $arrPrice['vat'] = $sourceData->vat;



            $arrData = new \Nette\Utils\ArrayHash;
            $arrData[$this->DataManager->tableName . '_id'] = $this->id;

            $arrData['cl_pricelist_id'] = $sourcePriceData->id;
            $arrData['item_order'] = $this->SaleItemsManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;

            $arrData['item_label'] = $sourcePriceData->item_label;
            $arrData['quantity'] = 1;

            $arrData['units'] = $sourcePriceData->unit;

            $arrData['vat'] = $arrPrice['vat'];

            $arrData['price_e_type'] = $this->settings->price_e_type;
            if ($arrData['price_e_type'] == 1) {
                $arrData['price_e'] = $arrPrice['price_vat'];
            } else {
                $arrData['price_e'] = $arrPrice['price'];
            }

            $arrData['discount'] = $arrPrice['discount'];
            $arrData['price_e2'] = $arrPrice['price_e2'];
            $arrData['price_e2_vat'] = $arrPrice['price_e2_vat'];


            //prepocet kurzem
            //potrebujeme kurz ceníkove polozky a kurz zakazky
            if ($sourceData->cl_currencies_id != NULL)
                $ratePriceList = $sourceData->cl_currencies->fix_rate;
            else
                $ratePriceList = 1;

            if ($tmpOrder = $this->DataManager->find($this->id))
                $rateOrder = $tmpOrder->currency_rate;
            else
                $rateOrder = 1;


            //$arrData['price_s'] = $arrData['price_s'] * $ratePriceList / $rateOrder;
            if ( $arrPrice['cl_currencies_id'] != $tmpOrder['cl_currencies_id'] ) {
                $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
                $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
                $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;
            }


            $row = $this->SaleItemsManager->insert($arrData);
            $this->updateSum($this->id, $this);
            return ($row);
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
            return FALSE;
        }
	    
    }
    
  /*  public function handlePrintSave($print_id)
    {
        $tmpData = $this->DataManager->find($print_id);
        if ($tmpData->sale_number === NULL)
        {
            //1. generate document number
            $this->getNumberSeries();
            $tmpData->update($this->defValues);
        }
        //18.05.2019 - update inv_date, vat_Date
        $dtmNow = new \Nette\Utils\DateTime;
        $tmpData->update(array('inv_date' => $dtmNow, 'vat_date' => $dtmNow));
        //12.06.2019 - update sum for final update of cash record
        $this->DataManager->updateSaleSum($this->id);

        $tmpData = $this->DataManager->find($print_id);

        //2. give out from store
        $this->giveOut($print_id);

        //3. send to EET
        $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);

        //bdump($dataForSign);

        if ($dataForSign && $dataForSign['eet_ghost'] == 0 && !is_null($tmpData->cl_payment_types_id) && ($tmpData->cl_payment_types->payment_type == 1 || $tmpData->cl_payment_types->eet_send == 1)) {
            try {
                $tmpNew = array();

                $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                //bdump($arrRet);

                $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                //$row = $this->EETManager->insert($tmpNew);

                $tmpData->update(array('cl_eet_id' => $tmpId));

            //\FilipSedivy\EET\Exceptions\Certificate
            } catch (\Exception $e) {
                $this->flashMessage('Chyba certifikátu. Zkontrolujte nahraný certifikát a heslo', 'danger');
                $this->flashMessage($e->getMessage(), 'danger');
            }
        }elseif ($dataForSign && $dataForSign['eet_ghost'] == 1) {
            $arrRet = array();
            $arrRet['id']           = $tmpData['id'];
            $arrRet['UUID']         = "";
            $arrRet['FIK']          = "2ef5347e-0165-4927-bb8e-047d9572d720-02";
            $arrRet['BKP']          = "00d4c3b5-d8b9127e-bb0d9aed-8d9bf025-3bfe7a86";
            $arrRet['PKP']          = "cAISIzXwZiqS8oBgpuU/JKE2EJCwR1xdlbgF9PKGG3MefyAk+FFsBZOIdYI2wZ/Xhuwn9vBEvv9/ewo4Il6BduQSNvUJYqJaj5JLTctbnG+FfLNme+c9A4xUcgNnwvIM0D6FbfsKVUdCHkSzyGWZ4sZFTzpKAfq636jurHOLVosQfo5h1pJbR5YONL5hOwPTslL0uWwKohmfwzJj31gdi/s2Qpd59mYpstL1dTWqWaf79wR7jzyLiyWRLlSb2z1mk3pB/GnLw63vmvk0zcRYBKgZ/XA6NwhsMDFr8j9o+wKJOauBwz+wgFk2KrOW5HHp3nvDla59pG5Z/YkjEZUWSQ==";
            $arrRet['Error']        = "";
            $arrRet['Warnings']     = "";
            $arrRet['eet_id']       = $dataForSign['eet_id_provoz'];
            $arrRet['eet_idpokl']   = $dataForSign['eet_id_poklad'];
            $arrRet['dat_trzby']    = new \DateTime();
            $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
            //$row = $this->EETManager->insert($tmpNew);

            $tmpData->update(array('cl_eet_id' => $tmpId));

        }

        //4. generate PDF
        $this->generatePDF($print_id);
        $this->redrawControl('content');

        //$this->redirect('default', array('id_print' => null));

    }*/

    public function handlePrintSave($print_id)
    {
        $tmpData = $this->DataManager->find($print_id);
        if ($tmpData->sale_number === NULL)
        {
            //1. generate document number
            $this->getNumberSeries();
            $tmpData->update($this->defValues);
        }
        //18.05.2019 - update inv_date, vat_Date
        $dtmNow = new \Nette\Utils\DateTime;
        $tmpData->update(array('inv_date' => $dtmNow, 'vat_date' => $dtmNow));
        //12.06.2019 - update sum for final update of cash record
        $this->DataManager->updateSaleSum($this->id);

        $tmpData = $this->DataManager->find($print_id);

        //2. give out from store
        $this->giveOut($print_id);

        //3. send to EET
        $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);

        //bdump($dataForSign);

        if ($dataForSign && $dataForSign['eet_ghost'] == 0 && !is_null($tmpData->cl_payment_types_id) && ($tmpData->cl_payment_types->payment_type == 1 || $tmpData->cl_payment_types->eet_send == 1)) {
            try {
                $tmpNew = array();

                $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                //bdump($arrRet);

                $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                //$row = $this->EETManager->insert($tmpNew);

                $tmpData->update(array('cl_eet_id' => $tmpId));

                //\FilipSedivy\EET\Exceptions\Certificate
            } catch (\Exception $e) {
                $this->flashMessage($this->translator->translate('Chyba_certifikátu_Zkontrolujte_nahraný_certifikát_a_heslo'), 'danger');
                $this->flashMessage($e->getMessage(), 'danger');
            }
        }elseif ($dataForSign && $dataForSign['eet_ghost'] == 1) {
            $arrRet = array();
            $arrRet['id']           = $tmpData['cl_eet_id'];
            $arrRet['UUID']         = "";
            $arrRet['FIK']          = "2ef5347e-0165-4927-bb8e-047d9572d720-02";
            $arrRet['BKP']          = "00d4c3b5-d8b9127e-bb0d9aed-8d9bf025-3bfe7a86";
            $arrRet['PKP']          = "cAISIzXwZiqS8oBgpuU/JKE2EJCwR1xdlbgF9PKGG3MefyAk+FFsBZOIdYI2wZ/Xhuwn9vBEvv9/ewo4Il6BduQSNvUJYqJaj5JLTctbnG+FfLNme+c9A4xUcgNnwvIM0D6FbfsKVUdCHkSzyGWZ4sZFTzpKAfq636jurHOLVosQfo5h1pJbR5YONL5hOwPTslL0uWwKohmfwzJj31gdi/s2Qpd59mYpstL1dTWqWaf79wR7jzyLiyWRLlSb2z1mk3pB/GnLw63vmvk0zcRYBKgZ/XA6NwhsMDFr8j9o+wKJOauBwz+wgFk2KrOW5HHp3nvDla59pG5Z/YkjEZUWSQ==";
            $arrRet['Error']        = "";
            $arrRet['Warnings']     =  array();
            $arrRet['eet_id']       = $dataForSign['eet_id_provoz'];
            $arrRet['eet_idpokl']   = $dataForSign['eet_id_poklad'];
            $arrRet['dat_trzby']    = new \DateTime();
            $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
            //$row = $this->EETManager->insert($tmpNew);

            $tmpData->update(array('cl_eet_id' => $tmpId));

        }

        //4. generate PDF
        $this->generatePDF($print_id);
        $this->redrawControl('content');

        //$this->redirect('default', array('id_print' => null));

    }


    public function getNumberSeries($data='')
    {
        if (isset($this->numberSeries['use']))
        {
            //if data is given, we use it for numberseries
            if ($data != '') {
                $nSeries = $this->NumberSeriesManager->getNewNumber($data);
            }else{
                if (is_null($this->branchNumberSeriesId)) {
                    $nSeries = $this->NumberSeriesManager->getNewNumber($this->numberSeries['use']);
                }else{
                    $nSeries = $this->NumberSeriesManager->getNewNumber($this->numberSeries['use'], $this->branchNumberSeriesId);
                }
            }

            $this->defValues[$this->numberSeries['table_key']] = $nSeries['id'];
            $this->defValues[$this->numberSeries['table_number']] = $nSeries['number'];
	
			if (isset($this->HeadersFootersManager) && $hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $nSeries->id))->fetch()){
				$this->defValues['header_txt'] = $hfData['header_txt'];
				$this->defValues['footer_txt'] = $hfData['footer_txt'];
			}
            
            if ($data != '')
                $tmpStatus = $data;
            else
                $tmpStatus = $this->numberSeries['use'];

            if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?',$tmpStatus,1)->fetch())
                $this->defValues['cl_status_id'] = $nStatus->id;
        }else{

        }
        return $data;
    }
    
    public function generatePDF($id_print)
    {
        $data = $this->DataManager->find($id_print);
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        $tmpBranch = $this->CompanyBranchManager->find($data->cl_company_branch_id);
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        if ($tmpBranch){
            $dataBranch = $tmpBranch;
        }else{
            $dataBranch = FALSE;
        }
	
		$this->cache->clean([
			Caching\Cache::TAGS => ['reports']
		]);
	
		$arrData = array('settings' => $this->settings,
                    'branch' => $dataBranch,
                    'RatesVatValid' => $this->RatesVatManager->findAllValid($this->DataManager->find($id_print)->vat_date),
                    'arrInvoiceVat' => $this->getArrInvoiceVat());
        $template = $this->createMyTemplateWS($data, $this->ReportManager->getReport(__DIR__ . '/../templates/Sale/saledoc65.latte'), $arrData);
        $this->pdfCreate($template, $data['sale_number'].$this->translator->translate('prodejka'), TRUE, FALSE);
        //$this->handleSavePDF($id_print, __DIR__ . '/../templates/Sale/saledoc65.latte', array(), FALSE, FALSE);

        $colName = $this->mainTableName . '_id';
        $type = 1; //1 for PDF
        if ($tmpFile = $this->FilesManager->findAll()->where(array($colName => $id_print, 'document_file' => $type))->fetch()) {
            $dataFolder = $this->CompaniesManager->getDataFolder($this->getUser()->getIdentity()->cl_company_id);
            $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
            $fileName = $dataFolder . '/' . $subFolder . '/' . $tmpFile->file_name;
            if (is_file($fileName))
                $this->pdfPreviewData = file_get_contents($fileName);
            else
                $this->pdfPreviewData = NULL;
        }
        $this->redrawControl('pdfPreview');
        $this->showModal('pdfModal');

    }
        
    

    //aditional processing of data from listgrid
    public function DataProcessListGrid($data)
    {
	return $data;
    }
    
    //validating of data from listgrid
    public function DataProcessListGridValidate($data)
    {

        foreach($data as $key => $one) {
            $data[$key] = str_replace(' ', '', $one);
            $data[$key] = str_replace(',', '.', $data[$key]);
        }

        if ($data['quantity'] > 10000 || $data['price_e2'] > 10000)
            $ret = $this->translator->translate('Chybné_množství_nebo_cena');
        else
            $ret = NULL;

	    return $ret;
    }    

        
    //control method to determinate if we can delete
    public function beforeDelete($lineId) {
		$result = TRUE;
		return $result;
    }	        
    
    //aditional action after delete from listgrid
    public function afterDelete($line)
    {
		return TRUE;
    }        
        
    //aditional processing data after save in listgrid
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
	
    }
    
    public function handleQuickSearch($qs)
    {
    /*TODO  29.07.2020 - solve using cl_pricelist_partner_group */

        $partners = $this->DataManager->find($this->id);

        if ( isset($partners->cl_partners_book_id) && $partners->cl_partners_book_id != NULL && $partners->cl_partners_book->pricelist_partner)
        {
            if (strlen($qs)<13 || strlen($qs)>13) {
                $tmpWhere = 'cl_pricelist.not_active = 0 AND (identification LIKE ?)';
                $tmpParam = $qs;
            }else {
                $tmpWhere = 'ean_code LIKE ?';
                $tmpParam = '%'.$qs.'%';
            }
            $sourceData = $this->PriceListPartnerManager->findAll()->where('('.$tmpWhere.') AND cl_pricelist_partner.cl_partners_book_id = ? ', $tmpParam, $partners->cl_partners_book_id)->fetch();
            if (!$sourceData){
                //26.08.2019 - if nothing was found, have to look in opposite way . Not found in identification, look at ean_code.
                if (strlen($qs)<13 || strlen($qs)>13) {
                    $tmpWhere = 'cl_pricelist.not_active = 0 AND (ean_code LIKE ?)';
                    $tmpParam = '%'.$qs.'%';
                }else {
                    $tmpWhere = 'identification LIKE ?';
                    $tmpParam = $qs;
                }
                $sourceData = $this->PriceListPartnerManager->findAll()->where('('.$tmpWhere.') AND cl_pricelist_partner.cl_partners_book_id = ? ', $tmpParam, $partners->cl_partners_book_id)->fetch();
            }
        }else
        {
            if (strlen($qs)<13 || strlen($qs)>13) {
                $tmpWhere = 'cl_pricelist.not_active = 0 AND (identification LIKE ?)';
                $tmpParam = $qs;
            }else {
                $tmpWhere = 'cl_pricelist.not_active = 0 AND (ean_code LIKE ?)';
                $tmpParam = '%'.$qs.'%';
            }
            $sourceData = $this->PriceListManager->findAll()->where($tmpWhere, $tmpParam)->fetch();
            if (!$sourceData){
                //26.08.2019 - if nothing was found, have to look in opposite way . Not found in identification, look at ean_code.
                if (strlen($qs)<13 || strlen($qs)>13) {
                    $tmpWhere = 'cl_pricelist.not_active = 0 AND (ean_code LIKE ?)';
                    $tmpParam = '%'.$qs.'%';
                }else {
                    $tmpWhere = 'cl_pricelist.not_active = 0 AND (identification LIKE ?)';
                    $tmpParam = $qs;
                }
                $sourceData = $this->PriceListManager->findAll()->where($tmpWhere, $tmpParam)->fetch();
            }
        }

        if ($sourceData)
        {
            $row = $this->ListGridInsert($sourceData);
            if ($row) {
                $this->flashMessage($this->translator->translate('Nový_záznam_byl_vložen'), 'success');
            }
            $this->redrawControl('flash');
            //$this->editIdLine = $row->id;
            $this->redrawControl('editLines');
            $this->redrawControl('items');
            $this->redrawControl('content');

        }else{
            $this->flashMessage($this->translator->translate('Kód_nebyl_nalezen'), 'success');
            $this->redrawControl('flash');
            $this->redrawControl('content');
        }


    }
    
    private function giveOut($id)
    {

        $docId = NULL;
        //bdump($arrDataItems);
        $tmpStoreDoc = NULL;
        $tmpDataItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $id);

        foreach ($tmpDataItems as $key => $one){

                if (!is_null($one->cl_pricelist_id) && !is_null($one->cl_sale->cl_storage_id)){


                    $tmpData = $this->SaleItemsManager->findAll()->
                                                        where('cl_sale_id = ?', $id);
                    $tmpData->update(array('cl_storage_id' => $one->cl_sale->cl_storage_id));

                    //commission items - give out
                    //1. check if cl_store_docs exists if not, create new one
                    $docId = $this->StoreDocsManager->createStoreDoc(1, $id, $this->DataManager, FALSE);
                    $this->StoreDocsManager->update(array('id' => $id, 'doc_title' => $this->translator->translate('výdej_z_prodejky')));

                    //store doc is created from commission, we need to update cl_invoice_id too
                    $this->StoreDocsManager->update(array('id' => $docId, 'cl_sale_id' => $id));

                    //2. giveout current item
                    $dataId = $this->StoreManager->giveOutItem($docId, $one->id, $this->SaleItemsManager);

                    //create pairedocs record with created cl_store_docs_id
                    $this->PairedDocsManager->insertOrUpdate(array('cl_sale_id' => $id, 'cl_store_docs_id' => $docId));
                }else{
                    $this->flashMessage($this->translator->translate('Není_nastaven_výchozí_sklad_pro_výdej_z_prodejny_Prosím_nastavte_jej'), 'danger');
                }

            }



    }


    /** update data from sale ticket
     * discount, discount_abs
     * @param $data json
     */
    public function handleSaleUpdate($data)
    {
        $arrData = json_decode($data,true);
        if ($arrData['discount'] > 100 || $arrData['discount_abs'] > 25000)
        {
            $this->flashMessage($this->translator->translate('Zadali_jste_příliš_vysokou_slevu'), 'warning');
            $this->redrawControl('contents');
            $this->redrawControl('recapitulation');
        }
        //bdump($arrData);
        if ($arrData['payment_card']){
            //card
            $row = $this->PaymentTypesManager->findOneBy(array('payment_type' => 3));
        }else{
            //cash
            $row = $this->PaymentTypesManager->findOneBy(array('payment_type' => 1));
        }
        if ($row){
            $cl_payment_types_id = $row->id;
        }else{
            //fallback to cash
            $row = $this->PaymentTypesManager->findOneBy(array('payment_type' => 1));
            if ($row){
                $cl_payment_types_id = $row->id;
            }else {
                $cl_payment_types_id = NULL;
            }
        }
        //30.08.2019 - set discount to 0 if previous abs_discount wasn't 0
        if ($tmpData = $this->DataManager->find($this->id))
        {
            if ($tmpData->discount != 0 && $arrData['discount'] == 0)
            {
                $arrData['discount_abs'] = 0;
            }
        }

        //customer have to find by company or partner_code
        if ($arrData['customer'] != '') {
            $row = $this->PartnersManager->findAll()->where('partner_code = ?', $arrData['customer'])->fetch();
            if (!$row) {
                $row = $this->PartnersManager->findAll()->where('company LIKE ?', $arrData['customer'] . '%')->fetch();
            }
            if (!$row) {
                $cl_partners_book_id = NULL;
            } else {
                $cl_partners_book_id = $row->id;
                if ($row->discount > 0) {
                    $arrData['discount'] = $row->discount;
                    $arrData['discount_abs'] = 0;
                }
            }
        }else{
            $cl_partners_book_id = NULL;
        }

        //bdump($cl_payment_types_id);
        $this->DataManager->update(array('id' => $this->id, 'discount_abs' => $arrData['discount_abs'],
                            'discount' => $arrData['discount'], 'cl_payment_types_id' => $cl_payment_types_id,
                            'cash_rec' => $arrData['cash_rec'],
                            'cl_partners_book_id' => $cl_partners_book_id
                          ));
        $this->DataManager->updateSaleSum($this->id);

        $this->redrawControl('contents');
        $this->redrawControl('recapitulation');

	
    }
    
}
    