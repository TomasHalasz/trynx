<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class EventMethodPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\PartnersEventMethodManager
    */
    public $DataManager;    

    
    protected function startup()
    {
	    parent::startup();
        //$this->translator->setPrefix(['applicationModule.EventMethod']);
        $this->dataColumns = array('method_name' => $this->translator->translate('Název_způsobu_události'), 'method_order' => $this->translator->translate('Pořadí'),
                                    'default_method' => array($this->translator->translate('Výchozí_způsob'),'format' => 'boolean'),
                                    'remote' => array($this->translator->translate('Vzdáleně'),'format' => 'boolean'),
                                    'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
        $this->formatColumns = array('created' => 'datetime', 'changed' => 'datetime');
        //$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
        $this->DefSort = 'method_order,method_name';
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'));
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
	    //$form->setMethod('POST');
        //$this->translator->setPrefix(['applicationModule.EventMethod']);
	    $form->addHidden('id',NULL);
            $form->addText('method_name', $this->translator->translate('Název_způsobu_události'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_prosím_název_způsobu_události'))
			->setAttribute('placeholder',$this->translator->translate('Název_způsobu_události'));
		$form->addText('method_order', $this->translator->translate('Pořadí'), 3, 3)
			->setAttribute('placeholder',$this->translator->translate('Pořadí_způsobu'));
	    $form->addCheckbox('default_method', $this->translator->translate('Výchozí_způsob'))
			->setDefaultValue(FALSE)
			->setAttribute('class', 'items-show');	  	    	    
	    $form->addCheckbox('remote', $this->translator->translate('Vzdáleně'))
			->setDefaultValue(FALSE)
			->setAttribute('class', 'items-show');	  	    	    		
		$form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
            return $form;
    }

    public function stepBack()
    {	    
		$this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
	$data=$form->values;
	//dump($data);
	//	die;
        if ($form['send']->isSubmittedBy())
	{
	    //dump($data->id);
	    //die;
	    if (!empty($data->id))
	    {
		$tmpOld = $this->DataManager->find($data->id);
		$this->DataManager->update($data, TRUE);
		if ($tmpOld->method_order < $data['method_order'])
		{
		    $tmp = $this->DataManager->findAll()->select("cl_partners_event_method.*,(CASE WHEN id = ? THEN 1 ELSE 0 END) AS poradi2",$data->id)->order('method_order ASC,poradi2 ASC');		    
		}else
		{
		    $tmp = $this->DataManager->findAll()->select("cl_partners_event_method.*,(CASE WHEN id = ? THEN 0 ELSE 1 END) AS poradi2",$data->id)->order('method_order ASC,poradi2 ASC');		    
		}


		$i = 1;
		foreach ($tmp as $one)
		{
		    $one->update(array('method_order' => $i));
		    $i++;
		}
	    }
	    else
	    {
		$this->DataManager->insert($data);
	    }
	}
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
	$this->redirect('default');
    }
    
    
    //aditional action after delete from baseList
    public function beforeDelete($lineId)
    {
		//pokud jde o hlavní událost musime kontrolovat její použití a případnou existenci podřízených událostí, 
		//a poté nepovolit mazání
		dump($this->translator->translate('nehotovo'));
		die;
		return TRUE;
    }       


}
