<?php

namespace App\IntranetModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

class NotificationsPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\NotificationsLangManager
     */
    public $NotificationsLangManager;


    /**
    * @inject
    * @var \App\Model\NotificationsManager
    */
    public $DataManager;    


    protected function startup()
    {
        parent::startup();
        $this->formName = "Oznámení";
        $this->mainTableName = "in_notifications";
        $this->dataColumns = ['subject' => ['Předmět', 'format' => 'text', 'size' => 30],
                                    'valid_from' => ['Platnost od', 'format' => 'datetime', 'size' => 10],
                                    'valid_to' => ['Platnost do', 'format' => 'datetime', 'size' => 10],
                                    'priority' => ['Priorita', 'format' => 'boolean', 'size' => 5],
                                    'created' => ['Vytvořeno','format' => 'datetime'],'create_by' => 'Vytvořil','changed' => ['Změněno','format' => 'datetime'],'change_by' => 'Změnil'];

        $this->filterColumns = ['subject' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['name'];
        //$this->filterColumns = array();
        $this->DefSort = 'valid_from DESC';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary']];
        $this->bscOff = FALSE;
        $this->bscEnabled = FALSE;
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
        $form->addText('subject', 'Předmět', 60, 120)
			->setHtmlAttribute('placeholder','Předmět zprávy');
        $form->addText('valid_from', 'Platnost od', 20, 20)
            ->setHtmlAttribute('placeholder','Platnost od');
        $form->addText('valid_to', 'Platnost do', 20, 20)
            ->setHtmlAttribute('placeholder','Platnost do');
        $form->addCheckbox('priority', 'Priorita' );

        $form->addTextArea('message', 'Zpráva', 60, 15)
            ->setHtmlAttribute('class', 'form-control, input-sm, trumbowyg-edit')
            ->setHtmlAttribute('placeholder','Tělo zprávy');

        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = [$this, 'stepBack'];
		$form->onSuccess[] = [$this,'SubmitEditSubmitted'];
            return $form;
    }

    public function stepBack()
    {	    
	$this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $data=$this->removeFormat($data);
            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }


    protected function createComponentNotificationsLangGrid()
    {
        $arrLang = $this->ArraysManager->getLanguages();
        $arrData = [
                    'lang' => ['Jazyk','format' => 'chzn-select', 'size' => 10,'values' =>  $arrLang, 'required' => 'Jazyk musí být vybrán'],
                    'subject' => ['Předmět', 'format' => 'text', 'size' => 20],
                    'message' => ['Zpráva', 'format' => 'textarea-formated', 'size' => 150, 'rows' => 10, 'newline' => true]
        ];
        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->NotificationsLangManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'id', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '' //txtSEarchcondition
        );
        $control->setContainerHeight('auto');
        return $control;
    }





}
