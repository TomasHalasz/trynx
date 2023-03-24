<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class EmailHistoryPresenter extends \App\Presenters\BaseListPresenter {




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
     * @var \App\Model\EmailingManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;


    protected function startup()
    {
        parent::startup();
        $this->formName = "Odeslané emaily";
        $this->mainTableName = "cl_emailing";
        $this->dataColumns = ['created' => ['Datum vytvoření emailu', 'format' => 'datetime', 'size' => 20],
            'singleEmailTo' => ['Komu', 'format' => 'unformated', 'size' => 35],
            'subject' => ['Předmět', 'format' => 'text', 'size' => 35],
            'singleEmailFrom' => ['Odesílatel', 'format' => 'unformated', 'size' => 35],
            'dt_sent_srvc' => ['Automaticky odesláno', 'format' => 'datetime', 'size' => 20],
            'cl_invoice.inv_number' => ['Faktura', 'format' => 'url', 'size' => 10, 'url' => 'invoice', 'value_url' => 'cl_invoice_id'],
            'cl_invoice_advance.inv_number' => ['Zálohová faktura', 'format' => 'url', 'size' => 10, 'url' => 'invoice', 'value_url' => 'cl_invoice_advance_id'],
            'cl_commission.cm_number' => ['Zakázka', 'format' => 'url', 'size' => 10, 'url' => 'commission', 'value_url' => 'cl_commission_id'],
            'cl_offer.cm_number' => ['Nabídka', 'format' => 'url', 'size' => 10, 'url' => 'offer', 'value_url' => 'cl_offer_id'],
            'cl_invoice_internal.inv_number' => ['Interní doklad', 'format' => 'url', 'size' => 10, 'url' => 'invoiceinternal', 'value_url' => 'cl_invoice_internal_id'],
            'cl_order.od_number' => ['Objednávka', 'format' => 'url', 'size' => 10, 'url' => 'order', 'value_url' => 'cl_order_id'],
            'cl_delivery_note.dn_number' => ['Dodací list', 'format' => 'url', 'size' => 10, 'url' => 'deliverynote', 'value_url' => 'cl_delivery_note_id'],
            'cl_b2b_order.cm_number' => ['Objednávka z B2B', 'format' => 'text', 'size' => 10, 'url' => 'b2border', 'value_url' => 'cl_b2b_order_id'],
            'in_emailing.em_number' => ['Hromadné emaily', 'format' => 'text', 'size' => 10, 'url' => 'inemailing', 'value_url' => 'in_emailing_id'],
        ];

        $this->filterColumns = ['singleEmailTo' => 'autocomplete' , 'subject' => 'autocomplete', 'singleEmailFrom' => 'autocomplete' , 'cl_invoice.inv_number' => 'autocomplete',
                                    'cl_commission.cm_number' => 'autocomplete', 'cl_commission.cm_number' => 'autocomplete', 'cl_offer.cm_number' => 'autocomplete',
                                    'cl_invoice_internal.inv_number' => 'autocomplete', 'cl_order.od_number' => 'autocomplete', 'cl_delivery_note.dn_number' => 'autocomplete',
                                    'cl_b2b_order.cm_number' => 'autocomplete', 'in_emailing.em_number' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['singleEmailTo', 'subject', 'singleEmailFrom'];
        //$this->filterColumns = array();
        $this->DefSort = 'created DESC';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->rowFunctions = ['copy' => 'disabled', 'erase' => 'disabled', 'edit' => 'disabled'];
        //$this->numberSeries = array('use' => 'emailing', 'table_key' => 'cl_number_series_id', 'table_number' => 'em_number');
        /*$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary')
        );*/

    }

    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }

    public function renderEdit($id,$copy,$modal){
        parent::renderEdit($id,$copy,$modal);

    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id',NULL);
        $form->addText('created', 'Datum a čas vytvoření', 20, 20)
            ->setHtmlAttribute('readonly', true);

        $form->addTextArea('singleEmailTo', 'Komu', 40, 3)
            ->setHtmlAttribute('readonly', true);
        $form->addTextArea('singleEmailFrom', 'Odesílatel', 40, 3)
            ->setHtmlAttribute('readonly', true);

        $form->addText('subject', 'Předmět', 60, 60)
            ->setHtmlAttribute('readonly', true);

        $form->addTextArea('body', 'Tělo', 60, 10)
            ->setHtmlAttribute('readonly', true);


        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('title', 'Vrátí se zpět bez uložení')
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        $form->onSuccess[] = array($this,'SubmitEditSubmitted');
        return $form;
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;

        //$this->redirect('default');
        $this->redrawControl('content');
    }



}
