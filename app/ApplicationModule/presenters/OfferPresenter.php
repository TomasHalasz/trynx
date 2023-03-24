<?php
	
	namespace App\ApplicationModule\Presenters;
	
	use App\Controls;
	use Nette\Application\UI\Form,
		Nette\Image;
	use Exception;
	use Nette\Utils\DateTime;
	use Tracy\Debugger;
	use Nette\Mail\Message,
		Nette\Utils\Strings;
	use Nette\Mail\SendmailMailer;
	
	class OfferPresenter extends \App\Presenters\BaseListPresenter
	{
		
		
		const
			DEFAULT_STATE = 'Czech Republic';
		
		
		public $newId = NULL, $headerModalShow = FALSE, $descriptionModalShow = FALSE, $pairedDocsShow = FALSE, $createDocShow = FALSE;
		
		public $filterCommissionUsed = array();
		
		public $filterInvoiceUsed = array();
		
		/** @persistent */
		public $page_b;
		
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
		 * @var \App\Model\OfferManager
		 */
		public $DataManager;
		
		/**
		 * @inject
		 * @var \App\Model\DocumentsManager
		 */
		public $DocumentsManager;
		
		/**
		 * @inject
		 * @var \App\Model\OfferItemsManager
		 */
		public $OfferItemsManager;
		
		
		/**
		 * @inject
		 * @var \App\Model\OfferWorkManager
		 */
		public $OfferWorkManager;
		
		/**
		 * @inject
		 * @var \App\Model\OfferTaskManager
		 */
		public $OfferTaskManager;
		
		
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
		 * @var \App\Model\PartnersBranchManager
		 */
		public $PartnersBranchManager;
		
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
		 * @var \App\Model\EmailingManager
		 */
		public $EmailingManager;
		
		
		/**
		 * @inject
		 * @var \App\Model\FilesManager
		 */
		public $FilesManager;
		
		/**
		 * @inject
		 * @var \App\Model\PricesManager
		 */
		public $PricesManager;
		
		/**
		 * @inject
		 * @var \App\Model\ArraysManager
		 */
		public $ArraysManager;
		
		/**
		 * @inject
		 * @var \App\Model\CenterManager
		 */
		public $CenterManager;
		
		/**
		 * @inject
		 * @var \App\Model\CommissionManager
		 */
		public $CommissionManager;
		
		/**
		 * @inject
		 * @var \App\Model\CommissionItemsManager
		 */
		public $CommissionItemsManager;
		
		/**
		 * @inject
		 * @var \App\Model\CommissionItemsSelManager
		 */
		public $CommissionItemsSelManager;
		
		
		/**
		 * @inject
		 * @var \App\Model\CommissionTaskManager
		 */
		public $CommissionTaskManager;
		
		/**
		 * @inject
		 * @var \App\Model\PairedDocsManager
		 */
		public $PairedDocsManager;
		
		/**
		 * @inject
		 * @var \App\Model\HeadersFootersManager
		 */
		public $HeadersFootersManager;
		
		/**
		 * @inject
		 * @var \App\Model\TextsManager
		 */
		public $TextsManager;
		
		/**
		 * @inject
		 * @var \App\Model\PriceListMacroManager
		 */
		public $PriceListMacroManager;
		
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
		 * @var \App\Model\PriceListBondsManager
		 */
		public $PriceListBondsManager;
		
		protected function createComponentEditTextFooter()
		{
			return new Controls\EditTextControl(
                $this->translator,$this->DataManager, $this->id, 'footer_txt');
		}
		
		protected function createComponentEditTextHeader()
		{
			return new Controls\EditTextControl(
                $this->translator,$this->DataManager, $this->id, 'header_txt');
		}
		
		protected function createComponentEditTextDescription()
		{
			return new Controls\EditTextControl(
                $this->translator,$this->DataManager, $this->id, 'description_txt');
		}
		
		protected function createComponentPairedDocs()
		{
            //$translator = clone $this->translator;
            //$translator->setPrefix([]);
			return new PairedDocsControl(
                $this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
		}
		
		protected function createComponentTextsUse()
		{
            //$translator = clone $this->translator;
            //$translator->setPrefix([]);
			return new TextsUseControl(
                $this->DataManager, $this->id, 'offer', $this->TextsManager, $this->translator);
		}
		
		
		protected function createComponentSumOnDocs()
		{
            //$this->translator->setPrefix(['applicationModule.Offer']);
			if ($data = $this->DataManager->findBy(array('id' => $this->id))->fetch()) {
				if ($data->cl_currencies) {
					$tmpCurrencies = $data->cl_currencies->currency_name;
				}
				if ($data->price_s > 0) {
					$tmpProfit = (int)((($data->price_e2 / $data->price_s) - 1) * 100);
				} else {
					$tmpProfit = 100;
				}
				if ($data->price_w > 0) {
					$tmpProfitW = (int)((($data->price_w2 / $data->price_w) - 1) * 100);
				} else {
					$tmpProfitW = 100;
				}
				
				$tmpPriceE2Base = $data->price_e2_base;
				$tmpPriceE2Vat = $data->price_e2_vat;
				$tmpProfitAbs = $data->price_e2_base - ($data->price_e);
				
				if ($data->price_e > 0) {
					$tmpProfitWS = (int)(((($data->price_e2_base) / $data->price_e) - 1) * 100);
				} else {
					$tmpProfitWS = 100;
				}
				
				if ($this->settings->platce_dph) {
					$tmpPriceNameBase = "Prodej_bez_DPH:";
					$tmpPriceNameVat = "Prodej_s_DPH:";
				} else {
					$tmpPriceNameBase = "Prodej:";
					$tmpPriceNameVat = "";
				}
				
				$dataArr = [
					['name' => $this->translator->translate('Nákup_položek'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],
					['name' => $this->translator->translate('Zisk_položek') . $tmpProfit . ' %', 'value' => ($data->price_e2 - $data->price_s), 'currency' => $tmpCurrencies],
					['name' => $this->translator->translate('Prodej_položek'), 'value' => $data->price_e2, 'currency' => $tmpCurrencies],
					['name' => 'separator'],
					['name' => $this->translator->translate('Náklady_práce'), 'value' => $data->price_w, 'currency' => $tmpCurrencies],
					['name' => $this->translator->translate('Zisk_práce') . $tmpProfitW . ' %', 'value' => ($data->price_w2 - $data->price_w), 'currency' => $tmpCurrencies],
					['name' => $this->translator->translate('Prodej_práce'), 'value' => $data->price_w2, 'currency' => $tmpCurrencies],
					['name' => 'separator'],
					['name' => $this->translator->translate('Doprava'), 'value' => $data->delivery_price, 'currency' => $tmpCurrencies],
					['name' => 'separator'],
					['name' => $this->translator->translate('Náklady_celkem'), 'value' => $data->price_s + $data->price_w + $data->delivery_price, 'currency' => $tmpCurrencies],
					['name' => $this->translator->translate('Zisk_celkem') . $tmpProfitWS . ' %', 'value' => $tmpProfitAbs, 'currency' => $tmpCurrencies],
					['name' => $tmpPriceNameBase, 'value' => $tmpPriceE2Base, 'currency' => $tmpCurrencies],
					['name' => $tmpPriceNameVat, 'value' => $tmpPriceE2Vat, 'currency' => $tmpCurrencies],
                ];
			} else {
				$dataArr = array();
			}
			
			return new SumOnDocsControl(
                $this->translator,$this->DataManager, $this->id, $this->settings, $dataArr);
		}
		
		protected function createComponentEmail()
		{
          // $translator = clone $this->translator->setPrefix([]);
			return new Controls\EmailControl(
                $this->translator,$this->EmailingManager, $this->mainTableName, $this->id);
		}
		
		protected function createComponentFiles()
		{
			if ($this->getUser()->isLoggedIn()) {
				$user_id = $this->user->getId();
				$cl_company_id = $this->settings->id;
			}
            //$translator = clone $this->translator->setPrefix([]);
			return new Controls\FilesControl(
                $this->translator,$this->FilesManager, $this->UserManager, $this->id, 'cl_offer_id', NULL, $cl_company_id, $user_id,
				$this->CompaniesManager, $this->ArraysManager);
		}
		
		protected function createComponentListgrid()
		{
		    //$this->translator->setPrefix(['applicationModule.Offer']);
			$tmpParentData = $this->DataManager->find($this->id);
			//dump($this->settings->platce_dph);
			//die;
			if ($tmpParentData->price_e_type == 1) {
				$tmpProdej = $this->translator->translate("Prodej_s_DPH");
			} else {
				$tmpProdej = $this->translator->translate("Prodej_bez_DPH");
			}
			
			
			//29.12.2017 - adaption of names
			$userTmp = $this->UserManager->getUserById($this->getUser()->id);
			$userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
			$userTmpAdapt = json_decode($userCompany1->own_names, true);
			if (!isset($userTmpAdapt['cl_offer_items__description1'])) {
				$userTmpAdapt['cl_offer_items__description1'] = $this->translator->translate("Poznámka_1");
				
			}
			if (!isset($userTmpAdapt['cl_offer_items__description2'])) {
				$userTmpAdapt['cl_offer_items__description2'] = $this->translator->translate("Poznámka_2");
			}
			
			if ($this->settings->platce_dph == 1) {
				$arrData = ['position' => [$this->translator->translate('Pozice'), 'format' => 'text', 'size' => 15],
					'cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
					'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'],
					'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
					'units' => ['', 'format' => 'text', 'size' => 7],
					'price_s' => [$this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
					'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 7],
					'price_e' => [$tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
					'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
					'discount' => [$this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 10],
					'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 12],
					'vat' => [$this->translator->translate('DPH_%'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 7],
					'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 12],
					'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_offer.cl_currencies_id', 'cl_pricelist.price', 'cl_offer.cl_partners_book_id']],
					'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE]];
			} else {
				$arrData = ['position' => [$this->translator->translate('Pozice'), 'format' => 'text', 'size' => 15],
					'cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
					'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'],
					'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
					'units' => ['', 'format' => 'text', 'size' => 7],
					'price_s' => [$this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
					'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 7],
					'price_e' => [$tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
					'price_e_type' => [$this->translator->translate('Typ prodejni ceny'), 'format' => "hidden"],
					'discount' => [$this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 10],
					'price_e2' => [$this->translator->translate('Celkem'), 'format' => "number", 'size' => 12],
					'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_offer.cl_currencies_id', 'cl_pricelist.price', 'cl_offeer.cl_partners_book_id']],
					'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE]];
			}
            //$translator = clone $this->translator->setPrefix([]);
			$control = new Controls\ListgridControl(
                $this->translator,
				$this->OfferItemsManager,
				$arrData,
				[],
				$this->id,
				['units' => $this->settings->def_mj, 'vat' => $tmpParentData->vat],
				$this->DataManager,
				$this->PriceListManager,
				$this->PriceListPartnerManager,
				TRUE,
				['pricelist2' => $this->link('RedrawPriceList2!'),
					'activeTab' => 2
                ], //custom links
				TRUE, //movable row
				NULL, //ordercolumn
				FALSE, //selectmode
				[], //quicksearch
				"", //fontsize
				FALSE, //parentcolumnname
				TRUE //pricelistbottom
			);
            $control->setContainerHeight("auto");
            $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');
			$control->onChange[] = function () {
				$this->updateSum();
				
			};
			return $control;
			
		}
		
		
		protected function createComponentListgridItemsSelect()
		{
            //$this->translator->setPrefix(['applicationModule.Offer']);
			$tmpParentData = $this->DataManager->find($this->id);
			if ($tmpParentData && $tmpParentData->price_e_type == 1) {
				$tmpProdej = $this->translator->translate("Prodej_s_DPH");
			} else {
				$tmpProdej = $this->translator->translate("Prodej_bez_DPH");
			}
			
			//29.12.2017 - adaption of names
			$userTmp = $this->UserManager->getUserById($this->getUser()->id);
			$userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
			$userTmpAdapt = json_decode($userCompany1->own_names, true);
			if (!isset($userTmpAdapt['cl_offer_items__description1'])) {
				$userTmpAdapt['cl_offer_items__description1'] = $this->translator->translate("Poznámka_1");
				
			}
			if (!isset($userTmpAdapt['cl_offer_items__description2'])) {
				$userTmpAdapt['cl_offer_items__description2'] = $this->translator->translate("Poznámka_2");
			}
			
			if ($this->settings->platce_dph == 1)
				$arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
					'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 15],
					'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
					'units' => ['', 'format' => 'text', 'size' => 7],
					'price_s' => [$this->translator->translate('Nákup'), 'format' => "number", 'size' => 6, 'decplaces' => $this->settings->des_cena],
					'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 7],
					'price_e' => [$tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
					'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
					'discount' => [$this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 5],
					'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 12],
					'vat' => [$this->translator->translate('DPH %'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 5],
					'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 12],
					'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE]];
			else
				$arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
					'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 15],
					'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
					'units' => ['', 'format' => 'text', 'size' => 7],
					'price_s' => [$this->translator->translate('Nákup'), 'format' => "number", 'size' => 6, 'decplaces' => $this->settings->des_cena],
					'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 7],
					'price_e' => [$tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
					'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
					'discount' => [$this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 5],
					'price_e2' => [$this->translator->translate('Celkem'), 'format' => "number", 'size' => 12],
					'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE]];
            //$translator = clone $this->translator->setPrefix([]);
			$control = new Controls\ListgridControl(
                $this->translator,
				$this->OfferItemsManager,
				$arrData,
				[],
				$this->id,
				['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba],
				$this->DataManager,
				NULL, //pricelist manager
				$this->PriceListPartnerManager,
				FALSE, //add emtpy row
				['pricelist2' => $this->link('RedrawPriceList2!'),
					'activeTab' => 2
                ], //custom links,
				FALSE, //movable row
				NULL, //ordercolumn
				TRUE, //selectmode
				[], //quicksearch
				"", //fontsize
				FALSE, //parentcolumnname
				FALSE, //pricelistbottom
				FALSE, //readonly
				FALSE, //nodelete
				FALSE, //$enableSearch
				'', //$txtSearchCondition
				NULL, //$toolbar
				FALSE, //$forceEnable
				FALSE, //$paginatorOff
				[], //$colours
				20, //$pagelength
				'auto' //$containerHeight
			
			);
            $control->setHideTimestamps(TRUE);
            $control->setPaginatorOff();
            $control->showHistory(FALSE);
			//$control->onChange[] = function ()
			//  {
			//	$this->updateSum();
//
//	    };
			return $control;
			
		}
		
		
		protected function createComponentListgridTask()
		{
            //$this->translator->setPrefix(['applicationModule.Offer']);
			$arrUsers = [];
			$arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
			$arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
			
			$arrData = ['name' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 70],
                                'work_time' => [$this->translator->translate('Hodin'), 'format' => 'number', 'size' => 10],
                                'work_rate' => [$this->translator->translate('Sazba'), 'format' => 'number', 'size' => 10],
                                'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 10],
                                'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE],
            ];
			
			$now = new \Nette\Utils\DateTime;
            //$translator = clone $this->translator->setPrefix([]);
			$control = new Controls\ListgridControl(
                $this->translator,
				$this->OfferTaskManager,
				$arrData,
				[],
				$this->id,
				['work_date_s' => $now->format('Y.m.d H:i'), 'work_date_e' => $now->format('Y.m.d H:i')],
				$this->DataManager,
				FALSE,
				FALSE,
				TRUE,
				['activeTab' => 3

                ], //custom links
				TRUE, //movable row
				NULL, //ordercolumn
				FALSE, //selectmode
				[], //quicksearch
				"", //fontsize
				FALSE, //parentcolumnname
				TRUE //pricelistbottom
			);
            $control->setContainerHeight("auto");
            //$control->setPaginatorOff();
			$control->onChange[] = function () {
				$this->updateSum();
				
			};
			$control->onPrint[] = function ($itemId) {
				$this->reportTask($itemId);
				
			};
			return $control;
			
		}
		
		
		protected function createComponentListgridTasksSelect()
		{
            //$this->translator->setPrefix(['applicationModule.Offer']);
			$arrUsers = [];
			$arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
			$arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
			
			$arrData = [
				'name' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 70],
				'work_time' => [$this->translator->translate('Hodin'), 'format' => 'number', 'size' => 10],
				'work_rate' => [$this->translator->translate('Sazba'), 'format' => 'number', 'size' => 10],
				'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 10],
				'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE],
            ];
			
			$now = new \Nette\Utils\DateTime;
            //$translator = clone $this->translator->setPrefix([]);
			$control = new Controls\ListgridControl(
                $this->translator,
				$this->OfferTaskManager,
				$arrData,
				[],
				$this->id,
				['work_date_s' => $now->format('Y.m.d H:i'), 'work_date_e' => $now->format('Y.m.d H:i')],
				$this->DataManager,
				FALSE,
				FALSE,
				FALSE,
				['activeTab' => 3], //custom links
				FALSE, //movable row
				NULL, //ordercolumn
				TRUE, //selectmode
				[], //quicksearch
				"", //fontsize
				FALSE, //parentcolumnname
				FALSE, //pricelistbottom
				FALSE, //readonly
				FALSE, //nodelete
				FALSE, //$enableSearch
				'', //$txtSearchCondition
				NULL, //$toolbar
				FALSE, //$forceEnable
				FALSE, //$paginatorOff
				[], //$colours
				20, //$pagelength
				'auto' //$containerHeight
			);
            $control->setPaginatorOff();
			$control->setHideTimestamps(TRUE);
			$control->showHistory(FALSE);
			
			
			//$control->onChange[] = function ()
			//{
			//$this->updateSum();
			
			//};
			//$control->onPrint[] = function ($itemId)
			//{
			//$this->reportTask($itemId);
			
			//};
			return $control;
			
		}
		
		
		protected function startup()
		{
			parent::startup();
            //$this->translator->setPrefix(['applicationModule.Offer']);
			$this->formName = $this->translator->translate("Nabídky");
			$this->mainTableName = 'cl_offer';
			//$settings = $this->CompaniesManager->getTable()->fetch();
			if ($this->settings->platce_dph == 1)
				$arrData = ['cm_number' => $this->translator->translate('Číslo_nabídky'),
					'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
					'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
					'cl_partners_book.company' => [$this->translator->translate('Klient'), 'format' => 'text', 'show_clink' => true],
					'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
					'cm_title' => $this->translator->translate('Popis'),
					'offer_date' => [$this->translator->translate('Datum_nabídky'), 'format' => 'date'],
					'validity_days' => [$this->translator->translate('Platnost_dní'), 'format' => 'text'],
					'validity_date' => [$this->translator->translate('Platnost_do'), 'format' => 'date'],
                    's_eml' => ['E-mail', 'format' => 'boolean'],
					'cl_invoice.inv_number' => [$this->translator->translate('Faktura'), 'format' => 'text'],
					'cl_commission.cm_number' => [$this->translator->translate('Zakázka'), 'format' => 'text'],
					'price_e2_base' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
					'delivery_price' => [$this->translator->translate('Cena_dopravy'), 'format' => 'currency'],
					'cl_currencies.currency_name' => $this->translator->translate('Měna'),
					'mark1' => [$this->translator->translate('Naše_značka'), 'format' => 'text'],
					'mark2' => [$this->translator->translate('Vaše_značka'), 'format' => 'text'],
					'currency_rate' => $this->translator->translate('Kurz'),
					'cm_order' => $this->translator->translate('Objednávka'),
					'cl_users.name' => $this->translator->translate('Obchodník'),
					'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
			else
				$arrData = ['cm_number' => $this->translator->translate('Číslo_nabídky'),
					'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
					'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
					'cl_partners_book.company' => [$this->translator->translate('Klient'), 'format' => 'text', 'show_clink' => true],
					'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
					'cm_title' => $this->translator->translate('Popis'),
					'offer_date' => [$this->translator->translate('Datum_nabídky'), 'format' => 'date'],
					'validity_days' => [$this->translator->translate('Platnost_dní'), 'format' => 'text'],
					'validity_date' => [$this->translator->translate('Platnost_do'), 'format' => 'date'],
                    's_eml' => ['E-mail', 'format' => 'boolean'],
					'cl_invoice.inv_number' => [$this->translator->translate('Faktura'), 'format' => 'text'],
                    'cl_commission.cm_number' => [$this->translator->translate('Zakázka'), 'format' => 'text'],
                    'price_e2_base' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                    'delivery_price' => [$this->translator->translate('Cena_dopravy'), 'format' => 'currency'],
                    'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                    'mark1' => [$this->translator->translate('Naše_značka'), 'format' => 'text'],
                    'mark2' => [$this->translator->translate('Vaše_značka'), 'format' => 'text'],
                    'currency_rate' => $this->translator->translate('Kurz'),
                    'cm_order' => $this->translator->translate('Objednávka'),
                    'cl_users.name' => $this->translator->translate('Obchodník'),
                    'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
			
			$this->dataColumns = $arrData;
			//$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
			//$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
			//$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
			$this->filterColumns = ['cm_number' => 'autocomplete', 'cl_status.status_name' => 'autocomplete', 'cl_partners_book.company' => 'autocomplete', 'cl_invoice.inv_number' => 'autocomplete',
				'cm_order' => 'autocomplete', 'cm_title' => 'autocomplete', 'cl_users.name' => 'autocomplete', 'cl_center.name' => 'autocomplete',
				'cl_partners_branch.b_name' => 'autocomplete', 'offer_date' => '', 'validity_days' => '', 'validity_day' => '', 'price_e2_base' => ''];
			
			$this->userFilterEnabled = TRUE;
			$this->userFilter = ['cm_number', 'cm_title', 'cm_order', 'cl_partners_book.company', 'price_e2_vat', 'price_e2', 'mark1', 'mark2'];

            $this->cxsEnabled = TRUE;
            $this->userCxsFilter = [':cl_offer_items.item_label', ':cl_offer_items.cl_pricelist.identification',
                ':cl_offer_items.cl_pricelist.item_label', ':cl_offer_items.description1', ':cl_offer_items.description2',
                ':cl_offer_task.name', ':cl_offer_task.description',
                ':cl_offer_work.work_label'];

			$this->DefSort = 'offer_date DESC';
			
			
			//if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;
			
			
			$this->defValues = ['offer_date' => new \Nette\Utils\DateTime,
				'cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id,
				'cl_currencies_id' => $this->settings->cl_currencies_id,
				'currency_rate' => $this->settings->cl_currencies->fix_rate,
				'header_show' => $this->settings->header_show_cm,
				'header_txt' => $this->settings->header_txt_cm,
				'vat' => $this->settings->offer_vat_def,
				'price_e_type' => $this->settings->price_e_type,
				'offer_vat_off' => $this->settings->offer_vat_off,
				'cl_users_id' => $this->user->getId()];
			
			//$this->numberSeries = 'offer';
			$this->numberSeries = ['use' => 'offer', 'table_key' => 'cl_number_series_id', 'table_number' => 'cm_number'];
			$this->readOnly = ['cm_number' => TRUE,
				'created' => TRUE,
				'create_by' => TRUE,
				'changed' => TRUE,
				'change_by' => TRUE];
			
			
			/*$this->toolbar = array(	0 => array('group_start' => ''),
				1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový dodací list', 'class' => 'btn btn-primary'),
				2 => $this->getNumberSeriesArray('delivery_note'),
				3 => array('group_end' => ''));*/
			
			$this->toolbar = [0 => ['group_start' => ''],
				1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'],
				2 => $this->getNumberSeriesArray('offer'),
				3 => ['group_end' => ''],
				4 => ['group' =>
					[0 => ['url' => $this->link('report!', ['index' => 1]), 'rightsFor' => 'report', 'label' => $this->translator->translate('applicationModule.offer.report_offers'), 'title' => $this->translator->translate('applicationModule.offer.report_offers_title'),
						'class' => 'ajax', 'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-print'],
                    ],
					'group_settings' => ['group_label' => $this->translator->translate('Tisk'), 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' => 'tisk', 'group_icon' => 'iconfa-print']
                ]
            ];
			
			
			$this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/Offer/rptOffersSet.latte',
				'reportName' => $this->translator->translate('applicationModule.offer.report_offers_title2')],
				2 => ['reportLatte' => __DIR__ . '/../templates/Helpdesk/helpdeskReportWorkers.latte',
					'reportName' => $this->translator->translate('Přehled podle techniků')]];
			
			
			//$this->showChildLink = 'PartnersEvent:default';
			//Condition for color highlit rows
			//$testDate = new \Nette\Utils\DateTime;
			//$testDate = $testDate->modify('-30 day');
			//$this->conditionRows = array( 'cdate','<=',$testDate);
			//$this->rowFunctions = array('copy' => 'disabled');
			
			$this->bscOff = FALSE;
            $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
			$this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Offer\card.latte'],
				'items' => ['active' => true, 'name' => $this->translator->translate('položky'), 'lattefile' => $this->getLattePath() . 'Offer\items.latte'],
				'works' => ['active' => false, 'name' => $this->translator->translate('práce'), 'lattefile' => $this->getLattePath() . 'Offer\tasks.latte'],
				'header' => ['active' => false, 'name' => $this->translator->translate('záhlaví'), 'lattefile' => $this->getLattePath() . 'Offer\header.latte'],
				'assignment' => ['active' => false, 'name' => $this->translator->translate('zápatí'), 'lattefile' => $this->getLattePath() . 'Offer\footer.latte'],
				'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Offer\description.latte'],
				'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Offer\files.latte']
            ];
			$this->bscSums = ['lattefile' => $this->getLattePath() . 'Offer\sums.latte'];
			$this->bscToolbar = [
				1 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('časté_texty'), 'class' => 'btn btn-success showTextsUse',
					'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'], 'icon' => 'glyphicon glyphicon-list'],
				2 => ['url' => 'createCommissionModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('vytvořit_zakázku'), 'class' => 'btn btn-success',
					'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
				3 => ['url' => 'createInvoiceModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('vytvořit_fakturu'), 'class' => 'btn btn-success',
					'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
				4 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
					'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
				5 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
					'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
				6 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
					'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
				7 => ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => $this->translator->translate('E-mail'), 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'],

            ];
			$this->bscTitle = ['cm_number' => $this->translator->translate('Číslo nabídky'), 'cl_partners_book.company' => $this->translator->translate('Odběratel')];
			
			
			//17.08.2018 - settings for documents saving and emailing
			$this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/Offer/offerv1.latte');
			$this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
			$this->docTitle = ["", "cl_partners_book.company", "cm_number"];
			//17.08.2018 - settings for sending doc by email
			$this->docEmail = ['template' => __DIR__ . '/../templates/Offer/emailOffer.latte',
				'emailing_text' => 'offer'];
			
			//27.08.2018 - filter for show only not used items and tasks to create commission
			$this->filterCommissionUsed = ['filter' => 'cl_commission_id IS NULL'];
			$this->filterInvoiceUsed = ['filter' => 'cl_invoice_id IS NULL'];
			
			$this->quickFilter = ['cl_status.status_name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
				'values' => $this->StatusManager->findAll()->where('status_use = ?', 'commission')->order('s_new DESC,s_work DESC,s_fin DESC,s_storno DESC,status_name ASC')->fetchPairs('id', 'status_name')]
            ];

            if ( $this->isAllowed($this->presenter->name,'report')) {
                $this->groupActions['pdf'] = 'stáhnout PDF';
            }

		}
		
		public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
		{
			parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
			//dump($this->conditionRows);
			//die;
			
		}
		
		public function renderEdit($id, $copy, $modal)
		{
			parent::renderEdit($id, $copy, $modal);
			
			/*if ($defData = $this->DataManager->findOneBy(array('id' => $id))) {
				$this['headerEdit']->setValues($defData);
				$this['descriptionEdit']->setValues($defData);
			}*/
			
		}
		
		
		protected function createComponentEdit($name)
		{
			$form = new Form($this, $name);
			//$form->setMethod('POST');
			$form->addHidden('id', NULL);
			$form->addText('cm_number', $this->translator->translate('Číslo_nabídky'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm')
				->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_nabídky'));
			$form->addText('offer_date', $this->translator->translate('Datum_nabídky'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
				->setHtmlAttribute('placeholder', $this->translator->translate('Datum_přijetí'));
			$form->addText('validity_date', $this->translator->translate('Platnost_do'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
				->setHtmlAttribute('placeholder', $this->translator->translate('Datum_dodání'));
			$form->addText('validity_days', $this->translator->translate('Platnost_dní'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm');
			$form->addTextArea('cm_title', $this->translator->translate('Popis_nabídky'), 150, 4)
				->setHtmlAttribute('class', 'form-control input-sm')
				->setHtmlAttribute('placeholder', $this->translator->translate('Popis_zakázky'));
			$form->addText('cm_order', $this->translator->translate('Číslo_objednávky'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm')
				->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_objednávky'));
			
			$form->addText('mark1', $this->translator->translate('Naše_značka'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm')
				->setHtmlAttribute('placeholder', $this->translator->translate('naše_značka'));
			$form->addText('mark2', $this->translator->translate('Vaše_značka'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm')
				->setHtmlAttribute('placeholder', $this->translator->translate('vaše_značka'));
			
			$form->addText('delivery_period', $this->translator->translate('Dodací_lhůta'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm');
			$arrDeliveryPeriodType = $this->ArraysManager->getDeliveryPeriodType();
			$form->addSelect('delivery_period_type', " : ", $arrDeliveryPeriodType)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Typ_dodací_lhůty'))
				->setPrompt($this->translator->translate('Zvolte_typ_dodací_lhůty'));
			
			$form->addText('terms_delivery', $this->translator->translate('Dodací_podmínky'), 200, 200)
				->setHtmlAttribute('class', 'form-control input-sm');
			$form->addText('terms_payment', $this->translator->translate('Platební_podmínky'), 200, 200)
				->setHtmlAttribute('class', 'form-control input-sm');
			
			$form->addText('delivery_price', $this->translator->translate('Cena_dopravy'), 20, 20)
				->setHtmlAttribute('class', 'form-control input-sm');
			
			$form->addCheckbox('total_sum_off', $this->translator->translate('Celkové_součty_vypnuty'))
				->setDefaultValue(FALSE)
				->setHtmlAttribute('class', 'form-control input-sm');
			
			$form->addCheckbox('offer_vat_off', $this->translator->translate('DPH_pro_tisk_vypnuto'))
				->setDefaultValue(FALSE)
				->setHtmlAttribute('class', 'form-control input-sm');
			
			$arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
			$form->addSelect('cl_center_id', $this->translator->translate("Středisko"), $arrCenter)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
				->setPrompt($this->translator->translate('Zvolte_středisko'));
			
			$arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'offer')->fetchPairs('id', 'status_name');
			$form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_nabídky'))
				->setPrompt($this->translator->translate('Zvolte_stav_nabídky'));
			
			
			//28.12.2018 - have to set $tmpId for found right record it could be bscId or id
			if ($this->id == NULL) {
				$tmpId = $this->bscId;
			} else {
				$tmpId = $this->id;
			}
			if ($tmpInvoice = $this->DataManager->find($tmpId)) {
				if (isset($tmpInvoice['cl_partners_book_id'])) {
					$tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
				} else {
					$tmpPartnersBookId = 0;
				}
				
			} else {
				$tmpPartnersBookId = 0;
			}
			$arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');
			//$arrPartners = $this->PartnersManager->findAll()->fetchPairs('id','company');
			//dump($arrPartners);
			
			
			//$mySection = $this->getSession('selectbox'); // returns SessionSection with given name
			//06.07.2018 - session selectbox is filled via baselist->handleUpdatePartnerInForm which is called by ajax from onchange event of selectbox
			//this is necessary because Nette is controlling values of selectbox send in form with values which were in selectbox accesible when it was created.
			//if (isset($mySection->cl_partners_book_id_values ))
			//{
			//$arrPartners = 	$mySection->cl_partners_book_id_values;
			//}else{
			//$arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
			//}
			
			
			$form->addSelect('cl_partners_book_id', $this->translator->translate("Klient"), $arrPartners)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_klienta'))
				->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
				->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
				->setPrompt($this->translator->translate('Zvolte_klienta'));
			
			
			$arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
			$form->addSelect('cl_partners_book_workers_id', $this->translator->translate("Kontakt"), $arrWorkers)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
				->setPrompt($this->translator->translate('Zvolte_kontaktní_osobu'));
			
			$arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $tmpPartnersBookId)->fetchPairs('id', 'b_name');
			$form->addSelect('cl_partners_branch_id', $this->translator->translate("Pobočka"), $arrBranch)
				->setPrompt($this->translator->translate('Zvolte_pobočku'))
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Pobočka'));
			
			
			$arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
			$form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_měnu'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm')
				->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
				->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
				->setPrompt($this->translator->translate('Zvolte_měnu'));
			$form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
				->setHtmlAttribute('class', 'form-control input-sm')
				->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
				->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));
			$arrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates');
			if ($this->settings->platce_dph) {
				$form->addSelect('vat', $this->translator->translate("Sazba_DPH"), $arrVat)
					->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_DPH'))
					->setHtmlAttribute('class', 'form-control chzn-select input-sm')
					->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
					->setPrompt($this->translator->translate('Zvolte_DPH'))
					->setRequired($this->translator->translate('Sazba_DPH_musí_být_zvolena'));
			}
			
			$arrUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
			$form->addSelect('cl_users_id', $this->translator->translate("Obchodník"), $arrUsers)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka'))
				->setPrompt($this->translator->translate('Zvolte_obchodníka'));
			
			//$form->addText('created', 'Datum vytvoření:', 10, 10)
			//	    	->setHtmlAttribute('class','form-control input-sm')
			//		->setHtmlAttribute('placeholder','Datum vytvoření');
			//$form->addText('create_by', 'Vytvořil:', 20, 20)
			//	    	->setHtmlAttribute('class','form-control input-sm')
			//		->setHtmlAttribute('placeholder','Vytvořil');
			//$form->addText('changed', 'Datum změny:', 10, 10)
			//	    	->setHtmlAttribute('class','form-control input-sm')
			//		->setHtmlAttribute('placeholder','Datum změny');
			//$form->addText('change_by', 'Změnil:', 20, 20)
			//	    	->setHtmlAttribute('class','form-control input-sm')
			//		->setHtmlAttribute('placeholder','Změnil');
			
			$form->onValidate[] = [$this, 'FormValidate'];
			$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
			$form->addSubmit('create_invoice', $this->translator->translate('Vytvořit fakturu'))->setHtmlAttribute('class', 'btn btn-primary');
			$form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-primary');
			$form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-primary');
			$form->addSubmit('back', $this->translator->translate('Zpět'))
				->setHtmlAttribute('class', 'btn btn-primary')
				->setValidationScope([])
				->onClick[] = [$this, 'stepBack'];
			//	    ->onClick[] = callback($this, 'stepSubmit');
			
			$form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
			return $form;
		}
		
		
		public function FormValidate(Form $form)
		{
            $data=$form->values;
            /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
            */
            $data = $this->updatePartnerId($data);
            if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
            {
                $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
            }
            $this->redrawControl('content');

			
		}
		
		
		public function stepBack()
		{
			//06.07.2018 - unset value of selectbox from session. Selectbox must be filled with default values
			$mySection = $this->getSession('selectbox');
			unset($mySection['cl_partners_book_id_values']);
			
			$this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'danger');
			$this->redirect('default');
		}
		
		public function SubmitEditSubmitted(Form $form)
		{
			$data = $form->values;
			//06.07.2018 - unset value of selectbox from session. Selectbox must be filled with default values
			$mySection = $this->getSession('selectbox');
			unset($mySection['cl_partners_book_id_values']);
			
			if ($form['send']->isSubmittedBy()) {
				$data = $this->RemoveFormat($data);
				
				$myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
				if (!($myReadOnly))
					$myReadOnly = false;
				
				//if (!$myReadOnly)
				//{//if record is not marked as finished, we can save edited data
				if (!empty($data->id)) {
					$this->DataManager->update($data);
					$this->UpdateSum();
					$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
				}
				
				//}

					//$this->redirect('default');
//		    $this->redrawControl('flash');
					//$this->redrawControl('formedit');
					//$this->redrawControl('timestamp');
					//$this->redrawControl('items');
					//$this->redirect('default');
					$this->redrawControl('content');
					// throw new Exception();

				
			} else {
				$this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
				//$this->redrawControl('flash');
				//$this->redrawControl('formedit');
				//$this->redrawControl('timestamp');
				//$this->redrawControl('items');
				//$this->redirect('default');
				
				//$this->redirect('default');
				$this->redrawControl('content');
			}
			
		}
		
		public function handleMakeRecalc($idCurrency, $rate, $oldrate, $recalc, $vat)
		{
			//in future there can be another work with rates
			//dump($this->editId);
			if ($rate > 0) {
				if ($recalc == 1) {
					$recalcItems = $this->OfferItemsManager->findBy(['cl_offer_id', $this->id]);
					foreach ($recalcItems as $one) {
						//'price_e' => array('Cena bez DPH',"number",'size' => 10),
						//'discount' => array('Sleva %',"number",'size' => 10),'price_e2' => array('Celkem bez DPH',"number"),
						//'vat' => array('Sazba DPH',"number",'values' => array($this->RatesVatManager->findAllValid()->fetchPairs('rates','rates')),'size' => 10),
						//'price_e2_vat' => array('Celkem s DPH',"number")),
						$data = [];
						$data['price_s'] = $one['price_s'] * $oldrate / $rate;
						$data['price_e'] = $one['price_e'] * $oldrate / $rate;
						$data['price_e2'] = $one['price_e2'] * $oldrate / $rate;
						$data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;
						$one->update($data);
					}
					$recalcWorks = $this->OfferWorkManager->findBy(['cl_offer_id', $this->id]);
					foreach ($recalcWorks as $one) {
						$data = [];
						$data['work_rate'] = $one['work_rate'] * $oldrate / $rate;
						$one->update($data);
					}
				}
				
				//we must save parent data
				$parentData = new \Nette\Utils\ArrayHash;
				$parentData['id'] = $this->id;
				if ($vat != 0) {
					$parentData['vat'] = $vat;
				} else {
					$parentData['vat'] = NULL;
				}
				
				if ($rate <> $oldrate) {
					$parentData['currency_rate'] = $rate;
				}
				
				$parentData['cl_currencies_id'] = $idCurrency;
				$this->DataManager->update($parentData);
				
				
				$this->UpdateSum();
				/*$price_s = $this->OfferItemsManager->findBy(array('cl_offer_id' => $this->id))->sum('price_s*quantity');
				$price_e2 = $this->OfferItemsManager->findBy(array('cl_offer_id' => $this->id))->sum('price_e2');
				$price_e2_vat = $this->OfferItemsManager->findBy(array('cl_offer_id' => $this->id))->sum('price_e2_vat');
				$parentData = new \Nette\Utils\ArrayHash;
				$parentData['id'] = $this->id;
				$parentData['price_s'] = $price_s;
				$parentData['price_e2'] = $price_e2;
				$parentData['price_e2_vat'] = $price_e2_vat;
				$parentData['cl_currencies_id'] = $idCurrency;
				$parentData['currency_rate'] = $rate;
				$this->DataManager->update($parentData);
				 */
			}
			$this->redrawControl('items');
			
		}
		
		
		public function updateSum()
		{
			$this->DataManager->updateSum($this->id);
			parent::UpdateSum();
			//$this['sumOnDocs']->redrawControl();
			
			//$this->redrawControl('baselistArea');
			//$this->redrawControl('bscArea');
			//$this->redrawControl('bsc-child');
			
			$this['listgrid']->redrawControl('editLines');
			//$this['sumOnDocs']->redrawControl();
		}
		
		
		/*
		 * modify data before addline
		 */
		public function beforeAddLine($data)
		{
			$data['price_e_type'] = $this->settings->price_e_type;
			return ($data);
		}
		
		
		public function ListGridInsert($sourceData)
		{
			$arrPrice = new \Nette\Utils\ArrayHash;
			//if (isset($sourceData['cl_pricelist_id']))
            if (array_key_exists('cl_pricelist_id',$sourceData->toArray())){
				$arrPrice['id'] = $sourceData['cl_pricelist_id'];
				$sourcePriceData = $this->PriceListManager->find($sourceData->cl_pricelist_id);
			} else {
				$arrPrice['id'] = $sourceData['id'];
				$sourcePriceData = $this->PriceListManager->find($sourceData->id);
			}
            $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];
			///04.09.2017 - find price if there are defince prices_groups
			$tmpData = $this->DataManager->find($this->id);
			if (isset($tmpData['cl_partners_book_id'])
				&& $tmpPrice = $this->PricesManager->getPrice($tmpData->cl_partners_book,
					$arrPrice['id'],
					$tmpData->cl_currencies_id,
					$this->settings['cl_storage_id_sale']))
            {
                $arrPrice['price']          = $tmpPrice['price'];
                $arrPrice['price_vat']      = $tmpPrice['price_vat'];
                $arrPrice['discount']       = $tmpPrice['discount'];
                $arrPrice['price_e2']       = $tmpPrice['price_e2'];
                $arrPrice['price_e2_vat']   = $tmpPrice['price_e2_vat'];
                $arrPrice['cl_currencies_id'] = $tmpPrice['cl_currencies_id'];
            }else{
                $arrPrice['price']          = $sourceData->price;
                $arrPrice['price_vat']      = $sourceData->price_vat;
                $arrPrice['discount']       = 0;
                $arrPrice['price_e2']       = $sourceData->price;
                $arrPrice['price_e2_vat']   = $sourceData->price_vat;
              //  $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
            }
			$arrPrice['vat'] = $sourceData->vat;

			
			$arrData = new \Nette\Utils\ArrayHash;
			$arrData[$this->DataManager->tableName . '_id'] = $this->id;
			//$arrData['cl_pricelist_id'] = $sourceData->id;
			$arrData['cl_pricelist_id'] = $sourcePriceData->id;
			$arrData['item_order'] = $this->OfferItemsManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;
			//$arrData['item_label'] = $sourceData->item_label;
			$arrData['item_label'] = $sourcePriceData->item_label;
			$arrData['quantity'] = 1;
			//$arrData['units'] = $sourceData->unit;
			$arrData['units'] = $sourcePriceData->unit;
			//$arrData['price_s'] = $sourceData->price_s;
			//01.06.2017 FiFo x VAP
			//for now without solution, because we don't know from which store will be item used
			$arrData['price_s'] = $sourcePriceData->price_s;
			//$arrData['price_e'] = $sourceData->price;
			//$arrData['price_e2'] = $sourceData->price;
			//$arrData['price_e2_vat'] = $sourceData->price_vat;
			//$arrData['vat'] = $sourceData->vat;
			
			
			$arrData['price_e_type'] = $this->settings->price_e_type;
			if ($arrData['price_e_type'] == 1) {
				$arrData['price_e'] = $arrPrice['price_vat'];
			} else {
				$arrData['price_e'] = $arrPrice['price'];
			}
			//$arrData['price_e'] = $arrPrice['price'];
            $arrData['discount'] = $arrPrice['discount'];
            $arrData['price_e2'] = $arrPrice['price_e2'];
            $arrData['price_e2_vat'] = $arrPrice['price_e2_vat'];

			$arrData['vat'] = $arrPrice['vat'];
			$arrData['profit'] = round((($arrData['price_e'] / $arrData['price_s']) - 1) * 100,2);

			//prepocet kurzem
			//potrebujeme kurz ceníkove polozky a kurz zakazky
			if ($sourceData->cl_currencies_id != NULL)
				$ratePriceList = $sourceData->cl_currencies->fix_rate;
			else
				$ratePriceList = 1;
			
			if ($tmpOffer = $this->DataManager->find($this->id))
				$rateOffer = $tmpOffer->currency_rate;
			else
				$rateOffer = 1;

            if ( $arrPrice['cl_currencies_id'] != $tmpOffer['cl_currencies_id'] ) {
                $arrData['price_s'] = $arrData['price_s'] * $ratePriceList / $rateOffer;
                $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOffer;
                $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOffer;
                $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOffer;
            }
			
			$row = $this->OfferItemsManager->insert($arrData);
			$this->updateSum();
			return ($row);
			
		}
		
		//javascript call when changing cl_partners_book_id
		public function handleRedrawPriceList2($cl_partners_book_id)
		{
			//dump($cl_partners_book_id);
			$arrUpdate = new \Nette\Utils\ArrayHash;
			$arrUpdate['id'] = $this->id;
			$arrUpdate['cl_partners_book_id'] = $cl_partners_book_id;
			
			//dump($arrUpdate);
			//die;
			$this->DataManager->update($arrUpdate);
			
			$this['listgrid']->redrawControl('pricelist2');
		}
		
		
		public function emailSetStatus()
		{
			$this->setStatus($this->id, ['status_use' => 'offer',
				's_new' => 0,
				's_eml' => 1]);
		}
		
		
		protected function createComponentHeaderEdit($name)
		{
			$form = new Form($this, $name);
			$form->addHidden('id', NULL);
			//$form->addCheckbox('header_show', 'Tiskount záhlaví');
			$form->addTextArea('header_txt', $this->translator->translate('Záhlaví'), 100, 20)
				->setHtmlAttribute('placeholder', $this->translator->translate('Záhlaví'));
			$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
			$form->addSubmit('back', $this->translator->translate('Zpět'))
				->setHtmlAttribute('class', 'btn btn-primary')
				->setValidationScope([])
				->onClick[] = [$this, 'stepHeaderBack'];
			$form->onSuccess[] = [$this, 'SubmitEditHeaderSubmitted'];
			return $form;
		}
		
		public function stepHeaderBack()
		{
			$this->headerModalShow = FALSE;
			$this->activeTab = 5;
			$this->redrawControl('headerModalControl');
		}
		
		public function SubmitEditHeaderSubmitted(Form $form)
		{
			$data = $form->values;
			//later there must be another condition for user rights, admin can edit everytime
			if ($form['send']->isSubmittedBy()) {
				$this->DataManager->update($data);
			}
			$this->headerModalShow = FALSE;
			$this->activeTab = 5;
			$this->redrawControl('items');
			$this->redrawControl('header_txt');
			$this->redrawControl('headerModalControl');
		}
		
		public function handleCmHeaderShow($value)
		{
			$arrData = new \Nette\Utils\ArrayHash;
			$arrData['id'] = $this->id;
			//Debugger::fireLog($value);
			if ($value == 'true')
				$arrData['header_show'] = 1;
			else
				$arrData['header_show'] = 0;
			
			$this->DataManager->update($arrData);
			
			$this->terminate();
		}
		
		public function handleHeaderShow()
		{
			$this->headerModalShow = TRUE;
			$this->redrawControl('headerModalControl');
		}
		
		
		protected function createComponentDescriptionEdit($name)
		{
			$form = new Form($this, $name);
			$form->addHidden('id', NULL);
			$form->addTextArea('description_txt', $this->translator->translate('Zadání'), 100, 20)
				->setHtmlAttribute('placeholder', $this->translator->translate('Zadání'));
			$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
			$form->addSubmit('back', $this->translator->translate('Zpět'))
				->setHtmlAttribute('class', 'btn btn-primary')
				->setValidationScope([])
				->onClick[] = [$this, 'stepDescriptionBack'];
			$form->onSuccess[] = [$this, 'SubmitEditDescriptionSubmitted'];
			return $form;
		}
		
		public function stepDescriptionBack()
		{
			$this->descriptionModalShow = FALSE;
			$this->activeTab = 4;
			$this->redrawControl('descriptionModalControl');
		}
		
		public function SubmitEditDescriptionSubmitted(Form $form)
		{
			$data = $form->values;
			//later there must be another condition for user rights, admin can edit everytime
			if ($form['send']->isSubmittedBy()) {
				$this->DataManager->update($data);
			}
			$this->descriptionModalShow = FALSE;
			$this->activeTab = 4;
			$this->redrawControl('items');
			$this->redrawControl('description_txt');
			$this->redrawControl('descriptionModalControl');
		}
		
		public function handleCmDescriptionShow($value)
		{
			$arrData = new \Nette\Utils\ArrayHash;
			$arrData['id'] = $this->id;
			//Debugger::fireLog($value);
			if ($value == 'true') {
				$arrData['description_show'] = 1;
			} else {
				$arrData['description_show'] = 0;
			}
			
			$this->DataManager->update($arrData);
			
			$this->terminate();
		}
		
		public function handleDescriptionShow()
		{
			$this->descriptionModalShow = TRUE;
			$this->redrawControl('descriptionModalControl');
		}
		
		//control method to determinate if we can delete
		public function beforeDelete($lineId)
		{
			//07.05.2017 - if line is from helpdesk, we must delete connection
			if ($tmpLine = $this->OfferWorkManager->find($lineId)) {
				if (!is_null($tmpLine->cl_partners_event_id)) {
					$this->PartnersEventManager->find($tmpLine->cl_partners_event_id)->update(array('cl_offer_id' => NULL));
					$tmpLine->update(['cl_partners_event_id' => NULL]);
				}
			}
			
			
			$result = TRUE;
			return $result;
		}
		
		//aditional control before delete from baseList
		public function beforeDeleteBaseList($id)
		{
			foreach ($this->DataManager->find($id)->related('cl_offer_work') as $one) {
				//07.05.2017 - if line is from helpdesk, we must delete connection
				if (!is_null($one->cl_partners_event_id)) {
					$this->PartnersEventManager->find($one->cl_partners_event_id)->update(array('cl_offer_id' => NULL));
					$one->update(['cl_partners_event_id' => NULL]);
				}
				
			}
			return TRUE;
		}
		
		
		private function createInvoice()
		{
			if ($tmpData = $this->DataManager->find($this->id)) {
				if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
					$tmpInvoiceType = $tmpInvoiceType->id;
				} else {
					$tmpInvoiceType = NULL;
				}
				//default values for invoice
				$defDueDate = new \Nette\Utils\DateTime;
				$arrInvoice = new \Nette\Utils\ArrayHash;
				$arrInvoice['cl_currencies_id'] = $this->settings->cl_currencies_id;
				$arrInvoice['currency_rate'] = $this->settings->cl_currencies->fix_rate;
				$arrInvoice['vat_active'] = $this->settings->platce_dph;
				
				$arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
				$arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
				$arrInvoice['currency_rate'] = $tmpData->currency_rate;
				$arrInvoice['cl_offer_id'] = $tmpData->id;
				$arrInvoice['price_e_type'] = $tmpData->price_e_type;
				$arrInvoice['inv_date'] = new \Nette\Utils\DateTime;
				$arrInvoice['vat_date'] = new \Nette\Utils\DateTime;
				
				$arrInvoice['konst_symb'] = $this->settings->konst_symb;
				$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
				//$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
				
				$arrInvoice['header_show'] = $this->settings->header_show;
				$arrInvoice['footer_show'] = $this->settings->footer_show;
				$arrInvoice['header_txt'] = $this->settings->header_txt;
				$arrInvoice['footer_txt'] = $this->settings->footer_txt;
				
				
				//settings for concrete partner
				if ($tmpData->cl_partners_book->due_date > 0)
					$strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
				else
					$strModify = '+' . $this->settings->due_date . ' day';
				
				$arrInvoice['due_date'] = $defDueDate->modify($strModify);
				
				if (isset($tmpData->cl_partners_book->cl_payment_types_id)) {
					$clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
					$spec_symb = $tmpData->cl_partners_book->spec_symb;
				} else {
					$clPayment = $this->settings->cl_payment_types_id;
					$spec_symb = "";
				}
				$arrInvoice['cl_payment_types_id'] = $clPayment;
				$arrInvoice['spec_symb'] = $spec_symb;
				
				//create or update invoice
				if ($tmpData->cl_invoice_id == NULL) {
					//new number
					$nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
					$arrInvoice['inv_number'] = $nSeries['number'];
					$arrInvoice['cl_number_series_id'] = $nSeries['id'];
					$arrInvoice['var_symb'] = preg_replace('/\D/', '', $arrInvoice['inv_number']);
					$tmpStatus = 'invoice';
					if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
						$arrInvoice['cl_status_id'] = $nStatus->id;
					
					$row = $this->InvoiceManager->insert($arrInvoice);
					$this->DataManager->update(array('id' => $this->id, 'cl_invoice_id' => $row->id));
					$invoiceId = $row->id;
				} else {
					$arrInvoice['id'] = $tmpData->cl_invoice_id;
					$row = $this->InvoiceManager->update($arrInvoice);
					$invoiceId = $tmpData->cl_invoice_id;
				}
				$this->PartnersManager->useHeaderFooter($invoiceId, $arrInvoice['cl_partners_book_id'], $this->InvoiceManager);
				
				//now content of invoice
				//at first, delete old content
				//next insert new content
				$this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId))->delete();
				$tmpItems = $tmpData->related('cl_offer_items');
				$lastOrder = 0;
				foreach ($tmpItems as $one) {
					$newItem = new \Nette\Utils\ArrayHash;
					$newItem['cl_invoice_id'] = $invoiceId;
					$newItem['item_order'] = $one->item_order;
					$newItem['cl_pricelist_id'] = $one->cl_pricelist_id;
					$newItem['item_label'] = $one->item_label;
					$newItem['quantity'] = $one->quantity;
					$newItem['units'] = $one->units;
					$newItem['price_s'] = $one->price_s;
					$newItem['price_e'] = $one->price_e;
					$newItem['discount'] = $one->discount;
					$newItem['price_e2'] = $one->price_e2;
					$newItem['vat'] = $one->vat;
					$newItem['price_e2_vat'] = $one->price_e2_vat;
					$newItem['price_e_type'] = $one->price_e_type;
					$this->InvoiceItemsManager->insert($newItem);
					$lastOrder = $one->item_order;
				}
				
				$tmpWorks = $tmpData->related('cl_offer_work');
				
				foreach ($tmpWorks as $one) {
					$newItem = new \Nette\Utils\ArrayHash;
					$newItem['cl_invoice_id'] = $invoiceId;
					$newItem['item_order'] = $lastOrder + $one->item_order;
					$newItem['cl_pricelist_id'] = NULL;
					if (isset($one->cl_users->name))
						$tmpLabel = $one->work_label . ":" . $one->cl_users->name;
					else
						$tmpLabel = $one->work_label;
					
					$newItem['item_label'] = $tmpLabel;
					$newItem['quantity'] = $one->work_time;
					$newItem['units'] = 'hod.';
					$newItem['price_s'] = 0;
					$newItem['discount'] = 0;
					if ($tmpData->price_e_type == 0 || $this->settings->platce_dph == 0) {
						$newItem['price_e'] = $one->work_rate;
					} else {
						$calcVat = round(($one->work_rate) * ($tmpData->vat / 100), 2);
						$newItem['price_e'] = $one->work_rate + $calcVat;
					}
					
					$newItem['price_e2'] = $one->work_rate * $one->work_time;
					
					$newItem['vat'] = $tmpData->vat;
					$calcVat = round(($one->work_rate * $one->work_time) * ($tmpData->vat / 100), 2);
					$newItem['price_e2_vat'] = ($one->work_rate * $one->work_time) + $calcVat;
					$this->InvoiceItemsManager->insert($newItem);
				}
				//InvoicePresenter::updateSum($invoiceId,$this);
				$this->InvoiceManager->updateInvoiceSum($invoiceId);
				
				$this->flashMessage($this->translator->translate('Změny byly uloženy a faktura byla vytvořena'), 'success');
				//$this->redirect('Offer:default');
				$this->redirect('Invoice:edit', $invoiceId);
			}
			
		}
		
		
		public function handlePairedDocs()
		{
			$this->pairedDocsShow = TRUE;
			$this->redrawControl('pairedDocs');
		}
		
		public function handleDeletePaired($id, $type)
		{
			if ($type == 'cl_store_docs') {
				if ($data = $this->DataManager->find($id)) {
					$data->update(['id' => $id, 'cl_store_docs_id' => NULL]);
					$this->flashMessage($this->translator->translate('Vazba_na_výdejku_byla_zrušena_Výdejka_však_stále_existuje'), 'success');
				}
			} elseif ($type == 'cl_invoice') {
				if ($data = $this->DataManager->find($id)) {
					$data->update(['id' => $id, 'cl_invoice_id' => NULL]);
					$this->flashMessage($this->translator->translate('Vazba_na_fakturu_byla_zrušena_Faktura_však_stále_existuje'), 'success');
				}
			}
			$this->pairedDocsShow = TRUE;
			//$this->redrawControl('pairedDocs');
			$this->redirect(':edit');
		}
		
		
		public function handleShowPairedDocs()
		{
			//bdump('ted');
			$this->pairedDocsShow = TRUE;
			/*$this->showModal('pairedDocsModal');
			$this->redrawControl('pairedDocs');
			$this->redrawControl('contents');*/
            $this->redrawControl('bscAreaEdit');
            $this->redrawControl('pairedDocs2');
            $this->showModal('pairedDocsModal');
		}
		
		public function handleShowTextsUse()
		{
			//bdump('ted');
			$this->pairedDocsShow = TRUE;
			$this->showModal('textsUseModal');
			$this->redrawControl('textsUse');
			//$this->redrawControl('contents');
		}
		
		
		public function handleCreateCommissionModalWindow()
		{
			//bdump('ted');
			$this->createDocShow = TRUE;
			$this->showModal('createCommissionModal');
            $this->redrawControl('bscAreaEdit');
			$this->redrawControl('createDocs');
			$this->redrawControl('contents');
		}
		
		public function handleCreateInvoiceModalWindow()
		{
			//bdump('ted');
			$this->createDocShow = TRUE;
			$this->showModal('createInvoiceModal');
            $this->redrawControl('bscAreaEdit');
			$this->redrawControl('createDocs');
			$this->redrawControl('contents');
		}
		
		
		/*26.07.2018 - return worker hour tax
		 *
		 */
		public function handleGetWorkerTax($cl_users_id)
		{
			$tax = $this->UserManager->getWorkerTax($cl_users_id);
			$this->payload->tax = $tax;
			$this->redrawControl();
			//$this->sendJson(array('tax' => $tax));
			//echo($tax);
			
		}
		
		
		public function handleReport($index = 0)
		{
			$this->rptIndex = $index;
			$this->reportModalShow = TRUE;
			$this->redrawControl('baselistArea');
			$this->redrawControl('reportModal');
			$this->redrawControl('reportHandler');
		}
		
		
		protected function createComponentReportClients($name)
		{
			$form = new Form($this, $name);
			//$form->setMethod('POST');
			$form->addHidden('id', NULL);
			
			$now = new \Nette\Utils\DateTime;
			$form->addText('cm_date_from', 'Od:', 0, 16)
				->setDefaultValue('01.' . $now->format('m.Y'))
				->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));
			
			$form->addText('cm_date_to', 'Do:', 0, 16)
				->setDefaultValue($now->format('d.m.Y'))
				->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));
			
			if ($this->settings->platce_dph == 1) {
				$tmpPriceFrom = $this->translator->translate("Cena_bez_DPH_od");
				$tmpPriceTo = $this->translator->translate("Cena_bez_DPH_do");
			} else {
				$tmpPriceFrom = $this->translator->translate("Cena_od");
				$tmpPriceTo = $this->translator->translate("Cena_do");
			}
			$form->addText('price_e2_from', $tmpPriceFrom . ":", 0, 16)
				->setDefaultValue(0)
				->setHtmlAttribute('placeholder', $tmpPriceFrom);
			
			$form->addText('price_e2_to', $tmpPriceTo . ":", 0, 16)
				->setDefaultValue(0)
				->setHtmlAttribute('placeholder', $tmpPriceTo);
			
			
			$form->addRadioList('type', $this->translator->translate('Typ filtru'), array(0 => $this->translator->translate('Datum_nabídky'), 1 => $this->translator->translate('Datum_platnosti')))
				->setDefaultValue(0);
			$form->addCheckbox('done', $this->translator->translate('Pouze_hotové'))
				->setDefaultValue(true);
			
			//$tmpArrPartners = $this->PartnersManager->findAll()->order('company')->fetchPairs('id','company');
			$tmpArrPartners = $this->PartnersManager->findAll()->
			select('CONCAT(cl_partners_book.id,"-",IFNULL(:cl_partners_branch.id,"")) AS id, CONCAT(cl_partners_book.company," ",IFNULL(:cl_partners_branch.b_name,"")) AS company')->
			order('company')->fetchPairs('id', 'company');
			$form->addMultiSelect('cl_partners_book', $this->translator->translate('applicationModule.commission.report_clients') . ':', $tmpArrPartners)
				->setHtmlAttribute('multiple', 'multiple')
				->setHtmlAttribute('placeholder', $this->translator->translate('applicationModule.commission.report_clientsPh'));
			
			$tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
			$form->addMultiSelect('cl_center_id', $this->translator->translate('Střediska'), $tmpArrCenter)
				->setHtmlAttribute('multiple', 'multiple')
				->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_střediska_pro_tisk'));
			
			
			$tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
			$form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
				->setHtmlAttribute('multiple', 'multiple')
				->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));
			
			$form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
			
			$form->addSubmit('back', $this->translator->translate('Návrat'))
				->setHtmlAttribute('class', 'btn btn-sm btn-primary')
				->setValidationScope([])
				->onClick[] = [$this, 'stepBackReportClients'];
			$form->onSuccess[] = [$this, 'SubmitReportClientsSubmitted'];
			//$form->getElementPrototype()->target = '_blank';
			return $form;
		}
		
		public function stepBackReportClients()
		{
			$this->rptIndex = 0;
			$this->reportModalShow = FALSE;
			$this->redrawControl('baselistArea');
			$this->redrawControl('reportModal');
			$this->redrawControl('reportHandler');
		}
		
		public function SubmitReportClientsSubmitted(Form $form)
		{
			$data = $form->values;
			
			if ($form['save']->isSubmittedBy()) {
				$data['cl_partners_branch'] = [];
				if ($data['cm_date_to'] == "")
					$data['cm_date_to'] = NULL;
				else {
					$data['cm_date_to'] = date('Y-m-d H:i:s', strtotime($data['cm_date_to']) + 86400 - 1);
				}
				
				if ($data['cm_date_from'] == "")
					$data['cm_date_from'] = NULL;
				else
					$data['cm_date_from'] = date('Y-m-d H:i:s', strtotime($data['cm_date_from']));
				
				if ($data['type'] == 0) {
					$dataReport = $this->DataManager->findAll()->
					where('offer_date >= ? AND offer_date <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
					order('cl_partners_book.company ASC, cl_partners_category_id ASC, offer_date ASC');
				} elseif ($data['type'] == 1) {
					$dataReport = $this->DataManager->findAll()->
					where('validity_date >= ? AND validity_date <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
					order('cl_partners_book.company ASC, cl_partners_category_id ASC, validity_date ASC');
				}
				
				if (count($data['cl_partners_book']) > 0) {
					$tmpPartners = [];
					$tmpBranches = [];
					foreach ($data['cl_partners_book'] as $one) {
						$arrOne = str_getcsv($one, "-");
						$tmpPartners[] = $arrOne[0];
						$tmpBranches[] = $arrOne[1];
					}
					$data['cl_partners_book'] = $tmpPartners;
					$data['cl_partners_branch'] = $tmpBranches;
					
					$dataReport = $dataReport->where('cl_partners_book_id IN (?) OR cl_partners_branch_id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch']);
					
					//$dataReport = $dataReport->where(array('cl_offer.cl_partners_book_id' => $data['cl_partners_book']))->
					//							where(array('cl_offer.cl_partners_branch_id' => $data['cl_partners_branch']));
					//$dataReport = $this->DataManager->findAll()->
					//				    where(array('cl_offer.cl_partners_book_id' =>  $data['cl_partners_book']));
				}
				
				if (count($data['cl_center_id']) > 0) {
					$dataReport = $dataReport->where(['cl_offer.cl_center_id' => $data['cl_center_id']]);
				}
				
				if (count($data['cl_users_id']) > 0) {
					$dataReport = $dataReport->where(['cl_offer.cl_users_id' => $data['cl_users_id']]);
				}
				
				if ($data['done']) {
					$dataReport->where('cl_status.s_fin = 1');
				}
				
				$data['price_e2_from'] = str_replace(' ', '', $data['price_e2_from']);
				$data['price_e2_from'] = str_replace(',', '.', $data['price_e2_from']);
				$data['price_e2_to'] = str_replace(' ', '', $data['price_e2_to']);
				$data['price_e2_to'] = str_replace(',', '.', $data['price_e2_to']);
				if ($data['price_e2_from'] != $data['price_e2_to'] && $data['price_e2_to'] > 0) {
					$dataReport->where('cl_offer.price_e2_base*cl_offer.currency_rate >= ? AND cl_offer.price_e2_base*cl_offer.currency_rate <= ?', $data['price_e2_from'], $data['price_e2_to']);
				}
				
				//bdump($data);
				$dataOther = [];//$this->CommissionTaskManager->find($itemId);
				$dataSettings = $data;
				//$dataOther['dataSettingsPartners']   = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
				$dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
																where('cl_partners_book.id IN (?) OR :cl_partners_branch.id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch'])->
																select('cl_partners_book.company')->
																order('company');
				$dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
				$dataOther['dataSettingsUsers'] = $this->UserManager->getAll()->where(['id' => $data['cl_users_id']])->order('name');
				$dataOther['settings'] = $this->settings;
				$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Offer/rptOffers.latte', $dataOther, $dataSettings, $this->translator->translate('Přehled_nabídek'));
				$tmpDate1 = new \DateTime($data['cm_date_from']);
				$tmpDate2 = new \DateTime($data['cm_date_to']);
				$this->pdfCreate($template, $this->translator->translate('Přehled_nabídek_') . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
			}
		}
		
		
		public function handleCreateCommission($dataItems, $dataTasks)
		{
			//create new commission
			//insert items
			//insert tasks
			$offerData = $this->DataManager->find($this->id);
			$data = [];
			$data['cl_partners_book_id'] = $offerData->cl_partners_book_id;
            $data['cl_partners_book_workers_id'] = $offerData->cl_partners_book_workers_id;
			$data['cl_company_branch_id'] = $offerData->cl_company_branch_id;
            $data['cl_partners_branch_id'] = $offerData->cl_partners_branch_id;
			$data['cl_users_id'] = $offerData->cl_users_id;
			$data['cl_center_id'] = $offerData->cl_center_id;
			$data['cl_currencies_id'] = $offerData->cl_currencies_id;
			$data['currency_rate'] = $offerData->currency_rate;
			$data['cm_title'] = $offerData->cm_title;
			$data['vat'] = $offerData->vat;
			$data['cm_order'] = $offerData->cm_order;
			$today = new \Nette\Utils\DateTime;
			$data['cm_date'] = $today;
			
			$numberSeries = ['use' => 'commission', 'table_key' => 'cl_number_series_id', 'table_number' => 'cm_number'];
			$nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
			$data[$numberSeries['table_key']] = $nSeries['id'];
			$data[$numberSeries['table_number']] = $nSeries['number'];
			
			$tmpStatus = $numberSeries['use'];
			$nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch();
			if ($nStatus) {
				$data['cl_status_id'] = $nStatus->id;
			}
			
			$commission = $this->CommissionManager->insert($data);
			$this->DataManager->update(['id' => $this->id, 'cl_commission_id' => $commission->id]);
			
			$arrDataItems = json_decode($dataItems, true);
			foreach ($arrDataItems as $key => $one) {
				$offerItem = $this->OfferItemsManager->find($one);
				if ($offerItem) {
					$dataItems = $offerItem->toArray();
					unset($dataItems['cl_offer_id']);
					unset($dataItems['position']);
					unset($dataItems['cl_commission_id']);
					unset($dataItems['cl_invoice_id']);
					unset($dataItems['id']);
					$dataItems['cl_commission_id'] = $commission->id;
					$commissionItem = $this->CommissionItemsSelManager->insert($dataItems);
					$offerItem->update(['cl_commission_id' => $commission->id]);
					//bdump($dataItems);
					if (!is_null($dataItems['cl_pricelist_id'])) {
						//21.01.2019 - solutions of macro cards
						$macroData = $this->PriceListMacroManager->findAll()->where('cl_pricelist_macro_id = ?', $dataItems['cl_pricelist_id']);
						foreach ($macroData as $one) {
							$newQuantity = $this->PriceListMacroManager->getQuantity($one->id, $dataItems['quantity']);
							$dataItemsOne = $dataItems;
							$dataItemsOne['cl_commission_items_sel_id'] = $commissionItem->id;
							$dataItemsOne['quantity'] = $newQuantity;
							$dataItemsOne['cl_pricelist_id'] = $one->cl_pricelist_id;
							$dataItemsOne['item_label'] = $one->cl_pricelist->item_label;
							$dataItemsOne['price_s'] = $this->PriceListManager->getStorePrice($one->cl_pricelist_id);
							$commissionItem2 = $this->CommissionItemsManager->insert($dataItemsOne);
						}
					}
				}
			}
			//die;
			
			$arrDataTasks = json_decode($dataTasks, true);
			foreach ($arrDataTasks as $key => $one) {
				$offerTask = $this->OfferTaskManager->find($one);
				if ($offerTask) {
					$dataTasks = $offerTask->toArray();
					unset($dataTasks['cl_offer_id']);
					unset($dataTasks['cl_commission_id']);
					unset($dataTasks['cl_invoice_id']);
					unset($dataTasks['id']);
					unset($dataTasks['work_date_s']);
					unset($dataTasks['work_date_e']);
					
					$dataTasks['cl_commission_id'] = $commission->id;
					$commissionTask = $this->CommissionTaskManager->insert($dataTasks);
					$offerTask->update(['cl_commission_id' => $commission->id]);
				}
			}
			
			$this->CommissionManager->updateSum($commission->id);
			
			//create pairedocs record
			$this->PairedDocsManager->insertOrUpdate(['cl_offer_id' => $this->id, 'cl_commission_id' => $commission->id]);
			
			
			$this->flashMessage('Zakázka byla vytvořena.', 'success');
			//$this->redirect('Offer:default');
			$this->createDocShow = FALSE;
			//$this->hideModal('createDocModal');
			//$this->redrawControl('unMoHandler');
			$this->payload->id = $commission->id;
			$this->redrawControl();
		}
		
		public function handleCreateInvoice($dataItems, $dataTasks)
		{
			//bdump($this->id,'this->id');
			//bdump($this->bscId,('this-bscId'));
			//$this->id = $this->bscId;
			if ($tmpData = $this->DataManager->find($this->id)) {
				if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
					$tmpInvoiceType = $tmpInvoiceType->id;
				} else {
					$tmpInvoiceType = NULL;
				}
				//default values for invoice
				$defDueDate = new \Nette\Utils\DateTime;
				$arrInvoice = new \Nette\Utils\ArrayHash;
				$arrInvoice['cl_currencies_id'] = $this->settings->cl_currencies_id;
				$arrInvoice['currency_rate'] = $this->settings->cl_currencies->fix_rate;
				$arrInvoice['vat_active'] = $this->settings->platce_dph;
				$arrInvoice['cl_company_branch_id'] = $tmpData->cl_company_branch_id;
                $arrInvoice['cl_partners_branch_id'] = $tmpData->cl_partners_branch_id;
				$arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
                $arrInvoice['cl_partners_book_workers_id'] = $tmpData->cl_partners_book_workers_id;
                $arrInvoice['cl_center_id'] = $tmpData->cl_center_id;
				$arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
				$arrInvoice['currency_rate'] = $tmpData->currency_rate;
				$arrInvoice['cl_offer_id'] = $tmpData->id;
				$arrInvoice['price_e_type'] = $tmpData->price_e_type;
				$arrInvoice['inv_date'] = new \Nette\Utils\DateTime;
				$arrInvoice['vat_date'] = new \Nette\Utils\DateTime;
				
				$arrInvoice['konst_symb'] = $this->settings->konst_symb;
				$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
				//$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
				
				$arrInvoice['header_show'] = $this->settings->header_show;
				$arrInvoice['footer_show'] = $this->settings->footer_show;
				$arrInvoice['header_txt'] = $this->settings->header_txt;
				$arrInvoice['footer_txt'] = $this->settings->footer_txt;
				
				//settings for specific partner
				if ($tmpData->cl_partners_book->due_date > 0)
					$strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
				else
					$strModify = '+' . $this->settings->due_date . ' day';
				
				$arrInvoice['due_date'] = $defDueDate->modify($strModify);
				
				if (isset($tmpData->cl_partners_book->cl_payment_types_id)) {
					$clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
					$spec_symb = $tmpData->cl_partners_book->spec_symb;
				} else {
					$clPayment = $this->settings->cl_payment_types_id;
					$spec_symb = "";
				}
				$arrInvoice['cl_payment_types_id'] = $clPayment;
				$arrInvoice['spec_symb'] = $spec_symb;
				
				//create or update invoice
				if ($tmpData->cl_invoice_id == NULL) {
					//new number
					$nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
					$arrInvoice['inv_number'] = $nSeries['number'];
					$arrInvoice['cl_number_series_id'] = $nSeries['id'];
					$arrInvoice['var_symb'] = preg_replace('/\D/', '', $arrInvoice['inv_number']);
					$tmpStatus = 'invoice';
					if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
						$arrInvoice['cl_status_id'] = $nStatus->id;
					
					$row = $this->InvoiceManager->insert($arrInvoice);
					$this->DataManager->update(['id' => $this->id, 'cl_invoice_id' => $row->id]);
					$invoiceId = $row->id;
				} else {
					$arrInvoice['id'] = $tmpData->cl_invoice_id;
					$row = $this->InvoiceManager->update($arrInvoice);
					$invoiceId = $tmpData->cl_invoice_id;
				}
				//now content of invoice
				//at first, delete old content
				//next insert new content
				$this->InvoiceItemsManager->findBy(['cl_invoice_id' => $invoiceId])->delete();
				
				
				$arrDataItems = json_decode($dataItems, true);
				$lastOrder = 0;
				$parentBondId = NULL;
				foreach ($arrDataItems as $key => $one) {
					$offerItem = $this->OfferItemsManager->find($one);
					if ($offerItem) {
						
						$newItem = new \Nette\Utils\ArrayHash;
						$newItem['cl_invoice_id'] = $invoiceId;
						$newItem['item_order'] = $offerItem->item_order;
						$newItem['cl_pricelist_id'] = $offerItem->cl_pricelist_id;
						$newItem['item_label'] = $offerItem->item_label;
						$newItem['quantity'] = $offerItem->quantity;
						$newItem['units'] = $offerItem->units;
						$newItem['price_s'] = $offerItem->price_s;
						$newItem['price_e'] = $offerItem->price_e;
						$newItem['discount'] = $offerItem->discount;
						$newItem['price_e2'] = $offerItem->price_e2;
						$newItem['vat'] = $offerItem->vat;
						$newItem['price_e2_vat'] = $offerItem->price_e2_vat;
						$newItem['price_e_type'] = $offerItem->price_e_type;
						if (is_null($offerItem['cl_parent_bond_id'])) {
							$parentBondId = NULL;
						}
						$newItem['cl_parent_bond_id'] = $parentBondId;
						
						$tmpNew = $this->InvoiceItemsManager->insert($newItem);
						$offerItem->update(['cl_invoice_id' => $invoiceId]);
						$lastOrder = $offerItem->item_order;
						
						
						//07.03.2019 - now we have to solve outcome from storage in case of item from pricelist and
						//active connection between invoice and store
						if ($this->settings->invoice_to_store == 1 && !is_null($offerItem->cl_pricelist_id)) {
							//saled items - give out
							//1. check if cl_store_docs exists if not, create new one
							$docId = $this->StoreDocsManager->createStoreDoc(1, $this->id, $this->DataManager);
							//store doc is created from commission, we need to update cl_invoice_id too
							$this->StoreDocsManager->update(['id' => $docId, 'cl_invoice_id' => $invoiceId]);
							//update storedocs_id in invoice
							$this->InvoiceManager->update(['id' => $invoiceId, 'cl_store_docs_id' => $docId]);
							//update storedocs_id in offer
							$this->DataManager->update(['id' => $tmpData->id, 'cl_store_docs_id' => $docId]);
							
							//2. giveout current item
							$dataId = $this->StoreManager->giveOutItem($docId, $tmpNew->id, $this->InvoiceItemsManager);
							
							//create pairedocs record with created cl_store_docs_id
							$this->PairedDocsManager->insertOrUpdate(['cl_offer_id' => $this->id, 'cl_store_docs_id' => $docId]);
							$this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_store_docs_id' => $docId]);
						}
						
						//14.03.2019 - bonded items solution
						//$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $offerItem->cl_pricelist_id));
                        if (!is_null($offerItem->cl_pricelist_id)) {
                            $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $offerItem->cl_pricelist_id, $offerItem->quantity);

                            if ($tmpBonds) {
                                $parentBondId = $tmpNew->id;
                            }
                        }
						
						
					}
				}
				
				
				$arrDataTasks = json_decode($dataTasks, true);
				foreach ($arrDataTasks as $key => $one) {
					$offerTask = $this->OfferTaskManager->find($one);
					if ($offerTask) {
						$newItem = new \Nette\Utils\ArrayHash;
						$newItem['cl_invoice_id'] = $invoiceId;
						$newItem['item_order'] = $lastOrder + $offerTask->item_order;
						$newItem['cl_pricelist_id'] = NULL;
						if (isset($offerTask->cl_users->name))
							$tmpLabel = $offerTask->name . ":" . $offerTask->cl_users->name;
						else
							$tmpLabel = $offerTask->name;
						
						$newItem['item_label'] = $tmpLabel;
						$newItem['quantity'] = $offerTask->work_time;
						$newItem['units'] = 'hod.';
						$newItem['price_s'] = 0;
						$newItem['discount'] = 0;
						$profit = 1 + ($offerTask->profit / 100);
						if ($tmpData->price_e_type == 0 || $this->settings->platce_dph == 0) {
							$newItem['price_e'] = $offerTask->work_rate * $profit;
						} else {
							$calcVat = round(($offerTask->work_rate * $profit) * ($tmpData->vat / 100), 2);
							$newItem['price_e'] = ($offerTask->work_rate * $profit) + $calcVat;
						}
						
						$newItem['price_e2'] = ($offerTask->work_rate * $profit) * $offerTask->work_time;
						$newItem['vat'] = $tmpData->vat;
						$calcVat = round(($offerTask->work_rate * $profit * $offerTask->work_time) * ($tmpData->vat / 100), 2);
						$newItem['price_e2_vat'] = ($offerTask->work_rate * $profit * $offerTask->work_time) + $calcVat;
						$this->InvoiceItemsManager->insert($newItem);
						$offerTask->update(['cl_invoice_id' => $invoiceId]);
					}
				}
				
				//InvoicePresenter::updateSum($invoiceId,$this);
				$this->InvoiceManager->updateInvoiceSum($invoiceId);
				
				//$this->flashMessage('Změny byly uloženy, faktura byla vytvořena.', 'success');
				//$this->redirect('Offer:default');
				//$this->redirect('Invoice:default', $invoiceId);
				
				//create pairedocs record
				$this->PairedDocsManager->insertOrUpdate(['cl_offer_id' => $this->id, 'cl_invoice_id' => $invoiceId]);
				
				
				$this->flashMessage($this->translator->translate('Změny byly uloženy faktura byla vytvořena'), 'success');
				//$this->redirect('Offer:default');
				$this->createDocShow = FALSE;
				//$this->hideModal('createDocModal');
				//$this->redrawControl('unMoHandler');
				$this->payload->id = $invoiceId;
				$this->redrawControl();
				
			}
			
			
		}
		
		
		public function handleShowCommissionUsed()
		{
			$this->filterCommissionUsed = [];
            $this['listgridItemsSelect']->setFilter($this->filterCommissionUsed);
            $this['listgridTasksSelect']->setFilter($this->filterCommissionUsed);
            $this->redrawControl('bscAreaEdit');
			$this->redrawControl('itemsForCommission');
		}
		
		public function handleShowCommissionNotUsed()
		{
			$this->filterCommissionUsed = ['filter' => 'cl_commission_id IS NULL'];
            $this['listgridItemsSelect']->setFilter($this->filterCommissionUsed);
            $this['listgridTasksSelect']->setFilter($this->filterCommissionUsed);
            $this->redrawControl('bscAreaEdit');
			$this->redrawControl('itemsForCommission');
		}
		
		public function handleShowInvoiceUsed()
		{
			$this->filterInvoiceUsed = [];
            $this['listgridItemsSelect']->setFilter($this->filterInvoiceUsed);
            $this['listgridTasksSelect']->setFilter($this->filterInvoiceUsed);
            $this->redrawControl('bscAreaEdit');
			$this->redrawControl('itemsForInvoice');
		}
		
		public function handleShowInvoiceNotUsed()
		{
			$this->filterInvoiceUsed = ['filter' => 'cl_invoice_id IS NULL'];
            $this['listgridItemsSelect']->setFilter($this->filterInvoiceUsed);
            $this['listgridTasksSelect']->setFilter($this->filterInvoiceUsed);
            $this->redrawControl('bscAreaEdit');
			$this->redrawControl('itemsForInvoice');
		}
		
		
		public function DataProcessMain($defValues, $data)
		{
			
			
			//20.12.2018 - headers and footers
			//19.10.2019 - solved in BaseListPresenter->getNumberSeries
			//if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $defValues['cl_number_series_id']))->fetch()){
			//    $defValues['header_txt'] = $hfData['header_txt'];
			//    $defValues['footer_txt'] = $hfData['footer_txt'];
			//}
			
			return $defValues;
		}
		
		
		/*14.03.2019 - metod called after saving record in listgrid component
		 * here we are solving for example transfering finished record from tasks to work
		 */
		public function afterDataSaveListGrid($dataId, $name = NULL)
		{
			//bdump($dataId,$name);
			if ($name == 'listgridTask') {
			
			} elseif ($name == 'listgrid') {
				//14.03.2019 - insert cl_pricelist_bond into cl_invoice_items
				$tmpOfferItem = $this->OfferItemsManager->find($dataId);
				//bdump($tmpInvoiceItem->cl_pricelist_id, 'cl_pricelist_id');
				if (!is_null($tmpOfferItem->cl_pricelist_id)) {
					//find if there are bonds in cl_pricelist_bonds
					//$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $tmpOfferItem->cl_pricelist_id));
                    $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpOfferItem->cl_pricelist_id, $tmpOfferItem->quantity);

                    foreach ($tmpBonds as $key => $oneBond) {
						//found in cl_invoice_items if there already is bonded item
						$tmpOfferItemBond = $this->OfferItemsManager->findBy(array('cl_parent_bond_id' => $tmpOfferItem->id,
							'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();
						$newItem = new \Nette\Utils\ArrayHash;
						$newItem['cl_offer_id'] = $this->id;
						$newItem['item_order'] = $tmpOfferItem->item_order + 1;
						$newItem['cl_pricelist_id'] = $oneBond->cl_pricelist_id;
						$newItem['item_label'] = $oneBond->cl_pricelist->item_label;
						$newItem['quantity'] = $oneBond->quantity * ($oneBond->multiply == 1) ? $tmpOfferItem->quantity : 1 ;//$tmpOfferItem->quantity;
						$newItem['units'] = $oneBond->cl_pricelist->unit;
						$newItem['price_s'] = $oneBond->cl_pricelist->price_s;
						$newItem['price_e'] = $oneBond->cl_pricelist->price;
						$newItem['discount'] = $oneBond->discount;
						$newItem['price_e2'] = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpOfferItem->quantity);
						$newItem['vat'] = $oneBond->cl_pricelist->vat;
						$newItem['price_e2_vat'] = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpOfferItem->quantity);
						$newItem['price_e_type'] = $tmpOfferItem->price_e_type;
						$newItem['cl_parent_bond_id'] = $tmpOfferItem->id;
						//bdump($newItem);
						if (!$tmpOfferItemBond) {
							$tmpNew = $this->OfferItemsManager->insert($newItem);
							$tmpId = $tmpNew->id;
						} else {
							$newItem['id'] = $tmpOfferItemBond->id;
							$tmpNew = $this->OfferItemsManager->update($newItem);
							$tmpId = $tmpOfferItemBond->id;
						}
						
					}
				}
				
			}
			
			
		}
		
		
		//aditional function called before insert copied record
		public function beforeCopy($data)
		{
			unset($data['cl_commission_id']);
			unset($data['cl_documents_id']);
			unset($data['locked']);
			$dtmNow = new DateTime();
			$data['offer_date'] = $dtmNow;
			$data['validity_date'] = $dtmNow->modifyClone();
			$data['validity_date']->add(date_interval_create_from_date_string($data['validity_days'] . ' days'));
			
			if ($tmpStatus = $this->StatusManager->findAll()->where(array('status_use' => 'offer', 's_new' => 1))->fetch()) {
				$data['cl_status_id'] = $tmpStatus->id;
			}
			return $data;
		}
		
		//aditional function called after inserted copied record
		public function afterCopy($newLine, $oldLine)
		{
			$tmpOld = $this->DataManager->find($oldLine);
			$tmpNew = $this->DataManager->find($newLine);
			if ($tmpOld && $tmpNew) {
				//solve cl_offer_items, cl_offer_task
				$tmpItems = $this->OfferItemsManager->findAll()->where('cl_offer_id = ?', $tmpOld['id']);
				foreach ($tmpItems as $key => $one) {
					$newArr = $one->toArray();
					$newArr['cl_offer_id'] = $tmpNew['id'];
					unset($newArr['id']);
					unset($newArr['cl_commission_id']);
					unset($newArr['cl_invoice_id']);
					unset($newArr['cl_partners_event_id']);
					$this->OfferItemsManager->insert($newArr);
				}
				
				$tmpItems = $this->OfferTaskManager->findAll()->where('cl_offer_id = ?', $tmpOld['id']);
				foreach ($tmpItems as $key => $one) {
					$newArr = $one->toArray();
					$newArr['cl_offer_id'] = $tmpNew['id'];
					unset($newArr['id']);
					unset($newArr['cl_commission_id']);
					unset($newArr['cl_invoice_id']);
					
					unset($newArr['work_date_s']);
					unset($newArr['work_date_e']);
					unset($newArr['done']);
					$this->OfferTaskManager->insert($newArr);
				}
				
			}
			
			return TRUE;
		}
		
	}

