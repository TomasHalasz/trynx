<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminGalleryPresenter extends SecuredPresenter
{
	private $gallery_id,$items,$gallery,$item_list_eng;
	private $images;

	/**
	* @inject
	* @var \App\Model\BlogImagesManager
	*/
	public $BlogImagesManager;        
	
	
	/**
	* @inject
	* @var \App\Model\BlogGalleryManager
	*/
	public $BlogGalleryManager;        

	/**
	* @inject
	* @var \App\Model\BlogCategoriesManager
	*/
	public $BlogCategoriesManager;	
  
	public function actionDefault()
	{
		$this->gallery = $this->BlogGalleryManager->findAll();
	}
	
	public function renderDefault()
	{
		$this->template->gallery = $this->gallery;
	}	
	
	public function actioneditGallery($id)
	{
	   	$mySet = $this->getSession('mySet');
		$mySet->editPhoto = 0;
		$this->gallery_id = $id;
	}
  
	public function renderEditGallery()
	{
		if ($this->gallery_id>0)
	    {
			$this->template->editItem =  $this->BlogGalleryManager->find($this->gallery_id);
			$def_data = $this->BlogGalleryManager->find($this->gallery_id);
			$this['editGallery']->setDefaults($def_data);
	    }else{            
			$this->template->editItem = array();
		}
	}		

	protected function createComponentEditGallery($name)
	{	
		$form = new Form($this, $name);
		$form->addText('name', 'Název galerie:', 100, 100)
			->setAttribute('placeholder','Název galerie')				
			->setAttribute('class', 'form-control');
	    $form->addSelect('blog_categories_id', 'Kategorie galerie:',$this->BlogCategoriesManager->findAll()->order('order_cat')->fetchPairs('id','name'))
			->setAttribute('class', 'form-control')
			->setPrompt('- Vyberte -');
	    $form->addHidden('id');
		$form->addSubmit('create', 'Uložit')->setAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setAttribute('class','btn btn-default');
		$form->onSuccess[] = array($this,'editGallerySubmitted');
		return $form;
	}
	
	public function editGallerySubmitted(Form $form)
	{
		if ($form['create']->isSubmittedBy())
		{
			$data=$form->values;

			if ($form->values['id']==NULL)
			{
				//$data=$form->values;
				unset($data['id']);				
				$row=$this->BlogGalleryManager->insert($data);
			}else{				
				//$data=$form->values;
				$this->BlogGalleryManager->update($data);
				$row['id']=$form->values['id'];
			}
			$this->flashMessage('Položka uložena', 'success');
		}
	    $this->redirect('AdminGallery:');
	}	
	
	
	public function handleEraseGallery($id)
	{
	    try{
			$this->BlogGalleryManager->delete($id);
	    }
	    catch (Exception $e) {
		    if ($e->errorinfo[1]=1451)
		    {
				$errorMess = 'Není možné vymazat galerii, je aktuálně použitá.'; 
		    } else {
				$errorMess = $e->getMessage(); 
			}
		    $this->flashMessage($errorMess,'danger');
	    }	    	    
	    $this->redirect('AdminGallery:');		
	}






}
