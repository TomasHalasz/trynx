<?php

namespace Pohoda;

use Pohoda\Export\Address;
use SimpleXMLElement;
use DateTime;


class BankTransaction
{
	const NS = 'http://www.stormware.cz/schema/version_2/bank.xsd';

	const RECEIPT_TYPE = 'receipt'; //income
	const EXPENSE_TYPE = 'expense'; //outgoing
	public $type = self::RECEIPT_TYPE;

	private $myIdentity = [];

	/** @var Address */
	private $customerAddress;
	private $id;

	/** @var string - kod meny */
	private $foreignCurrency;

	/** @var float kurz meny - např. 25.5 pro euro */
	private $rate;

	/** @var int mnozstvi cizi meny pro kurzovy prepocet  */
	private $amount = 1;

	/** @var int variable symbol */
	private $symVar;

    /** @var int constant symbol */
    private $symConst;

    /** @var int specific symbol */
    private $symSpec;

    /** @var date date of statement */
	private $dateStatement;

    /** @var date date of payment */
    private $datePay;

    /** @var str payment account  */
    private $paymentAccountNumber;

    /** @var int payment bank  */
    private $paymentBankCode;

    /** @var float amount of payment */
    private $amountPay;

    /** @var str description for payment */
    private $text;

	private $required = ['dateStatement', 'symVar', 'paymentAccountNumber', 'paymentBankCode', 'datePay', 'amountPay' ];

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function isValid()
	{
		return $this->checkRequired();
	}

	/**
	 * @throws InvoiceException
	 * @return bool
	 */
	private function checkRequired()
	{
		foreach ($this->required as $param) {
			if (!isset($this->$param)) {
				$result = false;
				throw new InvoiceException($this->getId() . ": required " . $param . " is not set");
			}
		}
		return true;
	}

	/**
	 * @throws InvoiceException
	 * @param string $name
	 * @param string $value
	 * @param bool $maxLength
	 * @param bool $isNumeric
	 * @param bool $isDate
	 */
	private function validateItem($name, $value, $maxLength = false, $isNumeric = false, $isDate = false)
	{
		try {
			if ($maxLength)
				Validators::assertMaxLength($value, $maxLength);
			if ($isNumeric)
				Validators::assertNumeric($value);
			if ($isDate)
				Validators::assertDate($value);

		} catch (\InvalidArgumentException $e) {
			throw new InvoiceException($this->getId() . ": " . $name . " - " . $e->getMessage(), 0, $e);
		}
	}

	private function removeSpaces($value)
	{
		return preg_replace('/\s+/', '', $value);
	}

	private function convertDate($date)
	{
		if ($date instanceof DateTime)
			return $date->format("Y-m-d");

		return $date;
	}


    /**
     * @param $type
     * receipt, expense
     */
    public function setType($type)
    {
        $this->type = $type;
    }

	public function setSymVar($value)
	{
		$value = $this->removeSpaces($value);
		$this->validateItem('variable number', $value, 20, true);
		$this->symVar = $value;
	}

    public function setSymConst($value)
    {
        $this->symConst = $value;
    }

    public function setSymSpec($value)
    {
        $this->symSpec = $value;
    }

    public function setPaymentAccountNumber($value)
    {
        $value = htmlspecialchars($value, ENT_XML1);
        $this->validateItem('account number', $value, 240);
        $this->paymentAccountNumber = $value;
    }

    public function setPaymentBankCode($value)
    {
        $this->validateItem('bank code', $value, 5, true);
        $this->paymentBankCode = $value;
    }

    public function setDateStatement($value)
    {
        $value = $this->convertDate($value);
        $this->validateItem('date created', $value, false, false, true);
        $this->dateStatement = $value;
    }

    public function setDatePay($value)
    {
        $value = $this->convertDate($value);
        $this->validateItem('date created', $value, false, false, true);
        $this->datePay = $value;
    }

    public function setAmountPay($value)
    {
        $this->amountPay = $value;
    }

	public function setText($value)
	{
        $value = htmlspecialchars($value, ENT_XML1);
		$this->validateItem('text', $value, 240);
		$this->text = $value;
	}

	public function setProviderIdentity($value)
	{
		if (isset($value['company'])) {
			$this->validateItem('provider - company', $value['company'], 96);
		}
		if (isset($value['ico'])) {
			$value['ico'] = $this->removeSpaces($value['ico']);
			$this->validateItem('provider - ico', $value['ico'], 15, true);
		}
		if (isset($value['street'])) {
			$this->validateItem('provider - street', $value['street'], 64);
		}
		if (isset($value['zip'])) {
			$value['zip'] = $this->removeSpaces($value['zip']);
			$this->validateItem('provider - zip', $value['zip'], 15, true);
		}
		if (isset($value['city'])) {
			$this->validateItem('provider - city', $value['city'], 45);
		}
		$this->myIdentity = $value;
	}

	/**
	 * @throws InvoiceException
	 * @param array $customerAddress
	 * @param null $identity
	 * @param array $shippingAddress
	 */
	public function createCustomerAddress(array $customerAddress, $identity = null)
	{
		try {
			$address = new Address(
				new \Pohoda\Object\Identity(
					$identity, //identifikator zakaznika [pokud neni zadan, neprovede se import do adresare]
					new \Pohoda\Object\Address($customerAddress) //adresa zakaznika
				)
			);

			$this->setCustomerAddress($address);

			return $address;

		} catch (\InvalidArgumentException $e) {
			throw new InvoiceException($this->getId() . ": " . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Get shop identity
	 * @return array
	 */
	public function getProviderIdentity()
	{
		return $this->myIdentity;
	}


	/**
	 * @param Address $address
	 */
	public function setCustomerAddress(Address $address)
	{
		$this->customerAddress = $address;
	}

	/** storno */
	public function cancelDocument($id)
	{
		$this->cancel = $id;
	}

	public function cancelNumber($number)
	{
		$this->cancelNumber = $number;
	}


	public function export(SimpleXMLElement $xml)
	{
		$xmlBankTrans = $xml->addChild("bnk:bank", null, self::NS);
		$xmlBankTrans->addAttribute('version', "2.0");

		if($this->cancel) {
            $xmlBankTrans
				->addChild('cancelDocument')
				->addChild('sourceDocument', null, Export::NS_TYPE)
				->addChild('number',  $this->cancel, Export::NS_TYPE);
			$this->exportCancelHeader($xmlBankTrans->addChild("bnk:bankHeader", null, self::NS));
		} else {
			$this->exportHeader($xmlBankTrans->addChild("bnk:bankHeader", null, self::NS));
			$this->exportSummary($xmlBankTrans->addChild("bnk:bankSummary", null, self::NS));
		}

	}

	private function exportCancelHeader(SimpleXMLElement $header)
	{
		$header->addChild("bankType", $this->type);
		if($this->cancelNumber !== null) {
			$num = $header->addChild("bnk:number");
			$num->addChild('typ:ids', $this->cancelNumber, Export::NS_TYPE);
		}

	}

	private function exportHeader(SimpleXMLElement $header)
	{
        $classification = $header->addChild("bnk:classificationVAT");
        $classification->addChild('typ:ids', 'UN', Export::NS_TYPE);
        $classification->addChild('typ:classificationVATType', 'nonSubsume', Export::NS_TYPE);

		$header->addChild("bnk:bankType", $this->type);
		$header->addChild("bnk:symVar", $this->symVar);
        $header->addChild("bnk:symConst", $this->symConst);
        $header->addChild("bnk:symSpec", $this->symSpec);

		$header->addChild("bnk:dateStatement", $this->dateStatement);
		if (!is_null($this->datePay))
			$header->addChild("bnk:datePayment", $this->datePay);

		$header->addChild("bnk:text", $this->text);

		$myIdentity = $header->addChild("bnk:myIdentity");
		$this->exportAddress($myIdentity, $this->myIdentity);

		$partnerIdentity = $header->addChild("bnk:partnerIdentity");
		$this->customerAddress->exportAddress($partnerIdentity);

		$paymentAccount = $header->addChild("bnk:paymentAccount");
        $paymentAccount->addChild('typ:accountNo', $this->paymentAccountNumber, Export::NS_TYPE);
        $paymentAccount->addChild('typ:bankCode', $this->paymentBankCode, Export::NS_TYPE);

	}

	private function exportAddress(SimpleXMLElement $xml, Array $data, $type = "address")
	{

		$address = $xml->addChild('typ:' . $type, null, Export::NS_TYPE );

		if (isset($data['company'])) {
			$address->addChild('typ:company', $data['company']);
		}

		if (isset($data['name'])) {
			$address->addChild('typ:name', $data['name']);
		}

		if (isset($data['division'])) {
			$address->addChild('typ:division', $data['division']);
		}

		if (isset($data['city'])) {
			$address->addChild('typ:city', $data['city']);
		}

		if (isset($data['street'])) {
			$address->addChild('typ:street', $data['street']);
		}

		if (isset($data['number'])) {
			$address->addChild('typ:number', $data['number']);
		}

		if (isset($data['country'])) {
			$address->addChild('typ:country')->addChild('typ:ids', $data['country']);
		}

		if (isset($data['zip'])) {
			$address->addChild('typ:zip', $data['zip']);
		}

		if (isset($data['ico'])) {
			$address->addChild('typ:ico', $data['ico']);
		}

		if (isset($data['dic'])) {
			$address->addChild('typ:dic', $data['dic']);
		}

		if (isset($data['phone'])) {
			$address->addChild('typ:mobilPhone', $data['phone']);
		}

		if (isset($data['email'])) {
			$address->addChild('typ:email', $data['email']);
		}

	}

	private function exportSummary(SimpleXMLElement $summary)
	{
	    
		$hc = $summary->addChild("bnk:homeCurrency");
		if (is_null($this->amountPay) === false)
			$hc->addChild('typ:priceNone', $this->amountPay, Export::NS_TYPE); //cena v nulove sazbe dph

	}
}

class BankTransactionException extends \Exception {};

