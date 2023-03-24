<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * InComplaintUsers management.
 */
class InComplaintUsersManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_complaint_users';

    /** @var App\Model\InComplaintUsersManager */
    public $InComplaintManager;

    /** @var App\Model\CompaniesManager */
    public $CompaniesManager;

    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                InvoiceTypesManager $InvoiceTypesManager, InComplaintManager $inComplaintManager,
                                CompaniesManager $CompaniesManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->InComplaintManager     = $inComplaintManager;
        $this->InvoiceTypesManager    = $InvoiceTypesManager;
        $this->CompaniesManager       = $CompaniesManager;
        $this->settings               = $CompaniesManager->getTable()->fetch();

    }

    public function addUsers($id)
    {
        $tmpData = $this->InComplaintManager->findAll()->where('id = ?', $id)->fetch();
        if ($tmpData){
            $strUsersList = $tmpData->cl_invoice_types['users_list'];
            foreach(json_decode($strUsersList, true) as $key => $one){
                $itemOrder = $this->findAll()->where('in_complaint_id = ?', $id)->max('item_order');
                $arrData = ['user_from_type' => 1,
                            'in_complaint_id' => $id,
                            'item_order' => $itemOrder + 1,
                            'cl_users_id' => $one];
                $rowData = $this->insert($arrData);
                $this->makeFinalEmail($rowData['id']);
            }

        }

    }

    public function validateEmail($eml)
    {
        if (!filter_var($eml, FILTER_VALIDATE_EMAIL)) {
            $eml = "";
        }
        return $eml;
    }

    public function makeFinalEmail($dataId)
    {
        $tmpData = $this->find($dataId);
        //bdump($tmpData);
        if ($tmpData && $tmpData['final_email'] == '') {
            if (isset($tmpData->cl_users['email']) && $tmpData->cl_users['email'] != '' && $this->checkEmailEnabled($tmpData->cl_users['email']) && ($this->validateEmail($tmpData->cl_users['email']) != ''))
                $email = $tmpData->cl_users['email'];
            elseif (isset($tmpData->cl_users['email2']) && $tmpData->cl_users['email2'] != '' && !$this->checkEmailEnabled($tmpData->cl_users['email2']) && ($this->validateEmail($tmpData->cl_users['email2']) != ''))
                $email = $tmpData->cl_users['email2'];
            else
                $email = '';

            $tmpData->update(['final_email' => $email]);
        }
    }

    public function checkEmailEnabled($email)
    {
        $arrTmp = str_getcsv($this->settings['enabled_observers_emails']);
        $emailEnabled = false;
        if (count($arrTmp) > 0) {
            foreach ($arrTmp as $key => $one) {
                $testDomain = str_getcsv($email, '@');
                if (count($testDomain) == 2)
                    $domain = str_contains($one, $testDomain[1]);
                else
                    $domain = false;

                $emailEnabled = $emailEnabled || (str_contains($email, $one)  && $domain);
            }
        }else{
            $emailEnabled = true;
        }
        return $emailEnabled;
    }

    public function removeUsers($id)
    {
        $tmpData = $this->findAll()->where('in_complaint_id = ? AND user_from_type = 1', $id);
        foreach($tmpData as $key => $one){
            $one->delete();
        }
    }


}

