<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class RatesVatPresenter extends \App\Presenters\BaseListPresenter {

    
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
    * @var \App\Model\RatesVatManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\CountriesManager
    */
    public $CountriesManager;        
    
   
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.RatesVat']);
        $this->dataColumns = ['rates' => $this->translator->translate('Sazba'),
                        'description' => $this->translator->translate('Název'),
                        'valid_from' => [$this->translator->translate('Platí_od'),'format' => 'date'],
                       'code_name' => $this->translator->translate('Kód'),
                       'cl_countries.name' => $this->translator->translate('Stát')];
        $this->formatColumns = ['valid_from' => "date"];
        $this->FilterC = '';
        $this->DefSort = 'rates';
        $this->defValues = ['valid_from' => new \Nette\Utils\DateTime];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
    }
    
    public function renderEdit($id,$copy,$modal){
        parent::renderEdit($id,$copy,$modal);
        if ($defData = $this->DataManager->findOneBy(['id' => $id]))
        {
            $this['edit']->setValues($defData);

            $this['edit']->setValues(['valid_from' => $defData['valid_from']->format('d.m.Y')]);
        }
    }
    
    
    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);

	    $arrCountries = $this->CountriesManager->findAllTotal()->fetchPairs('id','name');
	    $form->addSelect('cl_countries_id', $this->translator->translate("Stát"),$arrCountries)
		        ->setPrompt($this->translator->translate('Zvolte_stát'));
        $form->addText('valid_from', $this->translator->translate('Platí_od'), 0, 20)
                ->setHtmlAttribute('placeholder',$this->translator->translate('Platnost_od'));
        $form->addText('description', $this->translator->translate('Název'), 0, 20)
                ->setHtmlAttribute('placeholder',$this->translator->translate('Název'));
        $form->addText('rates', $this->translator->translate('Sazba_DPH'), 0, 20)
                ->setHtmlAttribute('placeholder',$this->translator->translate('Sazba'));
        $form->addText('code_name', $this->translator->translate('Kód'), 0, 5)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Kód'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))
                ->setHtmlAttribute('class','btn btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		        ->setHtmlAttribute('class','btn btn-primary')
		        ->setValidationScope([])
		        ->onClick[] = [$this, 'stepBack'];
        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
        return $form;
    }

    public function stepBack()
    {	    
	    $this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;

        $data['valid_from'] = date('Y-m-d H:i', strtotime($data['valid_from']." 00:00"));
        if ($form['send']->isSubmittedBy())
        {
            if (!empty($data->id))
                $this->DataManager->update($data);
            else
                $this->DataManager->insert($data);

            $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        }
        $this->redirect('default');
    }	    


}
