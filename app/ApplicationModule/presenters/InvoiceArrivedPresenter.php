<?php
namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Pohoda;
use Nette\Utils\FileSystem;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Tracy\Debugger;

use Nette\Utils\Json;

class InvoiceArrivedPresenter extends \App\Presenters\BaseListPresenter
{


    const
        DEFAULT_STATE = 'Czech Republic';


    public $newId = NULL, $pairedDocsShow = FALSE;
    public $paymentModalShow = FALSE, $headerModalShow = FALSE, $footerModalShow = FALSE, $commissionModalShow = FALSE;


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
     * @var \App\Model\InvoiceArrivedManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;
	
	/**
	 * @inject
	 * @var \App\Model\PairedDocsManager
	 */
	public $PairedDocsManager;
    

    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;


    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $InvoiceItemsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedPaymentsManager
     */
    public $InvoiceArrivedPaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedCommissionManager
     */
    public $InvoiceArrivedCommissionManager;

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
     * @var \App\Model\PartnersBookWorkersManager
     */
    public $PartnersBookWorkersManager;

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
     * @var \App\Model\BankAccountsManager
     */
    public $BankAccountsManager;


    /**
     * @inject
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;

    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;


    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;


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
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

    /**
     * @inject
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;

    /**
     * @inject
     * @var \App\Model\PartnersAccountManager
     */
    public $PartnersAccountManager;

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
	 * @var \App\Model\TextsManager
	 */
	public $TextsManager;
	
	
	/**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;

	/**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;



    protected function createComponentEditInvMemo()
    {
       // $translator = clone $this->translator->setPrefix([]);
        return new Controls\EditTextControl( $this->translator,$this->DataManager, $this->id, 'inv_memo');
    }


    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl( $this->translator,$this->EmailingManager, $this->mainTableName, $this->id);
    }
	
	protected function createComponentTextsUse() {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
		return new TextsUseControl($this->DataManager, $this->id, 'invoice_arrived', $this->TextsManager, $this->translator);
	}

    protected function createComponentPairedDocs()
    {
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }
	
	protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
       // $translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl( $this->translator,$this->FilesManager, $this->UserManager, $this->id, 'cl_invoice_arrived_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);

        //return new \App\ApplicationModule\Components\FilesControl($this->FilesManager,$this->UserManager,$this->id,'cl_invoice_arrived_id',
        //			$this->InvoiceArrivedManager,$this->settings->id,$this->user->id);
    }

    protected function createComponentSumOnDocs()
    {
        //$this->translator->setPrefix(['applicationModule.InvoiceArrived']);
        if ($data = $this->DataManager->findBy(array('id' => $this->id))->fetch()) {
            if ($data->cl_currencies) {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            }


            if ($this->settings->platce_dph) {
                $tmpSumToPay = $data->price_e2_vat - $data->price_payed - $data->advance_payed;
            } else {
                $tmpSumToPay = $data->price_e2 - $data->price_payed - $data->advance_payed;
            }

            $tmpChild = "";
            $i = 1;
            foreach ($data->related('cl_invoice_arrived_commission') as $oneChild) {
                if ($i > 1) {
                    $tmpChild .= ", ";
                }
                if (!is_null($oneChild->cl_commission_id)) {
                    $url = $this->link(':Application:Commission:edit', array('id' => $oneChild->cl_commission_id));
                    $tmpChild .= "<a href=$url>" . $oneChild->cl_commission->cm_number . "</a>";
                }
                $i++;
            }
            $dataArr = array(
                array('name' => $this->translator->translate('Spárované_zakázky'), 'value' => $tmpChild, 'format' => 'html'),
                array('name' => $this->translator->translate('Spárováno_celkem'), 'value' => $data->price_on_commission, 'currency' => $tmpCurrencies),
                array('name' => $this->translator->translate('Zaplaceno'), 'value' => $data->price_payed, 'currency' => $tmpCurrencies),
                array('name' => $this->translator->translate('Zbývá_k_úhradě'), 'value' => $tmpSumToPay, 'currency' => $tmpCurrencies),
            );
        } else {
            $dataArr = array();
        }
        //$translator = clone $this->translator->setPrefix([]);
        return new SumOnDocsControl( $this->translator,$this->DataManager, $this->id, $this->settings, $dataArr);
    }


    protected function createComponentPaymentListGrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //	dump($this->id);
        //die;
        //$this->translator->setPrefix(['applicationModule.InvoiceArrived']);
        if ($this->settings->platce_dph == 1) {
            $arrData = array('pay_price' => array($this->translator->translate('Cástka'), 'format' => 'number', 'size' => 10),
                'cl_currencies.currency_name' => array($this->translator->translate('Měna'), 'format' => 'text', 'size' => 10, 'values' => $this->CurrenciesManager->findAllTotal()->fetchPairs('id', 'currency_name')),
                'pay_doc' => array($this->translator->translate('Doklad'), 'format' => 'text', 'size' => 20),
                'cl_payment_types.name' => array($this->translator->translate('Typ_platby'), 'format' => 'text', 'size' => 10, 'values' => $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name')),
                'pay_type' => array($this->translator->translate('Druh_úhrady'), 'format' => 'text', 'size' => 10, 'values' => array('0' => $this->translator->translate('běžná_úhrada'), '1' => $this->translator->translate('záloha'))),
                'pay_vat' => array($this->translator->translate('Daňová_záloha'), 'format' => 'text', 'size' => 15, 'values' => array('0' => $this->translator->translate('ne'), '1' => $this->translator->translate('ano'))),
                'vat' => array($this->translator->translate('DPH %'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 7),
                'pay_date' => array($this->translator->translate('Datum_platby'), 'format' => 'date', 'size' => 15)
            );
            if ($tmpParentData) {
                $tmpPrice = $tmpParentData->price_e2_vat;
            } else {
                $tmpPrice = 0;
            }
        } else {
            $arrData = array('pay_price' => array($this->translator->translate('Částka'), 'format' => 'currency', 'size' => 10),
                'cl_currencies.currency_name' => array($this->translator->translate('Měna'), 'format' => 'text', 'size' => 10, 'values' => $this->CurrenciesManager->findAllTotal()->fetchPairs('id', 'currency_name')),
                'pay_doc' => array($this->translator->translate('Doklad'), 'format' => 'text', 'size' => 20),
                'cl_payment_types.name' => array($this->translator->translate('Typ_platby'), 'format' => 'text', 'size' => 10, 'values' => $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name')),
                'pay_type' => array($this->translator->translate('Druh_úhrady'), 'format' => 'text', 'size' => 10, 'values' => array('0' => $this->translator->translate('běžná_úhrada'), '1' => $this->translator->translate('záloha'))),
                'pay_vat' => array($this->translator->translate('Daňová_záloha'), 'format' => 'text', 'size' => 15, 'values' => array('0' => 'ne', '1' => 'ano')),
                'pay_date' => array($this->translator->translate('Datum_platby'), 'format' => 'date', 'size' => 15)
            );
            //$tmpPrice = $tmpParentData->price_e2;
            if ($tmpParentData) {
                $tmpPrice = $tmpParentData->price_e2;
            } else {
                $tmpPrice = 0;
            }
        }

        if ($tmpParentData) {
            $tmpCurrId = $tmpParentData->cl_currencies_id;
        } else {
            $tmpCurrId = $this->settings->cl_currencies_id;
        }
        //$translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->InvoiceArrivedPaymentsManager,
            $arrData,
            array(),
            $this->id,
            array('pay_date' => new \Nette\Utils\DateTime, 'cl_currencies_id' => $tmpCurrId,
                'vat' => $this->settings->def_sazba, 'pay_price' => $tmpPrice),
            $this->DataManager,
            NULL,
            NULL,
            TRUE,
            array() //custom links
        );

        $control->onChange[] = function () {
            $this->updatePaymentSum();
        };

        return $control;

    }

    public function updatePaymentSum(){
        //bdump('updatePaymentSum called');
        $this->DataManager->paymentUpdate($this->id);
        if (isset($this['sumOnDocs'])) {
            $this['sumOnDocs']->redrawControl('sumOnDocsSnp');
        }
        if (isset($this['pairedDocs'])) {
            $this['pairedDocs']->redrawControl('docs');
        }
    }



    protected function createComponentCommissionListGrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        $arrData = ['cl_commission.cm_number' => [$this->translator->translate('Zakázka'), 'format' => 'select', 'size' => 25,
                                                'values' => $this->CommissionManager->findAll()
                                                                    ->select('cl_commission.id,CONCAT(cl_commission.cm_number," - ",cl_partners_book.company) AS cm_number')
                                                                    ->order('cl_commission.cm_number')->fetchPairs('id', 'cm_number')],
                    'cl_commission.cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'noinput', 'size' => 30],
                    'amount' => [$this->translator->translate('Částka'), 'format' => 'number', 'size' => 10],
                    'note' => [$this->translator->translate('Poznámka'), 'format' => 'textarea', 'size' => 50, 'rows' => 3],
        ];
        //$tmpPrice = $tmpParentData->price_e2_vat;

        //$translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->InvoiceArrivedCommissionManager,
            $arrData,
            [],
            $this->id,
            [],//default values
            $this->DataManager,
            NULL,
            NULL,
            TRUE,
            [] //custom links
        );

        $control->onChange[] = function () {
            $this->updateSum();
        };

        return $control;

    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.InvoiceArrived']);
        $this->formName = $this->translator->translate("Faktury_přijaté");
        $this->mainTableName = 'cl_invoice_arrived';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        if ($this->settings->platce_dph == 1) {
            $arrData = ['inv_number' => $this->translator->translate('Účetní_číslo_faktury'),
                'rinv_number' => $this->translator->translate('Číslo_faktury'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'style' => 'glyphicon glyphicon-lock'],
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                'arv_date' => [$this->translator->translate('Přijato'), 'format' => 'date'],
                'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text', 'show_clink' => true],
                'cl_invoice_types.name' => [$this->translator->translate('Druh'), 'format' => 'text'],
                'cl_payment_types.name' => $this->translator->translate('Forma_úhrady'),
                'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
                'vat_date' => [$this->translator->translate('DUZP'), 'format' => 'date'],
                'pay_date' => [$this->translator->translate('Uhrazeno'), 'format' => 'date'],
                'inv_title' => $this->translator->translate('Popis'),
                'cl_payment_order.po_number' => [$this->translator->translate('Platební_příkaz'), 'format' => 'url', 'size' => 10, 'url' => 'paymentorder', 'value_url' => 'cl_payment_order_id'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                'advance_payed' => [$this->translator->translate('Záloha'), 'format' => 'currency'],
                'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'od_number' => $this->translator->translate('Objednávka'),
                'delivery_number' => $this->translator->translate('Dodací_list'),
                'import' => [$this->translator->translate('Import'), 'format' => 'boolean'],
                'var_symb' => $this->translator->translate('Var._symbol'),
                'spec_symb' => $this->translator->translate('Spec._symbol'),
                'konst_symb' => $this->translator->translate('Konst._symbol'),
                'cl_commission.cm_number' => $this->translator->translate('Číslo_zakázky'),
                'cl_users.name' => $this->translator->translate('Obchodník'),
                'price_base0' => [$this->translator->translate("Základ_0%"), 'format' => 'currency'],
                'price_base1' => [$this->translator->translate("Základ_1"), 'format' => 'currency'],
                'vat1' => [$this->translator->translate("Sazba_1"), 'format' => 'number'],
                'price_vat1' => [$this->translator->translate("Daň_1"), 'format' => 'currency'],
                'price_base2' => [$this->translator->translate("Základ_2"), 'format' => 'currency'],
                'vat2' => [$this->translator->translate("Sazba_2"), 'format' => 'number'],
                'price_vat2' => [$this->translator->translate("Daň_2"), 'format' => 'currency'],
                'price_base3' => [$this->translator->translate("Základ_3"), 'format' => 'currency'],
                'vat3' => [$this->translator->translate("Sazba_3"), 'format' => 'number'],
                'price_vat3' => [$this->translator->translate("Daň_3"), 'format' => 'currency'],
                'price_total1' => [$this->translator->translate("Celkem plus daň 1"), 'format' => 'currency'],
                'price_total2' => [$this->translator->translate("Celkem plus daň 2"), 'format' => 'currency'],
                'price_total3' => [$this->translator->translate("Celkem plus daň 3"), 'format' => 'currency'],
                'inv_memo' => $this->translator->translate('Poznámka'),
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        } else {
            $arrData = ['inv_number' => $this->translator->translate('Účetní_číslo_faktury'),
                'rinv_number' => $this->translator->translate('Číslo_faktury'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'style' => 'glyphicon glyphicon-lock'],
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                'arv_date' => [$this->translator->translate('Přijato'), 'format' => 'date'],
                'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text', 'show_clink' => true],
                'cl_invoice_types.name' => [$this->translator->translate('Druh'), 'format' => 'text'],
                'cl_payment_types.name' => $this->translator->translate('Forma_úhrady'),
                'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
                'pay_date' => [$this->translator->translate('Uhrazeno'), 'format' => 'date'],
                'inv_title' => $this->translator->translate('Popis'),
                'cl_payment_order.po_number' => [$this->translator->translate('Platební_příkaz'), 'format' => 'url', 'size' => 10, 'url' => 'paymentorder', 'value_url' => 'cl_payment_order_id'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_celkem'), 'format' => 'currency'],
                'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                'advance_payed' => [$this->translator->translate('Záloha'), 'format' => 'currency'],
                'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'od_number' => $this->translator->translate('Objednávka'),
                'delivery_number' => $this->translator->translate('Dodací_list'),
                'import' => [$this->translator->translate('Import'), 'format' => 'boolean'],
                'var_symb' => $this->translator->translate('Var._symbol'),
                'spec_symb' => $this->translator->translate('Spec._symbol'),
                'konst_symb' => $this->translator->translate('Konst._symbol'),
                'cl_commission.cm_number' => $this->translator->translate('Číslo zakázky'),
                'cl_users.name' => $this->translator->translate('Obchodník'),
                'inv_memo' => $this->translator->translate('Poznámka'),
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        }
        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['inv_number' => '', 'rinv_number' => '', 'price_e2_vat' => '', 'price_e2' => '', 'cl_partners_book.company' => 'autocomplete',
            'cl_invoice_arrived.var_symb' => 'autocomplete', 'cl_status.status_name' => 'autocomplete',
            'inv_title' => '', 'cl_users.name' => 'autocomplete'];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['inv_number', 'rinv_number', 'var_symb', 'inv_title', 'cl_partners_book.company', 'price_e2', 'price_e2_vat'];

        $this->DefSort = 'inv_date DESC';

        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        $this->conditionRows = [['due_date', '<', $testDate, 'color:red', 'notlastcond'],
            ['pay_date', '==', NULL, 'color:red', 'lastcond'],
            ['due_date', '>=', $testDate, 'color:green', 'notlastcond'],
            ['pay_date', '==', NULL, 'color:green', 'lastcond']];

        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;
        if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ? AND inv_type = ?', 1, 4)->fetch()) {
            $tmpInvoiceType = $tmpInvoiceType->id;
        } else {
            $tmpInvoiceType = NULL;
        }
        $defDueDate = new \Nette\Utils\DateTime;
        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;

        //die;
        $this->defValues = ['inv_date'              => new \Nette\Utils\DateTime,
                            'vat_date'              => new \Nette\Utils\DateTime,
                            'arv_date'              => new \Nette\Utils\DateTime,
                            'due_date'              => $defDueDate->modify('+' . $this->settings->due_date . ' day'),
                            'cl_company_branch_id'  => $tmpBranchId,
                            'cl_currencies_id'      => $this->settings->cl_currencies_id,
                            'currency_rate'         => $this->settings->cl_currencies->fix_rate,
                            'konst_symb'            => $this->settings->konst_symb,
                            'cl_invoice_types_id'   => $tmpInvoiceType,
                            'cl_payment_types_id'   => $this->settings->cl_payment_types_id,
                            'header_show'           => $this->settings->header_show,
                            'footer_show'           => $this->settings->footer_show,
                            'header_txt'            => $this->settings->header_txt,
                            'footer_txt'            => $this->settings->footer_txt,
                            'price_e_type'          => $this->settings->price_e_type,
                            'vat1'                  => 0,
                            'cl_users_id'           => $this->user->getId()];
        //26.08.2017 - set of default vats
        //dump($this->id);
        $tmpVats = (array)$this->getArrInvoiceVat();
        //dump($tmpVats);

        if (isset(array_keys($tmpVats)[0])) {
            //	dump(array_keys($tmpVats)[0]);
            $this->defValues['vat1'] = array_keys($tmpVats)[0];
        }
        if (isset(array_keys($tmpVats)[1])) {
            //	dump(array_keys($tmpVats)[1]);
            $this->defValues['vat2'] = array_keys($tmpVats)[1];
        }
        if (isset(array_keys($tmpVats)[2])) {
            //	dump(array_keys($tmpVats)[2]);
            $this->defValues['vat3'] = array_keys($tmpVats)[2];
        }

        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => 'invoice_arrived', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number'];
        $this->readOnly = ['inv_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];
//	$this->toolbar = array(	1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));

        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        if ($tmpBranch) {
            $tmpNSInvoiceId = $tmpBranch->cl_number_series_id_invoicearrived;
            $tmpNSCorrectionId = $tmpBranch->cl_number_series_id_invoicearrived_correction;
            $tmpNSAdvanceId = $tmpBranch->cl_number_series_id_invoicearrived_advance;
        }else{
            //20.12.2018 - headers and footers
            //if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $data->cl_number_series_id))->fetch()){
            //    $arrUpdate['header_txt'] = $hfData['header_txt'];
            //    $arrUpdate['footer_txt'] = $hfData['footer_txt'];
            //}
            $tmpNSInvoiceId = 'invoice_arrived';
            $tmpNSCorrectionId = 'invoice_arrived_correction';
            $tmpNSAdvanceId = 'invoice_arrived_advance';
        }
	

        
        $this->toolbar = [
								0 => ['group_start' => ''],
                                1 => ['url' => $this->link('new!', ['data'=> $tmpNSInvoiceId, 'defData' => '']), 'rightsFor' => 'write', 'label' => $this->translator->translate('Faktura'), 'title' => $this->translator->translate('nová_přijatá_faktura'), 'class' => 'btn btn-primary'],
								2 => $this->getNumberSeriesArray('invoice_arrived'),
								3 => ['group_end' => ''],
								4 => ['group_start' => ''],
                                5 => ['url' => $this->link('new!', ['data'=> $tmpNSCorrectionId, 'defData' => '']), 'rightsFor' => 'write', 'label' => $this->translator->translate('Opravný_d'), 'title' => $this->translator->translate('nový_přijatý_opravný_daňový_doklad'), 'class' => 'btn btn-primary'],
								6 => $this->getNumberSeriesArray('invoice_arrived_correction'),
								7 => ['group_end' => ''],
								8 => ['group_start' => ''],
                                9 => ['url' => $this->link('new!', ['data'=> $tmpNSAdvanceId, 'defData' => '']), 'rightsFor' => 'write', 'label' => $this->translator->translate('Záloha'), 'title' => $this->translator->translate('nová_přijatá_zálohová_faktura'), 'class' => 'btn btn-primary'],
								10 => $this->getNumberSeriesArray('invoice_arrived_advance'),
								11 => ['group_end' => ''],
                                'export' => ['group' =>
                                                [0 => ['url' => $this->link('report!', ['index' => 2]),
                                                                    'rightsFor' => 'report',
                                                                    'label' => $this->translator->translate('Pohoda_XML'),
                                                                    'title' => $this->translator->translate('Faktury_přijaté_ve_zvoleném_období'),
                                                                    'data' => ['data-ajax="true"', 'data-history="false"'],
                                                                    'class' => 'ajax', 'icon' => 'iconfa-file'],
                                                1 => ['url' => $this->link('report!', ['index' => 3]),
                                                        'rightsFor' => $this->translator->translate('report'),
                                                        'label' => $this->translator->translate('Stereo_2022'),
                                                        'title' => $this->translator->translate('Faktury_přijaté_ve_zvoleném_období'),
                                                        'data' => ['data-ajax="true"', 'data-history="false"'],
                                                        'class' => 'ajax', 'icon' => 'iconfa-file'],
                                                                ],
                                                'group_settings' =>
                                                    ['group_label' => $this->translator->translate('Export'),
                                                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                        'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-file-export']
                                ],
                                'import' => ['group' =>
                                                [0 => ['url' => $this->link('importDigitoo!'),
                                                                    'rightsFor' => 'write',
                                                                    'label' => $this->translator->translate('Digitoo'),
                                                                    'title' => $this->translator->translate('Import_faktur_ze_služby_Digitoo'),
                                                                    'data' => ['data-ajax="false"', 'data-history="false"'],
                                                                    'class' => 'ajax', 'icon' => 'iconfa-file'],
                                                                ],
                                                'group_settings' =>
                                                    ['group_label' => $this->translator->translate('Import'),
                                                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                        'group_title' =>  $this->translator->translate('Import'), 'group_icon' => 'iconfa-file-import']
                                ],                                
                                13 => ['group' =>
                                    [0 => ['url' => $this->link('report!', ['index' => 1]),
                                        'rightsFor' => 'report',
                                        'label' => $this->translator->translate('Kniha_faktur_přijatých'),
                                        'title' => $this->translator->translate('Faktury_přijaté_ve_zvoleném_období'),
                                        'data' => ['data-ajax="true"', 'data-history="false"'],
                                        'class' => 'ajax', 'icon' => 'iconfa-print'],
                                    ],
                                    'group_settings' =>
                                        ['group_label' => $this->translator->translate('Tisk'),
                                            'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                            'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
                                ]
        ];

        //$this->rowFunctions = array('copy' => 'disabled');

        $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/InvoiceArrived/ReportInvoiceBookSettings.latte',
            'reportName' => $this->translator->translate('Kniha_faktur_přijatých')],
            2 => ['reportLatte' => __DIR__.'/../templates/InvoiceArrived/ExportInvoiceBookSettings.latte',
                'reportName' => $this->translator->translate('Pohoda_XML_export')],
            3 => ['reportLatte' => __DIR__ . '/../templates/InvoiceArrived/ExportInvoiceBookStereoSettings.latte',
                'reportName' => 'Stereo 2022 export']
        ];

        //$this->showChildLink = 'PartnersEvent:default';
        //Condition for color highlit rows
        //$testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-30 day');
        //$this->conditionRows = array( 'cdate','<=',$testDate);

        //$this->rowFunctions = array('copy' => 'disabled');

        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'InvoiceArrived\card.latte'],
            'memos' => ['active' => true, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'InvoiceArrived\description.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'InvoiceArrived\files.latte']
        ];
        $this->bscSums = ['lattefile' => $this->getLattePath() . 'InvoiceArrived\sums.latte'];

        //1 => array('url' => $this->link('edit!'), 'rightsFor' => 'edit', 'label' => 'editovat záznam', 'class' => 'btn btn-primary', 'icon' => 'glyphicon glyphicon-edit'),
        //<a href={link paymentShow!} id="nhPayment" class="btn  btn-lg btn-success ajax"  title="úhrady faktury" >Úhrady a zálohy</a>

        $this->bscToolbar = [
            1 => ['url' => 'makePayment!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Uhradit'), 'class' => 'btn btn-success', 'title' =>  $this->translator->translate('Provede_jednorázovou_úhradu'),
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            2 => ['url' => ':Application:DeliveryNoteIn:FromInvoiceArrived', 'urlparams' => ['keyname' => 'cl_invoice_arrived_id', 'key' => 'id'], 'rightsFor' => 'write', 'title' => $this->translator->translate('Vytvoří_nový_dodací_list_přijatý'), 'label' => $this->translator->translate('DL_přijatý'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            3 => ['url' => ':Application:Store:FromInvoiceArrived', 'urlparams' => ['keyname' => 'cl_invoice_arrived_id', 'key' => 'id'], 'rightsFor' => 'write', 'title' => $this->translator->translate('Vytvoří_novou_příjemku'), 'label' => $this->translator->translate('Příjemka'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
			4 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('texty'),  'title' => $this->translator->translate('šablony_často_používaných_textů'), 'class' => 'btn btn-success showTextsUse',
				'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'],'icon' => 'glyphicon glyphicon-list'],
            5 => ['url' => 'commissionShow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Spárované_zakázky'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            6 => ['url' => 'paymentShow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Úhrady_a_zálohy'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            7 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'title' => $this->translator->translate('zobrazí_spárované_doklady'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt']

        ];

        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscTitle = ['inv_number' => $this->translator->translate('Číslo_faktury'), 'cl_partners_book.company' => $this->translator->translate('Odběratel')];

        $this->quickFilter = ['cl_invoice_types.name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
            'values' => $this->InvoiceTypesManager->findAll()->where('inv_type = ?', 4)->fetchPairs('id', 'name')]
        ];

        /*predefined filters*/
        $this->pdFilter = [0 => ['url' => $this->link('pdFilter!', ['index' => 0, 'pdFilterIndex' => 0]),
                                        'filter' => '(price_payed < price_e2_vat OR price_payed < price_e2) AND cl_invoice_arrived.due_date <= NOW()',
                                        'sum' => ['price_e2*currency_rate' => 'bez DPH', 'price_e2_vat*currency_rate' => 's DPH'],
                                        'rightsFor' => 'read',
                                        'label' => $this->translator->translate('po_splatnosti'),
                                        'title' => $this->translator->translate('Nezaplacené_faktury_po_splatnosti'),
                                        'data' => ['data-ajax="true"', 'data-history="true"'],
                                        'class' => 'ajax', 'icon' => 'iconfa-filter'],
                                1 => ['url' => $this->link('pdFilter!', ['index' => 1, 'pdFilterIndex' => 1]),
                                        'filter' => '(price_payed < price_e2_vat OR price_payed < price_e2)',
                                        'sum' => ['price_e2*currency_rate' => 'bez DPH', 'price_e2_vat*currency_rate' => 's DPH'],
                                        'rightsFor' => 'read',
                                        'label' => $this->translator->translate('nezaplacené'),
                                        'title' => $this->translator->translate('Všechny_doposud_nezaplacené_faktury'),
                                        'data' => ['data-ajax="true"', 'data-history="true"'],
                                        'class' => 'ajax', 'icon' => 'iconfa-filter']
        ];
        if ($this->settings->platce_dph == 0){
            $this->pdFilter[0]['sum'] = ['price_e2*currency_rate' => 'celkem'];
            $this->pdFilter[1]['sum'] = ['price_e2*currency_rate' => 'celkem'];
        }

        $this->groupActions['payment'] = 'uhradit';
    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
        $tmpInvoice = $this->dataForSums;
        if ($this->settings->platce_dph == 1) {
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount = $tmpInvoiceC->where('cl_invoice_arrived.due_date <= NOW() AND ABS(price_payed) < ABS(price_e2_vat-advance_payed)')->sum('(price_e2_vat-price_payed-advance_payed)*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount2 = $tmpInvoiceC->where('ABS(price_payed) < ABS(price_e2_vat-advance_payed)')->sum('(price_e2_vat-price_payed-advance_payed)*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount3 = $tmpInvoiceC->
                                    where('MONTH(vat_date) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(vat_date) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))')->
                                    sum('price_e2_vat*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount4 = $tmpInvoiceC->
                                    where('MONTH(vat_date) = MONTH(NOW()) AND YEAR(vat_date) = YEAR(NOW())')->
                                    sum('price_e2_vat*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount5 = $tmpInvoiceC->
                                    where('cl_invoice_arrived.due_date <= DATE_SUB(NOW(),INTERVAL 7 DAY) AND ABS(price_payed) < ABS(price_e2_vat-advance_payed)')->
                                    sum('(price_e2_vat-price_payed-advance_payed)*currency_rate');
        } else {
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount = $tmpInvoiceC->where('cl_invoice_arrived.due_date <= NOW() AND ABS(price_payed) < ABS(price_e2-advance_payed)')->sum('(price_e2-price_payed-advance_payed)*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount2 = $tmpInvoiceC->where('ABS(price_payed) < ABS(price_e2-advance_payed)')->sum('(price_e2-price_payed-advance_payed)*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount3 = $tmpInvoiceC->
                                    where('MONTH(inv_date) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(inv_date) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))')->
                                    sum('price_e2*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount4 = $tmpInvoiceC->
                                    where('MONTH(inv_date) = MONTH(NOW()) AND YEAR(inv_date) = YEAR(NOW())')->
                                    sum('price_e2*currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount5 = $tmpInvoiceC->
                                    where('cl_invoice_arrived.due_date <= DATE_SUB(NOW(),INTERVAL 7 DAY) AND ABS(price_payed) < ABS(price_e2-advance_payed)')->
                                    sum('(price_e2-price_payed-advance_payed)*currency_rate');
        }
        //$this->translator->setPrefix(['applicationModule.InvoiceArrived']);
        $this->template->headerText = [0 => [$this->translator->translate('Dnes_po_splatnosti'), $tmpAmount, $this->settings->cl_currencies->currency_name, 0, 'label-warning', 'rightsFor' => 'report'],
            1 => [$this->translator->translate('Týden_a_více_nezaplaceno'), $tmpAmount5, $this->settings->cl_currencies->currency_name, 0, 'label-danger', 'rightsFor' => 'report'],
            2 => [$this->translator->translate('Celkem_nezaplaceno'), $tmpAmount2, $this->settings->cl_currencies->currency_name, 0, 'label-warning', 'rightsFor' => 'report'],
            3 => [$this->translator->translate('Obrat_v_tomto_měsíci'), $tmpAmount4, $this->settings->cl_currencies->currency_name, 0, 'label-success', 'rightsFor' => 'report'],
            4 => [$this->translator->translate('Obrat_v_minulém_měsíci'), $tmpAmount3, $this->settings->cl_currencies->currency_name, 0, 'label-success', 'rightsFor' => 'report']];
        //dump($this->conditionRows);
        //die;

        $tmpDataVat = $this->DataManager->find($this->id);
        if ($tmpDataVat) {
            $tmpDate = $tmpDataVat->vat_date;
        } else {
            $tmpDate = new \Nette\Utils\DateTime();
        }
        $this->template->RatesVatValid = $this->RatesVatManager->findAllValid($tmpDate);
        $this->template->arrInvoiceVat = $this->getArrInvoiceVat();
        $this->template->paymentModalShow = $this->paymentModalShow;


        //27.08.2018 - fill selectbox with partners_book_workers
        if (!$this->bscOff) {
            if ($tmpInvoice = $this->DataManager->find($this->id)) {
                if (isset($tmpInvoice['cl_partners_book_id'])) {
                    $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
                } else {
                    $tmpPartnersBookId = 0;
                }

            } else {
                $tmpPartnersBookId = 0;
            }
            $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
            $this['edit']['cl_partners_book_workers_id']->setItems($arrWorkers);
        }
      //  bdump($this->id, 'renderDefault this->id');
      //  bdump($this->bscId, 'renderDefault this->bscId');

    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);

        if ($defData = $this->DataManager->findOneBy(['id' => $this->id])) {

            $this['edit']['price_base0']->caption = $this->translator->translate("Základ_pro_0%");
            $this['edit']['price_base1']->caption = $this->translator->translate("Základ_pro") . $defData['vat1'] . "%";
            $this['edit']['price_base2']->caption = $this->translator->translate("Základ_pro") . $defData['vat2'] . "%";
            $this['edit']['price_base3']->caption = $this->translator->translate("Základ_pro") . $defData['vat3'] . "%";
            $this['edit']['price_vat1']->caption = $this->translator->translate("DPH") . ' ' . $defData['vat1'] . "%";
            $this['edit']['price_vat2']->caption = $this->translator->translate("DPH") . ' ' . $defData['vat2'] . "%";
            $this['edit']['price_vat3']->caption = $this->translator->translate("DPH") . ' ' . $defData['vat3'] . "%";
            $this['edit']['price_total1']->caption = $this->translator->translate("sDPH_1") . ' ' . $defData['vat1'] . "%";
            $this['edit']['price_total2']->caption = $this->translator->translate("sDPH_2") . ' ' . $defData['vat2'] . "%";
            $this['edit']['price_total3']->caption = $this->translator->translate("sDPH_3") . ' ' . $defData['vat3'] . "%";

            $this['edit']['price_base0']->setHtmlAttribute('data-vat', '0');
            $this['edit']['price_base1']->setHtmlAttribute('data-vat', $defData['vat1']);
            $this['edit']['price_base2']->setHtmlAttribute('data-vat', $defData['vat2']);
            $this['edit']['price_base3']->setHtmlAttribute('data-vat', $defData['vat3']);

            $this['edit']['price_base1']->setHtmlAttribute('data-vatval', $this['edit']['price_vat1']->getHtmlId());
            $this['edit']['price_base2']->setHtmlAttribute('data-vatval', $this['edit']['price_vat2']->getHtmlId());
            $this['edit']['price_base3']->setHtmlAttribute('data-vatval', $this['edit']['price_vat3']->getHtmlId());
            $this['edit']['price_e2']->setHtmlAttribute('data-urlUpdate', $this->link('savePrices!'));
        }
        //$this->template->qrCode = $this->qrService->getQrImage('testttt');
        //'pricelist2' => $this->link('SavePartner!'),
        $this->template->customUrl = ['pricelist2' => $this->link('RedrawPriceList2!'),
            'duedate' => $this->link('RedrawDueDate2!'),
            'showflash' => $this->link('showFlash!')
        ];

        $tmpDataVat = $this->DataManager->find($this->id);
        if ($tmpDataVat) {
            $tmpDate = $tmpDataVat->vat_date;
        } else {
            $tmpDate = new \Nette\Utils\DateTime();
        }
        $this->template->RatesVatValid = $this->RatesVatManager->findAllValid($tmpDate);
        $this->template->arrInvoiceVat = $this->getArrInvoiceVat();
        $this->template->paymentModalShow = $this->paymentModalShow;


        //27.08.2018 - fill selectbox with partners_book_workers
        if (!$this->bscOff) {
            if ($tmpInvoice = $this->DataManager->find($this->id)) {
                if (isset($tmpInvoice['cl_partners_book_id'])) {
                    $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
                } else {
                    $tmpPartnersBookId = 0;
                }

            } else {
                $tmpPartnersBookId = 0;
            }
            $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
            $this['edit']['cl_partners_book_workers_id']->setItems($arrWorkers);
        }
    }

    protected function getArrInvoiceVat()
    {
        $tmpDate = new \Nette\Utils\DateTime;
        if (!is_null($this->id)) {
            if ($tmpDate1 = $this->DataManager->find($this->id)) {
                $tmpDate = $tmpDate1->vat_date;
            }

        }
        $arrRatesVatValid = $this->RatesVatManager->findAllValid($tmpDate);
        $arrInvoiceVat = [];
        foreach ($arrRatesVatValid as $key => $one) {
            $baseValue = 0;
            $vatValue = 0;
            $basePayedValue = 0;
            $vatPayedValue = 0;

            $arrInvoiceVat[$one['rates']] = ['base' => $baseValue,
                'vat' => $vatValue,
                'payed' => $basePayedValue,
                'vatpayed' => $vatPayedValue];
        }
        return ($arrInvoiceVat);

    }

    protected function getArrInvoicePay()
    {
        $tmpData = $this->DataManager->find($this->id);
        $arrInvoicePay = [];
        foreach ($tmpData->related('cl_invoice_payments')->where('pay_type = 0') as $key => $one) {
            $arrInvoicePay[$key] = ['pay_date' => $one->pay_date,
                'pay_price' => $one->pay_price,
                'pay_doc' => $one->pay_doc,
                'currency_name' => $one->cl_currencies->currency_name];
        }
        return ($arrInvoicePay);

    }

    public function handleMakePayment($id)
    {
        $retArr = $this->DataManager->makePayment($id);
        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error']), 'error');
        } else {
            $this->flashmessage($this->translator->translate($retArr['success']), 'success');
        }
        $this->redrawControl('content');
    }



    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('inv_number', $this->translator->translate('Účetní_číslo_faktury'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Účetní_číslo_faktury'));
        $form->addText('rinv_number', $this->translator->translate('Číslo_faktury'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-checkduplicity', $this->link('checkDuplicity!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_faktury'));
        $form->addText('od_number', $this->translator->translate('Číslo_objednávky'), 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_objednávky'));
        $form->addText('delivery_number', $this->translator->translate('Dodací_list'), 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Dodací_list'));
        $form->addText('inv_date', $this->translator->translate('Datum_vystavení'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vystavení'));
        $form->addText('vat_date', $this->translator->translate('DUZP'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_uskutečnění_zdanitelného_plnění'));
        $form->addText('due_date', $this->translator->translate('Splatnost'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Splatnost'));
        $form->addText('arv_date', $this->translator->translate('Datum_přijetí'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Přijetí'));
        $form->addText('inv_title', $this->translator->translate('Popis'), 150, 150)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis'));
        $form->addTextArea('inv_memo', $this->translator->translate('Poznámka'), 50, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Poznámka'));
        $form->addText('var_symb', $this->translator->translate('Var._symbol'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Var._symbol'));
        $form->addText('konst_symb', $this->translator->translate('Konst._symbol'), 5, 5)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Konst._symbol'));
        $form->addText('spec_symb', $this->translator->translate('Spec._symbol'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Spec._symbol'));
        $form->addText('price_correction', $this->translator->translate('Úprava_ceny'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Úprava_ceny'));

        $form->addText('price_base0', $this->translator->translate('V_sazbě_0%'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-vat', '0')
            ->setHtmlAttribute('placeholder', $this->translator->translate('V_sazbě_0%'));

        $form->addText('price_base1', $this->translator->translate('V_sazbě_1'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-vat', '0')
            ->setHtmlAttribute('placeholder', $this->translator->translate('V_sazbě_1'));

        $form->addText('price_vat1', $this->translator->translate('DPH_1'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('DPH_1'));

        $form->addText('price_base2', $this->translator->translate('V_sazbě_2'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-vat', '0')
            ->setHtmlAttribute('placeholder', $this->translator->translate('V_sazbě_2'));

        $form->addText('price_vat2', $this->translator->translate('DPH_2'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('DPH_2'));

        $form->addText('price_base3', $this->translator->translate('V_sazbě_3'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-vat', '0')
            ->setHtmlAttribute('placeholder', $this->translator->translate('V_sazbě_3'));

        $form->addText('price_vat3', $this->translator->translate('DPH_3'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('DPH_3'));

        $form->addText('price_total1', $this->translator->translate('sDPH_1'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('sDPH_1'));

        $form->addText('price_total2', $this->translator->translate('sDPH_2'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('sDPH_2'));

        $form->addText('price_total3', $this->translator->translate('sDPH_3'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('sDPH_3'));

        if ($this->settings->platce_dph) {
            $tmpPrice_e2_label = $this->translator->translate("Celkem_bez_DPH");
        } else {
            $tmpPrice_e2_label = $this->translator->translate("Fakturováno");
        }

        $form->addText('price_e2', $tmpPrice_e2_label, 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Celkem_bez_DPH'));

        $form->addText('price_e2_vat', $this->translator->translate('Celkem_s_DPH'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Celkem_s_DPH'));

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate("Středisko"), $arrCenter)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte_středisko'));

     /*   $arrTypes = $this->InvoiceTypesManager->findAll()->where('inv_type = ?', 4)->fetchPairs('id', 'name');
        $form->addSelect('cl_invoice_types_id', "Druh dokladu:", $arrTypes)
            ->setHtmlAttribute('data-placeholder', 'Zvolte druh dokladu')
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetGroupNumberSeries!'))
            ->setPrompt('Zvolte druh dokladu');
*/
        $form->addCheckbox('pdp', $this->translator->translate('Přenesená_daňová_povinnost'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('data-urlajax', $this->link('PDP!'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('import', $this->translator->translate('Importní_fa'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('recalc_disabled', $this->translator->translate('Nepočítat'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('title', $this->translator->translate('Částky_ve_faktuře_nebudou_počítány_automaticky'))
            ->setHtmlAttribute('class', 'items-show');

        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice_arrived')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_faktury'))
            ->setPrompt($this->translator->translate('Zvolte_stav_faktury'));

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


        /*        $mySection = $this->getSession('selectbox'); // returns SessionSection with given name
                //06.07.2018 - session selectbox is filled via baselist->handleUpdatePartnerInForm which is called by ajax from onchange event of selectbox
                //this is necessary because Nette is controlling values of selectbox send in form with values which were in selectbox accesible when it was created.
                if (isset($mySection->cl_partners_book_id_values ))
                {
                    $arrPartners = 	$mySection->cl_partners_book_id_values;
                }else{
                    $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
                }*/

        //
        $form->addSelect('cl_partners_book_id', $this->translator->translate("Dodavatel"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_dodavatele'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte_dodavatele'));


        $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
        $form->addSelect('cl_partners_book_workers_id', $this->translator->translate("Kontakt"), $arrWorkers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
            ->setPrompt($this->translator->translate('Zvolte_kontaktní_osobu'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_payment_types_id', $this->translator->translate('Forma_úhrady'), $arrPay)
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm');

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setPrompt($this->translator->translate('Zvolte měnu'));
        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));
        //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');
        //$arrUsers = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->fetchPairs('id','name');
        $arrUsers = [];
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        //dump($arrUsers);
        //die;
        $form->addSelect('cl_users_id', $this->translator->translate("Nakoupil"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka_který_nakoupil'))
            ->setPrompt($this->translator->translate('Zvolte_nákupčího'));
        //$form->addTextArea('footer_txt', 'Zápatí:', 100,3 )
        //	->setHtmlAttribute('placeholder','Text v zápatí faktury');

        $arrBank = $this->PartnersAccountManager->findAll()->
                        where('cl_partners_book_id = ?', $tmpPartnersBookId)->
                        select('cl_partners_account.id, CONCAT(cl_currencies.currency_code, " ", account_code, "/", bank_code) AS account_number')->
                        order('cl_currencies.currency_code')->fetchPairs('id', 'account_number');

        $form->addSelect('cl_partners_account_id', $this->translator->translate('Účet_dodavatele'), $arrBank)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_účet'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_účet'));


        $form->onValidate[] = [$this, 'FormValidate'];
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('store_out', $this->translator->translate('Vydat_za_skladu'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
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
        //bdump($data);
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
        {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }
        //13.06.2021 - invoice number uniqueness
        $tmpData = $this->DataManager->findAll()->where('inv_number = ? AND id != ?', $data['inv_number'], $this->id)->fetch();
        if ($tmpData){
            $form->addError($this->translator->translate('Zadané_číslo_faktury_je_již_použité'));
        }

        $this->redrawControl('content');
    }


    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() || $form['send_fin']->isSubmittedBy()) {
            //remove format from numbers
            $data = $this->removeFormat($data);
            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {
                    $this->DataManager->update($data, TRUE);
                    $this->DataManager->paymentUpdate($this->id);

                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                } else {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            } else {
                //$this->flashMessage('Změny nebyly uloženy.', 'success');
            }
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('content');
        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');
        }
    }


    protected function createComponentHeaderEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        //$form->addCheckbox('header_show', 'Tiskount záhlaví');
        $form->addTextArea('header_txt', $this->translator->translate('Záhlaví'), 100, 10)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Text_v_záhlaví_faktury'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepHeaderBack');
        $form->onSuccess[] = array($this, 'SubmitEditHeaderSubmitted');
        return $form;
    }

    public function stepHeaderBack()
    {
        $this->headerModalShow = FALSE;
        $this->activeTab = 2;
        $this->redrawControl('headerModalControl');
    }

    public function SubmitEditHeaderSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data, TRUE);
        }
        $this->headerModalShow = FALSE;
        $this->activeTab = 2;
        $this->redrawControl('items');
        $this->redrawControl('headerModalControl');
    }

    protected function createComponentFooterEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        //$form->addCheckbox('footer_show', 'Tiskount zápatí');
        $form->addTextArea('footer_txt', $this->translator->translate('Zápatí'), 100, 3)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Text_v_zápatí_faktury'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepFooterBack'];

        $form->onSuccess[] = [$this, 'SubmitEditFooterSubmitted'];
        return $form;
    }

    public function stepFooterBack()
    {
        $this->headerModalShow = FALSE;
        $this->activeTab = 3;
        $this->redrawControl('footerModalControl');
    }

    public function SubmitEditFooterSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data, TRUE);
        }
        $this->footerModalShow = FALSE;
        $this->activeTab = 3;
        $this->redrawControl('items');
        $this->redrawControl('footerModalControl');
    }


   /* public function handleGetCurrencyRate($idCurrency)
    {
        if ($rate = $this->CurrenciesManager->findOneBy(array('id' => $idCurrency)))
            echo($rate->fix_rate);
        else {
            echo(0);
        }
        //in future there can be another work with rates

        $this->terminate();
    }*/

    public function handleCheckDuplicity($check_id, $rinv_number, $price_e2_vat, $partners_book_id)
    {
        $arrData = [];
        $arrData['result'] = 0;
        if ($partners_book_id != 0 && $price_e2_vat != 0) {
            if ($tmpResult = $this->DataManager->findAll()->where('rinv_number = ? AND id != ? AND cl_partners_book_id = ? AND price_e2_vat = ?', $rinv_number, $check_id, $partners_book_id, $price_e2_vat)
                ->fetch()) {
                //$this->flashMessage('Faktura s tímto číslem, částkou a dodavatelem již existuje!', "danger");
                $arrData['result'] = 1;
            }

        } elseif ($partners_book_id == 0 && $price_e2_vat != 0) {
            if ($tmpResult = $this->DataManager->findAll()->where('rinv_number = ? AND id != ? AND price_e2_vat = ?', $rinv_number, $check_id, $price_e2_vat)
                ->fetch()) {
                //$this->flashMessage('Faktura s tímto číslem a částkou již existuje!', "danger");
                $arrData['result'] = 2;
            }

        } elseif ($partners_book_id != 0 && $price_e2_vat == 0) {
            if ($tmpResult = $this->DataManager->findAll()->where('rinv_number = ? AND id != ? AND cl_partners_book_id = ? ', $rinv_number, $check_id, $partners_book_id)
                ->fetch()) {
                //$this->flashMessage('Faktura s tímto číslem a dodavatelem již existuje!', "danger");
                $arrData['result'] = 3;
            }

        } elseif ($partners_book_id == 0 && $price_e2_vat == 0) {
            if ($tmpResult = $this->DataManager->findAll()->where('rinv_number = ? AND id != ? ', $rinv_number, $check_id)
                ->fetch()) {
                //$this->flashMessage('Faktura s tímto číslem již existuje!', "danger");
                $arrData['result'] = 4;
            }
        }



        $this->payload->data = $arrData;
        $this->redrawControl('flash');
        //$this->sendJson($arrData, Json::PRETTY);
    }

    /*
     * handle for showing nittro flash message from javascript ajax calling
     */
    public function handleShowFlash($result)
    {
        if ($result == 1)
        {
            $this->flashMessage($this->translator->translate('Faktura_s_tímto_číslem,_částkou_a_dodavatelem_již_existuje!'), "danger");
        }elseif ($result == 2)
        {
            $this->flashMessage($this->translator->translate('Faktura_s_tímto_číslem_a_částkou_již_existuje'), "danger");
        }elseif ($result == 3)
        {
            $this->flashMessage($this->translator->translate('Faktura_s_tímto_číslem_a_dodavatelem_již_existuje'), "danger");
        }elseif ($result == 4)
        {
            $this->flashMessage($this->translator->translate('Faktura_s_tímto_číslem_již_existuje'), "danger");
        }

        $this->redrawControl('flash');
    }

    public function handleMakeRecalc($idCurrency,$rate,$oldrate,$recalc)
    {
		//in future there can be another work with rates
		//dump($this->editId);
		if ($rate>0)
		{
			if ($recalc == 1)
			{
			$recalcItems = $this->InvoiceItemsManager->findBy(['cl_invoice_id',$this->id]);
			foreach($recalcItems as $one)
			{
				//$data = array();
				//$data['price_s'] = $one['price_s'] * $oldrate / $rate;

				//if ($this->settings->platce_dph == 1)
				$data['price_e'] = $one['price_e'] * $oldrate / $rate;
				$data['price_e2'] = $one['price_e2'] * $oldrate  / $rate;	    
				$data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;	    	    

				$one->update($data);
			}
			}

			//we must save parent data 
			$parentData = [];
			$parentData['id'] = $this->id;
			if ($rate<>$oldrate)
			$parentData['currency_rate'] = $rate;

			$parentData['cl_currencies_id'] = $idCurrency;
			$this->DataManager->update($parentData);


			$this->UpdateSum($this->id,$this);

		}
		$this->redrawControl('items');	

    }
    
    public function UpdateSum()
    {
        $this->InvoiceArrivedManager->updateInvoiceSum($this->id);
        parent::UpdateSum();
        //$this->redrawControl('baselistArea');
        //$this->redrawControl('bscArea');
        //$this->redrawControl('bsc-child');

        $this['paymentListGrid']->redrawControl('editLines');
        $this['commissionListGrid']->redrawControl('editLines');
        //$this['sumOnDocs']->redrawControl();
    }       
    
    public function beforeAddLine($data) {
        //parent::beforeAddLine($data);
        //dump($data['control_name']);
        if ($data['control_name'] == "invoicelistgrid")
        {
            $data['price_e_type'] = $this->settings->price_e_type;
        }
        return $data;
    }
    
    public function ListGridInsert($sourceData)
    {
	    $arrPrice = [];
	    //if (isset($sourceData['cl_pricelist_id']))
        if (array_key_exists('cl_pricelist_id',$sourceData->toArray()))
	    {
			$arrPrice['id'] = $sourceData['cl_pricelist_id'];
			$sourcePriceData = $this->PriceListManager->find($sourceData->cl_pricelist_id);		
	    }  else {
			$arrPrice['id'] = $sourceData['id'];
			$sourcePriceData = $this->PriceListManager->find($sourceData->id);		
	    }
	    $arrPrice['price'] = $sourceData->price;
	    $arrPrice['price_vat'] = $sourceData->price_vat;
	    $arrPrice['vat'] = $sourceData->vat;
	    $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;		
	    
	
	    $arrData = [];
	    $arrData[$this->DataManager->tableName.'_id'] = $this->id;

	    $arrData['cl_pricelist_id'] = $sourcePriceData->id;
	    $arrData['item_order'] = $this->InvoiceItemsManager->findAll()->where($this->DataManager->tableName.'_id = ?', $arrData[$this->DataManager->tableName.'_id'])->max('item_order') + 1;

	    $arrData['item_label'] = $sourcePriceData->item_label;
	    $arrData['quantity'] = 1;

	    $arrData['units'] = $sourcePriceData->unit;

	    $arrData['vat'] = $arrPrice['vat'];
		
	    $arrData['price_e_type'] = $this->settings->price_e_type;		
		if ($arrData['price_e_type'] == 1)
		{
			$arrData['price_e'] = $arrPrice['price_vat'];
		}else{
			$arrData['price_e'] = $arrPrice['price'];
		}

	    $arrData['price_e2'] = $arrPrice['price'];
	    $arrData['price_e2_vat'] = $arrPrice['price_vat'];
	    

	    
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
	    $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
	    $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
	    $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;


		
	    $row = $this->InvoiceItemsManager->insert($arrData);
	    $this->updateSum($this->id,$this);		    
	    return($row);
	    
    }
    
    //javascript call when changing cl_partners_book_id
    public function handleSavePartner($cl_partners_book_id)
    {
		//dump($cl_partners_book_id);
		$arrUpdate = array();
		$arrUpdate['id'] = $this->id;
		$arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL:$cl_partners_book_id ) ;

		//dump($arrUpdate);
		//die;
		$this->DataManager->update($arrUpdate);


    }    
    
    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
		//dump($cl_partners_book_id);
        bdump($this->id, 'redrawpricelist2 this->id' );
        bdump($this->bscId, 'redrawpricelist2 this->bscId' );
		$arrUpdate = [];
		$arrUpdate['id'] = $this->id;
        //$arrUpdate['id'] = $this->bscId;
        $arrUpdate['cl_partners_book_id'] = $cl_partners_book_id;
		//$arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL:$cl_partners_book_id ) ;

		//dump($arrUpdate);
		//die;
		$this->DataManager->update($arrUpdate);
		$this->sendJson([]);
		//$this['invoicelistgrid']->redrawControl('pricelist2');
    }    
        
    
    
    //javascript call when changing prices
    public function handleSavePrices($data)
    {
	$arrUpdate = [];
	$arrUpdate['id'] = $this->id;

	$tmpData = json_decode($data, true);
	foreach($tmpData as $key => $one)
	{
	    $arrUpdate[$key] = $one;
	}

	$this->DataManager->update($arrUpdate);
	$this->redrawControl('items');
    }
    
    //javascript call when changing cl_partners_book_id or change inv_date
    public function handleRedrawDueDate2($invdate)
    {
		$invdate = date_create($invdate);
		//Debugger::fireLog($invdate);	

		$tmpData = $this->DataManager->find($this->id);
                //dump(isset($tmpData->cl_partners_book_id);
		$arrUpdate = [];
		$arrUpdate['id'] = $this->id;
		if (isset($tmpData['cl_partners_book_id']) && $tmpData->cl_partners_book->due_date > 0)
                {
			$strModify = '+'.$tmpData->cl_partners_book->due_date.' day';
                }else{
			$strModify = '+'.$this->settings->due_date.' day';
                }

		$arrUpdate['due_date'] = $invdate->modify($strModify);

		if (isset($tmpData['cl_partners_book_id']) && isset($tmpData->cl_partners_book->cl_payment_types_id))
		{
			$clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
			$spec_symb = $tmpData->cl_partners_book->spec_symb;
			$comment = $tmpData->cl_partners_book->comment;
		}
		else 
		{
			$clPayment = $this->settings->cl_payment_types_id;
			$spec_symb = "";
			$comment="";			
		}


		//Debugger::fireLog($arrUpdate);

		$this->DataManager->update($arrUpdate);

		//$this->redrawControl('duedate2');
		//echo($arrUpdate['due_date']->format('d.m.Y'));
		$return = ['due_date' => $arrUpdate['due_date']->format('d.m.Y'),
				'cl_payment_types_id' => $clPayment,
				'spec_symb' => $spec_symb,
				'inv_memo' => $comment];
		echo(json_encode($return));
		$this->terminate();
    }    

    public function handlePDP($value)
    {
		//Debugger::fireLog($value);
		//$tmpData = $this->DataManager->find($this->id);
		$arrUpdate = [];
		$arrUpdate['id'] = $this->id;
		if ($value == 'true')
			$arrUpdate['pdp'] = 1;
		else 
			$arrUpdate['pdp'] = 0;

		//Debugger::fireLog($arrUpdate);
		$this->DataManager->update($arrUpdate);
		$this->updateSum($this->id,$this);		    	
		$this['invoicelistgrid']->redrawControl('editLines');	
		//$this->redrawControl('formedit');
		$this->redrawControl('items');

    }
    
	    

    //control method to determinate if we can delete
    public function beforeDelete($lineId, $name = NULL) {
        $result = FALSE;
        if ($name == "paymentListGrid") {
            $tmpPayment = $this->InvoiceArrivedPaymentsManager->find($lineId);
            if ($tmpPayment && !is_null($tmpPayment['cl_cash_id'])){
                $tmpPayment->cl_cash->delete();
            }
            $result = TRUE;
        }
        return $result;
    }	    
    

    
    
    //aditional control before delete from baseList
    public function beforeDeleteBaseList($id)
    {

	return TRUE;
    }    
    
    
    
    public function emailSetStatus() {
		$this->setStatus($this->id, array('status_use' => 'invoice',
						's_new'  => 0,
						's_eml' => 1));
    }    


    
    public function handleGetGroupNumberSeries($cl_invoice_types_id)
    {
		//Debugger::fireLog($this->id);	    
		$arrData = array();
		$arrData['id'] = NULL;
		$arrData['number'] = '';	
		if ($data = $this->InvoiceTypesManager->find($cl_invoice_types_id))
		{
			//dump($data->cl_number_series_id);
			if ($tmpData = $this->DataManager->find($this->id))
			{
			    if ($data->cl_number_series_id == $tmpData->cl_number_series_id){
				
				    $tmpId = $this->id;
			    }else{
				    $tmpId = NULL;
				    //ponizit o 1 minule pouzitou ciselnou radu.
				    $this->NumberSeriesManager->update(array('id' => $tmpData->cl_number_series_id, 'last_number' => $tmpData->cl_invoice_types->cl_number_series->last_number - 1));
			    }

			    if ($data2 = $this->NumberSeriesManager->getNewNumber('invoice_arrived', $data->cl_number_series_id, $tmpId))
			    {
				    $arrData = $data2;
			    }
			    //update main data
			    $this->DataManager->update(array('id' => $this->id, 'inv_number' => $arrData['number'], 'cl_invoice_types_id' => $cl_invoice_types_id, 'cl_number_series_id' => $data->cl_number_series_id));
			}
		}

		echo(json_encode($arrData));
		$this->terminate();
    }
    
    public function handlePaymentShow()
    {
		$this->paymentModalShow = TRUE;
        $this->showModal('paymentModal');
        $this->redrawControl('paymentControl');
       // $this->redrawControl('contents');
    }
    public function handleCommissionShow()
    {
		$this->commissionModalShow = TRUE;
        $this->showModal('commissionModal');
		$this->redrawControl('commissionControl');
    }    

    public function handleHeaderShow()
    {
		$this->headerModalShow = TRUE;
		$this->redrawControl('headerModalControl');
    }    
    
    public function handleFooterShow()
    {
		$this->footerModalShow = TRUE;
		$this->redrawControl('footerModalControl');
    }    
        
    
    public function DataProcessMain($defValues, $data) {
		//$defValues['var_symb'] = preg_replace('/\D/', '', $defValues['inv_number']);
		
		return $defValues;
    }
    
    
    
    public function handleReport($index = 0)
    {
            $this->rptIndex = $index;
            $this->reportModalShow = TRUE;
            $this->redrawControl('baselistArea');
            $this->redrawControl('reportModal');
            $this->redrawControl('reportHandler');
    }



    public function beforeCopy($data) {
        parent::beforeCopy($data);
        $tmpNow = new \Nette\Utils\DateTime;
        $data['arv_date'] = $tmpNow;
        $data['inv_date'] = $tmpNow;
        $data['vat_date'] = $tmpNow;
        $data['pay_date'] = NULL;
        $data['price_payed'] = 0;
        $data['cl_payment_order_id'] = NULL;

        if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', 'invoice_arrived', 1)->fetch()) {
            $data['cl_status_id'] = $nStatus->id;
        }else{
            $data['cl_status_id'] = null;
        }

        return $data;
    }
    

	public function afterCopy($newLine, $oldLine) {
		//parent::afterCopy($newLine, $oldLine);
                
		/*$tmpPayments = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ?',$oldLine);
		foreach ($tmpPayments as $one)
		{
                    $tmpOne = $one->toArray();
                    $tmpOne['cl_invoice_arrived_id'] = $newLine;
                    unset($tmpOne['id']);
                    $this->InvoiceArrivedPaymentsManager->insert($tmpOne);
		}*/
	}
	
	//aditional processing data after save in listgrid
	public function afterDataSaveListGrid($dataId, $name = NULL)
	{
		parent::afterDataSaveListGrid($dataId, $name);
		bdump($name);
		//bdump($dataId);
		if ($name == "commissionListGrid") {
			//create pairedocs record
			$tmpCommission = $this->InvoiceArrivedCommissionManager->find($dataId);
			
			if ($tmpCommission) {
				//bdump($tmpCommission);
				$this->PairedDocsManager->insertOrUpdate(array('cl_invoice_arrived_id' => $this->id, 'cl_commission_id' => $tmpCommission->cl_commission_id));
			}
		}elseif ($name == "paymentListGrid") {

        }
	}
			
			
			
			
    protected function createComponentReportInvoiceBook($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    //$this->translator->setPrefix(['applicationModule.InvoiceArrived']);
            $now = new \Nette\Utils\DateTime;
            /*if ($this->settings->platce_dph)
            {
                $lcText1 = $this->translator->translate('DUZP_od');
                $lcText2 = $this->translator->translate('DUZP_do');
            }else{
                $lcText1 = $this->translator->translate('Vystaveno_od');
                $lcText2 = $this->translator->translate('Vystaveno_do');
            }*/
            $lcText1 = $this->translator->translate('Datum_od');
            $lcText2 = $this->translator->translate('Datum_do');
            $form->addText('date_from', $lcText1, 0, 16)
                    ->setDefaultValue('01.'.$now->format('m.Y'))
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

            $form->addText('date_to', $lcText2, 0, 16)
                    ->setDefaultValue($now->format('d.m.Y'))
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

            $form->addRadioList('type', 'Dle datumu', [1 => 'přijetí', 2 => 'zdanitelného plnění'])
                ->setDefaultValue(1);

            $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
            $form->addMultiSelect('cl_partners_book',$this->translator->translate('Dodavatel'), $tmpArrPartners)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'));

            $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice_arrived')->order('status_name')->fetchPairs('id','status_name');
            $form->addMultiSelect('cl_status_id',$this->translator->translate('Stav_dokladu'), $tmpArrStatus)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_stav'));

	    $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
            $form->addMultiSelect('cl_center_id',$this->translator->translate('Středisko'), $tmpArrCenter)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_středisko'));

	    $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);		
	    $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
			    ->setHtmlAttribute('multiple','multiple')
			    ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));
	
	
		$tmpArrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
		$form->addMultiSelect('cl_currencies_id',$this->translator->translate('Měna'), $tmpArrCurrencies)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_měnu'));
			    	    
	    
	    $form->addCheckbox('after_due_date', $this->translator->translate('Po_splatnosti'));
	    $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
	    $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));
	    $form->addCheckbox('not_finished', $this->translator->translate('Jen_nedokončené_zakázky'));
	    
            $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
            $form->addSubmit('save_xml', $this->translator->translate('uložit_do_XML'))->setHtmlAttribute('class','btn btn-sm btn-primary');
            $form->addSubmit('save_xls', $this->translator->translate('XLS'))->setHtmlAttribute('class','btn btn-sm btn-primary');
            $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		
	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportInvoiceBook');	    	    
		$form->onSuccess[] = array($this, 'SubmitReportInvoiceBookSubmitted');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackReportInvoiceBook()
    {	    
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
    }		

    public function SubmitReportInvoiceBookSubmitted(Form $form)
    {
		$data=$form->values;	
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xml']->isSubmittedBy() || $form['save_xls']->isSubmittedBy())
		{    
		    
			$tmpPodm = array();
			if ($data['after_due_date'] == 1)
			{		    
			    if ($this->settings->platce_dph)
					$tmpPodm = 'cl_invoice_arrived.due_date < NOW() AND cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2_vat';
			    else
					$tmpPodm = 'cl_invoice_arrived.due_date < NOW() AND cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2';
			}

			if ($data['not_payed'] == 1)
			{		    
			    if ($this->settings->platce_dph)
					$tmpPodm = 'cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2_vat';
			    else
					$tmpPodm = 'cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2';
			}
			
			if ($data['payed'] == 1)
			{		    
			    if ($this->settings->platce_dph)
					$tmpPodm = 'cl_invoice_arrived.price_payed >= cl_invoice_arrived.price_e2_vat';
			    else
					$tmpPodm = 'cl_invoice_arrived.price_payed >= cl_invoice_arrived.price_e2';
			}			
			

		    
			if ($data['date_to'] == "")
				$data['date_to'] = NULL; 			
			else
			{
				//$tmpDate = new \Nette\Utils\DateTime;
				//$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
				//date('Y-m-d H:i:s',strtotime($data['date_to']));
				$data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
			}

			
			if ($data['date_from'] == "")
				$data['date_from'] = NULL; 			
			else
				$data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));

            if ($data['type'] == 1){
                $dateType = 'cl_invoice_arrived.arv_date';
            }elseif ($data['type'] == 2){
                $dateType = 'cl_invoice_arrived.vat_date';
            }

			if (count($data['cl_partners_book']) == 0)
			{
				if ($this->settings->platce_dph)
				{
					$dataReport = $this->InvoiceArrivedManager->findAll()->
									where($tmpPodm)->
									where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to'])->
									order('cl_invoice_arrived.vat_date ASC,cl_invoice_arrived.rinv_number ASC');
				}else{
					$dataReport = $this->InvoiceArrivedManager->findAll()->
									where($tmpPodm)->
									where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to'])->
									order('cl_invoice_arrived.vat_date ASC, cl_invoice_arrived.rinv_number ASC');
				}
			}else{
				if ($this->settings->platce_dph)
				{
					$dataReport = $this->InvoiceArrivedManager->findAll()->
									where($tmpPodm)->
									where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to'])->
									where(array('cl_partners_book_id' =>  $data['cl_partners_book']))->
									order('cl_invoice_arrived.vat_date ASC,cl_invoice_arrived.rinv_number ASC');
				}else{
					$dataReport = $this->InvoiceArrivedManager->findAll()->
									where($tmpPodm)->
									where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to'])->
									where(array('cl_partners_book_id' =>  $data['cl_partners_book']))->
									order('cl_invoice_arrived.vat_date ASC, cl_invoice_arrived.rinv_number ASC');
				}
			}
			
			if ($data['not_finished'] == 1)
			{
			    $dataReport = $dataReport->having('SUM(:cl_invoice_arrived_commission.cl_commission.cl_status.s_fin)=0')->group('cl_invoice_arrived.id');
			}
			
			if (count($data['cl_currencies_id']) > 0)
			{
				$dataReport = $dataReport->where(array('cl_invoice_arrived.cl_currencies_id' =>  $data['cl_currencies_id']));
			}
			
			if (count($data['cl_status_id']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_status_id' =>  $data['cl_status_id']));
			}

			if (count($data['cl_center_id']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_invoice_arrived.cl_center_id' =>  $data['cl_center_id']));
			}			
			
			if (count($data['cl_users_id']) > 0)
			{
			    $dataReport = $this->DataManager->findAll()->
								where(array('cl_invoice_arrived.cl_users_id' =>  $data['cl_users_id']));				
			}
            //cl_status.status_name,
			$dataReport = $dataReport->select('cl_partners_book.company,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice_arrived.*');
			//bdump($dataReport->fetchAll());
			if ($form['save_pdf']->isSubmittedBy())
			{
			    $dataOther = array();//$this->CommissionTaskManager->find($itemId);
			    $dataSettings = $data;
			    //$dataSettingsPartners   = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
			    $dataOther['dataSettingsPartners']		= $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
			    $dataOther['dataSettingsStatus']		= $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');
			    $dataOther['dataSettingsCenter']		= $this->CenterManager->findAll()->where(array('id' =>$data['cl_center_id']))->order('name');
				$dataOther['dataSettingsCurrencies']	= $this->CurrenciesManager->findAll()->where(array('id' =>$data['cl_currencies_id']))->order('currency_code');
			    $dataOther['dataSettingsUsers']			= $this->UserManager->getAll()->where(array('id' =>$data['cl_users_id']))->order('name');
			    $tmpArrVat = array();
			    $tmpArr2 = array();
			    $tmpArr = $dataReport;
			    foreach ($tmpArr as $one)
			    {
                    if ($one->price_vat1 != 0)
                        $tmpArr2[$one->vat1] = $one->vat1;
			    }

			    $tmpArr = $dataReport;
			    foreach ($tmpArr as $one)
			    {
                    if ($one->price_vat2 != 0)
                        $tmpArr2[$one->vat2] = $one->vat2;
			    }

			    $tmpArr = $dataReport;
			    foreach ($tmpArr as $one)
			    {
                    if ($one->price_vat3 != 0)
                        $tmpArr2[$one->vat3] = $one->vat3;
			    }
                $tmpArr2[0] = 0;

			    foreach ($tmpArr2 as $one)
			    {			
                    $tmpArrVat[] = $one;
			    }
			    rsort($tmpArrVat);	
			    $dataOther['arrVat'] = $tmpArrVat;
			    $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/InvoiceArrived/ReportInvoiceBook.latte', $dataOther, $dataSettings, 'Kniha faktur přijatých');
			    $tmpDate1 = new \DateTime($data['date_from']);
			    $tmpDate2 = new \DateTime($data['date_to']);
			    $this->pdfCreate($template, $this->translator->translate('Kniha_faktur_přijatých_za_období ').date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));
			    
			    //$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;	
			    //$tmpTitle = 'Kniha faktur přijatých za období';
			    //$template = $this->createTemplate()->setFile( __DIR__.'/../templates/InvoiceArrived/ReportInvoiceBook.latte');
			    //$template->data = $dataReport;  
			    //$template->dataSettings = $data;
			    //$template->dataSettingsPartners = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
			    //$template->dataSettingsStatus = $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');

			    //dump($template->arrVat);
			    //die;
			    //$template->settings = $this->settings;			
			    //$template->title = $tmpTitle;
			    //$template->author = $tmpAuthor;
			    //$template->today = new \Nette\Utils\DateTime;
			    //$this->tmpLogo();
			//$pdf = new \PdfResponse\PdfResponse($template, $this->context);

		   	
			//erase tmp files			
		    }
		    elseif ($form['save_csv']->isSubmittedBy() )
		    {
                if ( $dataReport->count()>0)
                {
                    $filename = "Kniha faktur přijatých";
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename."-" .date('Ymd-Hi').".csv",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
		    }elseif($form['save_xls']->isSubmittedBy()) {
                if ( $dataReport->count()>0)
                {
                    $filename = "Kniha faktur přijatých";
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".xls", true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }elseif ($form['save_xml']->isSubmittedBy())
            {

                if ( $dataReport->count()>0)
                {
                    $arrResult = array();
                    foreach($dataReport as $key => $one)
                    {
                        $tmpInv = $one->toArray();
                        $arrResult[$key] = array('id' => $tmpInv['id'], 'inv_number' => $tmpInv['inv_number'], 'rinv_number' => $tmpInv['rinv_number'], 'inv_date' => $tmpInv['inv_date'],
                            'vat_date' => $tmpInv['vat_date'],'arv_date' => $tmpInv['arv_date'], 'due_date' => $tmpInv['due_date'], 'pay_date' => $tmpInv['pay_date'],
                            'var_symb' => $tmpInv['var_symb'], 'konst_symb' => $tmpInv['konst_symb'], 'inv_title' => $tmpInv['inv_title'],
                            'price_base0' => $tmpInv['price_base0'], 'price_base1' => $tmpInv['price_base1'], 'price_base2' => $tmpInv['price_base2'], 'price_base3' => $tmpInv['price_base3'],
                            'price_vat1' => $tmpInv['price_vat1'], 'price_vat2' => $tmpInv['price_vat2'], 'price_vat3' => $tmpInv['price_vat3'],
                            'vat1' => $tmpInv['vat1'], 'vat2' => $tmpInv['vat2'], 'vat3' => $tmpInv['vat3'],
                            'price_e2' => $tmpInv['price_e2'], 'price_e2_vat' => $tmpInv['price_e2_vat'], 'price_correction' => $tmpInv['price_correction'], 'price_payed' => $tmpInv['price_payed'],
                            'base_payed0' => $tmpInv['base_payed0'], 'base_payed1' => $tmpInv['base_payed1'], 'base_payed2' => $tmpInv['base_payed2'], 'base_payed3' => $tmpInv['base_payed3'],
                            'vat_payed1' => $tmpInv['vat_payed1'], 'vat_payed2' => $tmpInv['vat_payed2'], 'vat_payed3' => $tmpInv['vat_payed3'], 'advance_payed' => $tmpInv['advance_payed'],
                            'pdp' => $tmpInv['pdp'], 'price_e_type' => $tmpInv['price_e_type'],
                            'delivery_number' => $tmpInv['delivery_number'], 'od_number' => $tmpInv['od_number'], 'druh faktury' => $tmpInv['druh faktury'],
                            'druh platby' => $tmpInv['druh platby'], 'status_name' => $tmpInv['status_name'], 'currency_code' => $tmpInv['currency_code'], 'currency_rate' => $tmpInv['currency_rate']);
                        $tmpPartnerBook = $one->ref('cl_partners_book');
                        $arrResult[$key]['partners_book'] = array('id' => $tmpPartnerBook['id'], 'company' => $tmpPartnerBook['company'],'street' => $tmpPartnerBook['street'],
                            'city' => $tmpPartnerBook['city'],'zip' => $tmpPartnerBook['zip'],'ico' => $tmpPartnerBook['ico'],'dic' => $tmpPartnerBook['dic']);
                        /*$arrResult[$key]['invoice_items'] = $one->related('cl_invoice_items')->
                                                    select('cl_pricelist_id,cl_pricelist.identification,cl_invoice_items.item_label, cl_invoice_items.quantity, cl_invoice_items.units, cl_invoice_items.price_s, cl_invoice_items.price_e,  cl_invoice_items.price_e_type,discount,price_e2, price_e2_vat,cl_invoice_items.cl_storage_id')->
                                                    fetchAll();
                        $arrResult[$key]['invoice_items_back'] = $one->related('cl_invoice_items_back')->
                                                    select('cl_pricelist_id,cl_pricelist.identification,cl_invoice_items_back.item_label, cl_invoice_items_back.quantity, cl_invoice_items_back.units, cl_invoice_items_back.price_s, cl_invoice_items_back.price_e,  cl_invoice_items_back.price_e_type,discount,price_e2, price_e2_vat,cl_invoice_items_back.cl_storage_id')->
                                                    fetchAll();*/
                    }
                    $filename = "Kniha faktur přijatých";
                    $this->sendResponse(new \XMLResponse\XMLResponse($arrResult, $filename."-" .date('Ymd-Hi').".xml", NULL, 'invoices'));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XML_uloženy_Zadaným_podmínkám_nevyhověl_žádný_záznam'), 'danger');
                    $this->redirect('default');
                }
            }
			
		}
	}
	
	
    /**
     * process QR data currency, supplier ID and VATID, account
     * and return IDs from database
     * 
     */
    public function handleProcessQrData($cc,$vii,$ini,$acc)
    {
	$arrResult = [];
	if ($tmpCurrency = $this->CurrenciesManager->findAll()->where('currency_code = ?', $cc)->fetch()) {
	    $arrResult['currency_id'] = $tmpCurrency->id;
	}
	if ($tmpPartner = $this->PartnersManager->findAll()->where('ico = ? OR dic = ?', $ini, $vii)->fetch()) {
	    $arrResult['partners_book_id'] = $tmpPartner->id;
	    $arrResult['company']	   = $tmpPartner->company;
	}else{
	    $arrPartner = $this->PartnersManager->GetAres2($ini);
	    $arrData = [];
	    $arrData['ico']	    = $arrPartner['in'];
	    $arrData['dic']	    = $arrPartner['tin'];
	    $arrData['company']	    = $arrPartner['company'];
	    $arrData['zip']	    = $arrPartner['zip'];
	    $arrData['street']	    = $arrPartner['street'].$arrPartner['house_number'];
	    $arrData['city']	    = $arrPartner['city'];
	    $arrData['platce_dph']  = $arrPartner['vat_payer'];
	    $arrData['iban_code']   = $acc;
	    if (substr($acc,2) == "CZ"){
		$arrData['account_code']    = substr($acc,8);
		$arrData['bank_code']	    = substr($acc,4,4);			
	    }
	    $row = $this->PartnersManager->insert($arrData);
	    $arrResult['partners_book_id']  = $row->id;
	    $arrResult['company']	    = $row->company;
	}
		
	$this->payload->data = $arrResult;
	$this->sendPayload();
    //    $this->redrawControl('content');
    }


    public function handleNew($data = '', $defData)
    {
        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        //$tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        //$tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        //if ($tmpBranch) {
          //  $this->numberSeries['cl_number_series_id'] = $tmpBranch->cl_number_series_id_invoicearrived;
        //}
        // bdump($this->numberSeries);

        if (intval($data) > 0){
            $this->numberSeries['cl_number_series_id'] = $data;
            $data = '';
        }else{
            $this->numberSeries['use'] = $data;
        }

        parent::handleNew($data = '', $defData);
    }
	
	public function handleShowTextsUse()
	{
		$this->pairedDocsShow = TRUE;
		$this->showModal('textsUseModal');
		$this->redrawControl('textsUse');
	}



    protected function createComponentExportInvoiceBook($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        if ($this->settings->platce_dph)
        {
            $lcText1 = $this->translator->translate('DUZP_od');
            $lcText2 = $this->translator->translate('DUZP_do');
        }else{
            $lcText1 = $this->translator->translate('Vystaveno_od');
            $lcText2 = $this->translator->translate('Vystaveno_do');
        }
        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice_arrived')->order('status_name')->fetchPairs('id','status_name');
        $form->addMultiSelect('cl_status_id',$this->translator->translate('Stav_dokladu'), $tmpArrStatus)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_stav'));

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_center_id',$this->translator->translate('Středisko'), $tmpArrCenter)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_středisko'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_payment_types_id',$this->translator->translate('Forma_úhrady'),$arrPay)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_formu_úhrady_pro_tisk'));



        $form->addCheckbox('after_due_date', $this->translator->translate('Po_splatnosti'));
        $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
        $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));

        $form->addSubmit('save_xml', $this->translator->translate('Exportovat_faktury'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_cash_xml', $this->translator->translate('Exportovat_úhrady_faktur'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackExportBook');
        $form->onSuccess[] = array($this, 'SubmitExportBookSubmitted');

        return $form;
    }

    public function stepBackExportBook()
    {
        $this->rptIndex = 1;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitExportBookSubmitted(Form $form)
    {
        $data=$form->values;
        //dump(count($data['cl_partners_book']));
        //die;
        if ($form['save_xml']->isSubmittedBy() || $form['save_cash_xml']->isSubmittedBy()  )
        {

            $data['cl_partners_branch'] = array();
            $tmpPodm = array();
            if ($data['after_due_date'] == 1)
            {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice_arrived.due_date < NOW() AND cl_invoice_arrived.price_payed < cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice_arrived.due_date < NOW() AND cl_invoice_arrived.price_payed < cl_invoice.price_e2';
            }

            if ($data['not_payed'] == 1)
            {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2';
            }

            if ($data['payed'] == 1)
            {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice_arrived.price_payed >= cl_invoice_arrived.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice_arrived.price_payed >= cl_invoice_arrived.price_e2';
            }


            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else
            {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));

            if ($this->settings->platce_dph)
            {
                $dataReport = $this->InvoiceArrivedManager->findAll()->
                                        where($tmpPodm)->
                                        where('cl_invoice_arrived.vat_date >= ? AND cl_invoice_arrived.vat_date <= ?', $data['date_from'], $data['date_to'])->
                                        order('cl_invoice_arrived.inv_number ASC, cl_invoice_arrived.vat_date ASC');
            }else{
                $dataReport = $this->InvoiceArrivedManager->findAll()->
                                        where($tmpPodm)->
                                        where('cl_invoice_arrived.inv_date >= ? AND cl_invoice_arrived.inv_date <= ?', $data['date_from'], $data['date_to'])->
                                        order('cl_invoice_arrived.inv_number ASC, cl_invoice_arrived.vat_date ASC');
            }

            if (count($data['cl_status_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_status_id' =>  $data['cl_status_id']));
            }



            if (count($data['cl_center_id']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_invoice_arrived.cl_center_id' =>  $data['cl_center_id']));
            }

            if (count($data['cl_users_id']) > 0)
            {
                $dataReport = $dataReport->
                where(array('cl_invoice_arrived.cl_users_id' =>  $data['cl_users_id']));
            }

            if (count($data['cl_payment_types_id']) > 0)
            {
                $dataReport = $dataReport->
                where(array('cl_invoice_arrived.cl_payment_types_id' =>  $data['cl_payment_types_id']));
            }
            $dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice_arrived.*');


            if ($form['save_xml']->isSubmittedBy())
            {
                $this->exportPohoda($dataReport);
            }elseif($form['save_cash_xml']->isSubmittedBy()){
               // $this->exportCashPohoda($dataReport);
            }
        }
    }

    private function exportPohoda($dataReport){
        $tmpArrVat = $this->getVats($dataReport);
        // zadejte ICO
        $pohoda = new Pohoda\Export($this->settings->ico);
        $customer = array();
        try {
            foreach($dataReport as $key => $one) {
                if ($this->settings->platce_dph == 1) {
                    $validRates = $this->RatesVatManager->findAllValid($one->vat_date);
                }else{
                    $validRates = array();
                }
                // cislo faktury
                $invoice = new Pohoda\Invoice($one->inv_number);

                $invoice->setType(Pohoda\Invoice::RECEIVED_TYPE);
                // cena faktury s DPH (po staru) - volitelně
                $invoice->setText($one->inv_title);
                //$price = 1000;
                $arrPrice = array();
                /*foreach ($validRates as $keyR => $oneR){
                    for ($x = 0; $x <= 3; $x++){
                        if ($x == 0 && $one['price_base0'] != 0){
                            $arrPrice['zakl_dan0']	= $one['price_base0'];
                        }elseif (isset($one['vat'.$x]) && $one['vat'.$x] != $oneR['rates']) {
                            $arrPrice['zakl_dan'.$x]	= $one['price_base'.$x];
                            $arrPrice['dan'.$x]		    = $one['price_vat'.$x];
                        }
                    }
                }*/
                $arrPrice['zakl_dan0'] = 0;
                $arrPrice['zakl_dan1'] = 0;
                $arrPrice['zakl_dan2'] = 0;
                $arrPrice['zakl_dan3'] = 0;
                $arrPrice['dan1']      = 0;
                $arrPrice['dan2']      = 0;
                $arrPrice['dan3']      = 0;
                $arrPrice['sazba1']    = 0;
                $arrPrice['sazba2']    = 0;
                $arrPrice['sazba3']    = 0;
                for ($x = 0; $x <= 3; $x++){
                    foreach ($validRates as $keyR => $oneR) {
                        if (isset($one['vat' . $x]) && $oneR['rates'] == $one['vat' . $x])
                        {
                            if ($one['vat' . $x] == 0 && $one['price_base'.$x] != 0){
                                $arrPrice['zakl_dan0']      = $one['price_base'.$x];
                                $arrPrice['sazba' . $x]     = $one['vat' . $x];
                            }elseif ( $one['price_base'.$x] != 0) {
                                $arrPrice['zakl_dan' . $x]  = $one['price_base' . $x];
                                $arrPrice['dan' . $x]       = $one['price_vat' . $x];
                                $arrPrice['sazba' . $x]     = $one['vat' . $x];
                            }
                        }elseif (!isset($one['vat' . $x]) && $one['price_base'.$x] != 0 && $arrPrice['zakl_dan0'] == 0 ){
                            //dump($x);
                            //dump($one['price_base'.$x]);
                            $arrPrice['zakl_dan0'] = $one['price_base'.$x];
                        }
                    }
                }


                $invoice->setPaymentType($this->ArraysManager->getPaymentTypePohodaName($one->cl_payment_types['payment_type']));
                //dump($arrPrice);
                //nulova sazba
                $invoice->setPriceNone($arrPrice['zakl_dan0']);
                //nizsi sazba dph
                $invoice->setPriceLow($arrPrice['zakl_dan2']); //cena bez dph ve snizene sazbe
                $invoice->setPriceLowVAT($arrPrice['dan2']); //samotna dan
                $invoice->setPriceLowSum($arrPrice['zakl_dan2'] + $arrPrice['dan2']); // cena s dph ve snizene sazba

                //druha nizsi sazba dph
                $invoice->setPriceLow3($arrPrice['zakl_dan3']); //cena bez dph ve snizene sazbe
                $invoice->setPriceLow3VAT($arrPrice['dan3']); //samotna dan
                $invoice->setPriceLow3Sum($arrPrice['zakl_dan3'] + $arrPrice['dan3']); //cena s dph v druhe snizene sazbe

                //nebo vyssi sazba dph
                $invoice->setPriceHigh($arrPrice['zakl_dan1']); //cena bez dph ve zvysene sazbe
                $invoice->setPriceHightVAT($arrPrice['dan1']); //samotna dan
                $invoice->setPriceHighSum($arrPrice['zakl_dan1'] + $arrPrice['dan1']); //cena s dph ve zvysene sazbe
                $invoice->setPriceRound($one->price_correction);
                $invoice->setWithVat($one->import == 0); //viz inv:classificationVAT - true nastavi cleneni dph na inland - tuzemske plneni, jinak da nonSubsume - nezahrnovat do DPH

                if ($one->cl_currencies_id != $this->settings->cl_currencies_id) {
                    $invoice->setForeignCurrency($one->cl_currencies->currency_code, (float)$one->currency_rate);
                }

                //$invoice->setActivity('eshop'); //cinnost v pohode [volitelne, typ:ids]
                //$invoice->setCentre('stredisko'); //stredisko v pohode [volitelne, typ:ids]
                if (!is_null($one->cl_commission_id)) {
                    $invoice->setContract($one->cl_commission->cm_number); //zakazka v pohode [volitelne, typ:ids]
                }

                //nebo pridanim polozek do faktury (nove)
                //$invoice->setText('Faktura za zboží');
                //polozky na fakture
                /*foreach ($one->related('cl_invoice_items')->order('item_order') as $keyItem => $oneItem){
                    $item = new Pohoda\InvoiceItem();
                    $item->setText($oneItem->item_label);
                    $item->setQuantity($oneItem->quantity); //pocet
                    if (!is_null($oneItem->cl_pricelist_id)) {
                        $item->setCode($oneItem->cl_pricelist->identification); //katalogove cislo
                    }
                    $item->setUnit($oneItem->units); //jednotka
                    $item->setNote($oneItem->description1. " " . $oneItem->description2); //poznamka
                    //$item->setStockItem(230); //ID produktu v Pohode
                    //nastaveni ceny je volitelne, Pohoda si umi vytahnout cenu ze sve databaze pokud je nastaven stockItem
                    $item->setUnitPrice($oneItem->price_e); //cena

                    if ($oneItem->vat == 21 )
                        $item->setRateVAT($item::VAT_HIGH); //21%
                    elseif ($oneItem->vat == 15 )
                        $item->setRateVAT($item::VAT_LOW); //15%
                    elseif ($oneItem->vat == 10 )
                        $item->setRateVAT($item::VAT_THIRD); //10%
                    elseif ($oneItem->vat == 0 )
                        $item->setRateVAT($item::VAT_NONE); //21%

                    $item->setPayVAT($oneItem->price_e_type == 1); //cena bez dph

                    $invoice->addItem($item);
                }*/

                // variabilni cislo
                if ($one->var_symb != '')
                    $invoice->setVariableNumber((int)$one->var_symb);
                elseif ($one->rinv_number != '')
                    $invoice->setVariableNumber((int)$one->rinv_number);
                else
                    $invoice->setVariableNumber(0);

                //číslo originálního dokladu
                $invoice->setOriginalDocument($one->rinv_number);

                // datum vytvoreni faktury
                $invoice->setDateCreated($one->inv_date);
                // datum zdanitelneho plneni
                $invoice->setDateTax($one->vat_date);
                // datum splatnosti
                $invoice->setDateDue($one->due_date);
                //datum vytvoreni objednavky
                //$invoice->setDateOrder('2014-01-24');

                //středisko
                if (!is_null($one->cl_center_id)) {
                    $invoice->setCentre($one->cl_center->name);
                }

                //cislo objednavky v eshopu
                $invoice->setNumberOrder($one->od_number);

                // nastaveni identity dodavatele
                $invoice->setProviderIdentity([
                    "company" => $one->cl_company->name,
                    "city" => $one->cl_company->city,
                    "street" => $one->cl_company->street,
                    "number" => "",
                    "zip" => (int)str_replace(" ","", $one->cl_company->zip),
                    "ico" => $one->cl_company->ico,
                    "dic" => $one->cl_company->dic
                ]);

                // nastaveni identity prijemce
                if (!is_null($one->cl_partners_book_id)){
                    $tmpCustomer = [
                        "company" => $one->cl_partners_book->company,
                        "city" => $one->cl_partners_book->city,
                        "street" => $one->cl_partners_book->street,
                        "number" => "",
                        "zip" => (int)str_replace(" ","", $one->cl_partners_book->zip),
                        "ico" => (int)str_replace(" ","", $one->cl_partners_book->ico),
                        "dic" => $one->cl_partners_book->dic
                    ];
                    $customer[$tmpCustomer['company']] = $invoice->createCustomerAddress($tmpCustomer);

                }else{
                    $tmpCustomer = array();
                    $invoice->createCustomerAddress($tmpCustomer);
                }
                // nebo jednoduseji identitu nechat vytvorit
                //$customerAddress = $invoice->createCustomerAddress($customer, "z125", ["street" => "Pod Mostem"]);
                //$customerAddress = $invoice->createCustomerAddress($tmpCustomer);

                if ($invoice->isValid()) {
                    // pokud je faktura validni, pridame ji do exportu
                    $pohoda->addInvoice($invoice);
                    //pokud se ma importovat do adresare
                    //$pohoda->addAddress($customerAddress);
                } else {
                    var_dump($invoice->getErrors());
                }
            }
            //příprava adres pro import, řešíme až zde aby nebyly duplicity v adresáři
            foreach ($customer as $key => $oneCustomer){
                $pohoda->addAddress($oneCustomer);
            }


            // ulozeni do souboru
            $errorsNo = 0; // pokud si pocitate chyby, projevi se to v nazvu souboru

            $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
            $subFolder  = $this->ArraysManager->getSubFolder(array(), 'cl_invoice_id');
            $destFile   =  $dataFolder . '/' . $subFolder;  // . '/' . 'invoice_export.xml';
            $pohoda->setExportFolder($destFile); //mozno nastavit slozku, do ktere bude proveden export

            $file = $pohoda->exportToFile(time(), 'Trynx', 'invoice_arrived_export', $errorsNo);
            // vypsani na obrazovku jako XML s hlavickou
            //Debugger::$showBar = false;s
            //$pohoda->exportAsXml(time(), 'popis', date("Y-m-d_H-i-s"));

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setHeader('Pragma', "public");
            $httpResponse->setHeader('Expires', 0);
            $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
            $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
            $httpResponse->setHeader('Content-Description', "File Transfer");
            $httpResponse->setHeader('Content-Length', filesize($file));
            //$this->sendResponse(new DownloadResponse($file, basename($file) , array('application/octet-stream', 'application/force-download', 'application/download')));
            $this->sendResponse(new \Nette\Application\Responses\FileResponse($file, basename($file), 'application/download'));


        } catch (Pohoda\InvoiceException $e) {
            $this->flashMessage($e->getMessage(), 'error');
        } catch (\InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /*** returns array of VATs used in given dataReport
     * @param $dataReport
     * @return array
     */
    public function getVats($dataReport)
    {
        $tmpArrVat = array();
        $tmpArr2 = array();
        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base1 != 0)
                $tmpArr2[$one->vat1] = $one->vat1;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base2 != 0)
                $tmpArr2[$one->vat2] = $one->vat2;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base3 != 0)
                $tmpArr2[$one->vat3] = $one->vat3;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base0 != 0)
                $tmpArr2[0] = 0;
        }
        foreach ($tmpArr2 as $one)
        {
            $tmpArrVat[] = $one;
        }
        rsort($tmpArrVat);

        return ($tmpArrVat);
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


    protected function createComponentExportInvoiceBookStereo($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $now = new \Nette\Utils\DateTime;
        if ($this->settings->platce_dph) {
            $lcText1 = $this->translator->translate('DUZP_od');
            $lcText2 = $this->translator->translate('DUZP_do');
        } else {
            $lcText1 = $this->translator->translate('Vystaveno_od');
            $lcText2 = $this->translator->translate('Vystaveno_do');
        }
        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Datum_začátek');

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id', 'status_name');
        $form->addMultiSelect('cl_status_id', $this->translator->translate('Stav_dokladu'), $tmpArrStatus)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_stav'));

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Středisko'), $tmpArrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_středisko'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_payment_types_id', 'Forma_úhrady', $arrPay)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_formu_úhrady'));


        $form->addCheckbox('after_due_date', $this->translator->translate('Po_splatnosti'));
        $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
        $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));

        $form->addText('ma_dati', $this->translator->translate('Účet_MD'), 15, 15)
            ->setDefaultValue('311001')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Účet_MD'));
        $form->addText('dal', $this->translator->translate('Účet_Dal'), 15, 15)
            ->setDefaultValue('600')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Účet_Dal'));
        $form->addText('item_label', $this->translator->translate('Popis'), 30, 30)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis'));
        $form->addText('short_desc', $this->translator->translate('Druh'), 30, 30)
            ->setDefaultValue('PDPH')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Druh'));
        $form->addText('txt_rada', $this->translator->translate('Číselná_řada_pro_Stereo'), 10, 10)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číselná_řada'));


        $form->addSubmit('save_xml', $this->translator->translate('Exportovat_faktury'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBackExportBookStereo'];
        $form->onSuccess[] = [$this, 'SubmitExportBookStereoSubmitted'];

        return $form;
    }

    public function stepBackExportBookStereo()
    {
        $this->rptIndex = 2;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitExportBookStereoSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['save_xml']->isSubmittedBy() || $form['save_cash_xml']->isSubmittedBy()) {

            $data['cl_partners_branch'] = [];
            $tmpPodm = [];
            if ($data['after_due_date'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice_arrived.due_date < NOW() AND cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice_arrived.due_date < NOW() AND cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2';
            }

            if ($data['not_payed'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice_arrived.price_payed < cl_invoice_arrived.price_e2';
            }

            if ($data['payed'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice_arrived.price_payed >= cl_invoice_arrived.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice_arrived.price_payed >= cl_invoice_arrived.price_e2';
            }


            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            if ($this->settings->platce_dph) {
                $dataReport = $this->InvoiceArrivedManager->findAll()->
                                where($tmpPodm)->
                                where('cl_invoice_arrived.vat_date >= ? AND cl_invoice_arrived.vat_date <= ?', $data['date_from'], $data['date_to'])->
                                order('cl_invoice_arrived.inv_number ASC, cl_invoice_arrived.vat_date ASC');
            } else {
                $dataReport = $this->InvoiceArrivedManager->findAll()->
                                where($tmpPodm)->
                                where('cl_invoice_arrived.inv_date >= ? AND cl_invoice_arrived.inv_date <= ?', $data['date_from'], $data['date_to'])->
                                order('cl_invoice_arrived.inv_number ASC, cl_invoice_arrived.vat_date ASC');
            }

            if (count($data['cl_status_id']) > 0) {
                $dataReport = $dataReport->where(['cl_status_id' => $data['cl_status_id']]);
            }


            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(['cl_invoice_arrived.cl_center_id' => $data['cl_center_id']]);
            }


            if (count($data['cl_payment_types_id']) > 0) {
                $dataReport = $dataReport->
                                where(['cl_invoice_arrived.cl_payment_types_id' => $data['cl_payment_types_id']]);
            }

            if ($form['save_xml']->isSubmittedBy()) {
                $dateFrom = date('d-m-Y', strtotime($data['date_from']));
                $dateTo = date('d-m-Y', strtotime($data['date_to']));
                $filename = $this->settings['name'] . ' - faktury přijaté ' . $dateFrom . '-' . $dateTo;
                $arrData = $this->DataManager->exportStereo($dataReport, $data);
                $this->sendResponse(new \CsvResponse\NCsvResponse($arrData, $filename . ".csv", true, ';', 'CP1250', NULL, [], FALSE,''));
            }


        }
    }

    public function handleImportDigitoo(){

        $server = 'https://api.digitoo.cz';
        /*$token  = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3NUb2tlbklkIjoiMTEyODM3ZTAtODM1NS00OTFlLWFiNmEtNzhmYjU4YzYxZTI4IiwiY3VzdG9tZXJJZCI6IjM1ZDFkM2RkLTEzNWQtNDYwYi04MDE1LWM5Mzk5MmE3MzMxNSIsImN1c3RvbWVyRW1haWwiOiJ0b21hcy5oYWxhc3pAa3NoLWRhdGEuY3oiLCJpYXQiOjE2Nzg4NzcyNzQsImV4cCI6MTcwNzkwNzY3NH0.Qe0qYQLn-NJ7uiVVadfSCB7kQv77KAz_7gwNMkxppR4';*/
        $token = $this->settings->digitoo_token;
        if ($token == ''){
            $this->flashMessage('Není_vyplněn_token_digitoo_Doplňte_jej_v_nastavení_aplikace', 'error');
            return;
        }
        $authorization = 'Authorization: Bearer ' . $token;
        // /api/documents?filter[status]=ready-to-export
        // /api/documents/{DOCUMENT_ID}/mark-as-exported

        // Initialize a file URL to the variable
        $url = $server . '/api/documents?filter[status]=ready-to-export';
     //   dump($url);
        //$url = $server . '/api/queues';
        // Initialize the cURL session
        $ch = curl_init($url);
        // Initialize directory name where
        //prepare folder
        $dir = __DIR__ . "/";
        if (!is_dir($dir))
            mkdir($dir);

        // Use basename() function to return
        // the base name of file
        //$file_name = basename($url);
        $file_name = 'digitoo.txt';

        // Save file into file location
        $save_file_loc = $dir . $file_name;

        // Open file
        $fp = fopen($save_file_loc, 'wb');

        // It set an option for a cURL transfer
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$authorization]);

        // Perform a cURL session
        curl_exec($ch);

        // Closes a cURL session and frees all resources
        curl_close($ch);

        // Close file
        fclose($fp);
        $str = file_get_contents($save_file_loc);

        $arrStr = json_decode($str, true);
        //dump($arrStr);
        //die;
        foreach($arrStr['data'] as $key => $one){
            $result = $this->createInvoiceDigitoo($one);
            if (!is_null($result)){
                //download original file from digitoo
                $this->getFileDigitoo($result); 
                //set invoice downloaded on digitoo
                $this->finishDigitoo($result);
            }else{
                $this->flashMessage($this->translator->translate('Chyba_při_importu'), 'error');
            }
        }
        $this->redrawControl('content');

    }

    private function finishDigiToo($arrResult){

        $server = 'https://api.digitoo.cz';
        /*$token  = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3NUb2tlbklkIjoiMTEyODM3ZTAtODM1NS00OTFlLWFiNmEtNzhmYjU4YzYxZTI4IiwiY3VzdG9tZXJJZCI6IjM1ZDFkM2RkLTEzNWQtNDYwYi04MDE1LWM5Mzk5MmE3MzMxNSIsImN1c3RvbWVyRW1haWwiOiJ0b21hcy5oYWxhc3pAa3NoLWRhdGEuY3oiLCJpYXQiOjE2Nzg4NzcyNzQsImV4cCI6MTcwNzkwNzY3NH0.Qe0qYQLn-NJ7uiVVadfSCB7kQv77KAz_7gwNMkxppR4';*/
        $token = $this->settings->digitoo_token;
        $authorization = 'Authorization: Bearer ' . $token;
        // Initialize a file URL to the variable
        $url = $server . '/api/documents/' . $arrResult['fileId'] . '/mark-as-exported';
        //dump($url);
        //die;
        // Initialize the cURL session
        $ch = curl_init($url);

        // Initialize directory name where
        //prepare folder
        $dir = __DIR__ . "/";
        if (!is_dir($dir))
            mkdir($dir);

        // Use basename() function to return
        // the base name of file
        //$file_name = basename($url);
        $file_name = 'digitoo.txt';

        // Save file into file location
        $save_file_loc = $dir . $file_name;



        // Open file
        $fp = fopen($save_file_loc, 'wb');

        // It set an option for a cURL transfer
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$authorization]);

        // Perform a cURL session
        curl_exec($ch);

        // Closes a cURL session and frees all resources
        curl_close($ch);

        // Close file
        fclose($fp); 



    }

    private function getFileDigitoo($arrResult){
        $server = 'https://api.digitoo.cz';
        /*$token  = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3NUb2tlbklkIjoiMTEyODM3ZTAtODM1NS00OTFlLWFiNmEtNzhmYjU4YzYxZTI4IiwiY3VzdG9tZXJJZCI6IjM1ZDFkM2RkLTEzNWQtNDYwYi04MDE1LWM5Mzk5MmE3MzMxNSIsImN1c3RvbWVyRW1haWwiOiJ0b21hcy5oYWxhc3pAa3NoLWRhdGEuY3oiLCJpYXQiOjE2Nzg4NzcyNzQsImV4cCI6MTcwNzkwNzY3NH0.Qe0qYQLn-NJ7uiVVadfSCB7kQv77KAz_7gwNMkxppR4';*/
        $token = $this->settings->digitoo_token;
        $authorization = 'Authorization: Bearer ' . $token;
        // Initialize a file URL to the variable
        $url = $server . '/api/documents/' . $arrResult['fileId'] . '/file';
       // dump($url);
        // Initialize the cURL session
        $ch = curl_init($url);

        // Initialize directory name where
        // Save file into file location

        $destFile=NULL;
        $fileName  = preg_replace('/[^a-zA-Z0-9\-\._]/','', $arrResult['fileName']); 
        $i = 0;
        $arrFile = str_getcsv($fileName, '.');
        while(file_exists($destFile) || is_null($destFile))
        {
            if (!is_null($destFile)) {

                //$fileName = $arrFile[0] . '-' . \Nette\Utils\Random::generate(1, 'A-Za-z0-9') . '.' . $arrFile[1];
                $fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];

            }

            //$destFile=__DIR__."/../../../../data/files/".$fileName;
            $dataFolder = $this->CompaniesManager->getDataFolder( $this->user->getIdentity()->cl_company_id);
            $subFolder = $this->ArraysManager->getSubFolder('','cl_invoice_arrived');
            $destFile =  $dataFolder . '/' . $subFolder . '/' . $fileName;        
            $i++;
        }
        //bdump($destFile);
        //$file->move($destFile);

        // Open file
        $fp = fopen($destFile, 'wb');

        // It set an option for a cURL transfer
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$authorization]);

        // Perform a cURL session
        curl_exec($ch);

        // Closes a cURL session and frees all resources
        curl_close($ch);

        // Close file
        fclose($fp); 

//        $file                           = fopen($destFile, 'r');

        $data                           = [];
        $data['file_name']              = $fileName;
        $data['label_name']             = $fileName;
        $data['mime_type']              = mime_content_type($destFile);
        $data['file_size']              = filesize($destFile);	    
        $data['create_by']              = 'Digitoo';
        $data['created']                = new \Nette\Utils\DateTime;
        $data['cl_users_id']            =  $this->presenter->getUser()->id;
        $data['cl_invoice_arrived_id']  = $arrResult['id'];
        $this->FilesManager->insert($data);
  //      fclose($file);
//
    }


    private function createInvoiceDigitoo($oneData){
        //dump($oneData);
        try{
            $tmpNow = new \Nette\Utils\DateTime;

            $arrData = $oneData['annotations_map'];
            $tmpClCountries['id'] = null;
            if (!is_null($arrData['sender_country'])){
                $tmpClCountries = $this->CountriesManager->findAll()->where('country_name = ?', $arrData['sender_country'])->fetch();
                if (!$tmpClCountries){
                    $tmpClCountries = [];
                    $tmpClCountries['id'] = null;
                }
            }

            $tmpPartner['id'] = null;
            if (!is_null($arrData['sender_name']) && !is_null($arrData['sender_register_id'])){
                $tmpPartner = $this->PartnersManager->findAll()->where('company = ? AND ico = ?', $arrData['sender_name'], $arrData['sender_register_id'])->fetch();
                if (!$tmpPartner){
                    $tmpPartner = $this->PartnersManager->insert(['company'               => $arrData['sender_name'] ?? '',
                                                                        'ico'               => $arrData['sender_register_id'] ?? '',
                                                                        'dic'               => $arrData['sender_tax_id'] ?? '',
                                                                        'street'            => $arrData['sender_street'] ?? '',
                                                                        'city'              => $arrData['sender_city'] ?? '',
                                                                        'zip'               => $arrData['sender_post_code'] ?? '',
                                                                        'cl_countries_id'   => $tmpClCountries['id']
                                                                    ]);
                }
        
            }


            $tmpCurrencies['id'] = null;
            if (!is_null($arrData['currency'])){
                $tmpCurrencies = $this->CurrenciesManager->findAll()->where('currency_code = ?', $arrData['currency'])->fetch();
                if (!$tmpCurrencies){
                    $tmpCurrencies = $this->CurrenciesManager->insert([ 'currency_code'      => $arrData['currency'] ?? '', 
                                                                        'currency_name'      => $arrData['currency'] ?? '', 
                                                                        'fix_rate'           => (isset($arrData['exchange_date']) ? $arrData['exchange_date'] : 1),
                                                                        'decimal_places'     => 2,
                                                                        'decimal_places_cash'=> 2]);
                }        
            }

            $tmpBankAccount['id'] = null;
            if (!is_null($arrData['bank_account'])){
                $arrAccount = str_getcsv($arrData['bank_account'],'/');
                if (count($arrAccount) > 1){
                    $tmpBankAccount = $this->PartnersAccountManager->findAll()->where('account_code = ? AND bank_code = ? AND cl_partners_book_id = ?', trim($arrAccount[0]), trim($arrAccount[1]), $tmpPartner['id'])->fetch();
                    if (!$tmpBankAccount){
                        $tmpBankAccount = $this->PartnersAccountManager->insert(['cl_partners_book_id'  => $tmpPartner['id'],
                                                                                'account_code'         => trim($arrAccount[0]),
                                                                                'bank_code'            => trim($arrAccount[1]),
                                                                                'cl_currencies_id'     => $tmpCurrencies['id']]);
                    }
                }else{
                    $tmpBankAccount = [];
                    $tmpBankAccount['id'] = null;
                }
            }

            $arrInvoice = ['cl_partners_book_id'     => $tmpPartner['id'],
                            'inv_date'               => $arrData['issue_date'],
                            'vat_date'               => $arrData['taxable_supply_date'],
                            'arv_date'               => $arrData['issue_date'],
                            'due_date'               => $arrData['due_date'],
                            'var_symb'               => $arrData['var_sym'],
                            'rinv_number'            => $arrData['invoice_id'] ?? '',
                            'delivery_number'        => '',
                            'cl_currencies_id'       => $tmpCurrencies['id'],
                            'currency_rate'          => (isset($arrData['exchange_date']) ? $arrData['exchange_date'] : 1),
                            'cl_delivery_note_in_id' => null,
                            'cl_partners_account_id' => $tmpBankAccount['id'],
                            'digitoo_id'             =>  $oneData['id'],
                            'digitoo_file_name'      =>  $oneData['file_name']
                        ];
                       

            $numberSeries = ['use' => 'invoice_arrived', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number'];
            $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
            $arrInvoice[$numberSeries['table_key']]		= $nSeries['id'];
            $arrInvoice[$numberSeries['table_number']]	= $nSeries['number'];
        
            $tmpStatus = $numberSeries['use'];
            $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch();
            if ($nStatus){
                $arrInvoice['cl_status_id'] = $nStatus->id;
            }
            $tmpInvoiceTypes = $this->InvoiceTypesManager->findAll()->where('inv_type = ?', '4')->order('default_type DESC')->fetch();
            if ($tmpInvoiceTypes){
                $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceTypes->id;
            }else{
                $arrInvoice['cl_invoice_types_id'] = NULL;
            }

            $tmpPaymentTypes['id'] = null;
            if (!is_null($arrData['payment_type'])){
                //0 - bank_transfer, 1- cash, 3 - card, proforma, offset
                $arrDigitooPayments = [ 'BANK_TRANSFER' => 0, 'CASH' => 1, 'CARD' => 3];
                $tmpPaymentTypes = $this->PaymentTypesManager->findAll()->where('payment_type = ? AND not_active = ?', $arrDigitooPayments[$arrData['payment_type']], 0)->limit(1)->fetch();
                if (!$tmpPaymentTypes){
                    $tmpPaymentTypes = $this->PaymentTypesManager->insert(['payment_type' =>  $arrDigitooPayments[$arrData['payment_type']], 'name' =>  $arrData['payment_type'], 'description' => 'Digitoo import']);
                }
            }

            $arrInvoice['cl_payment_types_id']  = $tmpPaymentTypes['id'];

            $arrInvoice['cl_store_docs_id' ]    = null;
            $arrInvoice['cl_company_branch_id'] = $this->user->getIdentity()->cl_company_branch_id;
            $invoice = $this->InvoiceArrivedManager->insert($arrInvoice);
            
            $arrInvoice['id'] = $invoice['id'];
            //01.10.2019 - sum for every VAT rate
            $validRates = $this->RatesVatManager->findAllValid($arrInvoice['inv_date']);
            $arrInvoiceVat              = [];
            $arrInvoice['price_base0']  = 0;
            $arrInvoice['price_base1']  = 0;
            $arrInvoice['price_base2']  = 0;
            $arrInvoice['price_base3']  = 0;
            $arrInvoice['price_vat1']   = 0;
            $arrInvoice['price_vat2']   = 0;
            $arrInvoice['price_vat3']   = 0;
            $arrInvoice['price_e2']     = 0;
            $arrInvoice['price_e2_vat'] = 0;

            foreach ($validRates as $key => $one) {
                $arrInvoiceVat[$one['rates']] = ['base'     => 0 ,
                                                'vat'      => 0,
                                                'payed'    => 0,
                                                'vatpayed' => 0];
            }

            if (isset(array_keys($arrInvoiceVat)[0])) {
                $arrInvoice['vat1'] = array_keys($arrInvoiceVat)[0];
            }
            if (isset(array_keys($arrInvoiceVat)[1])) {
                $arrInvoice['vat2'] = array_keys($arrInvoiceVat)[1];
            }
            if (isset(array_keys($arrInvoiceVat)[2])) {
                $arrInvoice['vat3'] = array_keys($arrInvoiceVat)[2];
            }
            foreach($arrData['tax_details'] as $key => $one){
                if ($one['rate'] == 0){
                    $arrInvoice['price_base0'] = $one['base'];
                }
                if ($one['rate'] == $arrInvoice['vat1'] ){
                    $arrInvoice['price_base1'] = $one['base'];
                    $arrInvoice['price_vat1'] = $one['tax'];
                }            
                if ($one['rate'] == $arrInvoice['vat2'] ){
                    $arrInvoice['price_base2'] = $one['base'];
                    $arrInvoice['price_vat2'] = $one['tax'];
                }                        
                if ($one['rate'] == $arrInvoice['vat3'] ){
                    $arrInvoice['price_base3'] = $one['base'];
                    $arrInvoice['price_vat3'] = $one['tax'];
                }                        
            }

            $arrInvoice['price_e2'] = $arrInvoice['price_base3'] + $arrInvoice['price_base2'] + $arrInvoice['price_base1'] + $arrInvoice['price_base0'];
            $arrInvoice['price_e2_vat'] = $arrInvoice['price_e2'] + $arrInvoice['price_vat3'] + $arrInvoice['price_vat2'] + $arrInvoice['price_vat1'];

            //dump($arrInvoice);
            $updatedInvoice = $this->DataManager->update($arrInvoice);

            //total sums of invoice
            $this->DataManager->updateInvoiceSum($invoice['id']);
            //redirect to invoice
            //die;
            return ['fileId' => $oneData['id'], 'fileName' => $oneData['file_name'], 'id' => $invoice['id']];
        } catch (\Exception $e) {
            Debugger::log('Digitoo import. ' . $e->getMessage());
            return null;
        }


    }








}




