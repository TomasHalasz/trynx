<?php

namespace App\Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class EditTextControl extends Control
{

    public $parent_id;
    
    private $edit_name;

    /** @var \App\Model\Base*/
    private $dataManager;    
    
    private $settings;
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public function __construct(Nette\Localization\Translator $translator,$dataManager,$parent_id, $edit_name)
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
	    $this->dataManager      = $dataManager;
	    $this->edit_name        = $edit_name;
	    $this->parent_id        = $parent_id;
        $this->translator       = $translator;
    }        

    public function action()
    {
	
    }
    
    public function render()
    {
      //  //profiler::start();
	//$id = NULL
	//10.07.2018 - this is have to do solution for situation when we need redraw control without redrawing whole content snippet. 
	//in this case we don't get property from paren template {control pairedDocs $data->id} 
	//so we define default value NULL and use $id only if it is not NULL
	//if (!is_null($id))
	//{
	  //  $this->id = $id;
	//}
        $this->template->setFile(__DIR__ . '/EditText.latte');
	$this->template->data = $this->dataManager->findBy(array('id' => $this->parent_id))->fetch();
	$this->template->settings = $this->settings;
	$this->template->edit_name = $this->edit_name;
	$this->template->cmpName = $this->name;
	$this->template->myReadOnly = $this->parent->myReadOnly;
        $this->template->render();
     //   //profiler::finish('editText '.$this->getName());
    }
    
     
    public function handleUndoChanges()
    {
	$this->redrawControl('edittext');
    }

    public function handleSaveChanges($html)
    {
	//bdump($this->id);
	//bdump($html);
	$this->dataManager->update(array('id' => $this->parent_id, $this->edit_name => $html), TRUE);
	$this->redrawControl('edittext');
    }
        

}

