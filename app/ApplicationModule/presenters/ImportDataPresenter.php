<?php

namespace App\ApplicationModule\Presenters;


use Mpdf\Utils\Arrays;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class ImportDataPresenter extends \App\Presenters\BaseAppPresenter {

    public $target = NULL, $modal = FALSE, $parentId = NULL;

    private $delimiter = ';', $enclosure = '"', $importKey='', $l_header=1, $l_data=2, $updateKey = array();
    private $noInsert = FALSE;
    private $noUpdate = FALSE;
    private $noInsertSet = 0;
    private $noUpdateSet = 0;
    private $partnersBookId;
    private $sourceOrder = array();
    private $targetData = array(), $targetFormat = array();
    private $data = array();
    private $arrModels = array();
    private $uniqueColumns = array();
    private $defValColumns = array();
    private $countResult = array();
    private $file = NULL;
    private $licenseLimit = NULL;
    private $toImport = 0;

    /** @var \App\Model\Base */
    public $nU;

    /**
     * @inject
     * @var \App\Model\CsvProfilesManager
     */
    public $CsvProfilesManager;

    /**
    * @inject
    * @var \App\Model\PriceListGroupManager
    */
    public $PriceListGroupManager;     

    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $PriceListManager;         
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;         
    
    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;       
    
    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;  
    
    /**
    * @inject
    * @var \App\Model\CenterManager
    */
    public $CenterManager;        
    
    /**
    * @inject
    * @var \App\Model\CountriesManager
    */
    public $CountriesManager;            
    
    /**
    * @inject
    * @var \App\Model\RegionsManager
    */
    public $RegionsManager;            
    
    /**
    * @inject
    * @var \App\Model\PartnersCategoryManager
    */
    public $PartnersCategoryManager;            
    
    /**
    * @inject
    * @var \App\Model\UsersManager
    */
    public $UsersManager;            
    
    /**
    * @inject
    * @var \App\Model\StorageManager
    */
    public $StorageManager;


    /**
     * @inject
     * @var \App\Model\StoreMoveManager
     */
    public $StoreMoveManager;

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
     * @var \App\Model\StaffManager
     */
    public $StaffManager;


    /**
     * @inject
     * @var \App\Model\EstateManager
     */
    public $EstateManager;


    /**
     * @inject
     * @var \App\Model\PlacesManager
     */
    public $PlacesManager;


    /**
     * @inject
     * @var \App\Model\EstateTypeManager
     */
    public $EstateTypeManager;

    /**
     * @inject
     * @var \App\Model\NationsManager
     */
    public $NationsManager;

    /**
     * @inject
     * @var \App\Model\ProfessionManager
     */
    public $ProfessionManager;

    /**
    * @inject
    * @var \App\Model\PricesGroupsManager
    */
    public $PricesGroupsManager;

    /**
     * @inject
     * @var \App\Model\OrderItemsManager
     */
    public $OrderItemsManager;

    /**
     * @inject
     * @var \App\Model\OrderManager
     */
    public $OrderManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $invoiceManager;

    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $invoiceItemsManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $paymentTypesManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvanceManager
     */
    public $invoiceAdvanceManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvanceItemsManager
     */
    public $invoiceAdvanceItemsManager;


    /**
     * @inject
     * @var \App\Model\TaskManager
     */
    public $TaskManager;

    /**
     * @inject
     * @var \App\Model\TaskCategoryManager
     */
    public $TaskCategoryManager;

    /**
     * @inject
     * @var \App\Model\ProjectManager
     */
    public $ProjectManager;

    /**
     * @var array[]|mixed
     */
    private $relatedDefVal = [];
    /**
     * @var mixed|\string[][]
     */
    private $relatedImportVal = [];
    /**
     * @var mixed|string[]
     */
    private $relatedMainKey = [];
    /**
     * @var mixed
     */
    private $uniqueCheckDisabled = FALSE;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.ImportData']);
    }

    public function actionDefault($modal,$target, $id = NULL)
    {

        $this->modal = $modal;
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $this->parentId = $id;
        if ($target == "Application:Store"){
            $this->target = 'store';
            $this->targetData =	array('cl_pricelist.identification' => $this->translator->translate('Kód_položky'),
                                        'cl_pricelist.ean_code' => $this->translator->translate('EAN'),
                                        'cl_pricelist.order_code' => $this->translator->translate('Objednací_kód'),
                                        'cl_pricelist.item_label' => $this->translator->translate('Název'),
                                        'cl_pricelist.unit' => $this->translator->translate('Jednotky'),
                                        'price_in' => $this->translator->translate('Nákupní_cena'),
                                        's_in' => $this->translator->translate('Dodané_množství'),
                                        'cl_pricelist.vat' => $this->translator->translate('Sazba_DPH'));
            $this->targetFormat = array('price_in' => 'number',
                                        's_in' => 'number');

            $this->arrModels = array('data' => $this->StoreMoveManager,
                                    'dataWork' => $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ?', $id),
                                    'parent_data' => $this->StoreDocsManager,
                                    'other_data' => $this->StoreManager,
                                    'cl_currencies' => $this->CurrenciesManager,
                                    'cl_pricelist' => $this->PriceListManager);
            $this->uniqueColumns = array('cl_pricelist.identification' => 'cl_pricelist.identification');
            $this->uniqueCheckDisabled = TRUE;
            $this->importKey = 'cl_pricelist.identification';
            $tmpNow = new DateTime();

            $this->licenseLimit = NULL;
            $this->noInsert = FALSE;
            $this->noUpdate = TRUE;
            $tmpParentData = $this->arrModels['parent_data']->findAll()->where('id = ?', $id)->fetch();
            if ($tmpParentData){
                $this->partnersBookId = $tmpParentData->cl_partners_book_id;
                $tmpCurrenciesId = $tmpParentData->cl_currencies_id;
                $tmpStorageId = $tmpParentData->cl_storage_id;
            }else{
                $tmpCurrenciesId = $this->settings->cl_currencies_id;
                $this->partnersBookId = NULL;
                $tmpStorageId = $this->settings->cl_storage_id;
            }
            $this->defValColumns = array('cl_store_docs_id' => $id, 'cl_storage_id' => $tmpStorageId, 'import' => 1, 'import_fin' => 0);
            $this->relatedMainKey = array('cl_pricelist' => 'identification');
            $this->relatedDefVal = array('cl_pricelist' => array( 'cl_currencies_id' => $tmpCurrenciesId,
                                                                  'cl_partners_book_id' => $this->partnersBookId));
            $this->relatedImportVal = array('cl_pricelist' => array('identification' => 'cl_pricelist.identification',
                                                                    'ean_code' => 'cl_pricelist.ean_code',
                                                                    'order_code' => 'cl_pricelist.order_code',
                                                                    'item_label' => 'cl_pricelist.item_label',
                                                                    'unit' => 'cl_pricelist.unit',
                                                                    'vat' => 'cl_pricelist.vat'));


            //bdump($this->partnersBookId );

        }
        elseif ($target == "Application:Order"){

            $this->target = 'order';
            $this->targetData =	array('cl_pricelist.identification' => $this->translator->translate('Kód_položky'),
										'cl_pricelist.ean_code' => $this->translator->translate('EAN'),
                                        'cl_pricelist.order_code' => $this->translator->translate('Objednací_kód'),
                                        'units' => $this->translator->translate('Jednotky'),
                                        'price_e_rcv' => $this->translator->translate('Nákupní_cena'),
                                        'quantity_rcv' => $this->translator->translate('Dodané_množství'));
            $this->targetFormat = array('price_e_rcv' => 'number',
                                        'quantity_rcv' => 'number');

            $this->arrModels = array('data' => $this->OrderItemsManager,
                                    'dataWork' => $this->OrderItemsManager->findAll()->where('cl_order_id = ?', $id),
                                    'parent_data' => $this->OrderManager,
                                    'cl_currencies' => $this->CurrenciesManager,
                                    'cl_pricelist' => $this->PriceListManager);
            $this->uniqueColumns = array('cl_pricelist.identification' => 'cl_pricelist.identification');
            $this->importKey = 'cl_pricelist.identification';
            $tmpParentData = $this->arrModels['parent_data']->findAll()->where('id = ?', $id)->fetch();
            if ($tmpParentData){
                $this->partnersBookId = $tmpParentData->cl_partners_book_id;
                $tmpCurrenciesId = $tmpParentData->cl_currencies_id;
            }else{
                $tmpCurrenciesId = $this->settings->cl_currencies_id;
            }
            $tmpNow = new DateTime();
            $this->defValColumns = array('cl_order_id' => $id, 'rea_date' => $tmpNow);
            $this->licenseLimit = NULL;
            $this->noInsert = TRUE;

            //bdump($this->partnersBookId );

        }
        elseif ($target == "Application:PriceList")
        {
            $this->target = 'pricelist';
            $this->targetData =	array('id' => 'ID',
                                        'identification' => $this->translator->translate('Kód'),
                                        'identification_new' => $this->translator->translate('Kód_nový'),
                                        'ean_code' => $this->translator->translate('EAN'),
                                        'order_code' => $this->translator->translate('Objednací_kód'),
                                        'item_label' => $this->translator->translate('Název'),
                                        'cl_pricelist_group.name' => $this->translator->translate('Skupina'),
                                        'unit' => $this->translator->translate('Jednotky'),
                                        'price_s' => $this->translator->translate('Nákupní_cena'),
                                        'price' => $this->translator->translate('Cena_bez_DPH'),
                                        'price_vat' =>  $this->translator->translate('Cena_s_DPH'),
                                        'cl_partners_book.company' =>  $this->translator->translate('Dodavatel'),
                                        'vat' => $this->translator->translate('Sazba_DPH'),
                                        'not_active' => $this->translator->translate('Neaktivní'),
                                        'cl_currencies.currency_name' => $this->translator->translate('Měna'));
            $this->arrModels = array('data' => $this->PriceListManager,
                                    'dataWork' => $this->PriceListManager->findAll(),
                                    'cl_partners_book' => $this->PartnersManager,
                                    'cl_currencies' => $this->CurrenciesManager,
                                    'cl_pricelist_group' => $this->PriceListGroupManager);
            $this->uniqueColumns = array('identification' => 'identification', 'id' => 'id');
            $this->importKey = 'id';
            $this->defValColumns = array('cl_currencies_id' => $this->settings->cl_currencies_id);
            $this->licenseLimit = $this->UserManager->trfRecords($this->getUser()->id);



        }elseif ($target == "Application:Invoice")
        {
            $this->target = 'invoice';
            $this->targetData =	array(
                'inv_number'                => $this->translator->translate('Číslo_faktury'),
                'inv_date'                  => $this->translator->translate('Datum_vystavení'),
                'vat_date'                  => $this->translator->translate('Datum_dph'),
                'due_date'                  => $this->translator->translate('Datum_splatnosti'),
                'var_symb'                  => $this->translator->translate('Var_symbol'),
                'konst_symb'                => $this->translator->translate('Konst_symbol'),
                'inv_title'                 => $this->translator->translate('Text_faktury'),
                'price_base0'               => $this->translator->translate('Základ_daně_0'),
                'price_base1'               => $this->translator->translate('Základ_daně_1'),
                'price_base2'               => $this->translator->translate('Základ_daně_2'),
                'price_base3'               => $this->translator->translate('Základ_daně_3'),
                'vat1'                      => $this->translator->translate('Sazba_daně_1'),
                'vat2'                      => $this->translator->translate('Sazba_daně_2'),
                'vat3'                      => $this->translator->translate('Sazba_daně_3'),
                'price_vat1'                => $this->translator->translate('Daň_1'),
                'price_vat2'                => $this->translator->translate('Daň_2'),
                'price_vat3'                => $this->translator->translate('Daň_3'),
                'price_e2'                  => $this->translator->translate('Celkem_bez_dph'),
                'price_e2_vat'              => $this->translator->translate('Celkem_s_dph'),
                'cl_currencies.currency_code' => $this->translator->translate('Měna'),
                'currency_rate'             => $this->translator->translate('Kurz'),
                'cl_center.name'            => $this->translator->translate('Středisko'),
                'cl_partners_book.company'  => $this->translator->translate('Firma'),
                'cl_partners_book.street'   => $this->translator->translate('Ulice'),
                'cl_partners_book.city'     => $this->translator->translate('Město'),
                'cl_partners_book.zip'      => $this->translator->translate('PSČ'),
                'cl_partners_book.ico'      => $this->translator->translate('IČ'),
                'cl_partners_book.dic'      => $this->translator->translate('DIČ'),
                'cl_countries.name'         => $this->translator->translate('Země'),
                'cl_payment_types.name'     => $this->translator->translate('Druh_platby'));
            $this->arrModels = array('data' => $this->invoiceManager,
                'dataWork'                  => $this->invoiceManager->findAll(),
                'cl_center'                 => $this->CenterManager,
                'cl_partners_book'          => $this->PartnersManager,
                'cl_payment_types'          => $this->paymentTypesManager,
                'cl_currencies'             => $this->CurrenciesManager,
                'cl_countries'              => $this->CountriesManager);
            $this->uniqueColumns = array('cl_partners_book.company' => 'cl_partners_book.company');
            $this->uniqueCheckDisabled = FALSE;
            $this->importKey = 'inv_number';
            $this->targetFormat = array('price_base0' => 'number',
                                        'price_base1' => 'number',
                                        'price_base2' => 'number',
                                        'price_base3' => 'number',
                                        'vat1' => 'number',
                                        'vat2' => 'number',
                                        'vat3' => 'number',
                                        'price_vat1' => 'number',
                                        'price_vat2' => 'number',
                                        'price_vat3' => 'number',
                                        'price_e2' => 'number',
                                        'price_e2_vat' => 'number',
                                        'currency_rate' => 'number',
                                        'inv_date' => 'date',
                                        'due_date' => 'date',
                                        'vat_date' => 'date'
                );
            //$this->defValColumns = array('cl_countries_id' => $this->settings->cl_countries_id);
            $this->licenseLimit = NULL;
            //         $this->relatedMainKey = array('cl_pricelist' => 'identification');
            $this->relatedMainKey = array('cl_center' => 'name',
                                            'cl_partners_book' => 'company',
                                            'cl_payment_types' => 'name',
                                            'cl_currencies' => 'currency_code',
                                            'cl_countries' => 'name');
            $this->relatedDefVal = array();
            $this->relatedImportVal = array('cl_center' => array('name' => 'cl_center.name'),
                                            'cl_partners_book' => array('company' => 'cl_partners_book.company',
                                                                        'street' => 'cl_partners_book.street',
                                                                        'city' => 'cl_partners_book.city',
                                                                        'zip' => 'cl_partners_book.zip',
                                                                        'ico' => 'cl_partners_book.ico',
                                                                        'dic' => 'cl_partners_book.dic'),
                                            'cl_payment_types' => array('name' => 'cl_payment_types.name'),
                                            'cl_countries' => array('name' => 'cl_countries.name'));

        }elseif ($target == "Application:InvoiceAdvance")
        {
            $this->target = 'invoice_advance';
            $this->targetData =	array(
                'inv_number'                => $this->translator->translate('Číslo_faktury'),
                'inv_date'                  => $this->translator->translate('Datum_vystavení'),
                'vat_date'                  => $this->translator->translate('Datum_dph'),
                'due_date'                  => $this->translator->translate('Datum_splatnosti'),
                'var_symb'                  => $this->translator->translate('Var_symbol'),
                'konst_symb'                => $this->translator->translate('Konst_symbol'),
                'inv_title'                 => $this->translator->translate('Text_faktury'),
                'price_base0'               => $this->translator->translate('Základ_daně_0'),
                'price_base1'               => $this->translator->translate('Základ_daně_1'),
                'price_base2'               => $this->translator->translate('Základ_daně_2'),
                'price_base3'               => $this->translator->translate('Základ_daně_3'),
                'vat1'                      => $this->translator->translate('Sazba_daně_1'),
                'vat2'                      => $this->translator->translate('Sazba_daně_2'),
                'vat3'                      => $this->translator->translate('Sazba_daně_3'),
                'price_vat1'                => $this->translator->translate('Daň_1'),
                'price_vat2'                => $this->translator->translate('Daň_2'),
                'price_vat3'                => $this->translator->translate('Daň_3'),
                'price_e2'                  => $this->translator->translate('Celkem_bez_dph'),
                'price_e2_vat'              => $this->translator->translate('Celkem_s_dph'),
                'cl_currencies.currency_code' => $this->translator->translate('Měna'),
                'currency_rate'             => $this->translator->translate('Kurz'),
                'cl_center.name'            => $this->translator->translate('Středisko'),
                'cl_partners_book.company'  => $this->translator->translate('Firma'),
                'cl_partners_book.street'   => $this->translator->translate('Ulice'),
                'cl_partners_book.city'     => $this->translator->translate('Město'),
                'cl_partners_book.zip'      => $this->translator->translate('PSČ'),
                'cl_partners_book.ico'      => $this->translator->translate('IČ'),
                'cl_partners_book.dic'      => $this->translator->translate('DIČ'),
                'cl_countries.name'         => $this->translator->translate('Země'),
                'cl_payment_types.name'     => $this->translator->translate('Druh_platby'));
            $this->arrModels = array('data' => $this->invoiceAdvanceManager,
                'dataWork'                  => $this->invoiceAdvanceManager->findAll(),
                'cl_center'                 => $this->CenterManager,
                'cl_partners_book'          => $this->PartnersManager,
                'cl_payment_types'          => $this->paymentTypesManager,
                'cl_currencies'             => $this->CurrenciesManager,
                'cl_countries'              => $this->CountriesManager);
            $this->uniqueColumns = array('cl_partners_book.company' => 'cl_partners_book.company');
            $this->uniqueCheckDisabled = FALSE;
            $this->importKey = 'inv_number';
            $this->targetFormat = array('price_base0' => 'number',
                'price_base1' => 'number',
                'price_base2' => 'number',
                'price_base3' => 'number',
                'vat1' => 'number',
                'vat2' => 'number',
                'vat3' => 'number',
                'price_vat1' => 'number',
                'price_vat2' => 'number',
                'price_vat3' => 'number',
                'price_e2' => 'number',
                'price_e2_vat' => 'number',
                'currency_rate' => 'number',
                'inv_date' => 'date',
                'due_date' => 'date',
                'vat_date' => 'date'
            );
            //$this->defValColumns = array('cl_countries_id' => $this->settings->cl_countries_id);
            $this->licenseLimit = NULL;
            //         $this->relatedMainKey = array('cl_pricelist' => 'identification');
            $this->relatedMainKey = array('cl_center' => 'name',
                'cl_partners_book' => 'company',
                'cl_payment_types' => 'name',
                'cl_currencies' => 'currency_code',
                'cl_countries' => 'name');
            $this->relatedDefVal = array();
            $this->relatedImportVal = array('cl_center' => array('name' => 'cl_center.name'),
                'cl_partners_book' => array('company' => 'cl_partners_book.company',
                    'street' => 'cl_partners_book.street',
                    'city' => 'cl_partners_book.city',
                    'zip' => 'cl_partners_book.zip',
                    'ico' => 'cl_partners_book.ico',
                    'dic' => 'cl_partners_book.dic'),
                'cl_payment_types' => array('name' => 'cl_payment_types.name'),
                'cl_countries' => array('name' => 'cl_countries.name'));

        }elseif ($target == "Intranet:Staff")
        {
            $this->target = 'staff';
            $this->targetData =	array('id' => 'ID',
                'personal_number' => $this->translator->translate('Osobní_číslo'),
                'title' => $this->translator->translate('Titul'),
                'name' => $this->translator->translate('Jméno'),
                'surname' => $this->translator->translate('Příjmení'),
                'birth_date' => $this->translator->translate('Datum_narození'),
                'birth_place' => $this->translator->translate('Místo_narození'),
                'cl_center.name' => $this->translator->translate('Středisko'),
                'in_proffesion.name' => $this->translator->translate('Profese'),
                'start_date' => $this->translator->translate('Datum_nástupu'),
                'end_date' => $this->translator->translate('Datum_ukončení'),
                'phone' =>  $this->translator->translate('Telefon'),
                'email' =>  $this->translator->translate('Dodavatel'),
                'vat' => $this->translator->translate('Email'),
                'gender' => $this->translator->translate('Pohlaví'),
                'in_nations.name' => $this->translator->translate('Národnost'),
                'city' => $this->translator->translate('Město'),
                'street' => $this->translator->translate('Ulice'),
                'number' => $this->translator->translate('Číslo'),
                'number2' => $this->translator->translate('Číslo_2'),
                'zip' => $this->translator->translate('PSČ'),
                'cl_countries.name' => $this->translator->translate('Stát'),
                'work_rate' => $this->translator->translate('Sazba'));
            $this->arrModels = array('data' => $this->StaffManager,
                                'dataWork' => $this->StaffManager->findAll(),
                                'cl_center' => $this->CenterManager,
                                'in_proffession' => $this->ProfessionManager,
                                'in_nations' => $this->NationsManager,
                                'cl_countries' => $this->CountriesManager);
            $this->uniqueColumns = array('id' => 'id');
            $this->importKey = 'id';
            $this->defValColumns = array('cl_countries_id' => $this->settings->cl_countries_id);
            $this->licenseLimit = NULL;
            $this->relatedMainKey = array('cl_center' => 'cl_center_id',
                                            'in_proffesion' => 'in_proffesion_id',
                                            'in_nations' => 'in_nations_id',
                                            'cl_countries' => 'cl_countries_id');
            $this->relatedDefVal = array();
            $this->relatedImportVal = array('cl_center' => array('name' => 'cl_center.name'),
                                            'in_proffesion' => array('name' => 'in_proffesion.name'),
                                            'in_nations' => array('name' => 'in_nations.name'),
                                            'cl_countries' => array('name' => 'cl_countries.name'));

        }elseif ($target == "Intranet:Estate")
        {
            $this->target = 'estate';
            $this->targetData =	['id' => 'ID',
                'est_number' => $this->translator->translate('Číslo_majetku'),
                'est_name' => $this->translator->translate('Název_majetku'),
                'est_description' => $this->translator->translate('Popis'),
                'host_name' => $this->translator->translate('Síťové_jméno'),
                'net_address' => $this->translator->translate('Síťová_adresa'),
                'ip_address' => $this->translator->translate('IP_adresa'),
                'est_price' => $this->translator->translate('Pořizovací_cena'),
                'rent_price' => $this->translator->translate('Denní_sazba'),
                'invoice' => $this->translator->translate('Faktura'),
                'cl_center.name' => $this->translator->translate('Středisko'),
                'description_txt' => $this->translator->translate('Poznámka'),
                'in_places.place_name' => $this->translator->translate('Místo'),
                'in_estate_type.type_name' => $this->translator->translate('Typ_majetku'),
                's_number' =>  $this->translator->translate('Sériové_číslo'),
                'dtm_purchase' =>  $this->translator->translate('Datum_nákupu'),
                'producer' => $this->translator->translate('Výrobce')];

            $this->arrModels = ['data' => $this->EstateManager,
                'dataWork' => $this->EstateManager->findAll(),
                'cl_center' => $this->CenterManager,
                'in_places' => $this->PlacesManager,
                'in_estate_type' => $this->EstateTypeManager
            ];
            $this->uniqueColumns = ['id' => 'id'];
            $this->importKey = 'id';
            $tmpStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = 1 ', "estate")->fetch();
            if ($tmpStatus){
                $tmpStatusId = $tmpStatus['id'];
            }else{
                $tmpStatusId = NULL;
            }
            $this->defValColumns = ['cl_status_id' => $tmpStatusId];
            $this->licenseLimit = NULL;
            $this->relatedMainKey = ['cl_center' => 'name',
                'in_places' => 'place_name',
                'in_estate_type' => 'type_name'];
            $this->relatedDefVal = [];
            $this->relatedImportVal = ['cl_center' => ['name' => 'cl_center.name'],
                'in_places' => ['place_name' => 'in_places.place_name'],
                'in_estate_type' => ['type_name' => 'in_estate_type.type_name']
            ];


        }
        elseif ($target == "Application:Partners")
        {
            $this->target = 'partners_book';
            $this->targetData =	['company' => $this->translator->translate('Název'),
                                    'cl_center.name' => $this->translator->translate('Středisko'),
                                    'street' => $this->translator->translate('Ulice'),
                                    'city' => $this->translator->translate('Město'),
                                    'zip' => $this->translator->translate('PSČ'),
                                    'cl_countries.name' => $this->translator->translate('Stát'),
                                    'ico' => $this->translator->translate('IČ'),
                                    'dic' => $this->translator->translate('DIČ'),
                                    'person' => $this->translator->translate('Osoba'),
                                    'email' => $this->translator->translate('E-mail'),
                                    'phone' => $this->translator->translate('Telefon'),
                                    'web' => $this->translator->translate('Web'),
                                    'contract' => $this->translator->translate('Smlouva'),
                                    'comment' => $this->translator->translate('Poznámka'),
                                    'show_comment' => $this->translator->translate('Zobrazovat_všude'),
                                    'pricelist_partner' => $this->translator->translate('Ceník'),
                                        'coop_enable' => $this->translator->translate('Partner'),
                                        'same_address' => $this->translator->translate('Shodná_doručovací_adr'),
                                    'company2' => $this->translator->translate('Název_2'),
                                    'street2' => $this->translator->translate('Ulice_2'),
                                    'city2' => $this->translator->translate('Město_2'),
                                    'zip2' => $this->translator->translate('PSČ_2'),
                                    'country2' => $this->translator->translate('Stát_2'),
                                    'cl_regions.region_name' => $this->translator->translate('Region'),
                                    'cl_partners_category.category_name' => $this->translator->translate('Kategorie'),
                                    'cl_users.name' => $this->translator->translate('Obchodník'),
                                    'cl_storage.name' => $this->translator->translate('Výchozí_sklad'),
                                    'cl_prices_groups.name' => $this->translator->translate('Cenová_skupina')
            ];

            $this->arrModels = ['data' => $this->PartnersManager,
                                     'dataWork' => $this->PartnersManager->findAll(),
                                     'cl_center' => $this->CenterManager,
                                     'cl_countries' => $this->CountriesManager,
                                     'cl_regions' => $this->RegionsManager,
                                     'cl_partners_category' => $this->PartnersCategoryManager,
                                     'cl_users' => $this->UserManager,
                                     'cl_storage' => $this->StorageManager,
                                     'cl_prices_groups' => $this->PricesGroupsManager];

            $this->uniqueColumns = [];
            $this->importKey = 'company';
            $this->defValColumns = [];
            $this->noUpdate = FALSE;
            $this->licenseLimit = $this->UserManager->trfRecords($this->getUser()->id);

        } elseif ($target == "Application:Task")
        {
            $this->target = 'task';
            $this->targetData =	['task_date' => $this->translator->translate('Datum_zápisu'),
                'cl_project.label' => $this->translator->translate('Projekt'),
                'cl_task_category.label' => $this->translator->translate('Druh_úkolu'),
                'task_number' => $this->translator->translate('Číslo_úkolu'),
                'priority' => $this->translator->translate('Priorita'),
                'label' => $this->translator->translate('Verze'),
                'description' => $this->translator->translate('Text_úkolu'),
                'cl_users.name' => $this->translator->translate('Pracovník'),
                'target_date' => $this->translator->translate('Ukončení_plánované'),
                'end_date' => $this->translator->translate('Skutečné_ukončení')
            ];

            $this->arrModels = ['data' => $this->TaskManager,
                'dataWork' => $this->TaskManager->findAll(),
                'cl_project' => $this->ProjectManager,
                'cl_users' => $this->UsersManager,
                'cl_task_category' => $this->TaskCategoryManager];

            $this->uniqueColumns = [];
            $this->importKey = 'task_number';
            $this->defValColumns = [];
            $this->noUpdate = TRUE;
            $this->licenseLimit = NULL;

            $this->targetFormat = ['task_date' => 'date',
                'target_date' => 'date',
                'end_date' => 'date'
            ];
            //         $this->relatedMainKey = array('cl_pricelist' => 'identification');
            $this->relatedMainKey = ['cl_project' => 'label',
                'cl_task_category' => 'label',
                'cl_users' => 'name'];
            $this->relatedDefVal = [];
            $this->relatedImportVal = ['cl_project' => ['label' => 'cl_project.label'],
                    'cl_task_category' => ['label' => 'cl_task_category.label'],
                    'cl_users' => ['name' => 'cl_users.name']];

        }
        $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
        $subFolder  = $this->ArraysManager->getSubFolder(array(), $this->target);
        $destFile   =  $dataFolder . '/' . $subFolder . '/' . $this->target . '_import_' .$this->user->getId(). '.csv';
        $this->file = $destFile;
        //bdump($this->file);
        //die;

    }

    public function renderDefault() {

            $this->template->modal          = $this->modal;
            $this->template->userCanErase   =  $this->isAllowed($this->presenter->name,'erase');
            $this->template->userCanWrite   =  $this->isAllowed($this->presenter->name,'write');
            $this->template->targetData     = $this->targetData;
            $this->template->countResult    = $this->countResult;
            $this->template->noInsert       = $this->noInsert;
            $this->template->noUpdate       = $this->noUpdate;
            $this->template->noInsertSet    = $this->noInsertSet;
            $this->template->noUpdateSet    = $this->noUpdateSet;
            //bdump($this->targetData);

            //$clPartnersBookId = NULL;
            if (isset($this->arrModels['parent_data'])) {
                $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target);
                $tmpParentData = $this->arrModels['parent_data']->findAll()->where('id = ?', $this->parentId)->fetch();
                if ($tmpParentData && !is_null($this->partnersBookId)) {
                    //$clPartnersBookId = $tmpParentData->cl_partners_book_id;
                    //bdump($this->partnersBookId,'ted');
                    $csvProfile = $csvProfile->where('cl_partners_book_id = ?', $this->partnersBookId)->fetch();

                    if (!$csvProfile){
                      //  $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target)->fetch();
                    }

                }else{
                    $csvProfile = $csvProfile->fetch();
                }

            }else{
                $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target)->fetch();
            }
        bdump($csvProfile);
            if (!$csvProfile) {
                //$section = $this->getSession('importData_' . $this->target);
                //$tmpArr = json_decode($data, true);
                //asort($tmpArr);
                //$tmpArr = json_encode($tmpArr);
                //$section->sourceOrder = $tmpArr;
                $arrImportSetting = array();
                $arrImportSetting['target'] = $this->target;
                $arrImportSetting['source_order'] = json_encode(array());
                    //json_encode($this->sourceOrder);
                $arrImportSetting['delimiter'] = $this->delimiter;
                $arrImportSetting['enclosure'] = $this->enclosure;
                $arrImportSetting['import_key'] = $this->importKey;
                $arrImportSetting['cl_partners_book_id'] = $this->partnersBookId;
                $arrImportSetting['l_header'] = 1;
                $arrImportSetting['l_data'] = 2;
                $this->CsvProfilesManager->insert($arrImportSetting);
                $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target);
                if (!is_null($this->partnersBookId)) {
                    $csvProfile = $csvProfile->where('cl_partners_book_id = ?', $this->partnersBookId);
                }
                $csvProfile = $csvProfile->fetch();
            }

            if (empty($csvProfile->update_keys)){
                $tmpArr = array();
                foreach($this->targetData as $key => $one){
                        $tmpArr[$key] = "true";
                }
                $csvProfile->update(array('update_keys' => json_encode($tmpArr)));
            }
            $this->template->updateKeys = json_decode($csvProfile->update_keys, true);
            $this->updateKey = $this->template->updateKeys;

            if ($csvProfile) {
                $this->sourceOrder = json_decode($csvProfile->source_order, true);
                //if (is_array($this->sourceOrder) && count($this->sourceOrder) > 0) {
                    $this->delimiter = $csvProfile->delimiter;
                    $this->enclosure = $csvProfile->enclosure;
                    $this->importKey = $csvProfile->import_key;

                    $this->noInsertSet = $csvProfile->no_insert_set;
                    $this->noUpdateSet = $csvProfile->no_update_set;
                    if (isset($csvProfile->l_header))
                        $this->l_header = $csvProfile->l_header;
                    if (isset($csvProfile->l_data))
                        $this->l_data = $csvProfile->l_data;
                //}
            }
            if (!$csvProfile) {
                $this->sourceOrder = array();
                $this->delimiter = ';';
                $this->enclosure = '"';
                if (count($this->uniqueColumns)) {
                    $this->importKey = array_values($this->uniqueColumns)[0];
                }else{
                    $this->importKey = '';
                }
                $this->l_header = 1;
                $this->l_data = 2;
            }
            //bdump($this->noInsertSet, 'render noInsertSet');
            $section = $this->getSession('importData_'. $this->target);
            $section->sourceOrder = $this->sourceOrder;
            $section->updateKey = $this->updateKey;


            //bdump($arrImportSetting);
            $this['uploadFile']->setDefaults(array('cl_company_id' => $this->user->getIdentity()->cl_company_id, 'target' => $this->target));
            $this['edit']->setDefaults(array('target' => $this->target,
                                                        'cl_partners_book_id' => $this->partnersBookId,
                                                        'import_key' => $this->importKey,
                                                        'delimiter' => $this->delimiter,
                                                        'enclosure' => $this->enclosure,
                                                        'l_header' => $this->l_header,
                                                        'l_data' => $this->l_data,
                                                        'no_insert_set' => $this->noInsertSet,
                                                        'no_update_set' => $this->noUpdateSet));


            $this->template->import_key = $this->importKey;
            $this->template->l_data = $this->l_data;
            $this->template->l_header = $this->l_header;
            $this->template->target = $this->target;
            $this->template->cl_partners_book_id = $this->partnersBookId;
            //if (count($this->sourceOrder) == 0) {
           // bdump($this->sourceOrder);
            //if ($this->file)
                if ($arrImport = $this->csv_to_array($this->file, $this->delimiter, $this->enclosure, 20, $this->l_header, $this->l_data)) {
                    bdump($arrImport, 'ted0');
                    $this->data = array_slice($arrImport, 0, 20);

                    //add order defintion if is not set yet
                    bdump($this->sourceOrder, 'ted1');
                    if (is_null($this->sourceOrder) || count($this->sourceOrder) == 0) {
                        $this->sourceOrder = NULL;
                        $i = 0;
                        foreach (array_slice($arrImport, 0, 1)[0] as $key => $one) {
                            $this->sourceOrder[$key] = $i;
                            $i++;
                        }
                    }

                } else {
              //      $this->sourceOrder = array();
                }
            //}

            if ($this->target == "pricelist" || $this->target == "partners_book") {
                $tmpLicense = $this->arrModels['data']->findAll()->count();
            }else{
                $tmpLicense = 1000000;
            }
            bdump($this->l_data);
            $arrImport = $this->csv_to_array($this->file, $this->delimiter, $this->enclosure,NULL, $this->l_header, $this->l_data);
            if ($arrImport) {
                bdump($arrImport,'ted');
                $totalToImport = count($arrImport);
            }else{
                $totalToImport = 0;
            }

            if (!is_null($this->licenseLimit))
            {
                $tmpLeft =  ($this->licenseLimit - $tmpLicense);
                if ($tmpLeft > $totalToImport)
                {
                    $this->toImport = $totalToImport;
                }else{
                    if ($tmpLeft < 0)
                    {
                        $tmpLeft = 0;
                    }
                    $this->toImport = $tmpLeft;
                }

            }else{
                $this->toImport = $totalToImport;
            }
            $this->template->countToImport = $this->toImport;
            //$section->toImport = $this->toImport;

            //dump($tmpLicense);
            //die;
            //dump($this->licenseLimit);
            if ( is_null($this->licenseLimit) || ($this->template->countToImport <= $this->licenseLimit ))
            {
                $this->template->licenseOk = TRUE;
            }else{
                $this->template->licenseOk = FALSE;
                $this->flashMessage( $this->translator->translate('nepovoluje_více_záznamů_než').$this->licenseLimit, 'danger' );
            }

            //dump($this->data);
            $this->template->data = $this->data;

            //bdump($this->sourceOrder,'source order');
            if (!is_null($this->sourceOrder)) {
                $this->template->sourceOrder = $this->sourceOrder;
            }else{
                $this->template->sourceOrder = array();
            }
            $csvProfile->update(array('source_order' => json_encode($this->template->sourceOrder)));
            $section['source_order'] = $this->template->sourceOrder;
            $section['noInsertSet']  = $this->noInsertSet;
            $section['noUpdateSet']  = $this->noUpdateSet;
            $section['enclosure'] = $this->enclosure ;
            $section['delimiter'] = $this->delimiter ;
            $section['importKey'] = $this->importKey ;
            $section['l_header'] = $this->l_header;
            $section['l_data'] = $this->l_data;
    }
    
    
    /**Stamp company form
     * @return Form
     */
    protected function createComponentUploadFile()
    {
		$form = new \Nette\Application\UI\Form();
		$form->getElementPrototype()->class = 'dropzone filedropzone';
		$form->getElementPrototype()->id = 'fileDropzone';
		$form->addHidden('cl_company_id');
		$form->addHidden('target');

		$form->onSuccess[] = [$this, 'processUploadFile'];
		return $form;
    }        
    
    /**upload method for imported data
     * @param Form $form
     */
    public function processUploadFile(Form $form)
    {
	$formValues = $form->getValues();
	$file = $form->getHttpData($form::DATA_FILE, 'file');
	if ($file->isOk())
	{
		//Debugger::fireLog($file->getContentType());	
		//next check if file exists, if yes generate new filename
		//$destFile=NULL;	

		//while(file_exists($destFile) || is_null($destFile))
		//{
		//  $fileName = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
		//  $destFile=__DIR__."/../../../../data/files/".$fileName;		    
		//}

        $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
        $subFolder  = $this->ArraysManager->getSubFolder(array(), $formValues['target']);
        $destFile   =  $dataFolder . '/' . $subFolder . '/' . $formValues['target'] . '_import_' .$this->user->getId(). '.csv';
        //bdump($destFile);
		//$destFile = __DIR__."/../../../data/files/".$this->user->getIdentity()->cl_company_id."_import_".$formValues['target'];
		$file->move($destFile);

		$data = array();
		//$data['file_name'] = $fileName;
		$data['label_name'] = $file->getSanitizedName();
		$data['mime_type'] = $file->getContentType();
		$data['file_size'] = $file->getSize();	  
		//$section = $this->getSession('importData_'.$this->target);
		//if (isset($section->sourceOrder))
		//{
		  //  unset($section->sourceOrder);
		//}
        //$this->sourceOrder = array();
		
		$this->flashMessage($this->translator->translate('Soubor_byl_nahrán'), 'info');
        //$this->sourceOrder = NULL;

		//$this->presenter->redrawControl('flash');	    	
		$this->redrawControl('importeddata');
		$this->redrawControl('testy');
	}else
	{
		$this->flashMessage($this->translator->translate('Soubor_nebyl_nahrán'), 'warning');
	}

	$this->redrawControl('flash');

			
//	$this->CompaniesManager->update($value);
        //} catch (\Exception $e) {
        //     $this->flashMessage($e->getMessage());
        //}
    }        
    
    public function csv_to_array($filename = '', $delimiter = ',', $enclosure = '', $rows = NULL, $l_header = 1, $l_data = 2) {
        bdump($l_header, 'l_header');
        bdump($l_data, 'l_data');
        //bdump($delimiter, 'delimiter');
        //bdump($enclosure, 'enclosure');
        if (!file_exists($filename) || !is_readable($filename))
            return FALSE;
        //die;
        $header = NULL;
        $data = array();
        //bdump($filename);
        //bdump($delimiter);
        if (($handle = fopen($filename, 'r')) !== FALSE) {
            $ln     = 0;
            $rowStr  = '';
            $line2 = FALSE;
            while (($line = fgets($handle)) && (is_null($rows) || $rows>=0)) {
                //bdump(mb_detect_encoding($line, 'UTF-8'),'mb_detect utf8');
                //bdump(mb_detect_encoding($line, 'Windows-1250'),'mb_detect 1250');
                bdump($line);
                $codepage = $this->mb_detect_encoding_my($line);
                bdump($codepage);
                if ($codepage == 'Windows-1250') {
                    $line = iconv('Windows-1250', 'UTF-8', $line);
                }

                if (($ln) >= $l_header-1) {
                    //$line = fgets($handle, 4096);
                   // bdump($line, 'line');
                    $row = str_getcsv($line, $delimiter, $enclosure);

                    if (is_null($header)) {
                        //$header = $row;
                        $ih = 1;
                        foreach ($row as $one) {
                            $header[] = ($one != "") ? $one : $ih++;
                        }
                        bdump($header, 'header');
                    } else {
                        if ( ($ln) >= ($l_data-1) && count($header) == count($row)) {
                            bdump($row, 'row');
                            $data[] = array_combine($header, $row);
                            $rows--;

                        }elseif($ln >= ($l_data-1) && count($header) > count($row)){
                            $rowStr = $line;
                            while ( count($header) > count($row)){
                                if ($line2 = fgets($handle)){
                                    bdump($line2);
                                    $codepage = $this->mb_detect_encoding_my($line2);
                                    bdump($codepage);
                                    if ($codepage == 'Windows-1250') {
                                        $line2 = iconv('Windows-1250', 'UTF-8', $line2);
                                    }
                                    $rowStr .= $line2;
                                    $row = str_getcsv($rowStr, $delimiter, $enclosure);
                                }

                            }

                            bdump($header,'header');
                            bdump($row,'row');
                            $data[] = array_combine($header, $row);
                            $rows--;



                        }

                    }


                }
                $ln++;
               // bdump($data, 'data');
            }
            fclose($handle);
        }
        bdump($data);

        return $data;
    }

    function mb_detect_encoding_my ($string, $enc=null, $ret=null) {

        static $enclist = array(
            'UTF-8', 'Windows-1250',
        );

        $result = false;

        foreach ($enclist as $item) {
            $sample = iconv($item, $item.'//IGNORE', $string);
            if (md5($sample) == md5($string)) {
                if ($ret === NULL) { $result = $item; } else { $result = true; }
                break;
            }
        }

        return $result;
    }

    public function handleChangeOrder($data){   
       // $section = $this->getSession('importData_'.$this->target);
        $tmpArr = json_decode($data, true );
        asort($tmpArr);
        $tmpArr = json_encode($tmpArr);
        //$section->sourceOrder = $tmpArr;
        $arrImportSetting = array();
        $arrImportSetting['target'] = $this->target;
        $arrImportSetting['source_order'] = $tmpArr;
        $arrImportSetting['delimiter'] = $this->delimiter;
        $arrImportSetting['enclosure'] = $this->enclosure;

        if (!is_null($this->partnersBookId)) {
            $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ? AND cl_partners_book_id = ?', $this->target, $this->partnersBookId)->fetch();
            $arrImportSetting['cl_partners_book_id'] = $this->partnersBookId;
        }else{
            $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target)->fetch();
            $arrImportSetting['cl_partners_book_id'] = NULL;
        }

        if ($csvProfile) {
            $csvProfile->update($arrImportSetting);
        }else{
            $this->CsvProfilesManager->insert($arrImportSetting);
        }

        //$this->redrawControl('testy');
        //dump($data);
    }

    public function handleImportData()
    {
        //dump($this->target);
       // bdump($this->noInsertSet, 'handle noInsertSet');
        $section = $this->getSession('importData_'. $this->target);
        $tmpArr = $section->sourceOrder;
        $this->countResult['updated'] = 0;
        $this->countResult['imported'] = 0;
        $tmpUpdateKey = $section->updateKey;
        $this->noInsertSet = $section->noInsertSet;
        $this->noUpdateSet = $section->noUpdateSet;
        $this->enclosure = $section->enclosure;
        $this->delimiter = $section->delimiter;
        $this->importKey = $section->importKey;
        $this->l_header    = $section->l_header;
        $this->l_data   = $section->l_data;
        //$arrImportSetting = $section->importSetting;

        //, $this->toImport
        if ($arrImport = $this->csv_to_array($this->file, $this->delimiter, $this->enclosure, NULL, $this->l_header, $this->l_data))
        {
            //dump($arrImport);
            //dump($tmpArr);
            //$arrImport = array_slice($arrImport,0,10); //!!! pak zrušit je to pro testy
            //prepare order array with correct keys
            $arrTargetKeys = array_keys($this->targetData);
            //dump($arrTargetKeys);
            $i = 0;
            foreach ($tmpArr as $key => $one)
            {
                if (isset($arrTargetKeys[$i]))
                {
                    $tmpArr[$key] = $arrTargetKeys[$i];
                }else{
                    unset($tmpArr[$key]);
                }

                $i++;
            }

            //bdump($tmpArr, 'tmpArr');
            //prepare data with correct array keys
            //bdump($tmpUpdateKey,'update key');

            $tmpFArr = Array();
            foreach ($arrImport as $key => $one)
            {
                //bdump($key,'key');
                //bdump($one,'one');
                foreach($one as $colKey => $colVal)
                {

                    if (isset($tmpArr[$colKey]) && (isset($tmpUpdateKey[$tmpArr[$colKey]]) && $tmpUpdateKey[$tmpArr[$colKey]] == "true" ) ) {
                        //bdump($colKey,'colKey');
                        //bdump($colVal,'colVal');
                        //30.09.2019 - formating
                        if (isset($this->targetFormat[$tmpArr[$colKey]]) && $this->targetFormat[$tmpArr[$colKey]] == 'number') {
                            $colVal = str_replace(',', '.', $colVal);
                            $tmpFArr[$key][$tmpArr[$colKey]] = str_replace(' ', '', $colVal);
                        }elseif(isset($this->targetFormat[$tmpArr[$colKey]]) && $this->targetFormat[$tmpArr[$colKey]] == 'date'){
                            //$colVal = str_replace(',', '.', $colVal);
                            if(strtotime($colVal)) {
                                $tmpDate = new \Nette\Utils\DateTime($colVal);
                                $colVal = $tmpDate->format('Y-m-d H:i:s');
                                //bdump($colVal);
                                $tmpFArr[$key][$tmpArr[$colKey]] = $colVal;
                            }

                        }else{
                            $tmpFArr[$key][$tmpArr[$colKey]] = rtrim($colVal);
                        }
                    }
                }
            }
            bdump($tmpFArr);
            //replace values for related tables with constraint keys
            foreach ($tmpFArr as $key => $one)
            {
                $tmpFArrUnset = array();
                $isUnique = false;
                $this->nU = NULL;
                $tmpRelatedName = "";
                //dump($one);
                foreach($one as $colKey => $colVal)
                {
                    //check for uniqueness
                    if ($colKey == $this->importKey && !$this->uniqueCheckDisabled)
                    {

                        $tmpArrModel = clone $this->arrModels['dataWork'];
                        if ($tmpNU = $tmpArrModel->where($colKey.'= ?', $colVal)->fetch())
                        {
                            $isUnique = true;
                            $this->nU = $tmpNU;
                        } else {
                         //   $isUnique = false;
                        }
                    }

                    $tmpRelatedName0 = substr($colKey,0,strpos($colKey,'.'));
                    if (strlen($tmpRelatedName0) > 0)
                    {
                        $tmpRelatedName = $tmpRelatedName0;


                        $tmpColumnName = substr($colKey,strpos($colKey,'.')+1);
                        //dump($tmpColumnName);
                        bdump($tmpRelatedName);
                        if ($colVal != "" && (array_key_exists($tmpRelatedName,$this->relatedMainKey) || count($this->relatedMainKey) == 0))
                        {
                            //10.08.2021 - only relatedMainKey column name could be inserted, other values are updated later
                            if ($this->relatedMainKey[$tmpRelatedName] == $tmpColumnName) {

                                if ($result = $this->arrModels[$tmpRelatedName]->findAll()->where($tmpColumnName . '= ?', $colVal)->fetch()) {
                                    //dump($result->id);
                                    $resVal = $result->id;
                                } else {
                                    $resVal = NULL;
                                    if (!$this->noInsert) {
                                        //prepare data for insert to related table
                                        $arrInsertData = [$tmpColumnName => $colVal];
                                        //default values for related table
                                        foreach ($this->relatedDefVal[$tmpRelatedName] as $keyRd => $oneRd) {
                                            $arrInsertData[$keyRd] = $oneRd;
                                        }

                                        //in $this->targetData find other columns with same tablename


                                        //insert new value to related table and get constraint key
                                       // bdump($arrInsertData);
                                        if ($inserted = $this->arrModels[$tmpRelatedName]->insert($arrInsertData)) {
                                            $resVal = $inserted->id;
                                        }
                                    }
                                }
                            }
                        }else{
                            $resVal = NULL;
                        }
                        if (!is_null($resVal)) {
                            $tmpFArr[$key][$tmpRelatedName . "_id"] = $resVal;
                        }
                        $tmpFArrUnset[$colKey] = $colVal;
                        unset($tmpFArr[$key][$colKey]);
                    }

                    //default values which are needed
                    foreach ($this->defValColumns as $keyDefVal => $oneDefVal)
                    {
                        if(!isset($tmpFArr[$key][$keyDefVal]) || $tmpFArr[$key][$keyDefVal] == "")
                        {
                            $tmpFArr[$key][$keyDefVal] = $oneDefVal;
                        }
                    }


                    bdump($tmpRelatedName);
                    if (strlen($tmpRelatedName) > 0) {
                        //update imported values for related table
                        $arrUpdateRd = array();
                        //prepare data
                        foreach ($this->relatedImportVal[$tmpRelatedName] as $keyRd => $oneRd) {
                            if (strpos($oneRd, '.') > 0 && array_key_exists($oneRd, $tmpFArrUnset)) {
                                $arrUpdateRd[$keyRd] = rtrim($tmpFArrUnset[$oneRd]);
                            } elseif (array_key_exists($oneRd, $tmpFArr)) {
                                $arrUpdateRd[$keyRd] = rtrim($tmpFArr[$oneRd]);
                            }
                        }
                        //find record and update
                        if (array_key_exists($tmpRelatedName, $this->relatedImportVal)) {
                            $tmpRd_key = $tmpFArr[$key][$tmpRelatedName . "_id"];
                            if ($tmpRd_data = $this->arrModels[$tmpRelatedName]->find($tmpRd_key)) {
                                $tmpRd_data->update($arrUpdateRd);
                            }
                        }
                        //end of imported values for related table
                    }


                }
                //prepare cl_company_id, created
                $tmpFArr[$key]['change_by'] = $this->user->getIdentity()->name;
                $tmpFArr[$key]['changed'] = new \Nette\Utils\DateTime;
                $tmpFArr[$key]['cl_company_id'] = $this->user->getIdentity()->cl_company_id;
                //bdump($isUnique, 'isUnique');
                //bdump($this->noInsertSet, '$this->noInsertSet');
                if ($isUnique)
                {
                    if (!$this->noUpdate && $this->noUpdateSet == 0) {
                        $this->nU->update($tmpFArr[$key]);
                        //unset($tmpFArr[$key]);
                        $this->countResult['updated']++;
                    }
                }else{
                    if (!$this->noInsert && $this->noInsertSet == 0) {
                        $tmpFArr[$key]['create_by'] = $this->user->getIdentity()->name;
                        $tmpFArr[$key]['created'] = new \Nette\Utils\DateTime;
                        $newRow = $this->arrModels['data']->insertPublic($tmpFArr[$key]);
                        $this->afterImport($newRow['id']);
                        $this->countResult['imported']++;
                    }
                }



            }
            if (count($tmpFArr) > 0)
            {

            }
            //$this->countResult['imported'] = count($tmpFArr);
            //$this->count['updated'] = count($tmpFArr);
            //dump($tmpFArr);
            //die;
            //clear file and settings
            //$this->sourceOrder = array();

            if (is_file($this->file))
            {
                unlink($this->file);
            }

            if (isset($this->arrModels['parent_data']) && method_exists($this->arrModels['parent_data'], 'updateSum')) {
                $this->arrModels['parent_data']->updateSum($this->parentId);
            }
            if (isset($this->arrModels['data']) && method_exists($this->arrModels['data'], 'replaceNew')) {
                $this->arrModels['data']->replaceNew();
            }
            if (isset($this->arrModels['other_data']) && method_exists($this->arrModels['other_data'], 'UpdateSum')) {
                $this->arrModels['other_data']->UpdateSum($this->parentId);
            }


            if ($this->countResult['imported']>0)
            {
                $this->flashMessage($this->translator->translate('Importováno').$this->countResult['imported'].$this->translator->translate('položek'), 'success');
            }
            if ($this->countResult['updated']>0)
            {
                $this->flashMessage($this->translator->translate('Aktualizováno').$this->countResult['not_imported'].$this->translator->translate('položek'), 'danger');
            }
            $this->flashMessage('Pro zobrazení nových dat zavřete okno importu a obnovte původní stránku.', 'success');
            $this->redrawControl('importeddata');
            $this->redrawControl('flash');

        }else{
            return FALSE;
        }
    }

    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('target',NULL);
        $form->addHidden('cl_partners_book_id',NULL);
        $form->addText('delimiter', $this->translator->translate('Oddělovač_sloupců'), 1, 1);
        $form->addText('enclosure', $this->translator->translate('Znakové_řetězce_uzavřeny_mezi'), 1, 1);
        $form->addCheckbox('no_insert_set', $this->translator->translate('Nevkládat_nové_záznamy'));
        $form->addCheckbox('no_update_set', $this->translator->translate('Neaktualizovat_záznamy'));
        $form->addText('l_header', 'Názvy sloupců na řádku', 1, 1);
        $form->addText('l_data', 'Data od řádku', 2, 2);
        foreach($this->targetData as $key => $one)
        {
            $arrKeys[$key] = $one;
        }
        //$arrKeys = array_keys($this->targetData);
        //$arrKeys = array_combine($arrKeys, $arrKeys);
        $form->addSelect('import_key', $this->translator->translate('Klíč'),  $arrKeys);
        $form->addSubmit('send', $this->translator->translate('Použít'))->setHtmlAttribute('class','btn btn-success');
        $form->addSubmit('reset', $this->translator->translate('Reset'))
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'resetImportSetting');
        $form->onSuccess[] = array($this,'SubmitImportSetting');
        return $form;
    }

    public function resetImportSetting()
    {
        if (!is_null( $this->partnersBookId)) {
            $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ? AND cl_partners_book_id = ?', $this->target, $this->partnersBookId)->fetch();
        }else{
            $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target)->fetch();
        }
        $csvProfile->delete();
        if (is_file($this->file))
        {
            unlink($this->file);
        }
        $this->sourceOrder = NULL;
        $this->sourceOrder = array();
       // $this->redrawControl('importeddata');
       // $this->redrawControl('importSetting');
       // $this->redrawControl('testy');
        //$this->redrawControl('content');
        $this->redirect('this');
    }

    public function SubmitImportSetting(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $arrImportSetting = array();

            $arrImportSetting['target'] = $data['target'];
            $arrImportSetting['delimiter'] = $data['delimiter'];
            $arrImportSetting['enclosure'] = $data['enclosure'];
            $arrImportSetting['import_key'] = $data['import_key'];
            $arrImportSetting['no_insert_set'] = $data['no_insert_set'];
            $arrImportSetting['no_update_set'] = $data['no_update_set'];
            $arrImportSetting['l_header'] = $data['l_header'];
            $arrImportSetting['l_data'] = $data['l_data'];
            $arrImportSetting['source_order'] = '[]';

            if ($data['cl_partners_book_id'] != '') {
                $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ? AND cl_partners_book_id = ?', $data['target'], $data['cl_partners_book_id'])->fetch();
                $arrImportSetting['cl_partners_book_id'] = $data['cl_partners_book_id'];
            }else{
                $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $data['target'])->fetch();
                $arrImportSetting['cl_partners_book_id'] = NULL;
            }

            if ($csvProfile) {
                $csvProfile->update($arrImportSetting);
            }else{
                $this->CsvProfilesManager->insert($arrImportSetting);
            }

            //$section = $this->getSession('importData_'. $data['target']);
            //$section->importSetting = $arrImportSetting;
            //if (isset($section->sourceOrder))
            //{
            $this->sourceOrder = array();
            //}

            //$this->redrawControl('importeddata');
            //$this->redrawControl('importSetting');
            //$this->redrawControl('testy');
            //$this->redrawControl('content');
            $this->redirect('this');

        }

    }

    public function handleCheckUpdate($name, $status)
    {
        $arrImportSetting = array();
        $arrImportSetting['target'] = $this->target;


        if ($this->partnersBookId != '') {
            $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ? AND cl_partners_book_id = ?', $this->target, $this->partnersBookId)->fetch();
            $arrImportSetting['cl_partners_book_id'] = $this->partnersBookId;
        }else{
            $csvProfile = $this->CsvProfilesManager->findAll()->where('target = ?', $this->target)->fetch();
            $arrImportSetting['cl_partners_book_id'] = NULL;
        }

        if ($csvProfile) {
            $updateKeys = json_decode($csvProfile->update_keys, true);
            $updateKeys[$name] = str_replace('"', '', $status);
            $arrImportSetting['update_keys'] = json_encode($updateKeys);

            $csvProfile->update($arrImportSetting);
        }else{
            $updateKeys = array();
            $updateKeys[$name] = $status;
            $arrImportSetting['update_keys'] = json_encode($updateKeys);

            $this->CsvProfilesManager->insert($arrImportSetting);
        }


        $this->sourceOrder = array();

        $this->redrawControl('importeddata');
        $this->redrawControl('importSetting');
        $this->redrawControl('testy');
    }

    private function afterImport($key)
    {
        if ( $this->target == 'task'){
            if ($tmpRow = $this->arrModels['data']->find($key)){
                if (!is_null($tmpRow['end_date'])){
                    $tmpRow->update(['finished' => 1]);
                }
            }
        }

    }


}
