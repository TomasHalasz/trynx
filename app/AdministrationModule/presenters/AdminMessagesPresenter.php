<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Message presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminMessagesPresenter extends SecuredPresenter
{

	public $id;
	private $messages;
	
	/**
	* @inject
	* @var \App\Model\BlogArticlesManager
	*/
	public $BlogArticlesManager;
	
	/**
	 * @inject
	 * @var \App\Model\MessagesManager
	 */
	public $MessagesManager;
	
	/**
	 * @inject
	 * @var \App\Model\MessagesMainManager
	 */
	public $MessagesMainManager;
	
	/**
	* @inject
	* @var \App\Model\UserManager
	*/
	public $UserManager;
	
  
	public function actionDefault()
	{
		$this->messages = $this->MessagesMainManager->findAll()->order('created DESC');
				
	}
	
	public function renderDefault()
	{
		$this->template->messages = $this->messages;
	}	
	

	public function renderEditMessage($id)
	{
		$this->id = $id;

		if ($this->id > 0)
	    {
            $this->template->editItem =  $this->MessagesMainManager->find($this->id);
            $def_data =  $this->MessagesMainManager->find($this->id);

		}else{
			$this->template->editItem = NULL;
			$def_data = array();
			$def_data['created'] = new Nette\Utils\DateTime();

		}
		$this['editMessage']->setDefaults($def_data);
		$this['editMessage']['created']->setDefaultValue($def_data['created']->format('d.m.Y H:i'));
	}		

	protected function createComponentEditMessage($name)
	{	
		$form = new Form($this, $name);
		$form->addTextArea('message', 'Zpráva:', 50, 10)
			->setHtmlAttribute('placeholder','Zpráva')
			->setHtmlAttribute('class', 'form-control');
		$form->addText('created', 'Datum zprávy:')
			->setHtmlAttribute('placeholder','Datum zprávy')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('class', 'form-control datetimepicker');

		$form->addHidden('id');
		$form->addSubmit('create', 'Uložit')->setHtmlAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setHtmlAttribute('class','btn btn-default');
		$form->onSuccess[] = array($this,'editMessageSubmitted');
        return $form;
	}
	
	public function editMessageSubmitted(Form $form)
	{
            if ($form['create']->isSubmittedBy())
            {
				$data=$form->values;
				$data['created'] = date('Y-m-d H:i',strtotime($data['created']));
				if ($form->values['id']==NULL)
				{
					unset($data['id']);				
					$tmpRow = $this->MessagesMainManager->insert($data);
					$row = $tmpRow['id'];

				}else{				
					//$data=$form->values;
					$this->MessagesMainManager->update($data);
					$row = $form->values['id'];
				}
		    	$this->flashMessage('Zpráva uložena', 'success');
				$this->redirect('AdminMessages:EditMessage', $row);
            }
			$this->redirect('AdminMessages:');
	}	
	
	
	public function handleEraseMessage($id)
	{
	    try{
			$this->MessagesMainManager->delete($id);

	    }
	    catch (Exception $e) {
		    if ($e->errorinfo[1]=1451)
		    {
				$errorMess = 'Není možné vymazat zprávu, je aktuálně použitá.';
		    } else {
				$errorMess = $e->getMessage(); 
			}
		    $this->flashMessage($errorMess,'danger');
	    }	    	    
	    $this->redirect('AdminMessages:');
	}


	public function handleSendMessage($id)
	{
		$users = $this->UserManager->getAll();
		$arrData = array();
		$arrData['cl_messages_main_id'] = $id;
		foreach($users as $key => $one){
		    if (!is_null($one->cl_company_id)) {
                $arrData['cl_company_id'] = $one->cl_company_id;
                $arrData['cl_users_id'] = $one->id;
                $this->MessagesManager->insertForeign($arrData);
            }
		}
		
		$this->flashMessage('Zprávy byly odeslány.','success');
	}




}
