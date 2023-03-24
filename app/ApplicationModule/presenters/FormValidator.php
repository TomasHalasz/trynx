<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class FormValidator
{
    const EMAIL_HELPDESK_UNIQUE = 'FormValidator::validateEmailHelpdeskUnique';
    //const EMAIL_DOMAIN = 'UserFormRules::validateEmailDomain';
	
    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;           
	

    public function validateEmailHelpdeskUnique(Nette\Forms\IControl $control)
    {
        // validace uživatelského jména
		if ($this->CompaniesManager->findAllTotal()->where('email_income = ?', $control->value)->fetch())
			return FALSE;
		else 
			return TRUE;
    }

    //public static function validateEmailDomain(IControl $control, $domain)
    //{
        // validace, zda se jedné o e-mail z domény $domain
    //}
}