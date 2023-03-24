<?php

namespace App\B2BModule\Presenters;

use Nette\Application\UI\Form;

class LostPasswordPresenter extends \App\Presenters\BasePresenter {

    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;

    /**
     * @inject
     * @var \App\Model\PartnersBookWorkersManager
     */
    public $PartnersBookWorkersManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.LostPassword']);
    }


    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentLostPasswordForm() {
        $form = new Form;
        //$form->setTranslator($this->translator);
        $form->addText('email')
		->setHtmlAttribute('class','form-control')
		->setHtmlAttribute('placeholder','Email')
                ->setRequired($this->translator->translate('Zadejte_svůj_email'))
                ->addRule(Form::EMAIL, 'Prosím_zadejte_svůj_platný_email');

        $form->addSubmit('submit', $this->translator->translate('Požádat_o_nové_heslo'))
		->setHtmlAttribute('class','form-control btn-primary');

        $form->onSuccess[] = array($this, "lostPasswordFormSubmitted");
        return $form;
    }

    /**
     * Sign in form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function lostPasswordFormSubmitted($form) {
	    $values = $form->getValues();

	    //nejdriv overit jestli uzivatel emailem existuje
	    //pokud neexistuje musime vratit 
	    $wasException = false;
	    try {
            $lPuser = $this->PartnersBookWorkersManager->findAllTotal()->where('worker_email = ?',$values['email'])->fetch();
            if ($lPuser)
            {
                //posleme email s linkem na zmenu hesla
                $confirm = $this->UserManager->genConfirmationB2B($lPuser->id);

                $confirmUrl = $this->link(':B2B:LostPassword:NewPassword',$confirm['urlKey'],$values->email );
                //dump(APP_DIR);

                //send confirmation email
                $this->emailService->sendNewPasswordEmail($values->email, $confirmUrl);

                $this->flashMessage($this->translator->translate('Zkontrolujte_svou_schránku_email_s_odkazem_pro_obnovu_hesla_byl_odeslán'), 'success');
                $this->redirect(':B2B:Homepage:default',array('email' => $values->email) );
            }else
            {
                $this->flashMessage($this->translator->translate('Email_nebyl_nalezen_Zadejte_svůj_správný_email_nebo_se_znovu_zaregistrujte'), 'danger' );
                $this->redirect(':B2B:LostPassword:default',array('email' => $values->email));
            }



	    } catch (Exception $e) {
    		$this->flashMessage($e->getMessage(), 'warning');
		    
	    }



    }

    public function renderDefault() {
        $this->template->anyVariable = 'any value';
    }
    
    public function renderNewPassword($key,$email)
    {
        //dump($key);
        //dump($email);
        //ověříme platnost klíče
        $expDate = new \Nette\Utils\DateTime;
        if ($this->PartnersBookWorkersManager->findAllTotal()->where('confirm_exp >= ? AND confirm_key = ? AND worker_email = ?', $expDate,$key,$email)->fetch())
            $this['newPasswordForm']->setValues(array('key' => $key, 'email' => $email));
        else
        {
            $this->flashMessage($this->translator->translate('Odkaz_pro_změnu_hesla_již_není_platný._Nechte_si_poslat_nový.'),'danger');
            $this->redirect(':B2B:LostPassword:default');
        }
    }
    
    
/**
     * NewPassword form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentNewPasswordForm($name)
    {
        $form = new Form($this, $name);
        //$form->setTranslator($this->translator);
        $form->addHidden('email');
        $form->addHidden('key');
        $form->addPassword('password')
			->setHtmlAttribute('class','form-control')
			->setHtmlAttribute('placeholder',$this->translator->translate('Heslo'))
			->setRequired($this->translator->translate('Prosím_zadejte_své_heslo'))
			->addRule($form::MIN_LENGTH, $this->translator->translate('Heslo_je_příliš_krátké_Musí_mít_alespoň_%d_znaků'), 5)
			->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_číslici'), '.*[0-9].*')
			->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_malé_písmeno'), '.*[a-z].*');
        $form->addPassword('password2')
				->setHtmlAttribute('class','form-control')
				->setHtmlAttribute('placeholder',$this->translator->translate('Heslo_znovu'))
                ->setRequired($this->translator->translate('Prosím_zadejte_znovu_své_heslo'))
				->addRule($form::EQUAL, $this->translator->translate('Hesla_se_neshodují'),$form['password']);

        $form->addSubmit('submit', $this->translator->translate('Změnit_heslo'))
			->setHtmlAttribute('class','form-control btn-primary');

        $form->onSuccess[] = array($this, "newPasswordFormSubmitted");
        return $form;
    }    
    
	public function newPasswordFormSubmitted($form)
	{
	    try{
		if ($form['submit']->submittedBy)
		    {
			$values = $form->getValues();		    
			$retVal = $this->UserManager->changePassB2B($values);
		    }

	    if ($retVal == 1)
	    {
            $this->flashMessage($this->translator->translate('Heslo_bylo_změněno'),'success');
            $this->redirect(':B2B:Homepage:default',array('email' => $values->email));
	    }
	    else{
            $this->flashMessage($this->translator->translate('Heslo_nebylo_změněno_Odkaz_pro_změnu_hesla_má_platnost_3_hodiny_Nechte_si_poslat_nový'),'danger');
            $this->redirect(':B2B:LostPassword:default',array('email' => $values->email));
	    }


	    }
	    catch (Exception $e) {
		    $errorMess = $e->getMessage(); 
		    $this->flashMessage($errorMess,'Error');		    		    

	    }	   
	    

	}	    

}
