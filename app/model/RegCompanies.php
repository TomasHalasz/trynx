<?php

namespace App\Model;

use Nette,
    Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Nette\Utils\Image;
use Tracy\Debugger;

/**
 * RegCompanies management.
 */
class RegCompaniesManager
{
    public $database;
    public $userManager;
    public $user;
    public $companyId = NULL;

    const COLUMN_ID = 'id';
    public $tableName = 'cl_company';


    /** @var ArraysManager */
    public $ArraysManager;

    /** @var \App\Model\UserManager */
    public $UserManager;


    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor, ArraysManager $ArraysManager)
    {
        //$this->database = $db;
        $this->userManager = $userManager;
        $this->user = $user;

        $this->database = $accessor->get('dbCurrent');

        //$database = $accessor->get("db2");
        if ($this->tableName === NULL) {
            $class = get_class($this);
            throw new \Nette\InvalidStateException("Název tabulky musí být definován v $class::\$tableName.");
        }
    }

    public function getDatabase()
    {
        return $this->database;
    }


    /**
     * Vrací table bez filtru na vlastnickou firmu
     * @return type
     */
    public function getTable()
    {

        $mySection = $this->session->getSection('company');
        //dump($mySection['cl_company_id']);
        if (is_null($mySection['cl_company_id'])) {
            if (!is_null($this->user->getId())) {
                $userId = $this->user->getId();
                $companyId = $this->user->getIdentity()->cl_company_id;
//                dump($userId);
//                dump($companyId);
                $ret = $this->database->table($this->tableName)->select('cl_company.*')
                    ->where(':cl_access_company.cl_users_id = ? AND cl_company.id = ?', $userId, $companyId);
            } else {
                $userId = 0;
                $companyId = 0;
                $ret = $this->database->table($this->tableName)->select('cl_company.*')
                    ->where(':cl_access_company.cl_users_id = 0 AND cl_company.id = 0');
            }
        } else {
            $companyId = $mySection['cl_company_id'];
            $ret = $this->database->table($this->tableName)->select('cl_company.*')
                ->where('id = ?', $companyId);
        }
      //  $this->session->close();
        //bdump($userId);
        //bdump($companyId);
        return $ret;
        //AND :cl_users.id = :cl_access_company.cl_users_id
    }

    /**
     * Vrací table bez filtru na vlastnickou firmu
     * @return type
     */
    protected function getTableUpdate()
    {
        return $this->database->table($this->tableName);
    }


    /**
     * Vrací vyfiltrované záznamy na základě vstupního pole
     * (pole array('name' => 'David') se převede na část SQL dotazu WHERE name = 'David')
     * @param array $by
     * @return \Nette\Database\Table\Selection
     */
    public function findByUpdate(array $by)
    {
        return $this->getTableUpdate()->where($by);
    }


    /**
     * Upraví záznam
     * @param array $data
     */
    public function update($data, $mark = FALSE)
    {

        if ($mark) {
            $data['change_by'] = $this->user->getIdentity()->name;
            $data['changed'] = new \Nette\Utils\DateTime;
        }
        if ($this->companyAccess($data['id'])) {
            $this->findByUpdate(array('id' => $data['id']))->update($data);
        } else {
            return false;
        }
    }

    /** return cl_users_id for given company_id
     * @param $cl_company_id
     * @return int
     */
    public function getAdminId($cl_company_id): int
    {
        if ($ret = $this->database->table($this->tableName)->select(':cl_access_company.cl_users_id AS id')
            ->where(':cl_access_company.admin = 1 AND :cl_access_company.cl_company_id = ?', $cl_company_id)
            ->limit(1)->fetch()) {
            $retId = $ret['id'];
        } else {
            $retId = NULL;
        }
        return $retId;
    }

    /**
     * set current moment as end of synchronization
     * @param type $cl_company_id
     */
    public function setSyncEnd($cl_company_id)
    {
        $this->getTableUpdate()->where('id', $cl_company_id)->update(array('sync_last' => new \Nette\Utils\DateTime()));
    }


    /**
     * Vloží nový záznam a vrátí jeho ID
     * @param array $data
     * @return \Nette\Database\Table\ActiveRow
     */
    public function insert($data)
    {
        if ($this->user->isLoggedIn()) {
            $data['create_by'] = $this->user->getIdentity()->name;
        } else {
            $data['create_by'] = 'system';
        }
        $tmpUserId = $data['cl_users_id'];
        unset($data['cl_users_id']);
        $data['created'] = new \Nette\Utils\DateTime;
        $new = $this->getTableUpdate()->insert($data);

        //insert also to cl_access_company
        $data2 = new \Nette\Utils\ArrayHash;
        $data2['cl_company_id'] = $new->id;
        $data2['admin'] = 1;
        $data2['cl_users_id'] = $tmpUserId;
        $this->database->table('cl_access_company')->insert($data2);

        return $new;
    }


    public function delete($id)
    {
        if ($this->companyAccess($id)) {
            $this->getTableUpdate()->where('id', $id)->delete();
        }
    }


    /**
     * returns false if user is not admin in cl_access_company
     * othervise returns active row
     * @param type $cl_company_id
     * @return type
     */
    public function companyAdmin($cl_company_id, $user_id = NULL)
    {
        if (is_null($user_id)) {
            $user_id = $this->user->getId();
        }
        return $this->database->table('cl_access_company')
            ->where('cl_users_id = ? AND cl_company_id = ? AND admin = 1', $user_id, $cl_company_id)
            ->limit(1)->fetch();
    }

    //28.08.2017 - check for needed data
    public function checkNeededData($id)
    {
        //15.02.2023 - move cl_partners_book.account_code etc into cl_partners_account
        $tmpSettings = $this->database->table('cl_company')->where('id = ?', $id)->fetch();
        if ($tmpSettings['partners_account_transfered'] == 0){
            try {
                $this->database->beginTransaction();
                $tmpData = $this->database->table('cl_partners_book')->where('cl_company_id = ? AND account_code != ?', $id, '');
                foreach ($tmpData as $key => $one) {
                    $maxRow = $this->database->table('cl_partners_account')->where('cl_company_id = ? AND cl_partners_book_id = ?', $id, $one['id'])->max('item_order') + 1;
                    $maxRow = is_null($maxRow) ? 0 : $maxRow;
                    $tmpUcet = $this->database->table('cl_partners_account')->where('cl_company_id = ? AND cl_partners_book_id = ? AND account_code = ? AND  bank_code = ?', $id, $one['id'], $one['account_code'], $one['bank_code'])->fetch();
                    if (!$tmpUcet) {
                        $new = $this->database->table('cl_partners_account')->
                                        insert(['cl_partners_book_id' => $one['id'],
                                                    'cl_company_id' => $id,
                                                    'cl_currencies_id' => $tmpSettings['cl_currencies_id'],
                                                    'account_code' => $one['account_code'],
                                                    'bank_code' => $one['bank_code'],
                                                    'iban_code' => $one['iban_code'],
                                                    'swift_code' => $one['swift_code'],
                                                    'spec_symb' => $one['spec_symb'],
                                                    'item_order' => $maxRow]);
                        $accountId = $new['id'];
                    }else{
                        $accountId = $tmpUcet['id'];
                    }
                    $tmpInvoiceArrived = $this->database->table('cl_invoice_arrived')->where('cl_company_id = ? AND cl_partners_book_id = ? AND cl_partners_account_id IS NULL', $id, $one['id']);
                    $tmpInvoiceArrived->update(['cl_partners_account_id' => $accountId]);
                    $tmpInvoice = $this->database->table('cl_invoice')->where('cl_company_id = ? AND cl_partners_book_id = ? AND cl_partners_account_id IS NULL', $id, $one['id']);
                    $tmpInvoice->update(['cl_partners_account_id' => $accountId]);
                }
                $this->database->commit();
                $tmpSettings->update(['partners_account_transfered' => 1]);
            }catch (Exception $e)
            {
                $this->database->rollBack();
                Debugger::log('cl_partners_account -> fill - error: ' . $e, 'NeededData');
            }
        }


        //28.09.2022 - vat rates
        if (!$this->database->table('cl_rates_vat')->where('cl_company_id = ?', $id)->fetch())
        {
            $tmpRates = [];
            $tmpRates['cl_company_id'] = $id;
            $tmpRates['rates'] = 21;
            $tmpRates['code_name'] = 'high';
            $tmpRates['description'] = 'základní sazba';
            $tmpRates['valid_from'] = '2015-01-01';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

            $tmpRates = [];
            $tmpRates['cl_company_id'] = $id;
            $tmpRates['rates'] = 15;
            $tmpRates['code_name'] = 'low';
            $tmpRates['description'] = 'snížená sazba';
            $tmpRates['valid_from'] = '2013-12-31';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

            $tmpRates = [];
            $tmpRates['cl_company_id'] = $id;
            $tmpRates['rates'] = 10;
            $tmpRates['code_name'] = 'third';
            $tmpRates['description'] = 'snížená sazba 2';
            $tmpRates['valid_from'] = '2015-01-01';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

            $tmpRates = [];
            $tmpRates['cl_company_id'] = $id;
            $tmpRates['rates'] = 0;
            $tmpRates['code_name'] = 'none';
            $tmpRates['description'] = 'nulová sazba';
            $tmpRates['valid_from'] = '2015-01-01';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);
        }

        //17.07.2022 - reminder invoice email
        if (!$this->database->table('cl_emailing_text')->where('email_use = ? AND cl_company_id = ?', 'ireminder', $id)->fetch()) {
            $tmpEmailingText = [['cl_company_id' => $id,
                'email_name' => 'Upomínky faktur',
                'email_subject' => 'Nezaplacené faktury po splatnosti',
                'email_body' =>
                    'Dobrý den,<br>
<br>
prosíme o kontrolu nezaplacených faktur. Pokud jste je již uhradili, děkujeme <br>
<br>
[invoices]
<br>
Celková dlužná částka k dnešnímu dni je: [total_sum]
 ',
                'email_use' => 'ireminder']];
            $this->database->table('cl_emailing_text')->insert($tmpEmailingText);
        }

        //17.07.2022 - reminder advance invoice email
        if (!$this->database->table('cl_emailing_text')->where('email_use = ? AND cl_company_id = ?', 'areminder', $id)->fetch()) {
            $tmpEmailingText = [['cl_company_id' => $id,
                'email_name' => 'Upomínky záloh',
                'email_subject' => 'Nezaplacené zálohy po splatnosti',
                'email_body' =>
                    'Dobrý den,<br>
<br>
prosíme o kontrolu nezaplacených záloh. Pokud jste je již uhradili, děkujeme <br>
<br>
[invoices]
<br>
Celková dlužná částka k dnešnímu dni je: [total_sum]
 ',
                'email_use' => 'areminder']];
            $this->database->table('cl_emailing_text')->insert($tmpEmailingText);
        }


        //03.01.2022 - default stores
        $defStorage = $this->database->table('cl_storage')->where('cl_company_id = ?', $id)->limit(1)->fetch();
        $tmpSettings = $this->database->table('cl_company')->where('id = ?', $id)->fetch();
        if ($defStorage && $tmpSettings) {
            $arrUpdate['id'] = $id;
            if (is_null($tmpSettings['cl_storage_id'])) {
                $arrUpdate['cl_storage_id'] = $defStorage['id'];
            }
            if (is_null($tmpSettings['cl_storage_id_sale'])) {
                $arrUpdate['cl_storage_id_sale'] = $defStorage['id'];
            }
            if (is_null($tmpSettings['cl_storage_id_commission'])) {
                $arrUpdate['cl_storage_id_commission'] = $defStorage['id'];
            }
            if (is_null($tmpSettings['cl_storage_id_macro'])) {
                $arrUpdate['cl_storage_id_macro'] = $defStorage['id'];
            }
            if (is_null($tmpSettings['cl_storage_id_back'])) {
                $arrUpdate['cl_storage_id_back'] = $defStorage['id'];
            }
            if (is_null($tmpSettings['cl_storage_id_back_sale'])) {
                $arrUpdate['cl_storage_id_back_sale'] = $defStorage['id'];
            }
            $tmpSettings->update($arrUpdate);
        }


        //08.07.2020 - cl_rates_vat
    /*    if (!$this->database->table('cl_rates_vat')->fetch()) {
            $tmpRates = array();
            $tmpRates['rates'] = 21;
            $tmpRates['code_name'] = 'high';
            $tmpRates['description'] = 'základní sazba';
            $tmpRates['valid_from'] = '2015-01-01';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

            $tmpRates = array();
            $tmpRates['rates'] = 15;
            $tmpRates['code_name'] = 'third';
            $tmpRates['description'] = 'snížená sazba';
            $tmpRates['valid_from'] = '2013-12-31';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

            $tmpRates = array();
            $tmpRates['rates'] = 10;
            $tmpRates['code_name'] = 'low';
            $tmpRates['description'] = 'snížená sazba 2';
            $tmpRates['valid_from'] = '2015-01-01';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

            $tmpRates = array();
            $tmpRates['rates'] = 0;
            $tmpRates['code_name'] = 'none';
            $tmpRates['description'] = 'nulová sazba';
            $tmpRates['valid_from'] = '2015-01-01';
            $tmpRates['cl_countries_id'] = 1;
            $this->database->table('cl_rates_vat')->insert($tmpRates);

        }*/

        //08.07.2020 - cl_countries
        if (!$this->database->table('cl_countries')->fetch()) {
            $this->makeCountries();
        }

        /*03.02.2023 - cl_oro statuses*/
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "oro", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'oro',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_work = 1 AND status_use = ? AND cl_company_id = ?', "oro", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'XML generován',
                's_new' => 0,
                's_work' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'oro',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "oro", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Podáno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'oro',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);
        }
        /*03.02.2023 cl_oro end*/


        /*18.12.2022 - cl_delivery_note_in statuses*/
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "delivery_note_in", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'delivery_note_in',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_storno = 1 AND status_use = ? AND cl_company_id = ?', "delivery_note_in", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Storno',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 1,
                's_eml' => 0,
                'status_use' => 'delivery_note_in',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "delivery_note_in", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Naskladněno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'delivery_note_in',
                'color_hex' => null);
            $this->database->table('cl_status')->insert($tmpData);
        }

        /*14.01.2023 - cl_payment_order statuses*/
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "payment_order", $id)->fetch()) {
            $tmpData = ['cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'payment_order',
                'color_hex' => '#32CD32'];
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_storno = 1 AND status_use = ? AND cl_company_id = ?', "payment_order", $id)->fetch()) {
            $tmpData = ['cl_company_id' => $id,
                'status_name' => 'Storno',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 1,
                's_eml' => 0,
                'status_use' => 'payment_order',
                'color_hex' => '#32CD32'];
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "payment_order", $id)->fetch()) {
            $tmpData = ['cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'payment_order',
                'color_hex' => null];
            $this->database->table('cl_status')->insert($tmpData);
        }

        /*26.11.2022 - number series for cl_payment_order*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'payment_order', $id)->fetch()) {
            $tmpData = [];
            $tmpData = [['cl_company_id' => $id,
                'formula' => 'PP(Z4)-(2R)',
                'form_name' => 'Platební příkaz',
                'form_default' => 1,
                'form_use' => 'payment_order']
            ];
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        /*18.12.2022 - number series for cl_delivery_note_in*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'delivery_note_in', $id)->fetch()) {
            $tmpData = [['cl_company_id' => $id,
                'formula' => 'DLP(Z4)-(2R)',
                'form_name' => 'Dodací list přijatý',
                'form_default' => 1,
                'form_use' => 'delivery_note_in']
            ];
            $this->database->table('cl_number_series')->insert($tmpData);
        }


        /*06.02.2022 - number series for cl_task*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'task', $id)->fetch()) {
            $tmpData = [];
            $tmpData = [['cl_company_id' => $id,
                'formula' => 'T(Z4)-(2R)',
                'form_name' => 'Úkoly',
                'form_default' => 1,
                'form_use' => 'task']
            ];
            $this->database->table('cl_number_series')->insert($tmpData);
        }


        /*26.08.2021 - number series for EAN generator*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'pricelist_ean', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => '(Z12)',
                'form_name' => 'EAN13 kódy pro ceník',
                'form_default' => 1,
                'form_use' => 'pricelist_ean')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }


        /*26.08.2021 - number series for invoice_internal*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_internal', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'ID-(Z4)/(2R)',
                'form_name' => 'Interní doklad',
                'form_default' => 1,
                'form_use' => 'invoice_internal')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        /*05.06.2021 - number series for in_complaint*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'complaint', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'RE-(Z4)/(2R)',
                'form_name' => 'Reklamace',
                'form_default' => 1,
                'form_use' => 'complaint')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        /*05.06.2021 - document types for complaint */
        $clNumberSeriesInternal = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_default = 1', 'invoice_internal', $id)->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesInternal, $id, 8)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesInternal,
                'name' => 'Interní doklad',
                'default_type' => 1,
                'inv_type' => 8));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }

        //26.08.2021 - document types for internal invoice
        $clNumberSeriesComp = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_default = 1', 'complaint', $id)->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesComp, $id, 7)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesComp,
                'name' => 'Dodavatelská',
                'default_type' => 1,
                'inv_type' => 7));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }

        //26.08.2021 - status for cl_invoice_internal
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "invoice_internal", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice_internal',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_eml = 1 AND status_use = ? AND cl_company_id = ?', "invoice_internal", $id)->fetch()) {
            $tmpData = array('cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'invoice_internal',
                'color_hex' => '#32CD32');
            $this->database->table('cl_status')->insert($tmpData);

        }

        //05.06.2021 - status for in_complaint
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "complaint", $id)->fetch()) {
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'complaint',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "complaint", $id)->fetch()) {
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Uzavřeno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'complaint',
                'color_hex' => null));
            $this->database->table('cl_status')->insert($tmpData);
        }


        /*15.03.2021 - number series for intranet instructions*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'instructions', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'PO-(Z4)/(2R)',
                'form_name' => 'Pokyn',
                'form_default' => 1,
                'form_use' => 'instructions')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        /*15.03.2021 - number series for intranet emailing*/
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'emailing', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'EM-(Z4)/(2R)',
                'form_name' => 'Email',
                'form_default' => 1,
                'form_use' => 'emailing')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'rental', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'PU-(Z4)/(2R)',
                'form_name' => 'Půjčovna',
                'form_default' => 1,
                'form_use' => 'rental')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        //19.10.2019 - new number series for invoice_correction, invoice_advance, invoice_arrived_advance, invoice_arrived_correction
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_arrived_correction', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'OP-(Z4)/(2R)',
                'form_name' => 'Opravný doklad přijatý',
                'form_default' => 1,
                'form_use' => 'invoice_arrived_correction')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_arrived_advance', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'ZP-(Z4)/(2R)',
                'form_name' => 'Zálohová faktura přijatá',
                'form_default' => 1,
                'form_use' => 'invoice_arrived_advance')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_default = 1', 'invoice_arrived_correction', $id)->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 2)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Opravný doklad přijatý',
                'default_type' => 1,
                'inv_type' => 2));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_default = 1', 'invoice_arrived_advance', $id)->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 3)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Zálohová faktura přijatá',
                'default_type' => 1,
                'inv_type' => 3));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }

        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_advance', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'Z-(Z4)/(2R)',
                'form_name' => 'Zálohová faktura',
                'form_default' => 1,
                'form_use' => 'invoice_advance')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_correction', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'Z-(Z4)/(2R)',
                'form_name' => 'Opravný doklad',
                'form_default' => 1,
                'form_use' => 'invoice_correction')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_default = 1', 'invoice_correction', $id)->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 2)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Opravný doklad',
                'default_type' => 1,
                'inv_type' => 2));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_default = 1', 'invoice_advance', $id)->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 3)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Zálohová faktura',
                'default_type' => 1,
                'inv_type' => 3));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }


        //23.05.2019 - number series for cl_cash
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'cash_in', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'PP(Z4)/(2R)',
                'form_name' => 'Pokladna příjem',
                'form_default' => 1,
                'form_use' => 'cash_in')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'cash_out', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'PV(Z4)/(2R)',
                'form_name' => 'Pokladna výdej',
                'form_default' => 1,
                'form_use' => 'cash_out')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }

        //23.05.2019 - cash type for cash
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'cash_in', $id, 'Pokladna příjem')->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 5)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Pokladna příjem',
                'default_type' => 1,
                'inv_type' => 5));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'cash_out', $id, 'Pokladna výdej')->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 6)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Pokladna výdej',
                'default_type' => 1,
                'inv_type' => 6));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }

        //31.08.2021 - number series for invoice_tax
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_tax', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'DZ(Z4)/(2R)',
                'form_name' => 'Daňový doklad k zaplacené záloze',
                'form_default' => 1,
                'form_use' => 'invoice_tax')
            );
            $this->database->table('cl_number_series')->insert($tmpData);

        }

        //26.03.2019 - number series for delivery_note
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'delivery_note', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'DL(Z4)/(2R)',
                'form_name' => 'Dodací list',
                'form_default' => 1,
                'form_use' => 'delivery_note')
            );
            $this->database->table('cl_number_series')->insert($tmpData);

        }
        //28.08.2017 - number series for invoice_arrived
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'invoice_arrived', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'F(Z+)',
                'form_name' => 'Faktura přijatá',
                'form_default' => 1,
                'form_use' => 'invoice_arrived')
            );
            $this->database->table('cl_number_series')->insert($tmpData);

        }
        //28.08.2017 - invoice type for invoice_arrived
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'invoice_arrived', $id, 'Faktura přijatá')->fetch()->id;
        if (!$this->database->table('cl_invoice_types')->where('cl_number_series_id = ? AND cl_company_id = ? AND inv_type = ?', $clNumberSeriesIdP, $id, 4)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Faktura přijatá',
                'default_type' => 1,
                'inv_type' => 4));
            $this->database->table('cl_invoice_types')->insert($tmpData);
        }

        //28.08.2017 - status for invoice_arrived
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "invoice_arrived", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'invoice_arrived',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_eml = 1 AND status_use = ? AND cl_company_id = ?', "invoice_arrived", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Email',
                's_eml' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'invoice_arrived',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "invoice_arrived", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'invoice_arrived',
                'color_hex' => NULL));
            $this->database->table('cl_status')->insert($tmpData);
        }
        //26.03.2019 - status for delivery_note
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "delivery_note", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'delivery_note',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_eml = 1 AND status_use = ? AND cl_company_id = ?', "delivery_note", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Email',
                's_eml' => 1,
                'status_use' => 'delivery_note',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "delivery_note", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Dodáno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'delivery_note',
                'color_hex' => NULL));
            $this->database->table('cl_status')->insert($tmpData);
        }

        //17.03.2021 - cl_status for in_estate
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "estate", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'estate',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_storno = 1 AND status_use = ? AND cl_company_id = ?', "estate", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Vyřazeno',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 1,
                'status_use' => 'estate',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "estate", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Vypůjčeno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'estate',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_repair = 1 AND status_use = ? AND cl_company_id = ?', "estate", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Oprava',
                's_new' => 0,
                's_repair' => 1,
                's_storno' => 0,
                'status_use' => 'estate',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        //17.03.2021 - cl_status for in_rental
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "rental", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Vypůjčeno',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'rental',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "rental", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Vráceno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'rental',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }


        //02.12.2020 - status for cash
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "cash", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'cash',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_eml = 1 AND status_use = ? AND cl_company_id = ?', "cash", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Email',
                's_eml' => 1,
                'status_use' => 'cash',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "cash", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'cash',
                'color_hex' => NULL));
            $this->database->table('cl_status')->insert($tmpData);
        }


        //09.03.2018 - offers
        //09.03.2018 - number series for offer
        if (!$this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ?', 'offer', $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'formula' => 'N-(Z4)/(2R)',
                'form_name' => 'Nabídka',
                'form_default' => 1,
                'form_use' => 'offer')
            );
            $this->database->table('cl_number_series')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_eml = 1 AND status_use = ? AND cl_company_id = ?', "offer", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Email',
                's_eml' => 1,
                'status_use' => 'offer',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }

        //09.03.2018 - status for offer
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "offer", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'offer',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }
        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "offer", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'offer',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }

        //26.05.2020 - status for b2b_order
        if (!$this->database->table('cl_status')->where('s_new = 1 AND status_use = ? AND cl_company_id = ?', "b2b_order", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'b2b_order',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_work = 1 AND status_use = ? AND cl_company_id = ?', "b2b_order", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Zpracování',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_work' => 1,
                'status_use' => 'b2b_order',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_fin = 1 AND status_use = ? AND cl_company_id = ?', "b2b_order", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                'status_use' => 'b2b_order',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }

        if (!$this->database->table('cl_status')->where('s_storno = 1 AND status_use = ? AND cl_company_id = ?', "b2b_order", $id)->fetch()) {
            $tmpData = array();
            $tmpData = array(array('cl_company_id' => $id,
                'status_name' => 'Storno',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 1,
                'status_use' => 'b2b_order',
                'color_hex' => '#32CD32'));
            $this->database->table('cl_status')->insert($tmpData);
        }

    }

    //set default data for some tables
    public function createDefaultData($id)
    {
        //cl_countries
        /*$tmpCountries = array();
        $tmpCountries['cl_company_id'] = $id;
        $tmpCountries['name'] = 'Czech republic';
        $tmpCountries['acronym'] = 'CZ';
        $tmpCountries['currency'] = 'CZK';
        $tmpCountries['cl_users_id'] = NULL;
        $tmpCountries2 = $this->database->table('cl_countries')->insert($tmpCountries);
        $tmpCountries['cl_company_id'] = $id;
        $tmpCountries['name'] = 'Slovak republic';
        $tmpCountries['acronym'] = 'SK';
        $tmpCountries['currency'] = 'EUR';
        $tmpCountries['cl_users_id'] = NULL;
        $this->database->table('cl_countries')->insert($tmpCountries);
        */

        /*
        //cl_rates_vat
        $tmpRates = array();
        $tmpRates['cl_company_id'] = $id;
        $tmpRates['rates'] = 21;
        $tmpRates['description'] = 'základní sazba';
        $tmpRates['valid_from'] = NULL;
        $tmpRates['cl_countries_id'] = $tmpCountries2->id;
        $this->database->table('cl_rates_vat')->insert($tmpRates);
        */


        //cl_currencies
        $tmpRates = array();
        $tmpRates = array(array('cl_company_id' => $id,
            'currency_code' => 'CZK',
            'currency_name' => 'Kč',
            'fix_rate' => 1,
            'amount' => 1),
            array('cl_company_id' => $id,
                'currency_code' => 'USD',
                'currency_name' => '$',
                'fix_rate' => 24.726,
                'amount' => 1),
            array('cl_company_id' => $id,
                'currency_code' => 'EUR',
                'currency_name' => '€',
                'fix_rate' => 27.03,
                'amount' => 1),
            array('cl_company_id' => $id,
                'currency_code' => 'PLN',
                'currency_name' => 'zł',
                'fix_rate' => 6.262,
                'amount' => 1),
            array('cl_company_id' => $id,
                'currency_code' => 'GBP',
                'currency_name' => '£',
                'fix_rate' => 37.118,
                'amount' => 1),
            array('cl_company_id' => $id,
                'currency_code' => 'DKK',
                'currency_name' => 'DKK',
                'fix_rate' => 3.622,
                'amount' => 1),
            array('cl_company_id' => $id,
                'currency_code' => 'HUF',
                'currency_name' => 'Ft',
                'fix_rate' => 8.542,
                'amount' => 1)
        );
        $this->database->table('cl_currencies')->insert($tmpRates);

        //cl_payment_types
        $tmpRates = array();
        $tmpRates = array(array('cl_company_id' => $id,
            'name' => 'V hotovosti',
            'description' => 'platba v hotovosti',
            'short_desc' => 'hot',
            'payment_type' => 1),
            array('cl_company_id' => $id,
                'name' => 'Převodem',
                'description' => 'platba převodem',
                'short_desc' => 'pre',
                'payment_type' => 0),
            array('cl_company_id' => $id,
                'name' => 'Zápočet',
                'description' => 'platba zápočtem',
                'short_desc' => 'zap',
                'payment_type' => 0),
            array('cl_company_id' => $id,
                'name' => 'Karta',
                'description' => 'platební kartou',
                'short_desc' => 'krt',
                'payment_type' => 3),
            array('cl_company_id' => $id,
                'name' => 'Dobírka',
                'description' => 'platba dobírkou',
                'short_desc' => 'dob',
                'payment_type' => 2)
        );

        $this->database->table('cl_payment_types')->insert($tmpRates);

        //cl_storage
        $tmpStorage = [];
        $tmpStorage = [['cl_company_id' => $id,
            'name' => 'Sklad',
            'description' => 'hlavní skupina']];
        $tmpRowStorage = $this->database->table('cl_storage')->insert($tmpStorage);
        $tmpStorage = [];
        $tmpStorage = [['cl_company_id' => $id,
            'name' => '01',
            'description' => 'Hlavní sklad',
            'cl_storage_id' => $tmpRowStorage->id]];
        $tmpRowStorage = $this->database->table('cl_storage')->insert($tmpStorage);

        //cl_prices_groups
        $tmpPricesGroups = [];
        $tmpPricesGroups = [['cl_company_id' => $id,
            'name' => 'Skupina A']];
        $tmpRowPricesGroups = $this->database->table('cl_prices_groups')->insert($tmpPricesGroups);


        //default settings to cl_company
        $tmpDefault = [];
        $tmpDefault['id'] = $id;
        $tmpDefault['def_sazba'] = 21;
        $tmpDefault['email_income'] = 'help-' . $id . '@klienti.cz';
        $tmpDefault['pdp_text'] = 'Dodání je v režimu přenesené daňové povinnosti dle § 92a zákona č. 235/2004 Sb., o DPH. Daň odvede zákazník.';

        $tmpDefault['cl_payment_types_id'] = $this->database->table('cl_payment_types')->where(['cl_company_id' => $id, 'name' => 'V hotovosti'])->fetch()->id;
        $tmpDefault['cl_currencies_id'] = $this->database->table('cl_currencies')->where(['cl_company_id' => $id, 'currency_code' => 'CZK'])->fetch()->id;
        $tmpDefault['cl_storage_id'] = $this->database->table('cl_storage')->where(['cl_company_id' => $id])->where('cl_storage_id IS NOT NULL')->fetch()->id;
        $this->findByUpdate(['id' => $id])->update($tmpDefault);

        $tmpNow = new Nette\Utils\DateTime();
        //default cl_number_series
        $tmpDefault = [];
        $tmpDefault = [['cl_company_id' => $id,
            'formula' => 'O-(Z4)/(2R)',
            'form_name' => 'Objednávka',
            'form_default' => 1,
            'form_use' => 'order',
            'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'Z-(Z4)/(2R)',
                'form_name' => 'Zakázka',
                'form_default' => 1,
                'form_use' => 'commission',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'N-(Z4)/(2R)',
                'form_name' => 'Nabídka',
                'form_default' => 1,
                'form_use' => 'offer',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => '01-(Z6)',
                'form_name' => 'Ceník zboží',
                'form_default' => 1,
                'form_use' => 'pricelist',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => '02-(Z6)',
                'form_name' => 'Ceník služby',
                'form_default' => 1,
                'form_use' => 'pricelist',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'P-(Z4)/(2R)',
                'form_name' => 'Sklad příjem',
                'form_default' => 1,
                'form_use' => 'store_in',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'V-(Z4)/(2R)',
                'form_name' => 'Sklad výdej',
                'form_default' => 1,
                'form_use' => 'store_out',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'F-(Z4)/(2R)',
                'form_name' => 'Faktura',
                'form_default' => 1,
                'form_use' => 'invoice',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'FP-(Z4)/(2R)',
                'form_name' => 'Faktura přijatá',
                'form_default' => 1,
                'form_use' => 'invoice_arrived',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'ID-(Z4)/(2R)',
                'form_name' => 'Interní doklad',
                'form_default' => 1,
                'form_use' => 'invoice_internal',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'DZ-(Z4)/(2R)',
                'form_name' => 'Daňový doklad k zaplacené záloze',
                'form_default' => 1,
                'form_use' => 'invoice_tax',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'DL(Z4)/(2R)',
                'form_name' => 'Dodací list',
                'form_default' => 1,
                'form_use' => 'delivery_note',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'O-(Z4)/(2R)',
                'form_name' => 'Opravný doklad',
                'form_default' => 1,
                'form_use' => 'invoice_correction',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'Z-(Z4)/(2R)',
                'form_name' => 'Zálohová faktura',
                'form_default' => 1,
                'form_use' => 'invoice_advance',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'OP-(Z4)/(2R)',
                'form_name' => 'Opravný doklad přijatý',
                'form_default' => 1,
                'form_use' => 'invoice_arrived_correction',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'ZP-(Z4)/(2R)',
                'form_name' => 'Zálohová faktura přijatá',
                'form_default' => 1,
                'form_use' => 'invoice_arrived_advance',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'PP-(Z4)/(2R)',
                'form_name' => 'Pokladna příjem',
                'form_default' => 1,
                'form_use' => 'cash',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'VP-(Z4)/(2R)',
                'form_name' => 'Pokladna výdej',
                'form_default' => 1,
                'form_use' => 'cash',
                'last_use' => $tmpNow],
            ['cl_company_id' => $id,
                'formula' => 'KDB-(Z4)',
                'form_name' => 'Všeználek',
                'form_default' => 1,
                'form_use' => 'kdb',
                'last_use' => $tmpNow]
        ];
        $this->database->table('cl_number_series')->insert($tmpDefault);

        //default cl_invoice_types
        $clNumberSeriesId = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'invoice', $id, 'Faktura')->fetch()->id;
        $clNumberSeriesIdP = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'invoice_arrived', $id, 'Faktura přijatá')->fetch()->id;
        $clNumberSeriesIdD = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'invoice_correction', $id, 'Opravný doklad')->fetch()->id;
        $clNumberSeriesIdZ = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'invoice_advance', $id, 'Zálohová faktura')->fetch()->id;
        $clNumberSeriesIdI = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'invoice_internal', $id, 'Interní doklad')->fetch()->id;
        $tmpDefault = [];
        $tmpDefault = [['cl_company_id' => $id,
            'cl_number_series_id' => $clNumberSeriesId,
            'name' => 'Běžná faktura',
            'default_type' => 1,
            'inv_type' => 1],
            ['cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdD,
                'name' => 'Opravný doklad',
                'default_type' => 0,
                'inv_type' => 2],
            ['cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdZ,
                'name' => 'Zálohová faktura',
                'default_type' => 0,
                'inv_type' => 3],
            ['cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdP,
                'name' => 'Faktura přijatá',
                'default_type' => 1,
                'inv_type' => 4],
            ['cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesIdI,
                'name' => 'Interní doklad',
                'default_type' => 1,
                'inv_type' => 8]];
        $this->database->table('cl_invoice_types')->insert($tmpDefault);

        //default cl_cash_types
        $clNumberSeriesCashIn = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'cash', $id, 'Pokladna příjem')->fetch()->id;
        $clNumberSeriesCashOut = $this->database->table('cl_number_series')->where('form_use = ? AND cl_company_id = ? AND form_name = ?', 'cash', $id, 'Pokladna výdej')->fetch()->id;
        $tmpDefault = array();
        $tmpDefault = array(array('cl_company_id' => $id,
            'cl_number_series_id' => $clNumberSeriesCashIn,
            'name' => 'Pokladna příjem',
            'default_type' => 1,
            'inv_type' => 5),
            array('cl_company_id' => $id,
                'cl_number_series_id' => $clNumberSeriesCashOut,
                'name' => 'Pokladna výdej',
                'default_type' => 0,
                'inv_type' => 6));
        $this->database->table('cl_invoice_types')->insert($tmpDefault);


        //default cl_status
        $tmpDefault = [];
        $tmpDefault = [['cl_company_id' => $id,
            'status_name' => 'Nová',
            's_new' => 1,
            's_fin' => 0,
            's_storno' => 0,
            's_eml' => 0,
            'status_use' => 'order',
            'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'commission',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'offer',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Odeslaná faktura',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice_arrived',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice_arrived',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Nová příjemka',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'store_in',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Nová výdejka',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'store_out',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'order',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'commission',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'offer',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Hotovo',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Uzavřená příjemka',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'store_in',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Uzavřená výdejka',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'store_out',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Nová',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'partners_event',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Uzavřeno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'partners_event',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Rozpracováno',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'partners_event',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Zrušeno',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 1,
                's_eml' => 0,
                'status_use' => 'partners_event',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'delivery_note',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Dodáno',
                's_new' => 0,
                's_fin' => 1,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'delivery_note',
                'color_hex' => NULL],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_eml' => 1,
                's_fin' => 0,
                's_storno' => 0,
                'status_use' => 'delivery_note',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'commission',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'offer',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'invoice',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'invoice_advance',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Nový',
                's_new' => 1,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 0,
                'status_use' => 'invoice_internal',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'invoice_internal',
                'color_hex' => '#32CD32'],
            ['cl_company_id' => $id,
                'status_name' => 'Email',
                's_new' => 0,
                's_fin' => 0,
                's_storno' => 0,
                's_eml' => 1,
                'status_use' => 'order',
                'color_hex' => '#32CD32']
        ];
        $this->database->table('cl_status')->insert($tmpDefault);


        //default cl_users_role
        $tmpDefault = [];
        $tmpDefault = [['cl_company_id' => $id,
            'name' => 'admin']];

        $this->database->table('cl_users_role')->insert($tmpDefault);

        //cl_partners_event_type
        $tmpRates = [];
        $tmpRates = [['cl_company_id' => $id,
            'event_order' => 1,
            'type_name' => 'První kontakt s klientem'],
            ['cl_company_id' => $id,
                'event_order' => 2,
                'type_name' => 'Práce na zakázce']];
        $this->database->table('cl_partners_event_type')->insert($tmpRates);


        //cl_emailing_text
        $tmpEmailingText = [];
        $tmpEmailingText = [['cl_company_id' => $id,
            'email_name' => 'Faktura',
            'email_subject' => 'Faktura č. [doc_number]',
            'email_body' =>
                'Dobrý den,<br>
<br>
zasílám Vám fakturu za dodané služby. Fakturu si můžete stáhnout z tohoto odkazu: [link]<br>
<br>
 ',
            'email_use' => 'invoice'],

            ['cl_company_id' => $id,
                'email_name' => 'Objednávka',
                'email_subject' => 'Objednávka č. [doc_number]',
                'email_body' =>
                    'Dobrý den,<br>
<br>
zasílám Vám objednávku. PDF soubor objednávky si můžete stáhnout z tohoto odkazu: [link]<br>
<br>
<br>
O termínu dodání nás prosím informujte obratem.',
                'email_use' => 'order'],

            ['cl_company_id' => $id,
                'email_name' => 'Zakázka',
                'email_subject' => 'Zakázka č. [doc_number]',
                'email_body' =>
                    'Dobrý den,<br>
<br>
zasílám Vám zakázkový list ve formátu PDF. Soubor můžete stáhnout z tohoto odkazu: [link]<br>
<br>
 ',
                'email_use' => 'commission'],
            ['cl_company_id' => $id,
                'email_name' => 'Nabídka',
                'email_subject' => 'Nabídka č. [doc_number]',
                'email_body' =>
                    'Dobrý den,<br>
<br>
zasílám Vám nabídkový list ve formátu PDF. Soubor můžete stáhnout z tohoto odkazu: [link]<br>
<br>
 ',
                'email_use' => 'offer'],
            ['cl_company_id' => $id,
                'email_name' => 'Pokladna',
                'email_subject' => 'Pokladní doklad č. [doc_number]',
                'email_body' =>
                    'Dobrý den,<br>
<br>
zasílám Vám pokladní doklad ve formátu PDF. Soubor můžete stáhnout z tohoto odkazu: [link]<br>
<br>
 ',
                'email_use' => 'cash'],
            ['cl_company_id' => $id,
                'email_name' => 'Úkoly',
                'email_subject' => 'Úkol č. [doc_number]',
                'email_body' =>
                    'Dobrý den,<br>
<br>
úkol byl právě uložen. Tady si jej můžete otevřít: [link]<br>
<br>
 ',
                'email_use' => 'task'],
            ['cl_company_id' => $id,
                'email_name' => 'Upomínky faktur',
                'email_subject' => 'Nezaplacené faktury po splatnosti',
                'email_body' =>
                'Dobrý den,<br>
<br>
prosíme o kontrolu nezaplacených faktur. Pokud jste je již uhradili, děkujeme <br>
<br>
[invoices]
<br>
Celková dlužná částka k dnešnímu dni je: [total_sum]
 ',
                'email_use' => 'ireminder'],

        ];
        $this->database->table('cl_emailing_text')->insert($tmpEmailingText);


    }


    public function getDataFolder($company_id)
    {
        return $this->ArraysManager->getDataFolder($company_id);
    }

    /**Return data for signing EET message. It solves company or branches
     * @param $company_branch_id
     * @return array
     */
    public function getDataForSignEET($company_branch_id)
    {
        $tmpCompany = $this->getTable()->fetch();
        $arrRet = array();
        if (!is_null($company_branch_id)) {
            $tmpBranch = $this->getTable()->select(':cl_company_branch.*')->where(':cl_company_branch.id = ?', $company_branch_id)->limit(1)->fetch();
        } else {
            $tmpBranch = false;
        }
        if ($tmpBranch && ($tmpBranch['eet_active'] == 1 || $tmpCompany['eet_test'] == 1)) {
            $arrRet['dic_popl'] = $tmpBranch['b_dic'];
            $arrRet['eet_pfx'] = $tmpBranch['eet_pfx'];
            $arrRet['eet_pass'] = $tmpBranch['eet_pass'];
            $arrRet['eet_id_provoz'] = $tmpBranch['eet_id_provoz'];
            $arrRet['eet_id_poklad'] = $tmpBranch['eet_id_poklad'];
            $arrRet['eet_test'] = $tmpBranch['eet_test'];
            $arrRet['eet_ghost'] = $tmpBranch['eet_ghost'];
        } elseif ($tmpCompany['eet_active'] == 1 || $tmpCompany['eet_test'] == 1) {
            $arrRet['dic_popl'] = $tmpCompany['dic'];
            $arrRet['eet_pfx'] = $tmpCompany['eet_pfx'];
            $arrRet['eet_pass'] = $tmpCompany['eet_pass'];
            $arrRet['eet_id_provoz'] = $tmpCompany['eet_id_provoz'];
            $arrRet['eet_id_poklad'] = $tmpCompany['eet_id_poklad'];
            $arrRet['eet_test'] = $tmpCompany['eet_test'];
            $arrRet['eet_ghost'] = 0;
        } else {
            $arrRet = FALSE;
        }

        return $arrRet;
    }

    public function getStamp()
    {
        $user_id = $this->user->getId();
        //$company_id = $rowUser->cl_company_id;
        $settings = $this->getTable()->fetch();
        //$settings = $this->find($company_id);
        if (!is_null($user_id) && $rowUser = $this->UserManager->getUserById($user_id)) {
            if ($rowUser['picture_stamp'] != '') {
                $img = $rowUser['picture_stamp'];
            } else {
                $img = $settings->picture_stamp;
            }
        } else {
            $img = $settings->picture_stamp;
        }

        $ret = '';
        if ($img != '') {
            $file = __DIR__ . "/../../data/pictures/" . $img;
            if (is_file($file)) {
                $image = Image::fromFile($file);
                // $image->send(Image::JPEG);
                $ret = $image->toString();
            }
        }
        return ($ret);
        // $this->terminate();
    }

    public function getLogo()
    {
        //$user_id = $this->user->getId();
        //$company_id = $this->UserManager->getUserById($user_id)->cl_company_id;
        //$settings = $this->find($company_id);
        $settings = $this->getTable()->fetch();
        $img = $settings->picture_logo;
        $ret = '';
        if ($img != '') {
            $file = __DIR__ . "/../../data/pictures/" . $img;
            if (is_file($file)) {
                $image = Image::fromFile($file);
                // $image->send(Image::JPEG);
                $ret = $image->toString();
                $ret = (string)$image;
            }
        }
        return ($ret);
    }

    private function makeCountries()
    {
        $this->database->query('INSERT INTO `cl_countries` (`id`, `name`, `acronym`, `country_name`, `cl_company_id`, `currency`, `vat`, `cl_users_id`, `create_by`, `created`, `change_by`, `changed`) VALUES
(1, \'Česko\', \'CZE\', \'Česká republika\', NULL, \'CZK\', 0, NULL, \'\', NULL, \'Tomáš Halász\', \'2015-07-09 10:05:41\'),
(2, \'Spojené státy\', \'USA\', \'Spojené státy\', NULL, \'USD\', 0, NULL, \'\', NULL, \'\', NULL),
(3, \'Slovensko\', \'SVK\', \'Slovensko\', NULL, \'EUR\', 0, NULL, \'\', NULL, \'\', NULL),
(5, \'Polsko\', \'POL\', \'Polsko\', NULL, \'EUR\', 0, NULL, \'Tomáš Halász\', \'2015-07-09 10:05:55\', \'Tomáš Halász\', \'2015-07-09 10:08:10\'),
(13, \'Německo\', \'DEU\', \'Německo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(14, \'Rakousko\', \'AUT\', \'Rakousko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(15, \'Rumunsko\', \'ROU\', \'Rumunsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(16, \'Francie\', \'FRA\', \'Francie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(17, \'Řecko\', \'GRC\', \'Řecko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(18, \'Slovinsko\', \'SVN\', \'Slovinsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(19, \'Norsko\', \'NOR\', \'Norsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(20, \'Nizozemsko\', \'NLD\', \'Nizozemsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(21, \'Monako\', \'MCO\', \'Monako\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(22, \'Lucembursko\', \'LUX\', \'Lucembursko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(23, \'Island\', \'ISL\', \'Island\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(24, \'Irsko\', \'IRL\', \'Irsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(25, \'Chorvatsko\', \'HRV\', \'Chorvatsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(26, \'Dánsko\', \'DNK\', \'Dánsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(27, \'China\', \'\', \'\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(28, \'Belgie\', \'BEL\', \'Belgie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(29, \'Spojené království\', \'GBR\', \'Spojené království\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(30, \'Ukrajina\', \'UKR\', \'Ukrajina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(31, \'Švédsko\', \'SWE\', \'Švédsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(32, \'Švýcarsko\', \'CHE\', \'Švýcarsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(33, \'Španělsko\', \'ESP\', \'Španělsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(35, \'Afghánistán\', \'AFG\', \'Afghánistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(36, \'Agentura spoj. národů\', \'UNA\', \'Agentura spoj. národů\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(37, \'Alandské ostrovy\', \'ALA\', \'Alandské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(38, \'Albánie\', \'ALB\', \'Albánie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(39, \'Alžírsko\', \'DZA\', \'Alžírsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(40, \'Americká Samoa\', \'ASM\', \'Americká Samoa\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(41, \'Americké Panenské ostr.\', \'VIR\', \'Americké Panenské ostr.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(42, \'Andorra\', \'AND\', \'Andorra\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(43, \'Angola\', \'AGO\', \'Angola\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(44, \'Anguilla\', \'AIA\', \'Anguilla\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(45, \'Antarktida\', \'ATA\', \'Antarktida\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(46, \'Antigua a Barbuda\', \'ATG\', \'Antigua a Barbuda\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(47, \'Argentina\', \'ARG\', \'Argentina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(48, \'Arménie\', \'ARM\', \'Arménie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(49, \'Aruba\', \'ABW\', \'Aruba\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(50, \'Austrálie\', \'AUS\', \'Austrálie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(51, \'Ázerbájdžán\', \'AZE\', \'Ázerbájdžán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(52, \'Bahamy\', \'BHS\', \'Bahamy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(53, \'Bahrajn\', \'BHR\', \'Bahrajn\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(54, \'Bangladéš\', \'BGD\', \'Bangladéš\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(55, \'Barbados\', \'BRB\', \'Barbados\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(57, \'Belize\', \'BLZ\', \'Belize\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(58, \'Bělorusko\', \'BLR\', \'Bělorusko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(59, \'Benin \', \'BEN\', \'Benin \', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(60, \'Bermudy\', \'BMU\', \'Bermudy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(61, \'Bhútán\', \'BTN\', \'Bhútán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(62, \'Bolívarovská republika Venezuela\', \'VEN\', \'Bolívarovská republika Venezuela\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(63, \'Bonaire, Svatý Eustach\', \'BES\', \'Bonaire, Svatý Eustach\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(64, \'Bosna a Hercegovina\', \'BIH\', \'Bosna a Hercegovina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(65, \'Botswana\', \'BWA\', \'Botswana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(66, \'Bouvetův ostrov\', \'BVT\', \'Bouvetův ostrov\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(67, \'Brazílie\', \'BRA\', \'Brazílie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(71, \'Britské Panenské ostrovy\', \'VGB\', \'Britské Panenské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(72, \'Britské zámořské území\', \'GBO\', \'Britské zámořské území\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(73, \'Brit.indickooceán.území\', \'IOT\', \'Brit.indickooceán.území\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(75, \'Brunej Darussalam\', \'BRN\', \'Brunej Darussalam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(76, \'Bulharsko\', \'BGR\', \'Bulharsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(77, \'Burkina Faso\', \'BFA\', \'Burkina Faso\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(78, \'Burundi\', \'BDI\', \'Burundi\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(79, \'Cookovy ostrovy\', \'COK\', \'Cookovy ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(80, \'Curaçao\', \'CUW\', \'Curaçao\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(81, \'Čad\', \'TCD\', \'Čad\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(82, \'Černá Hora\', \'MNE\', \'Černá Hora\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(84, \'China\', \'CHN\', \'Čína\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(86, \'Dominika\', \'DMA\', \'Dominika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(87, \'Dominikánská republika\', \'DOM\', \'Dominikánská republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(88, \'Džibutsko\', \'DJI\', \'Džibutsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(89, \'Egypt\', \'EGY\', \'Egypt\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(90, \'Ekvádor\', \'ECU\', \'Ekvádor\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(91, \'Eritrea\', \'ERI\', \'Eritrea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(92, \'Estonsko\', \'EST\', \'Estonsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(93, \'Etiopie\', \'ETH\', \'Etiopie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(94, \'Faerské ostrovy\', \'FRO\', \'Faerské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(95, \'Falklandské os. (Malvíny)\', \'FLK\', \'Falklandské os. (Malvíny)\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(96, \'Fidži\', \'FJI\', \'Fidži\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(97, \'Filipíny\', \'PHL\', \'Filipíny\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(98, \'Finsko\', \'FIN\', \'Finsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(100, \'Francouzská Guyana\', \'GUF\', \'Francouzská Guyana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(101, \'Francouzská jižní území\', \'ATF\', \'Francouzská jižní území\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(102, \'Francouzská Polynésie\', \'PYF\', \'Francouzská Polynésie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(103, \'Gabon\', \'GAB\', \'Gabon\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(104, \'Gambie\', \'GMB\', \'Gambie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(105, \'Ghana\', \'GHA\', \'Ghana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(106, \'Gibraltar\', \'GIB\', \'Gibraltar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(107, \'Grenada\', \'GRD\', \'Grenada\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(108, \'Grónsko\', \'GRL\', \'Grónsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(109, \'Gruzie\', \'GEO\', \'Gruzie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(110, \'Guadeloupe\', \'GLP\', \'Guadeloupe\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(111, \'Guam\', \'GUM\', \'Guam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(112, \'Guatemala\', \'GTM\', \'Guatemala\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(113, \'Guernsey\', \'GGY\', \'Guernsey\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(114, \'Guinea\', \'GIN\', \'Guinea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(115, \'Guinea-Bissau\', \'GNB\', \'Guinea-Bissau\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(116, \'Guyana\', \'GUY\', \'Guyana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(117, \'Haiti\', \'HTI\', \'Haiti\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(119, \'Honduras\', \'HND\', \'Honduras\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(120, \'Hongkong\', \'HKG\', \'Hongkong\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(121, \'Chile\', \'CHL\', \'Chile\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(123, \'Indie\', \'IND\', \'Indie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(124, \'Indonésie\', \'IDN\', \'Indonésie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(125, \'Irák\', \'IRQ\', \'Irák\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(126, \'Írán\', \'IRN\', \'Írán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(129, \'Itálie\', \'ITA\', \'Itálie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(130, \'Izrael\', \'ISR\', \'Izrael\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(131, \'Jamajka\', \'JAM\', \'Jamajka\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(132, \'Japonsko\', \'JPN\', \'Japonsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(133, \'Jemen\', \'YEM\', \'Jemen\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(134, \'Jersey\', \'JEY\', \'Jersey\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(135, \'Jižní Afrika\', \'ZAF\', \'Jižní Afrika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(136, \'Jižní Súdán\', \'SSD\', \'Jižní Súdán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(137, \'Jordánsko\', \'JOR\', \'Jordánsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(139, \'Kajmanské ostrovy\', \'CYM\', \'Kajmanské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(140, \'Kambodža\', \'KHM\', \'Kambodža\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(141, \'Kamerun\', \'CMR\', \'Kamerun\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(142, \'Kanada\', \'CAN\', \'Kanada\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(143, \'Kapverdy\', \'CPV\', \'Kapverdy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(144, \'Katar\', \'QAT\', \'Katar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(145, \'Kazachstán\', \'KAZ\', \'Kazachstán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(146, \'Keňa\', \'KEN\', \'Keňa\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(147, \'Kiribati\', \'KIR\', \'Kiribati\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(148, \'Kokosové(Keelingovy)os.\', \'CCK\', \'Kokosové(Keelingovy)os.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(149, \'Kolumbie\', \'COL\', \'Kolumbie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(150, \'Komory\', \'COM\', \'Komory\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(151, \'Kongo, republika\', \'COG\', \'Kongo, republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(152, \'Kongo,demokratická rep.\', \'COD\', \'Kongo,demokratická rep.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(153, \'Korea, lid. dem. rep.\', \'PRK\', \'Korea, lid. dem. rep.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(154, \'Korejská republika\', \'KOR\', \'Korejská republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(155, \'Kosovo\', \'XXK\', \'Kosovo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(156, \'Kostarika\', \'CRI\', \'Kostarika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(157, \'Kuba\', \'CUB\', \'Kuba\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(158, \'Kuvajt\', \'KWT\', \'Kuvajt\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(159, \'Kypr\', \'CYP\', \'Kypr\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(160, \'Kyrgyzstán\', \'KGZ\', \'Kyrgyzstán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(161, \'Laoská lid.dem.repub.\', \'LAO\', \'Laoská lid.dem.repub.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(162, \'Lesotho\', \'LSO\', \'Lesotho\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(163, \'Libanon\', \'LBN\', \'Libanon\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(164, \'Libérie\', \'LBR\', \'Libérie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(165, \'Libye\', \'LBY\', \'Libye\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(166, \'Lichtenštejnsko\', \'LIE\', \'Lichtenštejnsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(167, \'Litva\', \'LTU\', \'Litva\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(168, \'Lotyšsko\', \'LVA\', \'Lotyšsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(170, \'Macao\', \'MAC\', \'Macao\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(171, \'Madagaskar\', \'MDG\', \'Madagaskar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(172, \'Maďarsko\', \'HUN\', \'Maďarsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(173, \'Makedonie\', \'MKD\', \'Makedonie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(174, \'Malajsie\', \'MYS\', \'Malajsie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(175, \'Malawi\', \'MWI\', \'Malawi\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(176, \'Maledivy\', \'MDV\', \'Maledivy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(177, \'Mali\', \'MLI\', \'Mali\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(178, \'Malta\', \'MLT\', \'Malta\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(179, \'Maroko\', \'MAR\', \'Maroko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(180, \'Marshallovy ostrovy\', \'MHL\', \'Marshallovy ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(181, \'Martinik\', \'MTQ\', \'Martinik\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(182, \'Mauricius\', \'MUS\', \'Mauricius\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(183, \'Mauritánie\', \'MRT\', \'Mauritánie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(184, \'Mayotte\', \'MYT\', \'Mayotte\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(186, \'Mexiko\', \'MEX\', \'Mexiko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(187, \'Mikronésie\', \'FSM\', \'Mikronésie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(189, \'Moldavská republika\', \'MDA\', \'Moldavská republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(191, \'Mongolsko\', \'MNG\', \'Mongolsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(192, \'Montserrat\', \'MSR\', \'Montserrat\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(193, \'Mosambik\', \'MOZ\', \'Mosambik\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(194, \'Myanmar\', \'MMR\', \'Myanmar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(195, \'Namibie\', \'NAM\', \'Namibie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(196, \'Nauru\', \'NRU\', \'Nauru\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(198, \'Nepál\', \'NPL\', \'Nepál\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(199, \'neutrální zóna\', \'NTZ\', \'neutrální zóna\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(200, \'Niger\', \'NER\', \'Niger\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(201, \'Nigérie\', \'NGA\', \'Nigérie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(202, \'Nikaragua\', \'NIC\', \'Nikaragua\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(203, \'Niue\', \'NIU\', \'Niue\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(204, \'Nizozemské Antily\', \'ANT\', \'Nizozemské Antily\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(207, \'Nová Kaledonie\', \'NCL\', \'Nová Kaledonie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(208, \'Nový Zéland\', \'NZL\', \'Nový Zéland\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(209, \'Omán\', \'OMN\', \'Omán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(213, \'Ostrov Man\', \'IMN\', \'Ostrov Man\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(214, \'Ostrov Norfolk\', \'NFK\', \'Ostrov Norfolk\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(215, \'Ostrovy Severní Mariany\', \'MNP\', \'Ostrovy Severní Mariany\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(216, \'Ostrovy Turks a Caicos\', \'TCA\', \'Ostrovy Turks a Caicos\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(217, \'ostrovy USA v Tichém oceánu\', \'PUS\', \'ostrovy USA v Tichém oceánu\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(218, \'Pákistán\', \'PAK\', \'Pákistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(219, \'Palau\', \'PLW\', \'Palau\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(220, \'Palestina\', \'XAB\', \'Palestina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(222, \'Panama\', \'PAN\', \'Panama\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(223, \'Papua Nová Guinea\', \'PNG\', \'Papua Nová Guinea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(224, \'Paraguay\', \'PRY\', \'Paraguay\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(225, \'Peru\', \'PER\', \'Peru\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(226, \'Pitcairn\', \'PCN\', \'Pitcairn\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(227, \'Pobřeží slonoviny\', \'CIV\', \'Pobřeží slonoviny\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(229, \'Portoriko\', \'PRI\', \'Portoriko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(230, \'Portugalsko\', \'PRT\', \'Portugalsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(232, \'Republika Kosovo\', \'RKS\', \'Republika Kosovo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(233, \'Réunion\', \'REU\', \'Réunion\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(234, \'Rovníková Guinea\', \'GNQ\', \'Rovníková Guinea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(236, \'Ruská federace\', \'RUS\', \'Ruská federace\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(237, \'Rwanda\', \'RWA\', \'Rwanda\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(240, \'Saint Pierre a Miquelon\', \'SPM\', \'Saint Pierre a Miquelon\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(241, \'Salvador\', \'SLV\', \'Salvador\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(242, \'Samoa\', \'WSM\', \'Samoa\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(243, \'San Marino\', \'SMR\', \'San Marino\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(244, \'Saúdská Arábie\', \'SAU\', \'Saúdská Arábie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(245, \'Senegal\', \'SEN\', \'Senegal\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(246, \'Seychely\', \'SYC\', \'Seychely\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(247, \'Sierra Leone\', \'SLE\', \'Sierra Leone\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(248, \'Singapur\', \'SGP\', \'Singapur\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(251, \'Somálsko\', \'SOM\', \'Somálsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(252, \'Spojené arabské emiráty\', \'ARE\', \'Spojené arabské emiráty\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(255, \'Srbsko\', \'SRB\', \'Srbsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(256, \'Srbsko a Černá Hora\', \'SCG\', \'Srbsko a Černá Hora\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(257, \'Srí Lanka\', \'LKA\', \'Srí Lanka\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(258, \'Středoafrická republika\', \'CAF\', \'Středoafrická republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(259, \'Súdán\', \'SDN\', \'Súdán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(260, \'Surinam\', \'SUR\', \'Surinam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(261, \'Svalbard a Jan Mayen\', \'SJM\', \'Svalbard a Jan Mayen\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(262, \'Svatá Helena,Ascension\', \'SHN\', \'Svatá Helena,Ascension\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(263, \'Svatá Lucie\', \'LCA\', \'Svatá Lucie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(264, \'Svatý Bartoloměj\', \'BLM\', \'Svatý Bartoloměj\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(265, \'Svatý Kryštof a Nevis\', \'KNA\', \'Svatý Kryštof a Nevis\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(266, \'Svatý Martin\', \'MAF\', \'Svatý Martin\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(268, \'Svatý stolec (Vatik.mě)\', \'VAT\', \'Svatý stolec (Vatik.mě)\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(269, \'Svazijsko\', \'SWZ\', \'Svazijsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(270, \'Svazová republika Jugoslávie\', \'YUG\', \'Svazová republika Jugoslávie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(271, \'Sv. Vincenc a Grenadiny\', \'VCT\', \'Sv. Vincenc a Grenadiny\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(272, \'Sv.Tomáš a Princ.ostrov\', \'STP\', \'Sv.Tomáš a Princ.ostrov\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(273, \'Syrská arabská republik\', \'SYR\', \'Syrská arabská republik\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(274, \'Šalomounovy ostrovy\', \'SLB\', \'Šalomounovy ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(278, \'Tádžikistán\', \'TJK\', \'Tádžikistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(279, \'Tanzanie\', \'TZA\', \'Tanzanie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(280, \'Thajsko\', \'THA\', \'Thajsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(281, \'Tchaj-wan, čín.provinc\', \'TWN\', \'Tchaj-wan, čín.provinc\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(282, \'Tichomořské ostrovy\', \'PCI\', \'Tichomořské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(283, \'Togo\', \'TGO\', \'Togo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(284, \'Tokelau\', \'TKL\', \'Tokelau\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(285, \'Tonga\', \'TON\', \'Tonga\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(287, \'Tunisko\', \'TUN\', \'Tunisko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(288, \'Turecko\', \'TUR\', \'Turecko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(289, \'Turkmenistán\', \'TKM\', \'Turkmenistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(290, \'Tuvalu\', \'TUV\', \'Tuvalu\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(291, \'Uganda\', \'UGA\', \'Uganda\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(295, \'Uruguay\', \'URY\', \'Uruguay\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(296, \'Uzbekistán\', \'UZB\', \'Uzbekistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(298, \'Vanuatu\', \'VUT\', \'Vanuatu\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(299, \'Vietnam\', \'VNM\', \'Vietnam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(300, \'Východní Timor\', \'TLS\', \'Východní Timor\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(302, \'Zaire\', \'ZAR\', \'Zaire\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(303, \'Zambie\', \'ZMB\', \'Zambie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(306, \'Zimbabwe\', \'ZWE\', \'Zimbabwe\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL);
COMMIT;');
        return;
    }


}

