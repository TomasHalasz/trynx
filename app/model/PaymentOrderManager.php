<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;

/**
 * Paymnet Order management.
 */
class PaymentOrderManager extends Base
{
	const COLUMN_ID = 'id';
    private $CRLF;
	public $tableName = 'cl_payment_order';

    /** @var App\Model\PaymentOrderItemsManager */
    public $paymentOrderItemsManager;

    /** @var App\Model\InvoiceManager */
    public $invoiceManager;

    /** @var App\Model\InvoiceArrivedManager */
    public $invoiceArrivedManager;

    /** @var App\Model\PairedDocsManager */
    public $pairedDocsManager;

    private $settings;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                PaymentOrderItemsManager $paymentOrderItemsManager, InvoiceManager $invoiceManager, InvoiceArrivedManager $invoiceArrivedManager,
                                PairedDocsManager  $pairedDocsManager, CompaniesManager $CompaniesManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->paymentOrderItemsManager     = $paymentOrderItemsManager;
        $this->invoiceManager               = $invoiceManager;
        $this->invoiceArrivedManager        = $invoiceArrivedManager;
        $this->pairedDocsManager            = $pairedDocsManager;
        $this->settings                     = $CompaniesManager->getTable()->fetch();

    }



    public function insertInvoicesArrived($dataItems, $parentId){
        $arrDataItems = json_decode($dataItems, true);
        $tmpItems = $this->paymentOrderItemsManager->findAll()->where('cl_payment_order_id', $parentId);
        $counter = 0;
        $counterMax = count($tmpItems);
        $tmpParent = $this->find($parentId);
        foreach ($arrDataItems as $keyD => $oneD) {
            $tmpInvoice = $this->invoiceArrivedManager->find($oneD);
            if ($tmpInvoice) {
                $arrNew = ['cl_company_id'          => $tmpInvoice['cl_company_id'],
                            'item_order'            => $counterMax,
                            'cl_payment_order_id'   => $parentId,
                            'cl_invoice_arrived_id' => $tmpInvoice['id'],
                            'account_code'          => $tmpInvoice->cl_partners_account['account_code'],
                            'bank_code'             => $tmpInvoice->cl_partners_account['bank_code'],
                            'iban_code'             => $tmpInvoice->cl_partners_account['iban_code'],
                            'swift_code'            => $tmpInvoice->cl_partners_account['swift_code'],
                            'spec_symb'             => $tmpInvoice['spec_symb'],
                            'var_symb'              => $tmpInvoice['var_symb'],
                            'konst_symb'            => $tmpInvoice['konst_symb'],
                            'amount'                => abs($tmpInvoice['price_e2_vat']) - abs($tmpInvoice['price_payed']),
                            'cl_currencies_id'      => $tmpInvoice['cl_currencies_id'],
                            'pay_date'              => $tmpInvoice['due_date'],
                            'description_txt'       => $tmpInvoice['inv_title']
                        ];
                if ($tmpParent['pay_date_fixed'] == 1){
                    $arrNew['pay_date'] = $tmpParent['pay_date'];
                }
                $this->paymentOrderItemsManager->insert($arrNew);
                $tmpInvoice->update(['cl_payment_order_id' => $parentId]);
                $counterMax++;
                $counter++;
                $this->pairedDocsManager->insertOrUpdate(['cl_payment_order_id' => $parentId, 'cl_invoice_arrived_id' => $tmpInvoice['id']]);
            }
        }
        return ['status' => ($counter > 0) ? 'OK' : 'ERROR', 'data' => ['counter' => $counter]];
    }

    public function insertInvoices($dataItems, $parentId){
        $arrDataItems = json_decode($dataItems, true);
        $tmpItems = $this->paymentOrderItemsManager->findAll()->where('cl_payment_order_id', $parentId);
        $counter = 0;
        $counterMax = count($tmpItems);
        $tmpParent = $this->find($parentId);
        foreach ($arrDataItems as $keyD => $oneD) {
            $tmpInvoice = $this->invoiceManager->find($oneD);
            if ($tmpInvoice) {
                $arrNew = ['cl_company_id'  => $tmpInvoice['cl_company_id'],
                    'item_order'            => $counterMax,
                    'cl_payment_order_id'   => $parentId,
                    'cl_invoice_id'         => $tmpInvoice['id'],
                    'account_code'          => $tmpInvoice->cl_partners_account['account_code'],
                    'bank_code'             => $tmpInvoice->cl_partners_account['bank_code'],
                    'iban_code'             => $tmpInvoice->cl_partners_account['iban_code'],
                    'swift_code'            => $tmpInvoice->cl_partners_account['swift_code'],
                    'spec_symb'             => $tmpInvoice['spec_symb'],
                    'var_symb'              => $tmpInvoice['var_symb'],
                    'konst_symb'            => $tmpInvoice['konst_symb'],
                    'amount'                => abs($tmpInvoice['price_e2_vat']) - abs($tmpInvoice['price_payed']),
                    'cl_currencies_id'      => $tmpInvoice['cl_currencies_id'],
                    'pay_date'              => $tmpInvoice['due_date'],
                    'description_txt'       => $tmpInvoice['inv_title']
                ];
                if ($tmpParent['pay_date_fixed'] == 1){
                    $arrNew['pay_date'] = $tmpParent['pay_date'];
                }
                $this->paymentOrderItemsManager->insert($arrNew);
                $tmpInvoice->update(['cl_payment_order_id' => $parentId]);
                $counterMax++;
                $counter++;
                $this->pairedDocsManager->insertOrUpdate(['cl_payment_order_id' => $parentId, 'cl_invoice_id' => $tmpInvoice['id']]);
            }
        }
        return ['status' => ($counter > 0) ? 'OK' : 'ERROR', 'data' => ['counter' => $counter]];
    }

    public function exportGPC($id)
    {
        $arrRet  = [];
        try {
            $tmpData = $this->find($id);
            if (!$tmpData)
                throw new Exception();

            $this->CRLF = PHP_EOL;
            $tmpNow = new \DateTime();
            $lcDate = $tmpNow->format('dmy');
            //UHL
            //$lcUcet = LEFT(viw_exportb . u_ucet, AT("/", viw_exportb . u_ucet) - 1)
            $lcUcet = trim($tmpData->cl_bank_accounts['account_number']); // . $tmpData['bank_code'];
            //$lcUcet = RIGHT(ALLTRIM(lcUcet), 10) && zajima nas jen cas za prvni pomlckou, to by melo byt deset znaku
            if (iconv_strlen($lcUcet)  <= 10){
                //$lcUcet = REPLICATE("0", 10 - LEN(lcUcet)) + lcUcet && pokud neni doplnime na deset znaku
                $lcUcet = str_repeat('0', 10 - iconv_strlen($lcUcet)) . $lcUcet;
            }
            $lcName = iconv_substr($this->settings['name'],0,20);
            if (iconv_strlen($lcName) < 20){
                $lcName = $lcName . str_repeat(' ', 20 - iconv_strlen($lcName));
            }
            //$lcStr = "UHL1" . $lcDate . $lcName . "1234567890" . "001" . "999" . "000000" . "000000" . $this->CRLF;
            //24.01.2023 - length of last segment shortened by 1 zero to solve problem with ČS 0800 bank. (customer Daně a finnace)
            $lcStr = "UHL1" . $lcDate . $lcName . "1234567890" . "001" . "999" . "000000" . "00000" . $this->CRLF;

            //**hlavička účetního souboru
            //$lcUcet=ALLTRIM(SUBSTR(viw_exportb.u_ucet,AT("/",viw_exportb.u_ucet)+1,4))
            $lcUcet = $tmpData->cl_bank_accounts['bank_code'];
            if (iconv_strlen($lcUcet) < 4){
                $lcUcet = str_repeat('0', 4 - iconv_strlen($lcUcet)) . $lcUcet;
            }

            //***22.07.2010 - navysime pocitadlo transakci
            //replace tbl_ucty.cis_Tran	WITH IIF(tbl_ucty.cis_tran+1>=999,1,tbl_ucty.cis_tran+1)
            //=TABLEUPDATE()
            $tmpAccount = $tmpData->cl_bank_accounts;
            $transCount = $tmpAccount['trans_count'] + 1;
            if ($transCount >= 999)
                $transCount = 1;

            $tmpAccount->update(['trans_count' => $transCount]);

            //SELECT viw_exportb
            //**20.11.2020 - export Georger kompatibilta
            /*lnCisTran=tbl_ucty.cis_tran
            IF lnCisTran > 999
                lnCisTran = RIGHT(ALLTRIM(STR(lnCisTran)),3)
            ELSE
                lcCisTran=ALLTRIM(STR(lnCisTran,3,0))
            ENDIF*/
            $lcTransCount = (string)$transCount;
            //lcCisTran=REPLICATE("0",3-LEN(lcCisTran))+lcCisTran
            $lcTransCount = str_repeat('0', 3 - iconv_strlen($lcTransCount)) . $lcTransCount;
            $lcStr = $lcStr . '1 1501 ' . $lcTransCount . '000 ' . $lcUcet . $this->CRLF;

            //$OldDatSpl = CTOD("//");
            $OldDatSpl = new \DateTime();
            $OldDatSpl->setTime(0,0);
            $OldDatSpl->setDate(2000,1,1);
            $OldDatSpl = NULL;
            foreach($tmpData->related('cl_payment_order_items')->order('pay_date') as $key => $one){
                //if (viw_exportb.datum_s<>OldDatSpl)
                if ($one['pay_date'] != $OldDatSpl || is_null($OldDatSpl)){

                   if (!is_null($OldDatSpl)) {
                       /*IF viw_exportb.datum_s<>OldDatSpl
                        ***patička skupiny pokud je datum splatnosti jine od minuleho a nejde o první záznam
                        lcStr=lcStr+"3 +"+CRLF
                    ENDIF*/
                       $lcStr = $lcStr . '3 +' . $this->CRLF;
                   }


                    //***hlavička skupiny pokud je datum splatnosti jine od minuleho
                    //lcUcet=LEFT(viw_exportb.u_ucet,AT("/",viw_exportb.u_ucet)-1)
                    $tmpAccNum = $tmpData->cl_bank_accounts['account_number'];
                    $lcUcet = $tmpAccNum;
                    //lcUcet1=ALLTRIM(SUBSTR(lcUcet,AT("-",lcUcet)+1,20)) &&cast cisla uctu za pomlckou
                    if (iconv_strpos($tmpAccNum, '-') > 0) {
                        $lcUcet1 = substr($tmpAccNum, iconv_strpos($tmpAccNum, '-') + 1);
                    }else{
                        $lcUcet1 = $tmpAccNum;
                    }
                    //lcUcet2=LEFT(lcUcet,AT("-",lcUcet)-1) &&cast cisla uctu pred pomlckou
                    $lcUcet2 = iconv_substr($tmpAccNum, 0, iconv_strpos($tmpAccNum, '-'));
                    //IF LEN(lcUcet1)>=10
                    if (iconv_strlen($lcUcet1) < 10) {
                        //lcUcet=REPLICATE("0",10-LEN(lcUcet1))+lcUcet1 &&doplnime cast za pomlckou nulami do deseti znaku
                        $lcUcet = str_repeat("0", 10 - iconv_strlen($lcUcet1)) . $lcUcet1;
                    }else {
                        //lcUcet = lcUcet1
                        $lcUcet = $lcUcet1;
                    }
                    bdump($lcUcet);
                    //IF !EMPTY(lcUcet2)
                    if (!empty($lcUcet2)) {
                        //lcUcet = lcUcet2 + "-" + lcUcet && pridame pomlcku a cast pred pomlckou
                        $lcUcet = $lcUcet2 . '-' . $lcUcet;
                    }
                    bdump($lcUcet);
                    //IF LEN(lcUcet)>=17
                    if (iconv_strlen($lcUcet) < 17) {
                        //lcUcet=REPLICATE("0",17-LEN(lcUcet))+lcUcet	  &&doplnime do sedmnacti znaku
                        $lcUcet = str_repeat('0', 17 - iconv_strlen($lcUcet)) . $lcUcet;
                    }
                    bdump($lcUcet);

                    //lcDatSpl=IIF(day(viw_exportb.datum_s)<10,"0","")+ALLTRIM(STR(DAY(viw_exportb.datum_s)))+IIF(MONTH(viw_exportb.datum_s)<10,"0","")+ALLTRIM(STR(month(viw_exportb.datum_s)))+right(STR(year(viw_exportb.datum_s)),2)
                    $lcDatSpl = $one['pay_date']->format('dmy');
                    $lnCelkem = 0;
                    //$OldDatSpl = viw_exportb.datum_s
                    $OldDatSpl = $one['pay_date'];
                    //lnRecno=RECNO()
                    /*CALCULATE SUM(viw_exportb.castka) TO lnCelkem FOR viw_Exportb.datum_s=OldDatSpl
                                                                      GO lnRecno
                                lcCelkem=ALLTRIM(STR(lnCelkem*100,12,0))
                                    **01.08.2010 - uprava kvuli csas doplneni na max. pocet znaku nulami
                     *
                     */
                    $lnCelkem = $tmpData->related('cl_payment_order_items')->where('pay_date = ? ', $one['pay_date'])->sum('amount');
                    //$lcCelkem = (string)(int)($lnCelkem*100);
                    $lcCelkem = (string)(int)round($lnCelkem * 100);
                    //IF LEN(lcCelkem)>=14
                    if (iconv_strlen($lcCelkem) < 14) {
                        //lcCelkem=REPLICATE("0",14-LEN(lcCelkem))+lcCelkem
                        $lcCelkem = str_repeat('0', 14 - iconv_strlen($lcCelkem)) . $lcCelkem;
                    }
                    //lcStr=lcStr+"2 "+lcUcet+" "+lcCelkem+" "+lcDatSpl+CRLF
                    $lcStr = $lcStr  . '2 ' . $lcUcet . ' ' . $lcCelkem . ' ' . $lcDatSpl . $this->CRLF;
                }
                //***položka platebního příkazu
                //**	lcUcet=LEFT(viw_exportb.u_ucet,AT("/",viw_exportb.u_ucet)-1)

                //lcPUcet=LEFT(viw_exportb.p_ucet,AT("/",viw_exportb.p_ucet)-1)
                $lcPUcet =  $one['account_code'];
                //lcPucet1=ALLTRIM(SUBSTR(lcPUcet,AT("-",lcPUcet)+1,20)) &&cast cisla uctu za pomlckou
                if (iconv_strpos($lcPUcet, '-') > 0) {
                    $lcPUcet1 = iconv_substr($lcPUcet, iconv_strpos($lcPUcet, '-') + 1);
                }else{
                    $lcPUcet1 = $lcPUcet;
                }
                //lcPucet2=LEFT(lcPUcet,AT("-",lcPUcet)-1) &&cast cisla uctu pred pomlckou
                $lcPUcet2 = iconv_substr($lcPUcet, 0, iconv_strpos($lcPUcet, '-')  );
                //dump($lcPUcet1);
                //dump($lcPUcet2);
                //IF LEN(lcPUcet1)<=10
                //IF (strlen($lcPUcet1 < 10))
                //{
                    //lcPUcet=REPLICATE("0",10-LEN(lcPUcet1))+lcPUcet1
                    //***pomlcku pridavame pouze pokud je mene znaku v cisle uctu nez deset
                    //lcPUcet=lcPucet2+"-"+lcPucet
                    $lcPUcet = str_repeat('0', 10 - iconv_strlen($lcPUcet1)) . $lcPUcet1;
                    $lcPUcet = $lcPUcet2 . '-' . $lcPUcet;
                //}else{
                    //lcPUcet=lcPUcet1
                    //$lcPUcet = $lcPUcet1;
                //}
                //IF LEN(lcPUcet)<=17
                if (iconv_strlen($lcPUcet) < 17){
                    //lcPUcet =REPLICATE("0",17-LEN(lcPUcet))+lcPUcet
                    $lcPUcet = str_repeat('0', 17 - iconv_strlen($lcPUcet)) . $lcPUcet;
                }
                //lnCastka=ALLTRIM(STR(viw_exportb.castka*100,12,0))
                //$lnCastka = str_replace('.','', $one['amount']);
                //bdump($one['amount']);
                //bdump(((float)$one['amount'])*100);

                //bdump((int)round(($one['amount']*100)));
                //TH 07.02.2023 - chyba kdy při vynásobení 321391,10 krát 100 dává PHP výsledek  32139109.999999996
                $lnCastka = (string)(int)round($one['amount'] * 100);
                //bdump($lnCastka);
                //IF LEN(lnCastka)<=12
                if (iconv_strlen($lnCastka) < 12){
                    //lnCastka=REPLICATE("0",12-LEN(lnCastka))+lnCastka
                    $lnCastka = str_repeat('0', 12 - iconv_strlen($lnCastka)) . $lnCastka;
                }
                //lcKs=ALLTRIM(viw_exportb.ks)
                $lcKs = $one['konst_symb'];
                //IF EMPTY(lcKs)
                if ($lcKs == '') {
                    //lcKs = "0008"
                    $lcKs = '0008';
                }

                //lcSS=ALLTRIM(viw_exportb.ss)
                $lcSS = $one['spec_symb'];
                //IF EMPTY(lcSS)
                if ($lcSS == ''){
                    //lcSS="0000000000"
                    $lcSS = '0000000000';
                }
                //lcKs=SUBSTR(viw_exportb.p_ucet,AT("/",viw_exportb.p_ucet)+1,4)+REPLICATE("0",4-LEN(lcKs))+lcKs
                $lcKs = str_repeat('0', 4 - iconv_strlen($one['bank_code'])) . $one['bank_code'] . str_repeat('0', 4 - iconv_strlen($lcKs)) . $lcKs;
                //lcVars=LEFT(ALLTRIM(viw_exportb.vs),10)
                $lcVars = $one['var_symb'];
                //IF LEN(lcVars)<=10
                if (iconv_strlen($lcVars) < 10) {
                    //lcVars=REPLICATE("0",10-LEN(lcVars))+lcVars
                    $lcVars = str_repeat('0', 10 - iconv_strlen($lcVars)) . $lcVars;
                }
                //lcStr=lcStr+lcPUcet+" "+lnCastka+" "+lcVars+" "+;
                //        lcKs+" "+;
                //        lcSs+" "
                $lcStr = $lcStr . $lcPUcet . ' ' . $lnCastka . ' ' . $lcVars . ' ' . $lcKs . ' ' . $lcSS . ' ';

                /*DO CASE
                    CASE "/0800"$viw_exportb.u_ucet
                            ***05.01.2016 - exportujeme do České spořitelny
                            **03.09.2015 - úprava, AV: není nutné
                        lcStr = lcStr + ALLTRIM(LEFT(viw_exportb.popis_p1,35)) + CRLF
                    OTHERWISE
                        lcStr = lcStr + "AV:" + ALLTRIM(LEFT(viw_exportb.popis_p1,35)) + CRLF
                ENDCASE
                */
                $txtDescription = Strings::toAscii($one['description_txt']);
                if ($tmpData->cl_bank_accounts['bank_code'] == '0800'){
                    $lcStr = $lcStr . substr($txtDescription,0,35) . $this->CRLF;
                }else{
                    $lcStr = $lcStr . 'AV:' . substr($txtDescription,0,35) . $this->CRLF;
                }

                /*IF viw_exportb.datum_s<>OldDatSpl
                    ***patička skupiny pokud je datum splatnosti jine od minuleho
                    lcStr=lcStr+"3 +"+CRLF
                ENDIF
                Patička je nyní nahoře.
                */
            }
                 //***patička skupiny pokud je datum splatnosti jine od minuleho a nejde o první záznam
                $lcStr = $lcStr . '3 +' . $this->CRLF;


            /***konec účetního souboru
            lcStr=lcStr+"5 +"+CRLF
                **zapiseme do souboru
            lnBytes=STRTOFILE(lcStr,&cFileName)
            */
            $lcStr = $lcStr . '5 +' . $this->CRLF;
            $arrRet = ['success' => ['data' => $lcStr]];
        }catch(Exception $e){
            $arrRet = ['error' => 'Error GPC generator'];
            Debugger::log($e->getMessage(), 'GPCExport');
        }
        return $arrRet;
    }


}

