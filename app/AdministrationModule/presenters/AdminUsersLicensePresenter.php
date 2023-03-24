<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Users License presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminUsersLicensePresenter extends SecuredPresenter
{
	public $license,$license_id;


        /** @persistent */
        public $id;
	
        
	/**
	* @inject
	* @var \App\Model\UsersLicenseManager
	*/
	public $UsersLicenseManager;        
	
	/**
	* @inject
	* @var \App\Model\ArraysManager
	*/
	public $ArraysManager;        

        
	/**
	* @inject
	* @var \App\Model\CompaniesManager
	*/
        public $CompaniesManager;                
        
	public function actionDefault($id)
	{
            $this->id = $id;
				
	}
	
	public function renderDefault()
	{
            $this->template->license = $this->UsersLicenseManager->findAllTotal()->where('cl_users_id = ?',$this->id)->order('license_end DESC');
            $this->template->users = $this->userManager->getUserById($this->id);
	}	
	
        
        public function handleEraseLicense($license_id,$id)
        {

            $this->UsersLicenseManager->findAllTotal()->where('id = ?', $license_id)->delete();
            $this->flashMessage('Záznam byl vymazán');
            $this->redirect('default', $this->id);
                  
        }
	
        
	public function renderEditLicense($license_id,$id)
	{
	        $this->template->arrModules = $this->ArraysManager->getModules2P();
            $this->license_id = $license_id;            
            $this->id = $id;            
            if ($this->license_id>0)
	        {
                $def_data = $this->UsersLicenseManager->findAllTotal()->where('id = ?', $this->license_id)->limit(1)->fetch();
                $def_dataNew = $def_data->toArray();
                if ($def_dataNew['license_start'] != NULL)	    
                        $def_dataNew['license_start'] = $def_dataNew['license_start']->format('d.m.Y');	                 
                if ($def_dataNew['license_end'] != NULL)	    
                        $def_dataNew['license_end'] = $def_dataNew['license_end']->format('d.m.Y');
                if ($def_dataNew['support_end'] != NULL)
                    $def_dataNew['support_end'] = $def_dataNew['support_end']->format('d.m.Y');

                $this['editLicense']->setDefaults($def_dataNew);

                $arrDefMod = [];
                $arrDefModData = json_decode($def_data['license'], TRUE);
                foreach($this->ArraysManager->getModules2P() as $key => $one){
                    if (isset($arrDefModData[$key]['quant']) || isset($arrDefModData[$key]['exp'])){
                        $key2 = str_replace(':', '_', $key);
                        if (isset($arrDefModData[$key]['quant'])) {
                            $arrDefMod['quant_' . $key2] = $arrDefModData[$key]['quant'];
                        }
                        if (isset($arrDefModData[$key]['users'])) {
                            $arrDefMod['users_' . $key2] = json_encode($arrDefModData[$key]['users']);
                        }
                        if (isset($arrDefModData[$key]['exp'])) {
                            if (!is_null($arrDefModData[$key]['exp']))
                                $arrDefMod['exp_' . $key2] = date('d.m.Y',strtotime($arrDefModData[$key]['exp']));
                                    //$arrDefModData[$key]['exp']->format('d.m.Y');
                        }
                    }
                }
                $this['editLicense']->setDefaults($arrDefMod);
	        }else{
                //$this->template->editItem = new Nette\ArrayHash;
                $this['editLicense']->setDefaults(array('cl_users_id' => $id));
            }
	}	
        
	public function actionEditLicense($license_id)
	{
		$this->license_id = $license_id;
	}        

	protected function createComponentEditLicense($name)
	{	
		$form = new Form($this, $name);
        $form->addHidden('id');
        $form->addHidden('cl_users_id');
        $form->addText('license_start', 'Start licence:')
			->setHtmlAttribute('placeholder','Start')
			->setHtmlAttribute('class', 'form-control');
        $form->addText('license_end', 'Konec licence:')
			->setHtmlAttribute('placeholder','Konec')
			->setHtmlAttribute('class', 'form-control');
        $form->addText('support_end', 'Konec podpory:')
            ->setHtmlAttribute('placeholder','Konec podpory')
            ->setHtmlAttribute('class', 'form-control');
        $form->addText('amount', 'Celkem bez DPH:')
			->setHtmlAttribute('placeholder','bez DPH')
			->setHtmlAttribute('class', 'form-control');
        $form->addText('amount_total', 'Celkem s DPH:')
			->setHtmlAttribute('placeholder','s DPH')
			->setHtmlAttribute('class', 'form-control');
        $form->addSelect('payment_type', 'Platba:',$this->ArraysManager->getPaymentType())
			->setHtmlAttribute('placeholder','Platba')
			->setHtmlAttribute('class', 'form-control')
            ->setRequired('Typ platby musí být vybrán');

		$form->addText('discount', 'Sleva:')
			->setHtmlAttribute('placeholder','Sleva')
			->setHtmlAttribute('class', 'form-control');

		$form->addText('currency', 'Měna:')
			->setHtmlAttribute('placeholder','Měna')
			->setHtmlAttribute('class', 'form-control');

		$form->addText('total_duration', 'Počet měsíců:')
			->setHtmlAttribute('placeholder','Počet měsíců')
            ->setHtmlAttribute('readonly')
			->setHtmlAttribute('class', 'form-control');

        $form->addText('total_users', 'Počet uživatelů:')
			->setHtmlAttribute('placeholder','Počet uživatelů')
			->setHtmlAttribute('class', 'form-control');
                
        $form->addText('v_symb', 'Var. symbol:')
			->setHtmlAttribute('placeholder','Var. symbol')
			->setHtmlAttribute('class', 'form-control');
                
        $form->addText('gopay_id', 'GOPAY ID:')
			->setHtmlAttribute('placeholder','Gopay id')
            ->setHtmlAttribute('readonly')
			->setHtmlAttribute('class', 'form-control');
		
        $form->addTextArea('addons', 'Doplňky:')
			->setHtmlAttribute('placeholder','doplňky v json {"nazev doplňku":"hodnota"}')
			->setHtmlAttribute('class', 'form-control');

        $form->addSelect('status', 'Stav objednávky:', array('PAID' => 'zaplaceno'))
            ->setPrompt('čeká na platbu')
			->setHtmlAttribute('placeholder','Stav')
			->setHtmlAttribute('class', 'form-control');

        $form->addSelect('cl_company_id', 'Firma:', $this->CompaniesManager->findAllTotal()
            ->where('cl_company.id = :cl_access_company.cl_company_id AND :cl_access_company.cl_users_id = ?',$this->id)->fetchPairs('id','name'))
			->setHtmlAttribute('placeholder','Firma')
			->setHtmlAttribute('class', 'form-control');

            $modules = $this->ArraysManager->getModules2P();
            foreach($modules as $key => $one){
                $key2 = str_replace(':', '_', $key);
                $form->addText('quant_'.$key2, $one , 3,3)
                        ->setHtmlAttribute('placeholder', 'počet uživatelů')
                        ->setHtmlAttribute('class', 'form-control');
                $form->addText('users_'.$key2, $one . ' - Id uživatelů', 20,100)
                        ->setHtmlAttribute('placeholder', 'přiřazení uživatelé')
                        ->setHtmlAttribute('class', 'form-control');
                $form->addText('exp_'.$key2, $one . ' - expirace', 10,10)
                        ->setHtmlAttribute('placeholder', 'datum expirace')
                        ->setHtmlAttribute('class', 'form-control datepicker');
            }
                

		$form->addSubmit('create', 'Uložit')->setHtmlAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setHtmlAttribute('class','btn btn-default')
                        		    ->setValidationScope([]);
		$form->onSuccess[] = array($this,'editLicenseSubmitted');
                
                return $form;
	}
	
	public function editLicenseSubmitted(Form $form)
	{
            if ($form['create']->isSubmittedBy())
            {
                $data=$form->values;

                //$modules = $this->ArraysManager->getModules2P();
                $arrModData = [];
                //dump($data);
                //die;

                foreach($data as $key => $one) {
                    if (!(strpos($key, 'quant_') === false && strpos($key, 'exp_') === false && strpos($key, 'users_') === false) && $key != 'cl_users_id'){
                        if (!empty($one)) {
                            $key2 = str_replace('quant_', '', $key);
                            $key2 = str_replace('exp_', '', $key2);
                            $key2 = str_replace('users_', '', $key2);
                            $key2 = str_replace('_', ':', $key2);
                            if (!(strpos($key, 'quant_') === false)) {
                                $arrModData[$key2]['quant'] = $one;
                            } elseif (!(strpos($key, 'exp_') === false)) {
                                $arrModData[$key2]['exp'] = date('Y-m-d',strtotime($one));
                            } elseif (strpos($key, 'users_') == 0) {
                                $arrModData[$key2]['users'] = json_decode($one, true);
                            }
                        }
                        unset($data[$key]);
                    }
                }
                //dump($arrModData);
                //die;
                $data['license'] = json_encode($arrModData);
                $data['license_start'] = date('Y-m-d',strtotime($data['license_start'])); 	
                $data['license_end'] = date('Y-m-d',strtotime($data['license_end']));
                $data['support_end'] = date('Y-m-d',strtotime($data['support_end']));

                $date1 = date_create($data['license_start']);
                $date2 = date_create($data['license_end']);                    
                $diff=  date_diff($date2,$date1);
                $months = $diff->m + ($diff->y * 12);
                $data['total_duration'] = $months;

                if ($form->values['id']==NULL)
                {
                        unset($data['id']);				
                        $row=$this->UsersLicenseManager->insertForeign($data);

                }else{				
                        //$data=$form->values;
                        $this->UsersLicenseManager->updateForeign($data);
                        $row['id']=$form->values['id'];
                }

                $this->flashMessage('Položka uložena', 'success');
            }
	    $this->redirect('AdminUsersLicense:default');
	}	
	        
	




}
