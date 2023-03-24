<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Users presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminUsersPresenter extends SecuredPresenter
{
	private $user_id,$users;

    /** @persistent */
    public $type = 'license';


    /**
	* @inject
	* @var \App\Model\BlogCategoriesManager
	*/
	public $BlogCategoriesManager;      
        
	/**
	* @inject
	* @var \App\Model\ArraysManager
	*/
	public $ArraysManager;            
	
  
	public function actionDefault($type = 'license')
	{
        if ($type == 'license') {
            $this->users = $this->userManager->getAll()
                ->select('cl_users.*, cl_company.name, COUNT(:cl_users_license.id) AS count_lic')
                ->where('cl_company:cl_access_company.admin=1 AND erased = 0 AND :cl_users_license.license_end IS NOT NULL AND license_end > DATE_SUB(NOW(), INTERVAL 3 MONTH)')
                ->group('cl_users.id')
                ->order('MAX(:cl_users_license.license_end) ASC, last_login DESC, cl_company.name');
            // AND last_login > DATE_SUB(NOW(), INTERVAL 1 MONTH)
        }elseif ($type == 'demo') {
            $this->users = $this->userManager->getAll()
                ->select('cl_users.*, cl_company.name, COUNT(:cl_users_license.id) AS count_lic')
                ->where('cl_company:cl_access_company.admin=1 AND erased = 0 AND (:cl_users_license.license_end IS NULL OR NOT EXISTS(SELECT id FROM cl_users_license  WHERE cl_company_id = cl_company.id))')
                ->group('cl_users.id')
                ->order('MAX(:cl_users_license.license_end) ASC, last_login DESC, cl_company.name');
        }
				
	}
	
	public function renderDefault()
	{
		$this->template->users = $this->users;
	}	
	
	public function actioneditUser($id)
	{
	   	$mySet = $this->getSession('mySet');
		$this->user_id = $id;
	}
  
	public function renderEditUser()
	{
            if ($this->user_id>0)
	    {
                $this->template->editItem =  $this->userManager->getUserById($this->user_id);
                $def_data = $this->userManager->getUserById($this->user_id);
                $def_dataNew = $def_data->toArray();
		if (!is_null($def_data['created']))
		{
		    $def_dataNew['created'] = $def_data['created']->format('d.m.Y H:i');                
		}
		if (!is_null($def_data['last_login']))
		{
		    $def_dataNew['last_login'] = $def_data['last_login']->format('d.m.Y H:i');                                
		}
                $this['editUser']->setDefaults($def_dataNew);
	    }else{            
                $this->template->editItem = array();
            }
	}		

	protected function createComponentEditUser($name)
	{	
		$form = new Form($this, $name);
                $form->addHidden('id');                
		$form->addText('name', 'Jméno a příjmení:', 100, 100)
			->setAttribute('placeholder','Jméno a příjmení')				
			->setAttribute('class', 'form-control');
		$form->addText('nick_name', 'Přezdívka:', 100, 100)
			->setAttribute('placeholder','Přezdívka')
			->setAttribute('class', 'form-control');	    
		$form->addText('created', 'Vytvořeno:', 100, 100)
			->setAttribute('placeholder','Vytvořeno')
			->setAttribute('class', 'form-control');	    

                $form->addText('email', 'Email:', 50, 50)
                        ->setRequired('Email musí být zadán')
			->setAttribute('placeholder','Email')
			->setAttribute('class', 'form-control');	    		
                $form->addSelect('role', 'Role:',array('user' => 'user','admin' => 'admin','' => ''))
			->setAttribute('placeholder','Role')
			->setAttribute('class', 'form-control');	    	    
                $form->addCheckbox('event_manager', 'Správce helpdesku');	    
                $form->addCheckbox('store_manager', 'Správce skladu');	                    
                $form->addCheckbox('erased', 'Účet zablokován');	                                    
		$form->addText('work_rate', 'Hodinová sazba:')
			->setAttribute('placeholder','Hodinová sazba')				
			->setAttribute('class', 'form-control');	    
                $form->addTextArea('kdb_expand','Všeználek rozbalené záznamy',60,5)
                        ->setAttribute('class', 'form-control');	    
                $form->addTextArea('homepage_boxes','Nastavení domovské stránky',60,5)
                        ->setAttribute('class', 'form-control');	    
                $form->addPassword('password','Heslo:')
                                ->setAttribute('class','form-control')
                                ->setAttribute('autocomplete', 'off')
                                ->setAttribute('placeholder','Heslo')
                                ->setRequired(FALSE)
                                ->addConditionOn($form['id'], Form::EQUAL, 0)
                                    ->setRequired('Pro nového uživatele musí být heslo vyplněno')
                                ->addCondition($form::FILLED)
                                ->addRule($form::MIN_LENGTH, 'Heslo je příliš krátké. Musí mít alespoň %d znaků.', 5)		
                                ->addRule($form::PATTERN, 'Heslo je příliš jednoduché. Musí obsahovat číslici.', '.*[0-9].*')
                                ->addRule($form::PATTERN, 'Heslo je příliš jednoduché. Musí obsahovat malé písmeno.', '.*[a-z].*');
                $form->addPassword('password2')
                                ->setAttribute('class','form-control')
                                ->setAttribute('autocomplete', 'off')
                                ->setAttribute('placeholder','Heslo znovu')
                                ->addConditionOn($form['password'],$form::FILLED)
                                ->addRule($form::EQUAL, 'Hesla se neshodují.',$form['password'])
                                                ->setRequired('Prosím zadejte znovu své heslo.');	

		$form->addText('count_login', 'Počet přihlášení:')
			->setAttribute('placeholder','Počet přihlášení')				
                        ->setAttribute('readonly')                        
			->setAttribute('class', 'form-control');	    

		$form->addText('last_login', 'Poslední přihlášení:')
			->setAttribute('placeholder','Poslední přihlášení')
                        ->setAttribute('readonly')
			->setAttribute('class', 'form-control');	    
                
                

		$form->addSubmit('create', 'Uložit')->setAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setAttribute('class','btn btn-default')
                        		    ->setValidationScope([]);
		$form->onSuccess[] = array($this,'editUserSubmitted');
        return $form;
	}
	
	public function editUserSubmitted(Form $form)
	{
            if ($form['create']->isSubmittedBy())
            {
                $data=$form->values;
                unset($data['last_login']);
                
                if ($data['created'] == "")
                    $data['created'] = NULL;
                else
                    $data['created'] = date('Y-m-d H:i:s',strtotime($data['created'])); 	

                        
                if (empty($data['password']))
                {
                    unset($data['password']);                                                    
                }
                unset($data['password2']);                                        
                unset($data['count_login']);
                unset($data['last_login']);
                if ($form->values['id']==NULL)
                {
                        unset($data['id']);				
                        $row=$this->userManager->addRegistration($data);

                }else{				
                        //$data=$form->values;
                        $this->userManager->updateUser($data);
                        $row['id']=$form->values['id'];
                }

                $this->flashMessage('Položka uložena', 'success');
            }
	    $this->redirect('AdminUsers:');
	}	
	
	
	public function handleEraseUser($id)
	{
	    try{
                $this->userManager->deleteUser($id);

	    }
	    catch (Exception $e) {
		    if ($e->errorinfo[1]=1451)
		    {
				$errorMess = 'Není možné vymazat uživatele, je použitý jinde.'; 
		    } else {
				$errorMess = $e->getMessage(); 
			}
		    $this->flashMessage($errorMess,'danger');
	    }	    	    
	    $this->redirect('AdminUsers:');
	}






}
