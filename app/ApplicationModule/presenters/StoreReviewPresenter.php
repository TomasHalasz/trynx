<?php

namespace App\ApplicationModule\Presenters;

use App\Presenters;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Tracy\Debugger;
use Nette\Utils\DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;



class StoreReviewPresenter extends \App\Presenters\BaseAppPresenter {

    public $public_token = NULL;
    public $storage_price_in = 0,  $storage_price_out = 0, $storage_price_out2 = 0;

    /** @persistent */
    public $searchTxt;

    /** @persistent */
    public $showMinimum;

    /** @persistent */
    public $showNotActive;

    /** @persistent */
    public $storageId;

    public $priceShow;

    public $itemId = NULL;

    private $storagesBranch;

    const
	    DEFAULT_STATE = 'Czech Republic';


    /**
    * @inject
    * @var \App\Model\StoreDocsManager
    */
    public $StoreDocsManager;


	/**
	 * @inject
	 * @var \App\Model\PriceListGroupManager
	 */
	public $PriceListGroupManager;

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

    /**
    * @inject
    * @var \App\Model\OrderManager
    */
    public $OrderManager;

	/**
	 * @inject
	 * @var \App\Model\StoragePlacesManager
	 */
	public $StoragePlacesManager;

	/**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\InvoiceItemsBackManager
     */
    public $invoiceItemsBackManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteItemsBackManager
     */
    public $deliveryNoteItemsBackManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.StoreReview']);
    }


	protected function createComponentChangeStoragePlace() {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);

		$control = new ChangeStoragePlaceControl($this->StoreMoveManager, $this->StoragePlacesManager, $this->itemId,
                                                    $this->translator);

		$control->onChange[] = function ($item_id)
		{
			//$this->afterChangePlace($item_id);
			//$this['storeReview']->redrawControl('storePlaceShow');

			$tmpData = $this->StoreMoveManager->find($item_id);
			$this['storeReview']['storePlaceShow']->showDetail($tmpData->cl_pricelist_id, $tmpData->cl_storage_id);
		};

		return $control;
	}

    protected function createComponentStoreReview() {
        return new StorereviewControl($this->translatorMain,
            $this->translator,$this->StoreManager, $this->StoragePlacesManager,  $this->StoreMoveManager, $this->session, $this->PriceListManager, $this->StorageManager, $this->StoreOutManager);
    }



    public function actionDefault($public_token)
    {
        $this->formName = $this->translator->translate("Přehled_skladů");
		$this->public_token = $public_token;

		//$section = $this->session->getSection('storereview_data');
		//$section->arrData['cl_storage_id'] = NULL;
		//$section->arrData['search_txt'] = NULL;
    }



    public function renderDefault($newopen = FALSE, $page = 1, $searchTxt, $showMinimum, $storageId, $showNotActive = FALSE) {

        $this->searchTxt = $searchTxt;
        $this->showMinimum = $showMinimum;
        $this->showNotActive = $showNotActive;
        $this->storageId = $storageId;

        $this->priceShow = $this->isAllowed($this->name, 'edit', 'price');

		$this->template->public_token = $this->public_token;
		$this->template->modal = FALSE;
        $this->template->showMinimum = $this->showMinimum;
        $this->template->showNotActive = $this->showNotActive;
        //$this->template->exp_on = $this->settings->exp_on;

		$this->template->storages = $this->StorageManager->findAll()->fetchPairs('id', 'name');




		if (!$this->getUser()->isLoggedIn()) {
			//if $this->public_token is null, redirect to login
			if (is_null($this->public_token))
			{
				$this->redirect(':Login:Homepage:default', array('backlink' => $this->storeRequest()));
			}
			$this->template->logged = FALSE;
			//04.06.2016 - public access
			if (!is_null($this->public_token))
			{
				if ($tmpStorage = $this->StorageManager->findAllTotal()->where('public_token = ?', $this->public_token)->fetch())
				{
					$this->storageId = $tmpStorage->id;
					$this->template->storage = $this->StorageManager->findAllTotal()->where('id = ?', $this->storageId)->order('name');
					$this->template->oneStorage = $this->StorageManager->findAllTotal()->where('id = ?', $this->storageId);
				}
			}

		}else{
			$this->template->logged = TRUE;
			//04.06.2016 - normal run for logged user

            //17.07.2019 - if user has set allowed company branch, we show only allowed storages
            $arrBranches = json_decode( $this->user->getIdentity()->company_branches, TRUE);
            //bdump($arrBranches);
            if (count($arrBranches) > 0)
            {
               //$tmpBranches = $this->CompanyBranchManager->findAll()->where('id IN ?', $arrBranches);
               $tmpStorages = $this->StorageManager->findAll()->
                                where(':cl_company_branch.id IN ?', $arrBranches)->fetchPairs('id');
            }else{
               $tmpStorages = NULL;
            }
            $this->storagesBranch = $tmpStorages;

            if (!is_null($tmpStorages))
            {
                $this->template->storage = $this->StorageManager->findAll()->where('cl_storage_id IS NULL AND id IN ?', $tmpStorages)->order('name');
            }else {
                $this->template->storage = $this->StorageManager->findAll()->where('cl_storage_id IS NULL')->order('name');
            }
            if ($this->storageId == NULL){
                if ($tmpStorage = $this->template->storage->fetch())
                    $this->storageId = $tmpStorage->id;
                else
                    $this->storageId = NULL;
            }
			$this->template->oneStorage = $this->StorageManager->findAll()->where('id = ?', $this->storageId);
		}

		//$section = $this->session->getSection('storereview_data');
        //if (isset($this->data['cl_storage_id']))
		    //$this->storageId = $section->arrData['cl_storage_id'];
        //else
          //  $this->storageId = NULL;

        //($newopen) ? $section->arrData['search_txt'] = "" : FALSE;
        //$this->searchTxt = $section->arrData['search_txt'];



		if ($this->storageId == "")
        {
            if ($tmpStorage = $this->template->storage->fetch())
                $this->storageId = $tmpStorage->id;
            else
                $this->storageId = NULL;
        }
		$this->template->searchTxt = $this->searchTxt;
		$this->template->storageId = $this->storageId;

		$this->report = array( 1 => array(  'reportLatte' => __DIR__.'/../templates/StoreReview/pricelistReportLimits.latte',
                                            'reportName' => $this->translator->translate('Stavy_skladu')),
                                2 => array( 'reportLatte' => __DIR__.'/../templates/StoreReview/stockTakingReport.latte',
                                            'reportName' => $this->translator->translate('Inventura')),
                                3 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/storeSupplierReport.latte',
                                            'reportName' => $this->translator->translate('Obrat_dodavatelů')),
                                4 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/partnersTurnoverReport.latte',
                                            'reportName' => $this->translator->translate('Obrat_obchodních_partnerů_z_faktur')),
                                5 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/partnersTurnoverReport2.latte',
                                                'reportName' => $this->translator->translate('Obrat_obchodních_partnerů_-_celkem')),
                                6 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/pricelistTurnoverReport.latte',
                                            'reportName' => $this->translator->translate('Obrat_na_skladových_kartách')),
                                7 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/storeMovementReport.latte',
                                            'reportName' => $this->translator->translate('Pohyby_na_skladových_kartách')),
								8 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/storesCheckReport.latte',
											'reportName' => $this->translator->translate('Kontrola_stavu_zásob')),
								9 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/storeWaitingToOrderReport.latte',
                                            'reportName' => $this->translator->translate('Položky_k_objednání')),
                                10 => array( 'reportLatte' => __DIR__ . '/../templates/StoreReview/salesReport.latte',
                                    'reportName' => $this->translator->translate('Prodeje_za_období'))
                                );

		$this->template->report = $this->report;
		$this->template->reportModalShow = $this->reportModalShow;
		$this->template->rptIndex = $this->rptIndex;

		$this['searchStore']->setValues(array('searchTxt' => $this->searchTxt, 'storageId' => $this->storageId));
    }

	public function handleChangeStorage($storageId) {
		$this->storageId = $storageId;
		$this['searchStore']->setValues(array( 'storageId' => $this->storageId));
		//$section = $this->session->getSection('storereview_data');
		//$section->arrData['cl_storage_id'] = $storageId;
		//bdump($section->arrData, 'changeStorage');
		//$section->arrData['search_txt'] = $searchTxt;


		$this->redrawControl('storageList');
		$this->redrawControl('searchSnippet');
	}




	public function handleReport($index = 0)
	{
		$this->rptIndex = $index;
		$this->reportModalShow = TRUE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
	}


	protected function createComponentReportPricelistLimits($name)
    {
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);

		$form->addCheckbox('quantity_min', $this->translator->translate('Jen_stavy_pod_minimem'));
		$form->addCheckbox('quantity_req', $this->translator->translate('Jen_stavy_pod_požadovaným_množstvím'));
        if (!is_null( $this->storagesBranch )) {
            $tmpArrStorages = $this->StorageManager->findAll()
                ->select('id, CONCAT(name," ",description) AS name')
                ->where('id IN ?', $this->storagesBranch)
                ->order('name')->fetchPairs('id', 'name');
        }else {
            $tmpArrStorages = $this->StorageManager->findAll()->select('id, CONCAT(name," ",description) AS name')->order('name')->fetchPairs('id', 'name');
        }
		$form->addMultiSelect('cl_storage_id',$this->translator->translate('Sklady'), $tmpArrStorages)
				->setHtmlAttribute('multiple','multiple')
				->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_sklad'));

		$form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportPricelistLimits');
		$form->onSuccess[] = array($this, 'ReportPricelistLimitsSubmitted');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackReportPricelistLimits()
    {
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
    }

    public function ReportPricelistLimitsSubmitted(Form $form)
    {
		$data=$form->values;
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save']->isSubmittedBy())
		{
			if ($data['quantity_min'] == 1)
			{
				$tmpPodm = 'SUM(cl_store.quantity) <= cl_pricelist_limits.quantity_min AND ((cl_pricelist_limits.quantity_min - SUM(cl_store.quantity)) > 0 ) AND cl_store.cl_pricelist_limits_id = cl_pricelist_limits.id';
				 //
				//$tmpPodm = '(cl_store.quantity) <= cl_store.quantity_min AND (cl_store.quantity_min - (cl_store.quantity) > 0 )';
			}
			elseif ($data['quantity_req'] == 1){
				$tmpPodm = 'SUM(cl_store.quantity) <= cl_pricelist_limits.quantity_req AND (cl_pricelist_limits.quantity_req - SUM(cl_store.quantity) > 0 )';
				//
				// AND cl_store.quantity_req > 0
			} else {
				$tmpPodm = "";
			}


			if (count($data['cl_storage_id']) == 0)
			{

				$dataReport = $this->StoreManager->findAll()->
							select('cl_store.*,cl_pricelist.*,cl_storage.*, cl_pricelist_limits.*, cl_company.des_mj, cl_company.des_cena,SUM(cl_store.quantity) AS quantity_storage, cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req')->
                            group('cl_store.id')->
							order('cl_pricelist.identification');
                //group('cl_store.cl_pricelist_limits_id')->
//select('cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req')->
			}else
			{
				$dataReport = $this->StoreManager->findAll()->
							select('cl_store.*,cl_pricelist.*,cl_storage.*, cl_pricelist_limits.*, cl_company.des_mj, cl_company.des_cena, SUM(cl_store.quantity) AS quantity_storage, cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req')->
							where('cl_store.cl_storage_id IN ? OR cl_storage.cl_storage_id IN ?', $data['cl_storage_id'], $data['cl_storage_id'])->
							group('cl_store.id')->
							order('cl_pricelist.identification');
				//							select('cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req')->
			}
			if ($tmpPodm != '' )
			{
				$dataReport = $dataReport->having($tmpPodm);
			}

			    $dataOther = array();//$this->CommissionTaskManager->find($itemId);
			    $dataSettings = $data;
			    //$dataOther['dataSettingsPartners']   = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
			    $dataOther['dataSettingsStorage']	= $this->StorageManager->findAll()->where(array('id' =>$data['cl_storage_id']))->order('name');

				//bdump($dataReport);
			    $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportPricelistLimit.latte', $dataOther, $dataSettings ,'Stavy skladu');
			    //bdump($dataReport->fetchAll());
			    $this->pdfCreate($template, $this->translator->translate('Stavy_skladu'));
				//bdump($dataReport->fetchAll());



		}

	}

    protected function createComponentReportStockTaking($name)
    {
	    $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);

	    $form->addCheckbox('print_zero', 'Zpracovat i nulové stavy');

	    $now = new \Nette\Utils\DateTime;
	    $form->addText('st_date', $this->translator->translate('Datum_inventury'), 20, 20)
		    ->setDefaultValue($now->format('d.m.Y'))
		    ->setHtmlAttribute('class','form-control input-sm datepicker')
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_inventury'));

	    if (!is_null( $this->storagesBranch ))
        {
            $tmpArrStorages = $this->StorageManager->findAll()
                                                    ->select('id, CONCAT(name," ",description) AS name')
                                                    ->where('id IN ?', $this->storagesBranch)
                                                    ->order('name')->fetchPairs('id', 'name');
        }else {
            $tmpArrStorages = $this->StorageManager->findAll()->select('id, CONCAT(name," ",description) AS name')->order('name')->fetchPairs('id', 'name');
        }
	   // $form->addMultiSelect('cl_storage_id',$this->translator->translate('Sklady'), $tmpArrStorages)
		//	    ->setHtmlAttribute('multiple','multiple')
         //       ->setRequired('Sklad musí být vybrán')
		//	    ->setHtmlAttribute('placeholder','Vyberte sklad');

        $form->addSelect('cl_storage_id',$this->translator->translate('Sklady'), $tmpArrStorages)
            ->setRequired('Sklad musí být vybrán')
            ->setHtmlAttribute('placeholder','Vyberte sklad');

	    $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_csv', $this->translator->translate('CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_xls', $this->translator->translate('XLS'))->setHtmlAttribute('class','btn btn-sm btn-primary');

	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportStockTaking');
	    $form->onSuccess[] = array($this, 'ReportStockTakingSubmitted');
	    //$form->getElementPrototype()->target = '_blank';
	    return $form;
    }

    public function stepBackReportStockTaking()
    {
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
    }

    public function ReportStockTakingSubmitted(Form $form)
    {
		$data=$form->values;
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy()  || $form['save_csv']->isSubmittedBy()  || $form['save_xls']->isSubmittedBy())
		{

            $tmpPodm = [];
			$data['st_date'] = date('Y-m-d',strtotime($data['st_date']));
			//14.06.2017 - prepare data for stocktaking. Calculate end sum to cl_store.st_total a cl_store.st_price

			if (count($data['cl_storage_id']) == 0)
			{
				$dataReport = $this->StoreManager->findAll()->
                            select('cl_pricelist.id, cl_pricelist.identification, cl_pricelist.item_label, cl_pricelist.unit, cl_store.cl_storage_id')->
							where($tmpPodm)->
                            group('cl_pricelist.id')->
							order('cl_storage.name,cl_pricelist.identification');

			}else
			{
				$dataReport = $this->StoreManager->findAll()->
                            select('cl_pricelist.id, cl_pricelist.identification, cl_pricelist.item_label, cl_pricelist.unit, cl_store.cl_storage_id')->
							where($tmpPodm)->
							where('cl_store.cl_storage_id IN ? OR cl_storage.cl_storage_id IN ?',$data['cl_storage_id'],$data['cl_storage_id'])->
                            group('cl_pricelist.id')->
							order('cl_storage.name,cl_pricelist.identification');

					//nette 2.4
					//whereOr(array('cl_storage.cl_storage_id' => $data['cl_storage_id']))->
			}

			$arrResult = [];
            //session_write_close();
            //$counter = 0;
            //$counterMax = count($dataReport);
			foreach($dataReport as $one)
			{
                //$this->UserManager->setProgressBar($counter, $counterMax, $this->user->getId(), 'Příprava inventury');
			    //14.06.2017 - found last outgoing for st_date and on this outgoing is s_total
/*			    if ($tmpTotal = $this->StoreMoveManager->findAll()->where('cl_store_id = ? AND cl_store_docs.doc_date <= ?', $one->id, $data['st_date'])
                                        ->select('SUM(cl_store_move.s_in - cl_store_move.s_out) AS s_total, SUM((cl_store_move.s_in*cl_store_move.price_s) - (cl_store_move.s_out*cl_store_move.price_s)) AS price_s')
                                        ->fetch())*/
               /* $tmpTotal = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND cl_store_docs.doc_date <= ?', $one->id, $data['st_date'])
                                                ->select('SUM(cl_store_move.s_in - cl_store_move.s_out) AS s_total,
                                                        IF(SUM(cl_store_move.s_in - cl_store_move.s_out) >= 0, SUM((cl_store_move.s_in*cl_store_move.price_s) - (cl_store_move.s_out*cl_store_move.price_s)), 0) AS price_s');*/
                $tmpTotal = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND cl_store_docs.doc_date <= ?', $one->id, $data['st_date'])
                                        ->select('SUM(cl_store_move.s_in) AS s_in, SUM(cl_store_move.s_in*cl_store_move.price_s) AS price_s');
                $tmpItems = $this->StoreMoveManager->findAll()->select('cl_store_move.id AS id')->where('cl_pricelist_id = ? AND cl_store_docs.doc_date <= ?', $one->id, $data['st_date'])
                                        ->where('cl_store_docs.doc_type = 0')
                                        ->select('cl_store_move.id')->fetchPairs('id', 'id');

                if (count($data['cl_storage_id']) == 0) {
                    $tmpTotal = $tmpTotal->fetch();
                }else{
                    $tmpTotal = $tmpTotal->where('cl_store_move.cl_storage_id IN ?', $data['cl_storage_id'])->fetch();
                }
                if ($tmpTotal)
                {

                    $tmpSout = $this->StoreOutManager->findAll()->where('cl_store_out.cl_store_move_in_id IN (?)', $tmpItems)
                                    ->where('cl_store_move.cl_store_docs.doc_date <= ?', $data['st_date'])
                                    ->select('SUM(cl_store_out.s_out) AS s_out, SUM(cl_store_out.s_out * cl_store_move.price_s) AS price_s');
                    //                                    ->select('SUM(cl_store_out.s_out) AS s_out, SUM(cl_store_out.s_out * cl_store_out.price_s) AS price_s');

                    if (count($data['cl_storage_id']) == 0) {
                        $tmpSout = $tmpSout->fetch();
                    }else {
                        $tmpSout = $tmpSout->where('cl_store_move.cl_storage_id IN ?', $data['cl_storage_id'])->fetch();
                    }

                    if ($tmpSout){
                        $total = $tmpTotal['s_in'] - $tmpSout['s_out'];
                        $price = ($tmpTotal['price_s'] - $tmpSout['price_s']) ;
                    }else{
                        $total = $tmpTotal['s_in'];
                        $price = $tmpTotal['price_s'];
                    }

                    //$total = $tmpTotal['s_total'];
				    if ($total != 0)
                        $price = $price / $total;
				    else
                        $price = $price;

			    }else{
				    $total = 0;
                }



                //if ($tmpTotal = $this->StoreMoveManager->findAll()->where('cl_store_id = ? AND cl_store_docs.doc_date <= ?', $one->id, $data['st_date'])
                  //                                                          ->select('SUM(cl_Store_move.s_in) AS s_in'))
                //{
                  //  //$total = $tmpTotal->s_total;
                    //$total = $tmpTotal->s_in;
                //}

			    //14.06.2017 - found last income for st_date and on this income is price_s and price_vap
			    if ($tmpPrice = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND s_in > 0 AND cl_store_docs.doc_date <= ?', $one->id, $data['st_date'])->
							    order('cl_store_docs.doc_date DESC, cl_store_move.id DESC')->
							    limit(1)->fetch())
			    {
                    if ($one->cl_storage->price_method == 0) //FiFo
                    {
                        //$price = $tmpPrice->price_s;
                    } elseif ($one->cl_storage->price_method == 1) //VAP
                    {
                        $price = $tmpPrice->price_vap;
                    }
			    }else{
				    $price = 0;
			    }
			    //$one->update(array('st_total' => $total, 'st_price' => $price, 'st_date' => $data['st_date']));
                $tmpArr = ['identification'   => $one->identification,
                                'item_label'       => $one->item_label,
                                'unit'             => $one->unit,
                                'cl_storage_id'    => $one->cl_storage_id,
                                'cl_storage_name'  => $one->cl_storage->name,
                                'cl_storage_description'  => $one->cl_storage->description,
                                'cl_storage_price_method' => $one->cl_storage->price_method,
                                'st_total'         => $total,
                                'st_price'         => round($price, $this->settings->des_cena),
                                'st_date'          => $data['st_date']];
                if ($data['print_zero'] == 1)
                {
                    //$tmpPodm = array();
                    $arrResult[$one->id] = $tmpArr;
                }elseif ($total != 0){
                    //$tmpPodm = 'cl_store.st_total != 0';
                    $arrResult[$one->id] = $tmpArr;
                }


              //  $counter++;
			}

			$dataOther = [];
			$dataSettings = $data;
			$tmpTitle = $this->translator->translate('Inventura_skladu');
			//$dataOther['dataSettingsPartners']   = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
            $dataOther['des_mj'] = $this->settings->des_mj;
            $dataOther['des_cena'] = $this->settings->des_cena;
			$dataOther['dataSettingsStorage']	= $this->StorageManager->findAll()->where(['id' =>$data['cl_storage_id']])->order('name');
			//$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportStockTaking.latte', $dataOther, $dataSettings , $tmpTitle );
            $template = $this->createMyTemplateWS($arrResult, __DIR__ . '/../templates/StoreReview/ReportStockTaking.latte', $dataOther, $dataSettings , $tmpTitle );
            $filename = $this->translator->translate("Inventura_skladu");
            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, $tmpTitle);
            }elseif ($form['save_csv']->isSubmittedBy())
            {
                if ( count($arrResult)>0)
                {
                    $this->sendResponse(new \CsvResponse\NCsvResponse($arrResult, $filename."-" .date('Ymd-Hi').".csv",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }elseif($form['save_xls']->isSubmittedBy()) {
                $this->sendResponse(new \XlsResponse\NXlsResponse($arrResult, $filename . "-" . date('Ymd-Hi') . ".xls", true));
            }elseif ($form['save_pdf']->isSubmittedBy())
                $this->pdfCreate($template, $tmpTitle, FALSE, TRUE );

	}

	}


	public function handleResetSearch(){
        $this->searchTxt = "";
        $this->showMinimum = FALSE;
        $this->redrawControl('storageSelect');
        $this->redrawControl('storageList');
        $this->redrawControl('searchSnippet');
        $this->redrawControl('content');
    }


    public function handleShowMinimum(){
        $this->showMinimum = TRUE;
        $this->redrawControl('storageSelect');
        $this->redrawControl('storageList');
        $this->redrawControl('searchSnippet');
        $this->redrawControl('content');
    }

    public function handleShowNotActive(){
        $this->showNotActive = TRUE;
        $this->redrawControl('storageSelect');
        $this->redrawControl('storageList');
        $this->redrawControl('searchSnippet');
        $this->redrawControl('content');
    }

    public function handleShowActive(){
        $this->showNotActive = FALSE;
        $this->redrawControl('storageSelect');
        $this->redrawControl('storageList');
        $this->redrawControl('searchSnippet');
        $this->redrawControl('content');
    }


    protected function createComponentSearchStore($name)
    {
	    $form = new Form($this, $name);
	    $form->setMethod('GET');
	    $form->addHidden('storageId', $this->storageId );
	    $form->addText('searchTxt', $this->translator->translate('Hledaná_položka'))
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Hledaná_položka'));
	    $form->addSubmit('send', $this->translator->translate('Hledat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('minimum', $this->translator->translate('Minimum'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('reset', 'X')
		    ->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->onSuccess[] = array($this, 'SearchFormSubmitted');
	    return $form;
    }



    public function SearchFormSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $this->searchTxt = $data->searchTxt;
            $this->storageId = $data->storageId;

        }elseif ($form['minimum']->isSubmittedBy())
        {
            if (!empty($data->searchTxt)) {
                $this->searchTxt = $data->searchTxt;
            }else{
                $this->searchTxt = "";
            }
            $this->storageId = $data->storageId;
            $this->showMinimum = TRUE;

        }elseif ($form['reset']->isSubmittedBy())
        {
            $this->searchTxt = "";
            $this->storageId = $data->storageId;
            $this->showMinimum = FALSE;
        }
		//$section = $this->session->getSection('storereview_data');
		//$section->arrData['cl_storage_id'] = $this->storageId;
		//$section->arrData['search_txt'] = $this->searchTxt;
		//$section->arrData['showMinimum'] = $this->showMinimum;
        //bdump($this->searchTxt);
        $this->redrawControl('storageSelect');
        $this->redrawControl('storageList');
        $this->redrawControl('searchSnippet');
        $this->redrawControl('content');
    }


    public function handleCreateOrderPeriodModalWindow()
    {
        //bdump('ted');
        //$this->createDocShow = TRUE;
        $this->showModal('createOrderPeriodModal');
        $this->redrawControl('createOrder');
        $this->redrawControl('contents');
    }

  protected function createComponentOrderPeriodForm($name)
    {
	    $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);


	    $now = new \Nette\Utils\DateTime;

        $tmpArr = json_decode($this->settings->order_period_last, true);
        if (!is_null($tmpArr))
        {
            foreach($tmpArr as $key => $one)
            {
                $date_from = DateTime::from($one[0]);
                $date_to = DateTime::from($one[1]);
                //bdump($date_from);
            }
            //bdump($date_from);
            //bdump($date_to);
            $datePeriod =  ($date_from->diff($date_to));
            //bdump($datePeriod);
                    $date_from_new  = $date_to->add(date_interval_create_from_date_string('1 days'));
            $date_to_new    = $date_from_new->modifyClone();
            $date_to_new->add($datePeriod);
            //bdump($date_from_new);
            //bdump($date_to_new);
        }else{
            $date_from_new  = DateTime::from('01.'.$now->format('m.Y'));
            $date_to_new    = $now;

        }

	    $form->addText('date_from', $this->translator->translate('Prodeje_od'), 0, 16)
		    ->setDefaultValue($date_from_new->format('d.m.Y'))
		    ->setHtmlAttribute('class', 'form-control input-sm datepicker')
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_od'));

	    $form->addText('date_to', $this->translator->translate('Prodeje_do'), 0, 16)
		    ->setDefaultValue($date_to_new->format('d.m.Y'))
		    ->setHtmlAttribute('class', 'form-control input-sm datepicker')
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_do'));

        if (!is_null( $this->storagesBranch )) {
            $tmpArrStorages = $this->StorageManager->findAll()
                            ->select('id, CONCAT(name," ",description) AS name')
                            ->where('id IN ? AND auto_order = 0', $this->storagesBranch)
                            ->order('name')->fetchPairs('id', 'name');
        }else {
            $tmpArrStorages = $this->StorageManager->findAll()->select('id, CONCAT(name," ",description) AS name')
                                ->where('auto_order = 0')
                                ->order('name')->fetchPairs('id', 'name');
        }
	    $form->addMultiSelect('cl_storage_id',$this->translator->translate('Sklady'), $tmpArrStorages)
                ->setRequired($this->translator->translate('Alespoň_jeden_sklad_musí_být_vybrán'))
			    ->setHtmlAttribute('multiple','multiple')
			    ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_sklad'));

	    $tmpArrPartners = $this->PartnersManager->findAll()->
                                                        select('cl_partners_book.id, cl_partners_book.company')->
                                                        where('supplier = 1')->
                                                        order('company')->fetchPairs('id','company');

	    $form->addMultiSelect('cl_partners_book_id', $this->translator->translate('Dodavatelé'), $tmpArrPartners)
			    ->setHtmlAttribute('multiple','multiple')
			    ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele'));

	    $form->addSubmit('save', $this->translator->translate('vytvořit_objednávku'))->setHtmlAttribute('class','btn btn-sm btn-primary');

	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackOrderPeriodForm');
		$form->onSuccess[] = array($this, 'SubmitOrderPeriodFormSubmitted');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackOrderPeriodForm()
    {
		$this->hideModal('createOrderPeriodModal');
		//$this->redrawControl('baselistArea');

    }

    public function SubmitOrderPeriodFormSubmitted(Form $form)
    {
	    $data=$form->values;
        if ($this->isAllowed($this->name,'write')) {
            if ($form['save']->isSubmittedBy()) {
                $tmpDate_from = $data['date_from'];
                $tmpDate_to = $data['date_to'];
                $data['date_from'] = date('Y-m-d', strtotime($data['date_from']));
                $data['date_to'] = date('Y-m-d', strtotime($data['date_to']));

                //$test = new \Nette\Utils\DateTime;
                //bdump($data);
                $arrStorages = $this->StorageManager->findAll()->fetchPairs('id', 'name');
                foreach ($data['cl_storage_id'] as $one) {
                    $dataMove = $this->StoreManager->getMovesPeriod($data['date_from'], $data['date_to'], array($one), $data['cl_partners_book_id'], TRUE);
                    $dataMove = $this->ArraysManager->select2array($dataMove);
                    //add cl_store.quantity_to_order
                    $dataMove = $this->StoreManager->addToOrder($dataMove);
                    $dataMove = $this->OrderManager->prepareDataMove($dataMove);
                    //bdump($dataMove->fetchAll());
                    if (count($dataMove) > 0) {
                        $arrDocId = $this->OrderManager->createOrder($dataMove, $this->translator->translate('objednávka_dle_pohybů_od') . $tmpDate_from . $this->translator->translate('do') . $tmpDate_to);
                        //$this->OrderManager->update(array('id' => $docId, 'cl_storage_id' => $data['cl_storage_id']));
                        foreach ($arrDocId as $oneDoc) {
                            $this->OrderManager->update(array('id' => $oneDoc, 'cl_storage_id' => $one));
                        }
                        foreach ($dataMove as $key2 => $one2) {
                            $this->StoreManager->update(array('id' => $one2['cl_store_id'], 'quantity_to_order' => 0, 'supplier_sum' => 0));
                        }


                        $this->flashMessage($this->translator->translate('Objednávky_pro_sklad') . $arrStorages[$one] . $this->translator->translate('byly_vytvořeny'), 'success');
                    } else {
                        $this->flashMessage($this->translator->translate('Objednávky_pro_sklad') . $arrStorages[$one] . $this->translator->translate('nebyly_vytvořeny'), 'success');
                    }
                }

                $this->redrawControl('flash');
            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
            $this->redrawControl('flash');
        }
    }

    public function handleCreateOrderUnderLimitsModalWindow()
    {
		//bdump('ted');
		//$this->createDocShow = TRUE;
		$this->showModal('createOrderUnderLimitsModal');
		$this->redrawControl('createOrder');
		$this->redrawControl('contents');
    }


    protected function createComponentOrderUnderLimitsForm($name)
    {
	    $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);

	    $now = new \Nette\Utils\DateTime;

        if (!is_null( $this->storagesBranch )) {
            $tmpArrStorages = $this->StorageManager->findAll()
                ->select('id, CONCAT(name," ",description) AS name')
                ->where('id IN ?', $this->storagesBranch)
                ->order('name')->fetchPairs('id', 'name');
        }else {
            $tmpArrStorages = $this->StorageManager->findAll()->select('id, CONCAT(name," ",description) AS name')->order('name')->fetchPairs('id', 'name');
        }
	    $form->addMultiSelect('cl_storage_id',$this->translator->translate('Sklady'), $tmpArrStorages)
			    ->setHtmlAttribute('multiple','multiple')
			    ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_sklad'));

	    $tmpArrPartners = $this->PartnersManager->findAll()->
				select('cl_partners_book.id, cl_partners_book.company')->
                where('supplier = 1')->
				order('company')->fetchPairs('id','company');

	    $form->addMultiSelect('cl_partners_book_id', $this->translator->translate('Dodavatelé'), $tmpArrPartners)
			    ->setHtmlAttribute('multiple','multiple')
			    ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele'));

	    $form->addSubmit('save', $this->translator->translate('vytvořit_objednávku'))->setHtmlAttribute('class','btn btn-sm btn-primary');

	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackOrderUnderLimitsForm');
		$form->onSuccess[] = array($this, 'SubmitOrderUnderLimitsForm');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackOrderUnderLimitsForm()
    {
		$this->hideModal('createOrderUnderLimitsModal');
		//$this->redrawControl('baselistArea');

    }


    public function SubmitOrderUnderLimitsForm(Form $form)
    {
        if ($this->isAllowed($this->name,'write')) {
            $data = $form->values;
            if ($form['save']->isSubmittedBy()) {
                //$test = new \Nette\Utils\DateTime;
                //bdump($data);
                $dataMove = $this->StoreManager->getUnderLimits($data['cl_storage_id'], $data['cl_partners_book_id']);
                $dataMove = $this->ArraysManager->select2array($dataMove);
                //bdump($dataMove);
                $this->OrderManager->createOrder($dataMove, $this->translator->translate('objednávka_do_množství'), NULL, $data['cl_partners_book_id']);
                $this->flashMessage($this->translator->translate('Objednávka_byla_vytvořena'), 'success');
                $this->redrawControl('flash');
            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
            $this->redrawControl('flash');
        }

    }



    protected function createComponentReportSupplier($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Období_od'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $tmpArrPartners = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Dodavatelé'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setRequired($this->translator->translate('Dodavatel_musí_být_vybrán'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele_pro_tisk'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklady'), $tmpArrStorage)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'));

        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportSupplier');
        $form->onSuccess[] = array($this, 'SubmitReportSupplier');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportSupplier()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportSupplier(Form $form)
    {
        $data=$form->values;
        if ($form['save']->isSubmittedBy())
        {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else
            {
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));

            $dataReport = $this->StoreMoveManager->findAll()->select('SUM(cl_store_move.s_out) AS sum_out, SUM(cl_store_move.s_out * cl_store_move.price_s) AS sum_price, cl_pricelist.identification AS identification, cl_pricelist.item_label AS item_label, cl_storage.name AS storage_name')->
                                                        group('cl_store_move.cl_pricelist_id, cl_store_move.cl_storage_id')->
                                                        where('cl_store_docs.doc_type = 1 AND cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?', $data['date_from'], $data['date_to']);
            if (count($data['cl_partners_book']) == 0)
            {
                $dataReport = $dataReport->order('cl_partners_book.company ASC, cl_pricelist.identification ASC');
            }elseif(count($data['cl_partners_book']) == 1)
            {
                $dataReport = $dataReport->where(array('cl_pricelist.cl_partners_book_id' =>  $data['cl_partners_book']))->
                							order('cl_pricelist.identification ASC');
            }else{
                $dataReport = $dataReport->where(array('cl_pricelist.cl_partners_book_id' =>  $data['cl_partners_book']))->
                							order('cl_pricelist.identification ASC');
            }

            if (count($data['cl_storage_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_store_move.cl_storage_id' =>  $data['cl_storage_id']));
            }


            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Obrat_dodavatelů');

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners']	= $this->PartnersManager->findAll()->
                                                                            where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
                                                                            order('company');
            $dataOther['dataSettingsStorage']	= $this->StorageManager->findAll()->where(array('id' =>$data['cl_storage_id']))->order('name');
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportSupplier.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);
            $this->pdfCreate($template, 'Obrat dodavatelů'.date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));


        }
    }



    protected function createComponentReportPartnersTurnover($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $tmpArrPartners = $this->PartnersManager->findAll()->where('customer = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Odběratelé'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklady'), $tmpArrStorage)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

		$tmpArrPricelistGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Jen_skupina'), $tmpArrPricelistGroup)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'));

		$tmpArrPricelistGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_pricelist_group_id2', $this->translator->translate('Bez_skupiny'), $tmpArrPricelistGroup)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'));

        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportPartnersTurnover');
        $form->onSuccess[] = array($this, 'SubmitReportPartnersTurnover');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportPartnersTurnover()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportPartnersTurnover(Form $form)
    {
        $data=$form->values;

        if ($form['save']->isSubmittedBy())
        {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else
            {
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));

            /*$dataReport = $this->InvoiceManager->findAll()->select('cl_partners_book_id, cl_partners_book.company, SUM(cl_invoice_items.price_s*cl_invoice_items.quantity) AS price_s,'.
                                                            'SUM(cl_invoice.price_e2) AS price_e2, COUNT(cl_invoice.id) AS doc_count,'.
                                                            'SUM(cl_invoice.price_e2 - (cl_invoice_items.price_s*cl_invoice_items.quantity)) AS profit_abs,'.
                                                            '( (1 - SUM(cl_invoice_items.price_s*cl_invoice_items.quantity) / (SUM(cl_invoice.price_e2))) * 100 ) AS profit_per,'.
                                                            'cl_users.name AS users_name')->
                                                            group('cl_partners_book_id')->
                                                            where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ?', $data['date_from'], $data['date_to'])->
                                                            order('profit_abs DESC');*/
            $dataReport = $this->InvoiceManager->findAll()->select('cl_partners_book.company, ' .
                                'SUM(DISTINCT (:cl_invoice_items.price_s * :cl_invoice_items.quantity)) AS price_s,'.
                                'SUM(:cl_invoice_items.price_e2) -  SUM(:cl_invoice_items.price_s*:cl_invoice_items.quantity) AS profit_ord,'.
                                'SUM(DISTINCT   :cl_invoice_items.price_e2) AS price_e2, '.
                                'SUM(DISTINCT :cl_invoice_items_back.price_e2) AS price_e2_back, '.
                                'SUM(DISTINCT (:cl_invoice_items_back.price_s * :cl_invoice_items_back.quantity)) AS price_s_back, '.
                                'COUNT(DISTINCT cl_invoice.inv_number) AS doc_count, '.
                                'cl_users.name AS users_name')->
                            group('cl_partners_book.company')->
                            where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ?', $data['date_from'], $data['date_to'])->
                            order('profit_ord DESC');

            //where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ? AND :cl_invoice_items.cl_pricelist.cl_pricelist_group.is_return_package = 0 AND
              //                              :cl_invoice_items_back.cl_pricelist.cl_pricelist_group.is_return_package = 0 ', $data['date_from'], $data['date_to'])->

            if (count($data['cl_partners_book']) == 0)
            {
                //$dataReport = $dataReport->order('cl_partners_book.company ASC, cl_pricelist.identification ASC');
            }elseif(count($data['cl_partners_book']) >= 1)
            {
                $dataReport = $dataReport->where(array('cl_invoice.cl_partners_book_id' =>  $data['cl_partners_book']));
            }

            if (count($data['cl_storage_id']) > 0)
            {
                $tmpStorageStr = implode(",", $data['cl_storage_id']);
                $dataReport = $dataReport->joinWhere(':cl_invoice_items', ':cl_invoice_items.cl_storage_id IN (?)', $tmpStorageStr)->
                                            joinWhere(':cl_invoice_items_back', ':cl_invoice_items_back.cl_storage_id IN (?)', $tmpStorageStr);
                /*having(array(':cl_invoice_items.cl_storage_id' =>  $data['cl_storage_id'],
                                                        ':cl_invoice_items_back.cl_storage_id' =>  $data['cl_storage_id']));*/
            }

            if (count($data['cl_users_id']) == 0) {

            }elseif (count($data['cl_users_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_invoice.cl_users_id' =>  $data['cl_users_id']));
            }

			if (count($data['cl_pricelist_group_id']) > 0)
			{
				$dataReport = $dataReport->where(':cl_invoice_items.cl_pricelist.cl_pricelist_group_id ?',$data['cl_pricelist_group_id']);
			}

			if (count($data['cl_pricelist_group_id2']) > 0)
			{
				$dataReport = $dataReport->where('NOT (:cl_invoice_items.cl_pricelist.cl_pricelist_group_id ?)',$data['cl_pricelist_group_id2']);
			}


            //bdump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Obrat_obchodních_partnerů_z_faktur');

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners']			= $this->PartnersManager->findAll()->
																							where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
																							order('company');
			$dataOther['dataSettingsStorage']			= $this->StorageManager->findAll()->where(array('id' =>$data['cl_storage_id']))->order('name');
			$dataOther['dataSettingsPricelistGroup']	= $this->PriceListGroupManager->findAll()->where(array('id' =>$data['cl_pricelist_group_id']))->order('name');
			$dataOther['dataSettingsPricelistGroup2']	= $this->PriceListGroupManager->findAll()->where(array('id' =>$data['cl_pricelist_group_id2']))->order('name');
            $dataOther['dataSettingsUsers']				= $this->UserManager->getAll()->where(array('id' =>$data['cl_users_id']))->order('name');
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportPartnersTurnover.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);
            $this->pdfCreate($template, $this->translator->translate('Obrat_obchodních_partnerů_z_faktur').date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));


        }
    }


    protected function createComponentReportPartnersTurnover2($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $tmpArrPartners = $this->PartnersManager->findAll()->where('customer = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Odběratelé'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklady'), $tmpArrStorage)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $tmpArrPricelistGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Jen_skupina'), $tmpArrPricelistGroup)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'));

        $tmpArrPricelistGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group_id2', $this->translator->translate('Bez_skupiny'), $tmpArrPricelistGroup)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'));

        $form->addCheckbox('invoice', $this->translator->translate('Pouze_výdeje_s_fakturou'));
        $form->addCheckbox('zip_group', $this->translator->translate('Seskupit_podle_PSČ_a_položek_ceníku'));
        $form->addTextArea('items', $this->translator->translate('Položky'),30,3)
                ->setHtmlAttribute('placeholder', $this->translator->translate('seznam_položek_oddělených_čárkou'));

        $form->addSubmit('save_xls', $this->translator->translate('uložit_do_XLS'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportPartnersTurnover2');
        $form->onSuccess[] = array($this, 'SubmitReportPartnersTurnover2');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportPartnersTurnover2()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportPartnersTurnover2(Form $form)
    {
        $data=$form->values;
        if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xls']->isSubmittedBy())
        {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else
            {
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));


            /*$dataReport = $this->InvoiceManager->findAll()->select('cl_partners_book.company, ' .
                'SUM(:cl_invoice_items.price_s * :cl_invoice_items.quantity) AS price_s,'.
                'SUM(:cl_invoice_items.price_e2) -  SUM(:cl_invoice_items.price_s*:cl_invoice_items.quantity) AS profit_ord,'.
                'SUM(:cl_invoice_items.price_e2) AS price_e2, '.
                'SUM(DISTINCT :cl_invoice_items_back.price_e2) AS price_e2_back, '.
                'SUM(DISTINCT :cl_invoice_items_back.price_s * :cl_invoice_items_back.quantity) AS price_s_back, '.
                'COUNT(DISTINCT cl_invoice.inv_number) AS doc_count, '.
                'cl_users.name AS users_name')->
            group('cl_partners_book.company')->
            where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ?', $data['date_from'], $data['date_to'])->
            order('profit_ord DESC');*/

            if ($data['zip_group'] == 0) {
                $dataReport = $this->StoreDocsManager->findAll()->select(
                    'cl_partners_book.company,
                                    COUNT(DISTINCT cl_store_docs.id) AS doc_count,
                                    SUM(:cl_store_move.price_e2) AS price_e2,
                                    SUM(:cl_store_move.price_s*:cl_store_move.s_out) AS price_s,
                                    SUM((:cl_store_move.price_e2) - (:cl_store_move.price_s*:cl_store_move.s_out)) AS profit_ord,
                                    cl_users.name AS users_name')->
                            where('cl_store_docs.doc_type = 1')->
                            where('cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? ', $data['date_from'], $data['date_to'])->
                            // where('NOT EXISTS(SELECT cl_delivery_note.id FROM cl_delivery_note WHERE cl_delivery_note.cl_store_docs_id = cl_store_docs.id AND cl_delivery_note.cl_invoice_id IS NOT NULL)')->
                            where(':cl_store_move.cl_pricelist.cl_pricelist_group.is_return_package = 0')->
                            group('cl_partners_book.company')->
                            order('profit_ord DESC');

                $tmpReportIn = $this->invoiceItemsBackManager->findAll()->select(
                                'cl_invoice.cl_partners_book.company,
                                    SUM(cl_invoice_items_back.price_e2) AS price_e2,
                                    SUM(cl_invoice_items_back.price_s * cl_invoice_items_back.quantity) AS price_s,
                                    cl_users.name AS users_name')->
                            where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ? ', $data['date_from'], $data['date_to'])->
                            where('cl_pricelist.cl_pricelist_group.is_return_package = 0')->
                            group('cl_invoice.cl_partners_book.company');


                foreach($tmpReportIn as $key => $one){
                    $dataReportIn[$one['company']] = ['price_e2' => $one['price_e2'], 'price_s' => $one['price_s']];
                }

                $tmpReportIn = $this->deliveryNoteItemsBackManager->findAll()->select(
                    'cl_delivery_note.cl_partners_book.company,
                                    SUM(cl_delivery_note_items_back.price_e2) AS price_e2,
                                    SUM(cl_delivery_note_items_back.price_s * cl_delivery_note_items_back.quantity) AS price_s,
                                    cl_users.name AS users_name')->
                            where('cl_delivery_note.cl_invoice_id IS NULL')->
                            where('cl_delivery_note.issue_date >= ? AND cl_delivery_note.issue_date <= ? ', $data['date_from'], $data['date_to'])->
                            where('cl_pricelist.cl_pricelist_group.is_return_package = 0')->
                            group('cl_delivery_note.cl_partners_book.company');

                foreach($tmpReportIn as $key => $one){
                    if (isset($dataReportIn[$one['company']])){
                        $price_e2 = $dataReportIn[$one['company']]['price_e2'];
                        $price_s = $dataReportIn[$one['company']]['price_s'];
                    }else{
                        $price_e2 = 0;
                        $price_s = 0;
                    }
                    $dataReportIn[$one['company']] = ['price_e2' => $price_e2 + $one['price_e2'], 'price_s' => $price_s + $one['price_s']];
                }

                    //bdump($dataReportIn);

            }else{
                $dataReport = $this->StoreDocsManager->findAll()->select(
                    '   SUM(:cl_store_move.price_s*:cl_store_move.s_out) AS nakup,
                                SUM(:cl_store_move.price_e2) AS prodej,
                                COUNT(DISTINCT cl_store_docs.id) AS pocet,
                                SUM(:cl_store_move.s_out) AS pocetmj,
                                REPLACE(cl_partners_book.zip, " ","") AS psc,
                                :cl_store_move.cl_pricelist.identification AS ident_ci,
                                :cl_store_move.cl_pricelist.ean_code AS caro_kod,
                                :cl_store_move.cl_pricelist.item_label AS nazev')->
                            where('cl_store_docs.doc_type = 1')->
                            where('cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? ', $data['date_from'], $data['date_to'])->
                            // where('NOT EXISTS(SELECT cl_delivery_note.id FROM cl_delivery_note WHERE cl_delivery_note.cl_store_docs_id = cl_store_docs.id AND cl_delivery_note.cl_invoice_id IS NOT NULL)')->
                            where(':cl_store_move.cl_pricelist.cl_pricelist_group.is_return_package = 0')->
                            group('REPLACE(cl_partners_book.zip," ",""),:cl_store_move.cl_pricelist.identification, :cl_store_move.cl_pricelist.item_label')->
                            order('pocetmj DESC');
               /* $dataReportIn = $this->StoreDocsManager->findAll()->select(
                    '   SUM(:cl_store_move.price_in*:cl_store_move.s_in) AS nakup,
                                SUM(:cl_store_move.price_s*:cl_store_move.s_in) AS prodej,
                                COUNT(DISTINCT cl_store_docs.id) AS pocet,
                                SUM(:cl_store_move.s_in) AS pocetmj,
                                REPLACE(cl_partners_book.zip, " ","") AS psc,
                                :cl_store_move.cl_pricelist.identification AS ident_ci,
                                :cl_store_move.cl_pricelist.ean_code AS caro_kod,
                                :cl_store_move.cl_pricelist.item_label AS nazev')->
                            where('cl_store_docs.doc_type = 0')->
                            where('cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? ', $data['date_from'], $data['date_to'])->
                            // where('NOT EXISTS(SELECT cl_delivery_note.id FROM cl_delivery_note WHERE cl_delivery_note.cl_store_docs_id = cl_store_docs.id AND cl_delivery_note.cl_invoice_id IS NOT NULL)')->
                            where(':cl_store_move.cl_pricelist.cl_pricelist_group.is_return_package = 0')->
                            group('REPLACE(cl_partners_book.zip," ",""),:cl_store_move.cl_pricelist.identification, :cl_store_move.cl_pricelist.item_label')->
                            order('pocetmj DESC')->fetchPairs('cl_partners_book.company', 'price_in');*/
                $dataReportIn = NULL;
            }

            if ($data['items'] != '') {
                $arrItems = str_getcsv($data['items'], ",");
                if (count($arrItems) > 0) {
                    $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.identification IN (?)', $arrItems);
                }
            }else{
                $arrItems = array();
            }

            if (count($data['cl_partners_book']) == 0)
            {
                //$dataReport = $dataReport->order('cl_partners_book.company ASC, cl_pricelist.identification ASC');
            }elseif(count($data['cl_partners_book']) >= 1)
            {
                $dataReport = $dataReport->where(array('cl_store_docs.cl_partners_book_id' =>  $data['cl_partners_book']));
            }

            if (count($data['cl_storage_id']) > 0)
            {
                $tmpStorageStr = implode(",", $data['cl_storage_id']);
                $dataReport = $dataReport->joinWhere(':cl_store_move', ':cl_store_move.cl_storage_id IN (?)', $tmpStorageStr);
                /*having(array(':cl_invoice_items.cl_storage_id' =>  $data['cl_storage_id'],
                                                        ':cl_invoice_items_back.cl_storage_id' =>  $data['cl_storage_id']));*/
            }

            if (count($data['cl_users_id']) == 0) {

            }elseif (count($data['cl_users_id']) > 0)
            {
                $dataReport = $dataReport->where(['cl_store_docs.cl_users_id' =>  $data['cl_users_id']]);
            }

            if (count($data['cl_pricelist_group_id']) > 0)
            {
                $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.cl_pricelist_group_id ?',$data['cl_pricelist_group_id']);
            }

            if (count($data['cl_pricelist_group_id2']) > 0)
            {
                $dataReport = $dataReport->where('NOT (:cl_store_move.cl_pricelist.cl_pricelist_group_id ?)',$data['cl_pricelist_group_id2']);
            }

            if ($data['invoice'] == 1)
            {
                $dataReport = $dataReport->where('cl_store_docs.cl_invoice_id IS NOT NULL');
            }



            //bdump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;


            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners']			= $this->PartnersManager->findAll()->
                                                where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
                                                order('company');
            $dataOther['dataSettingsStorage']			= $this->StorageManager->findAll()->where(array('id' =>$data['cl_storage_id']))->order('name');
            $dataOther['dataSettingsPricelistGroup']	= $this->PriceListGroupManager->findAll()->where(array('id' =>$data['cl_pricelist_group_id']))->order('name');
            $dataOther['dataSettingsPricelistGroup2']	= $this->PriceListGroupManager->findAll()->where(array('id' =>$data['cl_pricelist_group_id2']))->order('name');
            $dataOther['dataSettingsUsers']				= $this->UserManager->getAll()->where(array('id' =>$data['cl_users_id']))->order('name');
            $dataOther['items']                         = $this->PriceListManager->findAll()->where('identification IN ?', $arrItems);
            $dataOther['itemsBack']                     = $dataReportIn;
            if ($data['zip_group'] == 0) {
                $tmpTitle = $this->translator->translate('Obrat_obchodních_partnerů_-_celkem');
                $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportPartnersTurnover2.latte', $dataOther, $dataSettings, $tmpTitle);
            }else{
                $tmpTitle = $this->translator->translate('Obrat_dle_PSČ_a_položek_-_celkem');
                $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportPartnersTurnover2zip.latte', $dataOther, $dataSettings, $tmpTitle);
            }
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);

            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, $this->translator->translate('Obrat_obchodních_partnerů_-_celkem') . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_csv']->isSubmittedBy())
            {
                if ( $dataReport->count()>0)
                {
                    if ($data['zip_group'] == 0) {
                        $filename = $this->translator->translate("Obrat_obchodních_partnerů_celkem");
                    }else{
                        $filename = $this->translator->translate("Obrat_dle_PSČ_a_položek_-_celkem");
                    }
                    $dataReport2 = [];
                    foreach($dataReport as $key => $one)
                    {
                        $dataReport2[$key] = $one->toArray();
                        if (!is_null($dataOther['itemsBack'])) {
                            $dataReport2[$key]['price_e2_back'] = $dataOther['itemsBack'][$one['company']]['price_e2'];
                            $dataReport2[$key]['price_s_back'] = $dataOther['itemsBack'][$one['company']]['price_s'];
                        }else{
                            $dataReport2[$key]['price_e2_back'] = 0;
                            $dataReport2[$key]['price_s_back'] = 0;
                        }

                        unset($dataReport2[$key]['profit_ord']);
                    }

                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport2, $filename."-" .date('Ymd-Hi').".csv",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }elseif ($form['save_xls']->isSubmittedBy())
            {
                if ( $dataReport->count()>0)
                {
                    if ($data['zip_group'] == 0) {
                        $filename = $this->translator->translate("Obrat_obchodních_partnerů_celkem");
                    }else{
                        $filename = $this->translator->translate("Obrat_dle_PSČ_a_položek_-_celkem");
                    }
                    $dataReport2 = [];
                    foreach($dataReport as $key => $one)
                    {
                        $dataReport2[$key] = $one->toArray();
                        if (!is_null($dataOther['itemsBack'])) {
                            $dataReport2[$key]['price_e2_back'] = $dataOther['itemsBack'][$one['company']]['price_e2'];
                            $dataReport2[$key]['price_s_back'] = $dataOther['itemsBack'][$one['company']]['price_s'];
                        }else{
                            $dataReport2[$key]['price_e2_back'] = 0;
                            $dataReport2[$key]['price_s_back'] = 0;
                        }

                        unset($dataReport2[$key]['profit_ord']);
                    }
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport2, $filename."-" .date('Ymd-Hi').".xls",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XLS_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }

            }


        }
    }


    /**Obrat_na_skladových_kartách
     * @param $name
     * @return Form
     */
    protected function createComponentReportPricelistTurnover($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $tmpArrPartners = $this->PartnersManager->findAll()->
                            where('customer = 1')->
                            select('id, CONCAT(company, " ", partner_code) AS company2')->
                            order('company')->fetchPairs('id','company2');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Odběratelé'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklady'), $tmpArrStorage)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'));

        $tmpArrPricelistGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Jen_skupina'), $tmpArrPricelistGroup)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'));

        $form->addCheckbox('no_price', $this->translator->translate('Netisknout_ceny'));
        $form->addCheckbox('group_partners', $this->translator->translate('Podle_odběratelů'));

        $form->addTextArea('items', $this->translator->translate('Položky'),30,3)
            ->setHtmlAttribute('placeholder', $this->translator->translate('seznam_položek_oddělených_čárkou'));

        $form->addSubmit('save_xls', $this->translator->translate('XLS'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBackReportPricelistTurnover'];
        $form->onSuccess[] = [$this, 'SubmitReportPricelistTurnover'];
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportPricelistTurnover()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    /**Obrat_na_skladových_kartách
     * @param Form $form
     * @return void
     * @throws \Nette\Application\AbortException
     */
    public function SubmitReportPricelistTurnover(Form $form)
    {
        $data=$form->values;
        if ($form['save_pdf']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            if ($data['no_price'] == 0){
                $select = 'cl_pricelist.identification, cl_pricelist.item_label, cl_pricelist.unit,' .
                    'SUM(cl_store_move.s_out) AS s_out,' .
                    'SUM(cl_store_move.price_s * cl_store_move.s_out) AS price_s,' .
                    'SUM(cl_store_move.price_e2) AS price_e2,' .
                    'SUM(cl_store_move.price_e2 - (cl_store_move.price_s * cl_store_move.s_out)) AS profit_abs';

            }else{
                $select = 'cl_pricelist.identification, cl_pricelist.item_label, cl_pricelist.unit,' .
                    'SUM(cl_store_move.s_out) AS s_out';
            }

            if ($data['group_partners'] == 0){
                    $dataReport = $this->StoreMoveManager->findAll()->select($select)->
                                        group('cl_pricelist.id')->
                                        where('cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? AND cl_store_docs.doc_type = 1', $data['date_from'], $data['date_to'])->
                                        order('cl_pricelist.identification');
            }else{
                $select .= ', cl_store_docs.cl_partners_book.company AS company,cl_store_docs.cl_partners_book.ico AS ICO';
                $dataReport = $this->StoreMoveManager->findAll()->select($select)->
                                        group('cl_store_docs.cl_partners_book.company, cl_pricelist.identification')->
                                        where('cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? AND cl_store_docs.doc_type = 1', $data['date_from'], $data['date_to'])->
                                        order('cl_store_docs.cl_partners_book.company, cl_pricelist.identification');
            }


            if ($data['items'] != '') {
                $arrItems = str_getcsv($data['items'], ",");
                if (count($arrItems) > 0) {
                    $dataReport = $dataReport->where('cl_pricelist.identification IN (?)', $arrItems);
                }
            }else{
                $arrItems = array();
            }



            if (count($data['cl_partners_book']) == 0) {
                //$dataReport = $dataReport->order('cl_partners_book.company ASC, cl_pricelist.identification ASC');
            } elseif (count($data['cl_partners_book']) >= 1) {
                $dataReport = $dataReport->where(['cl_store_docs.cl_partners_book_id' => $data['cl_partners_book']]);
            }

            if (count($data['cl_storage_id']) > 0) {
                $dataReport = $dataReport->where(['cl_store_move.cl_storage_id' => $data['cl_storage_id']]);
            }

            if (count($data['cl_pricelist_group_id']) > 0) {
                $dataReport = $dataReport->where(['cl_pricelist.cl_pricelist_group_id' => $data['cl_pricelist_group_id']]);
            }


            //bdump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . ' z ' . $this->settings->name;
            $tmpTitle = $this->translator->translate('Obrat_na_skladových_kartách');

            $dataOther = [];
            $dataSettings = $data;
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
                                                            where(['cl_partners_book.id' => $data['cl_partners_book']])->
                                                            order('company');
            $dataOther['dataSettingsStorage'] = $this->StorageManager->findAll()->where(['id' => $data['cl_storage_id']])->order('name');
            $dataOther['dataSettingsPricelistGroup'] = $this->PriceListGroupManager->findAll()->where(['id' => $data['cl_pricelist_group_id']])->order('name');
            $dataOther['items']                         = $this->PriceListManager->findAll()->where('identification IN ?', $arrItems);

            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportPricelistTurnover.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);
            if ($form['save_pdf']->isSubmittedBy()){
                $this->pdfCreate($template, $this->translator->translate('Obrat_na_skladových_kartách') . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_xls']->isSubmittedBy()){
                if ( $dataReport->count() > 0)
                {
                    $filename = $this->translator->translate('Obrat_na_skladových_kartách');
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename . '-' . $tmpDate1->format('d_m_Y') . '-' . $tmpDate2->format('d_m_Y') . '.xls', NULL, []));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XLS_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }


        }
    }

    /** report with items waiting to automatic order
     * @param $name
     * @return Form
     * @throws \Exception
     */
    protected function createComponentReportWaitingToOrder($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;

        $tmpArrPartners = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Dodavatelé'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele_pro_tisk'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_storage_id', $this->translator->translate('Sklady'), $tmpArrStorage)
            ->setPrompt($this->translator->translate("Sklad_musí_být_vybrán"))
            ->setRequired(true)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'));

        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportWaitingToOrder');
        $form->onSuccess[] = array($this, 'SubmitReportWaitingToOrder');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportWaitingToOrder()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportWaitingToOrder(Form $form)
    {
        $data=$form->values;
        if ($form['save']->isSubmittedBy())
        {

            $dataReport = $this->StoreManager->findAll()->where('quantity_to_order > 0')->
                                order('cl_pricelist.cl_partners_book.company,cl_pricelist.identification');

            if (count($data['cl_partners_book']) == 0)
            {
                //$dataReport = $dataReport->order('cl_partners_book.company ASC, cl_pricelist.identification ASC');
            }elseif(count($data['cl_partners_book']) >= 1)
            {
                $dataReport = $dataReport->where(array('cl_pricelist.cl_partners_book_id' =>  $data['cl_partners_book']));
            }

            //if (count($data['cl_storage_id']) > 0)
            //{
                $dataReport = $dataReport->where(array('cl_store.cl_storage_id' =>  $data['cl_storage_id']));
            //}

            //bdump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Položky_čekající_na_objednání');

            $this->StoreManager->updateSupplierSum($data['cl_storage_id']);

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners']	= $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
                                                    order('company');
            $dataOther['dataSettingsStorage']	= $this->StorageManager->findAll()->where(array('id' =>$data['cl_storage_id']))->order('name');

            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportWaitingToOrder.latte', $dataOther, $dataSettings, $tmpTitle);
            $this->pdfCreate($template, $this->translator->translate('Položky_k_objednání'));


        }
    }







    protected function createComponentReportStoreMovement($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addCheckbox('show_income', $this->translator->translate('Tisknout_příjmy'))
                    ->setDefaultValue(TRUE);
        $form->addCheckbox('show_outcome', $this->translator->translate('Tisknout_výdaje'))
                    ->setDefaultValue(TRUE);

        $form->addText('date_from', $this->translator->translate('Příjem_od'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Příjem_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $form->addText('date_from2', $this->translator->translate('Výdej_od'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to2', $this->translator->translate('Výdej do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $form->addTextArea('identification', $this->translator->translate('Kód_zboží'), 30, 3)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Kód_zboží_nebo_jeho_část_nebo_více_kódů_oddělených_středníkem_případně_čárkou'));

        $tmpArrPartners = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele_pro_tisk'));

        $tmpArrPartners2 = $this->PartnersManager->findAll()->
               select('id, CONCAT(company, " ", partner_code) AS company2')->
                where('customer = 1')->order('company')->fetchPairs('id','company2');
        $form->addMultiSelect('cl_partners_book2', $this->translator->translate('Odběratel'), $tmpArrPartners2)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklad'), $tmpArrStorage)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'));

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportStoreMovement');
        $form->onSuccess[] = array($this, 'SubmitReportStoreMovement');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportStoreMovement()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportStoreMovement(Form $form)
    {
        $data=$form->values;
        if ($form['save']->isSubmittedBy())
        {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else
            {
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));

            if ($data['date_to2'] == "")
                $data['date_to2'] = NULL;
            else
            {
                $data['date_to2'] = date('Y-m-d H:i:s',strtotime($data['date_to2']) + 86400 - 10);
            }

            if ($data['date_from2'] == "")
                $data['date_from2'] = NULL;
            else
                $data['date_from2'] = date('Y-m-d H:i:s',strtotime($data['date_from2']));


            $dataReport = $this->StoreMoveManager->findAll()->select('cl_store_move.id,cl_pricelist.identification, cl_pricelist.item_label, cl_pricelist.unit,' .
                                                                        's_in, s_end, cl_pricelist.id AS cl_pricelist_id, cl_store_move.price_in, cl_store_move.price_s, cl_storage.name AS storage_name, cl_store_docs.doc_title, cl_store.exp_date, cl_store.batch,'.
                                                                        'cl_store_docs.doc_date, cl_store_docs.id AS cl_store_docs_id, cl_store_docs.doc_number, cl_store_docs.cl_partners_book.company, cl_store_docs.cl_partners_book_id, cl_store_docs.invoice_number, cl_store_docs.doc_type')->
                                                            where('(cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? AND cl_store_docs.doc_type = 0) OR (cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ? AND cl_store_docs.doc_type = 1)', $data['date_from'], $data['date_to'], $data['date_from2'], $data['date_to2'])->
                                                            order('cl_pricelist.identification, cl_store_docs.doc_date');
//AND cl_store_docs.doc_type = 0

           /* if ($data['identification'] != ''){
                $dataReport = $dataReport->where('cl_pricelist.identification LIKE ?', '%' . $data['identification'] . '%');
            }*/
            $arrIdentification = [];
            if ($data['identification'] != ''){
                $tmpIdent = str_ireplace(';',',', $data['identification']);
                $tmpIdent = str_ireplace(' ','', $tmpIdent);
                $arrIdentification = explode(',', $tmpIdent);
                if (count($arrIdentification) == 1) {
                    $dataReport = $dataReport->where('cl_pricelist.identification LIKE ?', '%' . $data['identification'] . '%');
                }else{
                    $dataReport = $dataReport->where('cl_pricelist.identification IN ?', $arrIdentification);
                }
            }

            if (count($data['cl_partners_book']) >= 1) {
                if (count($data['cl_partners_book2']) >= 1) {
                    $dataReport = $dataReport->where('cl_store_docs.cl_partners_book_id IN ? OR cl_store_docs.cl_partners_book_id IN ? ', $data['cl_partners_book'], $data['cl_partners_book2']);
                } else {
                    $dataReport = $dataReport->where(array('cl_store_docs.cl_partners_book_id' => $data['cl_partners_book']));
                }
            } else {
                if(count($data['cl_partners_book2']) >= 1) {
                    $dataReport = $dataReport->where('(cl_store_docs.cl_partners_book_id IN ? AND cl_store_docs.doc_type = 1) ', $data['cl_partners_book2'] );
                }
            }



            if (count($data['cl_storage_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_store_move.cl_storage_id' =>  $data['cl_storage_id']));
            }
            //dump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Pohyby_na_skladových_kartách');

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners']	= $this->PartnersManager->findAll()->
                                                                        where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
                                                                        order('company');

            $dataOther['suppliersIds'] = $this->PartnersManager->findAll()->
                                                                        where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
                                                                        order('company')->fetchPairs('id');

            $dataOther['dataSettingsIdentification'] = $data['identification'];
            $dataOther['dataSettingsStorage']	= $this->StorageManager->findAll()->where(array('id' =>$data['cl_storage_id']))->order('name');
            $dataOther['customers']	= $this->PartnersManager->findAll()->
                                                                        where(array('cl_partners_book.id' =>$data['cl_partners_book2']))->
                                                                        order('company');
            $dataOther['customersIds'] = $this->PartnersManager->findAll()->
                                                                        where(array('cl_partners_book.id' =>$data['cl_partners_book2']))->
                                                                        order('company')->fetchPairs('id');



            if (count($arrIdentification) == 1) {
                //$dataReport = $dataReport->where('cl_pricelist.identification LIKE ?', '%' . $data['identification'] . '%');
                $dataOther['pricelistIds'] = $this->PriceListManager->findAll()->where('identification LIKE ?', '%' . $data['identification'] . '%' )->fetchPairs('id');
            }elseif (count($arrIdentification) > 1){
                //$dataReport = $dataReport->where('cl_pricelist.identification IN ?', $arrIdentification);
                $dataOther['pricelistIds'] = $this->PriceListManager->findAll()->where('identification IN ?', $arrIdentification)->fetchPairs('id');
            }else{
                $dataOther['pricelistIds'] = false;
            }


            $dataOther['cl_store_move_in'] = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 0');
            $dataOther['exp_on'] = $this->settings->exp_on;
            $dataOther['batch_on'] = $this->settings->batch_on;
            //bdump($dataOther['customersIds']);
            //die;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportStoreMovement.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);
            $this->pdfCreate($template, $this->translator->translate('Pohyby_na_skladových_kartách').date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));

        }
    }


	public function changePlace($itemId)
	{
		$this->itemId = $itemId;
		//$this->hideModal('showPlaceModal');
		$this['changeStoragePlace']->setItemId($itemId);
		$this->redrawControl('changeform');

		$this->showModal('changePlace');
	}

	protected function createComponentReportStoresCheck($name)
	{
		$form = new Form($this, $name);
		//$form->setMethod('POST');
		$form->addHidden('id',NULL);

		$tmpArrStorage = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Skupina'), $tmpArrStorage)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'));

		$form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		$form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

		$form->addSubmit('back', $this->translator->translate('Návrat'))
			->setHtmlAttribute('class','btn btn-sm btn-primary')
			->setValidationScope([])
			->onClick[] = array($this, 'stepBackReportStoresCheck');
		$form->onSuccess[] = array($this, 'SubmitReportStoresCheck');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
	}

	public function stepBackReportStoresCheck()
	{
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
	}

	public function SubmitReportStoresCheck(Form $form)
	{
		$data=$form->values;
		//dump($data);
		//die;
		if ($form['save']->isSubmittedBy())
		{

			$dataReport = $this->PriceListManager->findAll()->
                                    select('SUM(:cl_store.quantity) AS quantity_store, cl_pricelist.quantity AS quantity_pricelist,
                                                    cl_pricelist.identification AS identification, cl_pricelist.item_label AS item_label,
                                                    cl_partners_book.company AS company, cl_pricelist_group.name AS group_name')->
                                    group('cl_pricelist.id')->
                                    having('SUM(:cl_store.quantity) != cl_pricelist.quantity')->
                                    order('cl_pricelist.item_label');
			$dataReport2 = $this->StoreMoveManager->findAll()->
                                    select('cl_store.id, cl_store.quantity, SUM(cl_store_move.s_end) AS quantity_move,
                                                    cl_pricelist.identification AS identification, cl_pricelist.item_label AS item_label,
                                                    cl_pricelist.cl_partners_book.company AS company, cl_pricelist.cl_pricelist_group.name AS group_name')->
                                    where('cl_store_move.s_in > 0 AND cl_store.quantity >= 0')->
                                    group('cl_store.id')->
                                    having('SUM(cl_store_move.s_end) != cl_store.quantity ')->
                                    order('cl_pricelist.item_label');



			$dataReportStoreMinus = $this->StoreManager->findAll()->
							where('cl_store.quantity < 0');

			if (count($data['cl_pricelist_group_id']) > 0)
			{
				$dataReport = $dataReport->where(array('cl_pricelist.cl_pricelist_group_id' =>  $data['cl_pricelist_group_id']));
				$dataReportStoreMinus = $dataReportStoreMinus->where(array('cl_pricelist.cl_pricelist_group_id' =>  $data['cl_pricelist_group_id']));
			}



			//die;
			$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
			$tmpTitle = 'Kontrola zásob';

			$dataOther = array();
			$dataSettings = $data;
			$dataOther['dataSettingsStorage']	= $this->StorageManager->findAll()->where(array('id' =>$data['cl_pricelist_group_id']))->order('name');
			$dataOther['dataReportStoreMinus'] = $dataReportStoreMinus;
            $dataOther['dataReport2'] = $dataReport2;
			$dataOther['companySettings'] = $this->settings;
			$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportStoresCheck.latte', $dataOther, $dataSettings, $tmpTitle);
			$this->pdfCreate($template, $this->translator->translate('Kontrola_zásob'));


		}
	}


    protected function createComponentReportSales($name)
    {
        $form = new \Nette\Application\UI\Form($this, $name);
       // $form->addHidden('id',NULL);

        $form->addCheckbox('discount_only', $this->translator->translate('Jen_slevy'));
        $form->addCheckbox('group_customers', $this->translator->translate('Seskupit_prodeje_podle_odběratelů'));
        $form->addCheckbox('no_price', $this->translator->translate('Nezobrazovat_ceny'));

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Prodej_do'), 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Prodej_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));



        $tmpArrPartners = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele_pro_tisk'))
            ->setHtmlAttribute('multiple','multiple');

        $tmpArrPartners2 = $this->PartnersManager->findAll()->where('customer = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book2', $this->translator->translate('Odběratel'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'))
            ->setHtmlAttribute('multiple','multiple');



        $tmpArrProducer = $this->PartnersManager->findAll()->where('producer = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_producer', $this->translator->translate("Výrobce"), $tmpArrProducer)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Zvolte_výrobce'))
            ->setHtmlAttribute('multiple','multiple');


                $form->addTextarea('identification', $this->translator->translate('Kód-zboží'), 30, 3)
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Kód_zboží_nebo_jeho_část_nebo_více_kódů_oddělených_středníkem_případně_čárkou'));

                $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id','name');
                $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklad'), $tmpArrStorage)
                    ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'))
                    ->setHtmlAttribute('multiple','multiple');



                $tmpArrGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
                $form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Skupina'), $tmpArrGroup)
                    ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'))
                    ->setHtmlAttribute('multiple','multiple');


        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('save_pdf', $this->translator->translate('uložit_do_PDF'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('save', $this->translator->translate('Tisk'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportSales');
        $form->onSuccess[] = array($this, 'SubmitReportSales');
        $form->onValidate[] = array($this, 'FormValidate');
        return $form;
    }

    public function formValidate(Form $form){
        $data=$form->values;
    }

    public function stepBackReportSales()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function submitReportSales(Form $form)
    {
        $data=$form->values;
        if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy()  || $form['save_pdf']->isSubmittedBy() ) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else
            {
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));

            //dump($data);
            //die;
            $dataReport = $this->StoreMoveManager->findAll()->select('cl_store_move.id,cl_pricelist.identification, cl_pricelist.ean_code, cl_pricelist.item_label, cl_pricelist.unit,' .
                                        'cl_store_move.s_out, cl_store_move.discount, cl_pricelist.id AS cl_pricelist_id, cl_store_move.price_in, cl_store_move.price_e, cl_store_move.price_s, cl_store_move.price_e2, cl_storage.name AS storage_name, cl_store_docs.doc_title,'.
                                        'cl_store_docs.doc_date, cl_store_docs.doc_number, cl_store_docs.cl_partners_book.company, cl_store_docs.cl_partners_book_id, cl_store_docs.cl_sale.sale_number,'.
                                        'cl_store_docs.cl_invoice.inv_number, cl_store_docs.cl_currencies.currency_code')->
                                    where('cl_store_docs.doc_type = 1 AND (cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?)', $data['date_from'], $data['date_to']);

            if ($data['group_customers'] == 1){
                $dataReport = $dataReport->order('cl_store_docs.cl_partners_book.company, cl_pricelist.identification, cl_store_docs.doc_date');
            }else{
                $dataReport = $dataReport->order('cl_pricelist.identification, cl_store_docs.doc_date');
            }

            //AND cl_store_docs.doc_type = 0
            if ($data['discount_only'] == 1){
                $dataReport = $dataReport->where('cl_store_move.discount != 0');
            }

       /*     if ($data['show_income'] == 0){
                $dataReport = $dataReport->where('cl_store_docs.doc_type = 1');
            }
            if ($data['show_outcome'] == 0){
                $dataReport = $dataReport->where('cl_store_docs.doc_type = 0');
            }
*/
            if ($data['identification'] != ''){
                $tmpIdent = str_ireplace(';',',', $data['identification']);
                $tmpIdent = str_ireplace(' ','', $tmpIdent);
                $arrIdentification = explode(',', $tmpIdent);
                if (count($arrIdentification) == 1) {
                    $dataReport = $dataReport->where('cl_pricelist.identification LIKE ?', '%' . $data['identification'] . '%');
                }else{
                    $dataReport = $dataReport->where('cl_pricelist.identification IN ?', $arrIdentification);
                }
            }

            $data['cl_partners_book']   = $data['cl_partners_book'] == NULL ? array() : $data['cl_partners_book'];
            $data['cl_partners_book2']  = $data['cl_partners_book2'] == NULL ? array() : $data['cl_partners_book2'];
            $data['cl_producer']        = $data['cl_producer'] == NULL ? array() : $data['cl_producer'];

            if(count($data['cl_partners_book']) >= 1)
            {
                //$dataReport = $dataReport->where(array('cl_pricelist.cl_partners_book_id' =>  $data['cl_partners_book']));
                $dataReport = $dataReport->where('cl_pricelist.cl_partners_book_id IN (?) OR cl_store_docs_in.cl_partners_book_id IN (?)', $data['cl_partners_book'], $data['cl_partners_book']);
                $dataReport->alias(':cl_store_out.cl_store_move', 'cl_store_move_in');
                $dataReport->alias('cl_store_move_in.cl_store_docs', 'cl_store_docs_in');

            }
            if(count($data['cl_partners_book2']) >= 1){
                $dataReport = $dataReport->where('cl_store_docs.cl_partners_book_id IN ?', $data['cl_partners_book2']);
            }

            if(count($data['cl_producer']) >= 1){
                $dataReport = $dataReport->where('cl_pricelist.cl_producer_id IN ?', $data['cl_producer']);
            }

            if (count($data['cl_storage_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_store_move.cl_storage_id' =>  $data['cl_storage_id']));
            }

            if (count($data['cl_pricelist_group_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_pricelist.cl_pricelist_group_id' =>  $data['cl_pricelist_group_id']));
            }


            //dump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Prodeje_v_období');

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners']	= $this->PartnersManager->findAll()->
                                                        where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
                                                        order('company');
            $dataOther['dataSettingsIdentification']    = $data['identification'];
            $dataOther['dataSettingsDiscount']          = $data['discount_only'];
            $dataOther['dataSettingsStorage']	        = $this->StorageManager->findAll()->where(array('id' => $data['cl_storage_id']))->order('name');
            $dataOther['dataSettingsPricelistGroup']	= $this->PriceListGroupManager->findAll()->where(array('id' => $data['cl_pricelist_group_id']))->order('name');
            $dataOther['customers']	= $this->PartnersManager->findAll()->
                                                        where(array('cl_partners_book.id' => $data['cl_partners_book2']))->
                                                        order('company');
            $dataOther['customersIds'] = $this->PartnersManager->findAll()->
                                                        where(array('cl_partners_book.id' => $data['cl_partners_book2']))->
                                                        order('company')->fetchPairs('id');
            $dataOther['producers'] = $this->PartnersManager->findAll()->
                                                        where(array('cl_partners_book.id' => $data['cl_producer']))->
                                                        order('company');
            $dataOther['cl_store_move_in'] = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 0');

            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/StoreReview/ReportSales.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);

            if ($form['save']->isSubmittedBy()) {
                //$this->pdfCreate($template, $tmpTitle);
                $this->pdfCreate($template, 'Prodeje v období' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_csv']->isSubmittedBy())
            {
                if ( $dataReport->count()>0)
                {
                    $filename = $this->translator->translate("Prodeje_v_období");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename."-" .date('Ymd-Hi').".csv",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
            elseif ($form['save_pdf']->isSubmittedBy()) {
                $this->pdfCreate($template, $tmpTitle, FALSE, TRUE);
            }

        }
    }



}
