<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use App\BankCom;

class StoreMoveInPresenter extends \App\Presenters\BaseListPresenter {

    public $importType=0;

    private $cl_pricelist_id, $cl_storage_id, $doc_type = 0;

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
     * @var \App\Model\StoreMoveManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    /**
     * @inject
     * @var \App\Model\StoreOutManager
     */
    public $StoreOutManager;

    protected function startup()
    {
        parent::startup();
        $mySection              = $this->session->getSection('storemove');
        $this->cl_pricelist_id  = $mySection['cl_pricelist_id'];
        $this->cl_storage_id    = $mySection['cl_storage_id'];
        $this->doc_type         = $mySection['doc_type'];
        $tmpPricelist           = $this->PriceListManager->find($this->cl_pricelist_id );
        //$this->translator->setPrefix(['applicationModule.StoreReview']);
        if ($tmpPricelist) {
            $this->formName = $this->translator->translate('Příjmy') . ': ' . $tmpPricelist['identification'] . ' ' . $tmpPricelist['item_label'];
            //                    ' ----- ' . $this->translator->translate('přijato') . ': ' . $tmpSumIn['s_in']  . ' ' . $tmpPricelist['unit'] . ' ' . $this->translator->translate('zůstatek') . ': ' . $tmpSumIn['s_end'] . ' ' . $tmpPricelist['unit'] ;

        }
        //$this->formName = $this->translator->translate("Bankovní transakce");
        $this->mainTableName = 'cl_store_move';
        $this->tableNameAddOn = "in";

        $this->dataColumns = ['cl_store_doc.doc_date'         => ['format' => 'date', $this->translator->translate('Datum')],
                                    'cl_store_doc.doc_number'       => [$this->translator->translate('Příjemka'), 'format' => "url", 'size' => 9, 'url' => 'storein', 'value_url' => 'cl_store_docs_id'],
                                    'cl_store_doc.cl_partners_book.company' => ['format' => 'text', $this->translator->translate('Dodavatel')],
                                    'cl_store_doc.invoice_number'   =>  ['format' => 'text',$this->translator->translate('Faktura')],
                                    'cl_store_doc.delivery_number'  =>  ['format' => 'text',$this->translator->translate('Dodací_list')],
                                    'cl_storage.name'               => ['format' => 'text', $this->translator->translate('Sklad')],
                                    's_in'                          => ['format'=> 'number', $this->translator->translate('Příjem')],
                                    's_end'                         => ['format'=> 'number', $this->translator->translate('Zůstatek')],
                                    'price_in'                      => ['format'=> 'currency', $this->translator->translate('Nákupní_cena')],
                                    'cl_store_doc.cl_currencies.currency_code' => ['format' => 'text', $this->translator->translate('Měna')],
                                    'price_s'                       => ['format'=> 'currency', $this->translator->translate('Skladová_cena')],
                                    'cl_store.exp_date'             => ['format' => 'date', $this->translator->translate('Expirace')],
                                    'cl_store.batch'                => ['format' => 'text', $this->translator->translate('Šarže')],
                                    'created'                       => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
                                    'create_by'                     => $this->translator->translate('Vytvořil'),
                                    'changed'                       => [$this->translator->translate('Změněno'),'format' => 'datetime'],
                                    'change_by'                     => $this->translator->translate('Změnil')];


        $this->FilterC = ' ';
        $this->DefSort = 'cl_store_doc.doc_date DESC';
        $this->defValues = [];

        $this->mainFilter = 'cl_store_doc.doc_type = 0 AND cl_store_move.cl_pricelist_id = ' . $this->cl_pricelist_id . ' AND cl_store_move.cl_storage_id = ' . $this->cl_storage_id;
        //$this->readOnly = array('account_number_foreign' => TRUE    );
        $this->filterColumns = ['cl_store_doc.doc_date' => 'autocomplete' , 'cl_store_doc.doc_number' => 'autocomplete', 'cl_store_doc.cl_partners_book.company' => 'autocomplete', 'cl_store_doc.invoice_number' => 'autocomplete',
                                        's_in' => 'autocomplete', 's_end' => 'autocomplete', 'price_in' => 'autocomplete', 'price_s' => 'autocomplete', 'cl_store_doc.cl_currencies.currency_code' => 'autocomplete',
                                        'cl_store_doc.delivery_number' => 'autocomplete', 'cl_store.exp_date' => 'autocomplete', 'cl_store.batch' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cl_store_doc.doc_number', 'cl_store_doc.cl_partners_book.company', 'cl_store_doc.invoice_number', 'cl_store_doc.delivery_number'];
        $this->rowFunctions = ['copy' => 'disabled', 'erase' => 'disabled'];
        $this->toolbar = [];
        /*$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový záznam'), 'class' => 'btn btn-primary'),
            2 => array('group' =>
                array(0 => array('url' => $this->link('importTrans!', array('type' => 0)),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('Import GPC/ABO'),
                    'title' => $this->translator->translate('Import z formátu GPC/ABO'),
                    'data' => array('data-ajax="true"', 'data-history="false"'),
                    'class' => 'ajax', 'icon' => 'iconfa-import'),
                ),
                'group_settings' => array(  'group_label' => $this->translator->translate('Import'),
                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                    'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-import')
            )
        );*/

        /*predefined filters*/
        $this->pdFilter = [];


        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;

    }


    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

        $tmpSumIn = $this->DataManager->findAll()->where('cl_store_move.cl_storage_id = ? AND cl_store_move.cl_pricelist_id = ? AND cl_store_docs.doc_type IN (0,1)', $this->cl_storage_id, $this->cl_pricelist_id)->
                                                    select('SUM(cl_store_move.s_in) AS s_in, SUM(cl_store_move.s_in - cl_store_move.s_out) AS s_end, cl_pricelist.unit AS unit')->fetch();
        //bdump(round($tmpSumIn['s_in'], $this->settings->des_mj),'ddd');
        $arrPdSum = [$this->translator->translate('přijato')  => round($tmpSumIn['s_in'], $this->settings->des_mj) . ' ' . $tmpSumIn['unit'],
                            $this->translator->translate('zůstatek') => round($tmpSumIn['s_end'], $this->settings->des_mj) . ' ' . $tmpSumIn['unit']];

        $this->template->pdSum = $arrPdSum;

    }

    public function renderEdit($id,$copy,$modal){
        parent::renderEdit($id,$copy,$modal);

        $data = $this->StoreOutManager->findAll()->where('cl_store_move_in_id = ? ', $id)->order('cl_store_move.cl_store_docs.doc_date DESC, cl_store_move.id DESC');
        $tmpSumOut = $this->StoreOutManager->findAll()->where('cl_store_move.cl_storage_id = ? AND 
                                                                    cl_store_move.cl_pricelist_id = ? AND cl_store_move.cl_store_docs.doc_type = 1 AND
                                                                    cl_store_move_in_id = ?', $this->cl_storage_id, $this->cl_pricelist_id, $id)->
                                                    select('SUM(cl_store_out.s_out) AS s_out, cl_store_move.cl_pricelist.unit AS unit')->fetch();
        $this->template->dataMove = $data;
        $this->template->dataSum = $tmpSumOut;

    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        return $form;
    }

}
