<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * RatesVat management.
 */
class RatesVatManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_rates_vat';

    /** @var App\Model\Base */
    public $CompaniesManager;

    public $settings;

    public $session;

    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                CompaniesManager $CompaniesManager, \Nette\Http\Session $session)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->CompaniesManager = $CompaniesManager;
        $this->session  = $session;
    }

    /**
     * Vrací všechny sazby, kterou jsou aktuálně platné
     * @return \Nette\Database\Table\Selection
     */
    public function findAllValid($today = NULL, $cl_countries_id = NULL) {
        $mySection = $this->session->getSection('company');
        if (is_null($mySection['cl_company_id'])) {
            $company_id = $this->userManager->getCompany($this->user->getId());
            $this->settings = $this->CompaniesManager->getTable()->fetch();
        }else{
            $company_id = $mySection['cl_company_id'];
            $this->settings = $this->CompaniesManager->findAllTotal()->where(['id' => $company_id])->fetch();
        }


	    if (is_null($today)) {
            $today = new \Nette\Utils\DateTime;
        }

        if (is_null($cl_countries_id))
        {
            if (is_null($this->settings->cl_countries_id))
                $cl_countries_id = 1;
            else
                $cl_countries_id = $this->settings->cl_countries_id;
        }

//        if ($this->settings['platce_dph'] == 1){
            $retRow = $this->findAll()->where('(valid_from <= ? OR ISNULL(valid_from)) AND cl_countries_id = ?', $today, $cl_countries_id)->order('rates DESC,valid_from DESC');
//        }else{
//            $retRow = $this->findAll()->where('(valid_from <= ? OR ISNULL(valid_from)) AND cl_countries_id = ? AND rates = ?', $today, $cl_countries_id, 0)->order('rates DESC,valid_from DESC');
//        }
	    return $retRow;
    }
    	


	
}

