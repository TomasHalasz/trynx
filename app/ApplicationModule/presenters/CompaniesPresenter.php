<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class CompaniesPresenter extends \App\Presenters\BaseAppPresenter {

    /** @persistent */
    public $modal;   
    
    public $reload;
    
    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\RegCompaniesManager
     */
    public $RegCompaniesManager;

    /**
    * @inject
    * @var \App\Model\CompaniesAccessManager
    */
    public $CompaniesAccessManager;        

    /**
    * @inject
    * @var \App\Model\UsersManager
    */
    public $UsersManager;    	
	
    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;            
    

    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.Companies']);
    }	
    
    public function renderDefault($modal) {
		$this->modal = $modal;
		$this->template->modal = $modal;
		$this->template->companies = $this->UserManager->getUserCompanies($this->user->getId());
		$this->template->users = $this->UserManager->getUserById($this->user->getId());
		$this->template->reload = $this->reload;
    }
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
            $form->addText('name', $this->translator->translate('Založit_novou_firmu'), 40, 40)
                    ->setRequired($this->translator->translate('Zadejte_prosím_název_firmy'))
                    ->setHtmlAttribute('class','form-control input-sm')		
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Název_firmy'));
            $form->addSubmit('send', $this->translator->translate('Vytvořit'))->setHtmlAttribute('class','btn btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');
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
                if ($this->CompaniesManager->companyAdmin($this->user->getIdentity()->cl_company_id))
                {
                    $tmpUser = $this->UsersManager->find($this->getUser()->id);
                    if (!is_null($tmpUser->cl_users_license_id))
                    {
                        $tmpLicense = $this->CompaniesManager->findAll()->where('cl_company.cl_users_license_id = ?', $tmpUser->cl_users_license_id)->count();
                    }else
                    {
                        $tmpLicense = 1;
                    }

                    $data['cl_users_license_id'] = $tmpUser->cl_users_license_id;

                    $row = $this->CompaniesManager->insert($data);
                    $this->RegCompaniesManager->createDefaultData($row->id);

                    $result = TRUE;
                    $this->flashMessage($this->translator->translate('Firma_byla_vytvořena'), 'success');


                }else{
                    $result = FALSE;
                    $this->flashMessage($this->translator->translate('Novou_firmu_může_založit_pouze_administrátor'), 'danger' );
                    
                }
        }
        $this->redirect('default',$this->modal);		

    }	    
    
    public function handleDelete($id)
    {
        $userId = $this->user->getId();
        //dump($this->CompaniesAccessManager->findAllTotal()->where('id',$id)->fetch()->cl_company_id);
        //dump($this->UserManager->getUserById($userId)->cl_company_id);
        //die;

        if ($this->CompaniesAccessManager->findAllTotal()->where('id',$id)->fetch()->cl_company_id == $this->UserManager->getUserById($userId)->cl_company_id)
        {
            $this->flashMessage($this->translator->translate('Není_možné_vymazat_aktivní_firmu'), 'warning');
            $this->redirect('default',$this->modal);
        }else{
            $this->CompaniesAccessManager->deleteByUser($id,$userId);
            $this->flashMessage($this->translator->translate('Firma_byla_vymazána'), 'success');
            $this->redirect('default',$this->modal);
        }
    }
    
    public function handleSwitchCompany($id)
    {
        //05.01.2020 - save company roles

        $arrRoles = json_decode($this->user->getIdentity()->company_roles,true);
        //bdump($arrRoles,'arrRoles');
        if (!is_null($arrRoles) && array_key_exists($id,$arrRoles)){
            $newRole = $arrRoles[$id];
        }else{
            $newRole = NULL;
        }
        //bdump($this->user->getIdentity());
        //bdump($this->user->getIdentity()->cl_company_id, 'cl_company_id');
        //bdump($this->user->getIdentity()->cl_users_role_id, 'cl_users_role_id');
        $arrRoles[$this->user->getIdentity()->cl_company_id] = $this->user->getIdentity()->cl_users_role_id;


		$this->user->identity->cl_company_id        = $id;
		$result = $this->UserManager->CompanyBranchCheck($this->user->getId(), $id);
        $this->user->identity->company_branches     = $result['tmpJson'];
        $this->user->identity->cl_company_branch_id = $result['activeBranchId'];
        $this->user->identity->cl_users_role_id     = $newRole;
        $this->user->identity->company_roles        = json_encode($arrRoles);

		$this->UserManager->updateUser(array('id' => $this->user->getId(),
                                            'cl_company_id' => $id,
                                            'company_branches' => $result['tmpJson'],
                                            'cl_company_branch_id' => $result['activeBranchId'],
                                            'cl_users_role_id' => $newRole,
                                            'company_roles' => json_encode($arrRoles)));



		$this->reload = TRUE;
		$this->redrawControl('modalCompanyHide');
        //30.09.2022 - check needed lists if there are records which are necessery
        $this->RegCompaniesManager->checkNeededData($id);

		$this->flashMessage($this->translator->translate('Firma_byla_změněna'), 'success');
    }




}
