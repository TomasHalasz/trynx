<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Contributte\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class TextsUseControl extends Control
{

    public $editText = FALSE, $idText = NULL, $showFilter = '',  $mainFilter = '', $textSearch = '';
    
    /** @persistent */
    public $id;

    /** @var \App\Model\Base*/
    private $dataManager;    
    
    

    /** @var \App\Model\Base*/
    private $TextsManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public $pairedDocsShow;
    
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct($dataManager,$parent_id, $text_use, \App\Model\TextsManager $textsManager, Nette\Localization\Translator $translator )
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->dataManager = $dataManager;
        $this->id = $parent_id;
        $this->TextsManager = $textsManager;
        $this->mainFilter = $text_use;
        $this->showFilter = $text_use;
        $this->translator = $translator;
    }        

    public function action()
    {
    }

    public function render()
    {

        //profiler::start();
        $this->template->showFilter = $this->showFilter;
        $this->template->mainFilter = $this->mainFilter;
        $this->template->setFile(__DIR__ . '/TextsUse.latte');
       
       $mainTableName			= $this->dataManager->getTableName() . '_id';
       $this->template->mainTableName	= $mainTableName;
       

       $tmpDataTexts = $this->TextsManager->findAll()->order('name');
       if ($this->showFilter != ''){
	    $tmpDataTexts = $tmpDataTexts->where('text_use = ? OR text_use = ""', $this->showFilter);
       }
       
       if ($this->textSearch != ''){
	   $tmpSearch = '%'.$this->textSearch.'%';
	   $tmpDataTexts = $tmpDataTexts->where('name LIKE ? OR text LIKE ?', $tmpSearch, $tmpSearch);
       }else{
	   $this['search']->setValues(array('search' => ''));
       }
       
        $this->template->dataTexts	= $tmpDataTexts;

        //bdump($this->template->dataDocs);
        //$this->template->pairedDocsShow = $pairedDocsShow;
        $this->template->editText = $this->editText;
        if ($this->idText != NULL){
            $tmpDefValEdit = $this->TextsManager->find($this->idText);
            $this['edit']->setValues($tmpDefValEdit);
        }else{
            $this['edit']->setValues(array('text_use' => $this->mainFilter));
        }
        $this->template->render();
	    //profiler::finish('TextsUse');
    }
    
    public function handleSelectText()
    {
	
    }
    
    public function handleEditText($idText)
    {
        $this->editText = TRUE;
        $this->idText = $idText;
        $this->redrawControl('docs');
    }
    
    public function handleNewText()
    {
        $this->editText = TRUE;
        $this->idText = NULL;
        $this->redrawControl('docs');
    }
	        
    public function handleShowAll($showFilter)
    {
        if ($this->showFilter == $showFilter){
            $this->showFilter = "";
        }else{
            $this->showFilter = $this->mainFilter;
        }

        $this->redrawControl('docs');
    }

    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
            $form->addText('name', $this->translator->translate('Název'), 20, 20)
		    	->setHtmlAttribute('class','form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Název_textu'));

	    $arrStatus_use = $this->presenter->getStatusAll();
	    $form->addselect('text_use', $this->translator->translate('Použití'),$arrStatus_use)
		    ->setPrompt($this->translator->translate('Zvolte_použití'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_použití'))		    
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Použití'));		    
	    
        $form->addTextArea('text', 'Text', 40, 7)
			->setHtmlAttribute('placeholder','Text');	  
	    $form->addCheckbox('no_format', 'Bez formátování textu');

        $form->addSubmit('send', $this->translator->translate('Uložit'))
            ->setHtmlAttribute('data-not-check',"1")
            ->setHtmlAttribute('class','btn btn-sm btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setHtmlAttribute('data-not-check',"1")
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');

		$form->onSuccess[] = array($this,'SubmitEditSubmitted');
		return $form;
    }

    public function stepBack()
    {	    
		//$this->redirect('default');
	$this->editText = FALSE;
	$this->redrawControl('docs');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
	    $data=$form->values;

	    //later there must be another condition for user rights, admin can edit everytime
	    if ($form['send']->isSubmittedBy())
	    {    
		if ($data['id'] == 0){
		    $this->TextsManager->insert($data);
		}else{
		    $this->TextsManager->update($data);
		}
	    }
	$this->editText = FALSE;
	$this->redrawControl('docs');	    
    }
    
   protected function createComponentSearch($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
            $form->addText('search', $this->translator->translate('Hledat:'), 30, 30)
		    	->setHtmlAttribute('class','form-control input-sm')
			->setHtmlAttribute('placeholder',$this->translator->translate('Hledaný_text'));
	    $form->addSubmit('back', $this->translator->translate('Zrušit'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackSearch');	
            $form->addSubmit('send', 'Hledat')->setHtmlAttribute('class','btn btn-sm btn-primary');

	    $form->onSuccess[] = array($this,'SearchSubmitted');
	    return $form;
    }

    public function stepBackSearch()
    {	    
	$this->textSearch = '';
	$this->redrawControl('docs');	 
    }		
    
    
    public function SearchSubmitted(Form $form)
    {
	$data=$form->values;	
	//later there must be another condition for user rights, admin can edit everytime
	if ($form['send']->isSubmittedBy())
	{    
	    $this->textSearch = $data->search;
	}
	$this->redrawControl('docs');	    
    }    
        

}

