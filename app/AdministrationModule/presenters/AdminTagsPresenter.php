<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Tags presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminTagsPresenter extends SecuredPresenter
{
	private $tag_id,$tags;


	/**
	* @inject
	* @var \App\Model\BlogTagsManager
	*/
	public $BlogTagsManager;        
	
	  
	public function actionDefault()
	{
		$this->tags = $this->BlogTagsManager->findAll()->order('name');
				
	}
	
	public function renderDefault()
	{
		$this->template->tags = $this->tags;
	}	
	
	public function actioneditTag($id)
	{
	   	//$mySet = $this->getSession('mySet');
		$this->tag_id = $id;
	}
  
	public function renderEditTag()
	{
		if ($this->tag_id>0)
	    {
            $this->template->editItem =  $this->BlogTagsManager->find($this->tag_id);
            $def_data = $this->BlogTagsManager->find($this->tag_id);
			$this['editTag']->setDefaults($def_data);
	    }else{            
			$this->template->editItem = array();
		}
	}		

	protected function createComponentEditTag($name)
	{	
		$form = new Form($this, $name);
		$form->addText('name', 'Název značky:', 100, 100)
			->setAttribute('placeholder','Název značky')				
			->setAttribute('class', 'form-control');
		$form->addText('description', 'Popis:', 100, 100)
			->setAttribute('placeholder','Popis')				
			->setAttribute('class', 'form-control');		
		$form->addText('keywords', 'Klíčová slova:', 60, 60)
			->setAttribute('placeholder','Klíčová slova')				
			->setAttribute('class', 'form-control');		
		$form->addHidden('id');
		$form->addSubmit('create', 'Uložit')->setAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setAttribute('class','btn btn-default');
		$form->onSuccess[] = array($this,'editTagSubmitted');
        return $form;
	}
	
	public function editTagSubmitted(Form $form)
	{
		if ($form['create']->isSubmittedBy())
		{
			$data=$form->values;
			if ($form->values['id']==NULL)
			{
				unset($data['id']);				
				$row=$this->BlogTagsManager->insert($data);

			}else{				
				//$data=$form->values;
				$this->BlogTagsManager->update($data);
				$row['id']=$form->values['id'];
			}

		$this->flashMessage('Položka uložena', 'success');
		}
	    $this->redirect('AdminTags:');
	}	
	
	
	public function handleEraseTag($id)
	{
	    try{
			$this->BlogTagsManager->delete($id);

	    }
	    catch (Exception $e) {
		    if ($e->errorinfo[1]=1451)
		    {
				$errorMess = 'Není možné vymazat značku, je aktuálně použitá.'; 
		    } else {
				$errorMess = $e->getMessage(); 
			}
		    $this->flashMessage($errorMess,'danger');
	    }	    	    
	    $this->redirect('AdminTags:');			    
	}






}
