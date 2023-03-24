<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Categories presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminCategoriesPresenter extends SecuredPresenter
{
	private $category_id,$categories;


	/**
	* @inject
	* @var \App\Model\BlogImagesManager
	*/
	public $BogImagesManager;        
	
	
	/**
	* @inject
	* @var \App\Model\BlogCategoriesManager
	*/
	public $BlogCategoriesManager;        
	
  
	public function actionDefault()
	{
                $this->categories = $this->BlogCategoriesManager->findAll()->order('order_cat');
				
	}
	
	public function renderDefault()
	{
		$this->template->categories = $this->categories;
	}	
	
	public function actioneditCategory($id)
	{
	   	$mySet = $this->getSession('mySet');
		$this->category_id = $id;
	}
  
	public function renderEditCategory()
	{
		if ($this->category_id>0)
	    {
            $this->template->editItem =  $this->BlogCategoriesManager->find($this->category_id);
            $def_data = $this->BlogCategoriesManager->find($this->category_id);
			$this['editCategory']->setDefaults($def_data);
	    }else{            
			$this->template->editItem = array();
		}
	}		

	protected function createComponentEditCategory($name)
	{	
		$form = new Form($this, $name);
		$form->addText('name', 'Název kategorie:', 100, 100)
			->setAttribute('placeholder','Název kategorie')				
			->setAttribute('class', 'form-control');
		$form->addText('description', 'Popis:', 100, 100)
			->setAttribute('placeholder','Popis')
			->setAttribute('class', 'form-control');	    
		$form->addText('keywords', 'Klíčová slova:', 50, 50)
			->setAttribute('placeholder','Klíčová slova')
			->setAttribute('class', 'form-control');	    		
		$form->addText('class', 'Třída css pro ikonu:', 40, 40)
			->setAttribute('placeholder','Třída css')
			->setAttribute('class', 'form-control');	    	    
	    $form->addCheckbox('not_show', 'Kategorii nezobrazovat');	    
		$form->addText('order_cat', 'Pořadí kategorie:', 100, 100)
			->setAttribute('placeholder','Pořadí kategorie')				
			->setAttribute('class', 'form-control');	    
		$form->addSelect('subcat_id', 'Nadřazená kategorie:',$this->BlogCategoriesManager->findAll()->where('subcat_id IS NULL')->order('order_cat')->fetchPairs('id','name'))
			->setAttribute('class', 'form-control')
			->setPrompt('- Vyberte -');	    	    
		$form->addHidden('id');
		$form->addSubmit('create', 'Uložit')->setAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setAttribute('class','btn btn-default');
		$form->onSuccess[] = array($this,'editCategorySubmitted');
        return $form;
	}
	
	public function editCategorySubmitted(Form $form)
	{
            if ($form['create']->isSubmittedBy())
            {
				$data=$form->values;
				if ($form->values['id']==NULL)
				{
					unset($data['id']);				
					$row=$this->BlogCategoriesManager->insert($data);

				}else{				
					//$data=$form->values;
					$this->BlogCategoriesManager->update($data);
					$row['id']=$form->values['id'];
				}

		    $this->flashMessage('Položka uložena', 'success');
            }
	    $this->redirect('AdminCategories:');
	}	
	
	
	public function handleEraseCategory($id)
	{
	    try{
			$this->BlogCategoriesManager->delete($id);

	    }
	    catch (Exception $e) {
		    if ($e->errorinfo[1]=1451)
		    {
				$errorMess = 'Není možné vymazat kategorii, je aktuálně použitá.'; 
		    } else {
				$errorMess = $e->getMessage(); 
			}
		    $this->flashMessage($errorMess,'danger');
	    }	    	    
	    $this->redirect('AdminCategories:');			    
	}






}
