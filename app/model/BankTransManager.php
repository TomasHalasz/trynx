<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Bank Trans management.
 */
class BankTransManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_bank_trans';

    /** @var App\Model\InvoiceArrivedManager */
    public $InvoiceArrivedManager;
    /** @var App\Model\InvoiceAdvanceManager */
    public $InvoiceAdvanceManager;
    /** @var App\Model\InvoiceManager */
    public $InvoiceManager;
    /** @var App\Model\InvoicePaymentsManager */
    public $InvoicePaymentsManager;
    /** @var App\Model\InvoiceAdvancePaymentsManager */
    public $InvoiceAdvancePaymentsManager;
    /** @var App\Model\InvoiceArrivedPaymentsManager */
    public $InvoiceArrivedPaymentsManager;
    /** @var App\Model\PaymentTypesManager */
    public $PaymentTypesManager;
    /** @var App\Model\BankTransItemsManager */
    public $BankTransItemsManager;

    private $settings;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                InvoiceManager $InvoiceManager, InvoiceArrivedManager $InvoiceArrivedManager, InvoiceAdvanceManager $InvoiceAdvanceManager, PaymentTypesManager $PaymentTypesManager,
                                InvoicePaymentsManager $InvoicePaymentsManager, InvoiceArrivedPaymentsManager $InvoiceArrivedPaymentsManager, InvoiceAdvancePaymentsManager $InvoiceAdvancePaymentsManager,
                                BankTransItemsManager $BankTransItemsManager, CompaniesManager $CompaniesManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);

        $this->InvoiceManager                   = $InvoiceManager;
        $this->InvoiceArrivedManager            = $InvoiceArrivedManager;
        $this->InvoiceAdvanceManager            = $InvoiceAdvanceManager;
        $this->InvoicePaymentsManager           = $InvoicePaymentsManager;
        $this->InvoiceArrivedPaymentsManager    = $InvoiceArrivedPaymentsManager;
        $this->InvoiceAdvancePaymentsManager    = $InvoiceAdvancePaymentsManager;
        $this->PaymentTypesManager              = $PaymentTypesManager;
        $this->BankTransItemsManager            = $BankTransItemsManager;

        $this->settings                         = $CompaniesManager->getTable()->fetch();

    }

    /**
     * pair transaction with cl_invoice, cl_invoice_arrived, cl_invoice_advance
     */
	public function pairTrans()
    {
        $tmpPayments = $this->PaymentTypesManager->findAll()->where('payment_type IN (0)')->limit(1)->fetch();
        if (!$tmpPayments){
            $paymentId = $tmpPayments->id;
        }else{
            $paymentId = NULL;
        }
        $data = $this->findAll()->where('cl_invoice_id IS NULL AND cl_invoice_arrived_id IS NULL AND cl_invoice_advance_id IS NULL');
        foreach($data as $key => $one){
            $this->pairCheck($one, $paymentId);
            $this->updateSum($one);
        }
    }

    public function pairCheck($one, $paymentId, $cl_invoice_id = NULL, $cl_invoice_advance_id = NULL, $cl_invoice_arrived_id = NULL){
	    //bdump(array($cl_invoice_id, $cl_invoice_advance_id, $cl_invoice_arrived_id));
        //dump($one);
	    if (isset($one['amount_to_pay'])){
            $amountOrig = $one['amount_to_pay'];
            $amount = abs($one['amount_to_pay']);
            $vSymbol = $one['v_symbol'];
            $bankTransId = $one['id'];
            $payment = abs($one['amount_paired']);
            $paymentOne = 0;
            if (!is_null($cl_invoice_id)){
                $tmpPayment =  $this->InvoicePaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_id = ?', $bankTransId, $cl_invoice_id)->fetch();
                if ($tmpPayment) {
                    $paymentOne += abs($tmpPayment['pay_price']);
                }
            }

            if (!is_null($cl_invoice_advance_id)) {
                $tmpPayment = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_advance_id = ?', $bankTransId, $cl_invoice_advance_id)->fetch();
                if ($tmpPayment) {
                    $paymentOne += abs($tmpPayment['pay_price']);
                }
            }

            if (!is_null($cl_invoice_arrived_id)){
                $tmpPayment = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_arrived_id = ?', $bankTransId, $cl_invoice_arrived_id)->fetch();
                if ($tmpPayment) {
                    $paymentOne += abs($tmpPayment['pay_price']);
                }
            }

        }else{
            $amountOrig = $one->cl_bank_trans['amount_to_pay'];
            $amount = abs($one->cl_bank_trans['amount_to_pay']);
            $vSymbol = $one->cl_bank_trans['v_symbol'];
            $bankTransId = $one['cl_bank_trans_id'];
            $payment = abs($one['amount_paired']);
            $paymentOne = abs($one->cl_bank_trans['amount_paired']);
        }
        bdump($bankTransId);
        //bdump($payment);
        //bdump($paymentOne);
        //if (empty($vSymbol)){
        //    return;
        //}

	    $amountLeft = $amount - $payment + $paymentOne;

	    if (!is_null($cl_invoice_id)){
	        $tmpInvoice = $this->InvoiceManager->findAll()->where('id = ?', $cl_invoice_id)->limit(1)->fetch();
        } elseif (!empty($vSymbol) && $amountOrig > 0) {
            if ($this->settings->platce_dph == 1) {
//AND price_e2_vat - price_payed >= ? , $amountLeft
                $tmpInvoice = $this->InvoiceManager->findAll()->where('TRIM(LEADING 0 FROM var_symb) = ? AND pay_date IS NULL ', $vSymbol)->limit(1)->fetch();
            } else {
                $tmpInvoice = $this->InvoiceManager->findAll()->where('TRIM(LEADING 0 FROM var_symb)  = ? AND pay_date IS NULL', $vSymbol)->limit(1)->fetch();
            }
        } else {
	        $tmpInvoice = false;
        }
        if ($tmpInvoice){
            bdump($tmpInvoice);
            $one->update(['cl_invoice_id' => $tmpInvoice->id]);
            $maxCount = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $tmpInvoice->id)->max('item_order');

            $tmpInvoicePayment = $this->InvoicePaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_id = ?', $bankTransId, $tmpInvoice->id)->fetch();
            bdump($tmpInvoicePayment);
            $arrPayment = $this->prepareData($tmpInvoice, $one, $paymentId, $maxCount + 1, $tmpInvoicePayment);
            $arrPayment['cl_invoice_id'] = $tmpInvoice->id;
            if ($tmpInvoicePayment){
                $arrPayment['id'] = $tmpInvoicePayment['id'];
                $this->InvoicePaymentsManager->update($arrPayment);
            }else {
                $this->InvoicePaymentsManager->insert($arrPayment);
            }
            $this->InvoiceManager->paymentUpdate($tmpInvoice->id);
        }

        if (!is_null($cl_invoice_advance_id)){
            $tmpInvoice = $this->InvoiceAdvanceManager->findAll()->where('id = ?', $cl_invoice_advance_id)->limit(1)->fetch();
        } elseif (!empty($vSymbol) && $amountOrig > 0) {
            //$tmpInvoice = $this->InvoiceAdvanceManager->findAll()->where('var_symb = ? AND pay_date IS NULL AND price_e2_vat = ?', $vSymbol, abs($one['amount_to_pay']))->limit(1)->fetch();
            if ($this->settings->platce_dph == 1) {
                $tmpInvoice = $this->InvoiceAdvanceManager->findAll()->where('TRIM(LEADING 0 FROM var_symb)  = ? AND pay_date IS NULL ', $vSymbol)->limit(1)->fetch();
            } else {
                $tmpInvoice = $this->InvoiceAdvanceManager->findAll()->where('TRIM(LEADING 0 FROM var_symb)  = ? AND pay_date IS NULL', $vSymbol)->limit(1)->fetch();
            }
        } else {
            $tmpInvoice = false;
        }
        if ($tmpInvoice) {
            $one->update(['cl_invoice_advance_id' => $tmpInvoice->id]);
            $maxCount = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_invoice_advance_id = ?', $tmpInvoice->id)->max('item_order');

            $tmpInvoiceAdvancePayment = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_advance_id = ?', $bankTransId, $tmpInvoice->id)->fetch();
            $arrPayment = $this->prepareData($tmpInvoice, $one, $paymentId, $maxCount + 1, $tmpInvoiceAdvancePayment);
            $arrPayment['cl_invoice_advance_id'] = $tmpInvoice->id;
            if ($tmpInvoiceAdvancePayment){
                $arrPayment['id'] = $tmpInvoiceAdvancePayment['id'];
                $this->InvoiceAdvancePaymentsManager->update($arrPayment);
            }else {
                $this->InvoiceAdvancePaymentsManager->insert($arrPayment);
            }
            $this->InvoiceAdvanceManager->paymentUpdate($tmpInvoice->id);
        }

        if (!is_null($cl_invoice_arrived_id)){
            $tmpInvoice = $this->InvoiceArrivedManager->findAll()->where('id = ?', $cl_invoice_arrived_id)->limit(1)->fetch();
        } elseif (!empty($vSymbol) && $amountOrig < 0) {
            //$tmpInvoice = $this->InvoiceArrivedManager->findAll()->where('var_symb = ? AND pay_date IS NULL AND price_e2_vat = ?', $vSymbol, abs($one['amount_to_pay']))->limit(1)->fetch();
            if ($this->settings->platce_dph == 1) {
                $tmpInvoice = $this->InvoiceArrivedManager->findAll()->where('TRIM(LEADING 0 FROM var_symb)  = ? AND pay_date IS NULL', $vSymbol)->limit(1)->fetch();
            } else {
                $tmpInvoice = $this->InvoiceArrivedManager->findAll()->where('TRIM(LEADING 0 FROM var_symb)  = ? AND pay_date IS NULL', $vSymbol)->limit(1)->fetch();
            }
        } else {
            $tmpInvoice = false;
        }
        if ($tmpInvoice) {
            $one->update(['cl_invoice_arrived_id' => $tmpInvoice->id]);
            $maxCount = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ?', $tmpInvoice->id)->max('item_order');

            $tmpInvoiceArrivedPayment = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_arrived_id = ?', $bankTransId, $tmpInvoice->id)->fetch();
            $arrPayment = $this->prepareData($tmpInvoice, $one, $paymentId, $maxCount + 1, $tmpInvoiceArrivedPayment);
            $arrPayment['cl_invoice_arrived_id'] = $tmpInvoice->id;
            if ($tmpInvoiceArrivedPayment){
                $arrPayment['id'] = $tmpInvoiceArrivedPayment['id'];
                $this->InvoiceArrivedPaymentsManager->update($arrPayment);
            }else {
                $this->InvoiceArrivedPaymentsManager->insert($arrPayment);
            }
            $this->InvoiceArrivedManager->paymentUpdate($tmpInvoice->id);
        }


    }


    /**Prepare data for payment table
     * @param $tmpInvoice
     * @param $one
     * @param $paymentId
     * @return array
     */
    public function prepareData($tmpInvoice, $one, $paymentId, $maxCount, $dataPayment)
    {
        $arrPayment                         = array();
        $arrPayment['item_order']            = $maxCount;
        //$arrPayment['cl_users_id']          = $one->cl_users_id;

        if (isset($one['cl_bank_accounts_id']) && !is_null($one['cl_bank_accounts_id'])) {
            //payment of whole invoice
            $arrPayment['cl_currencies_id'] = $one->cl_bank_accounts->cl_currencies_id;
            $trans_date = $one->trans_date;
            $amount_to_pay = abs($one->amount_to_pay);
            $price = ($this->settings->platce_dph == 1) ? ($tmpInvoice->price_e2_vat - $tmpInvoice->price_payed) : ($tmpInvoice->price_e2 - $tmpInvoice->price_payed);
            if ($dataPayment){
                $price += $dataPayment['pay_price'];
            }
            $bankTransId = $one->id;
        }elseif (!isset($one['cl_bank_accounts_id'])){
            //partial payment
            $trans_date = $one->cl_bank_trans['trans_date'];
            $amount_to_pay = abs($one->cl_bank_trans['amount_to_pay']);
            $price = $one['amount_paired'];
            $bankTransId = $one->cl_bank_trans['id'];
        }else{
            //whole payment
            $trans_date = $one->trans_date;
            $amount_to_pay = abs($one->amount_to_pay);
            $price = ($this->settings->platce_dph == 1) ? ($tmpInvoice->price_e2_vat - $tmpInvoice->price_payed) : ($tmpInvoice->price_e2 - $tmpInvoice->price_payed);
            if ($dataPayment){
                $price += $dataPayment['pay_price'];
            }
            $bankTransId = $one->id;
        }

        $arrPayment['cl_payment_types_id']  = $paymentId;
        $arrPayment['cl_bank_trans_id']     = $bankTransId;
        $arrPayment['pay_date']             = $trans_date;

        $price = ($price > abs($amount_to_pay)) ? abs($amount_to_pay): $price;

        $arrPayment['pay_price']            = $price; // abs($one->amount_to_pay);
        $arrPayment['pay_doc']              = 'import z banky';
        $arrPayment['pay_type']             = 0;
        $arrPayment['create_by']            = '';
        $arrPayment['created']              = NULL;
        $arrPayment['change_by']            = '';
        $arrPayment['changed']              = NULL;

        return $arrPayment;
    }


    public function updateSum($one){
        //$sum = $this->BankTransItemsManager->findAll()->where('cl_bank_trans_id = ?', $one['id'])->sum('amount_paired');
        $sum = 0;
      //  if (!is_null($one['cl_invoice_id'])) {
        $sum += $this->InvoicePaymentsManager->findAll()->where('cl_bank_trans_id = ?', $one['id'])->sum('pay_price');
      //  }
       // if (!is_null($one['cl_invoice_advance_id'])) {
        $sum += $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_bank_trans_id = ?', $one['id'])->sum('pay_price');
            //$this->update(array('id' => $one['id'], 'amount_paired' => $sum));
       // }
       // if (!is_null($one['cl_invoice_arrived_id'])) {
        $sum += $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_bank_trans_id = ?', $one['id'])->sum('pay_price');
            //$this->update(array('id' => $one['id'], 'amount_paired' => 0-$sum));
        //}

        $this->update(array('id' => $one['id'], 'amount_paired' => $sum));
        return;
    }

}

