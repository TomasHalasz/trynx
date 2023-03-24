<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Transport management.
 */
class TransportManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_transport';

    public $TransportDocsManager;
    /** @var App\Model\TransportDocsManager */

    public $TransportItemsBackManager;
    /** @var App\Model\TransportItemsBackManager */

    public $TransportCashManager;
    /** @var App\Model\TransportCashManager */

    public $NumberSeriesManager;
    /** @var App\Model\NumberSeriesManager */

    public $StatusManager;
    /** @var App\Model\StatusManager */


    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;


    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                TransportDocsManager $transportDocsManager, \Nette\Http\Session $session, ArraysManager $ArraysManager,
                                TransportItemsBackManager $transportItemsBackManager, TransportCashManager $transportCashManager,
                                NumberSeriesManager $numberSeriesManager, StatusManager $statusManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->TransportDocsManager         = $transportDocsManager;
        $this->TransportCashManager         = $transportCashManager;
        $this->TransportItemsBackManager    = $transportItemsBackManager;
        $this->ArraysManager                = $ArraysManager;
        $this->NumberSeriesManager          = $numberSeriesManager;
        $this->StatusManager                = $statusManager;
    }


	public function transportSum($cl_transport_types_name = NULL, $cl_company_id = NULL, $cl_transport_id = NULL)
    {
        $data = $this->TransportDocsManager->findAllTotal()->select('SUM(price_payed) AS price_payed, cl_transport.given_cash')->
                                            where('cl_transport_docs.cl_company_id = ?', $cl_company_id);
        if (!is_null($cl_transport_id))
        {
            $data = $data->where('cl_transport.id = ?', $cl_transport_id)->
                            fetch();
        }else{
            $data = $data->where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
                            where('cl_transport.cl_status.s_fin = 0 AND cl_transport.cl_status.s_storno = 0')->
                            where('cl_transport_docs.payed = 1 AND cl_transport_docs.delivered = 1')->
                            fetch();
        }

        $totalSum = 0;
        //bdump($data);
        if ($data) {
            $totalSum += $data['price_payed'];
        }

        /*$data = $this->TransportItemsBackManager->findAllTotal()->select('SUM(price_e2_vat) AS price_e2_vat, cl_transport.given_cash')->
                                                where('cl_transport_items_back.cl_company_id = ?', $cl_company_id);

        if (!is_null($cl_transport_id))
        {
            $data = $data->where('cl_transport.id = ?', $cl_transport_id)->
                        fetch();
        }else {
            $data = $data->where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
            where('cl_transport.cl_status.s_fin = 0 AND cl_transport.cl_status.s_storno = 0')->
            fetch();
        }

        //bdump($data);
        if ($data) {
            $totalSum -= $data['price_e2_vat'];
        }*/

        $data = $this->TransportCashManager->findAllTotal()->select('SUM(amount) AS amount')->
                                            where('cl_transport_cash.cl_company_id = ?', $cl_company_id);
        if (!is_null($cl_transport_id))
        {
            $data = $data->where('cl_transport.id = ?', $cl_transport_id)->
                            fetch();
        }else {
            $data = $data->where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
                            where('cl_transport.cl_status.s_fin = 0 AND cl_transport.cl_status.s_storno = 0')->
                            fetch();
        }
        //bdump($data);
        if ($data) {
            $totalSum -= $data['amount'];
        }

        $data = $this->findAllTotal()->select('given_cash')->
                                        where('cl_transport.cl_company_id = ?', $cl_company_id);
        if (!is_null($cl_transport_id))
        {
            $data = $data->where('cl_transport.id = ?', $cl_transport_id)->
                    fetch();
        }else {
            $data = $data->where('cl_transport_types.name = ?', $cl_transport_types_name)->
                    where('cl_status.s_fin = 0 AND cl_status.s_storno = 0')->
                    fetch();
        }

        bdump($data);
        if ($data) {
            $totalSum += $data['given_cash'];
        }

        return $totalSum;
    }

    public function prepareDHL($data){
        $arrData = [];
        foreach($data as $key => $one){
            if (!is_null($one['cl_delivery_note_id']) ){
                $arrStreet = explode(' ',$one->cl_company['street']);
                $strNumberOnly = end($arrStreet);
                array_pop($arrStreet);
                $strStreetOnly = implode(' ', $arrStreet);


                if (!is_null($one->cl_delivery_note->cl_partners_branch_id)) {
                    $strNazev   = $one->cl_delivery_note->cl_partners_branch['b_name'] . ' ' . $one->cl_delivery_note->cl_partners_branch['b_person'];
                    $strUlice   = $one->cl_delivery_note->cl_partners_branch['b_street'];
                    $strPsc     = $one->cl_delivery_note->cl_partners_branch['b_zip'];
                    $strMesto   = $one->cl_delivery_note->cl_partners_branch['b_city'];
                    $strStat    = (!is_null($one->cl_delivery_note->cl_partners_branch['cl_countries_id'])) ? $one->cl_delivery_note->cl_partners_branch->cl_countries['vat_code'] : $one->cl_company->cl_countries['vat_code'];
                }else{
                    $strNazev   = $one->cl_delivery_note->cl_partners_book['company'];
                    $strUlice   = $one->cl_delivery_note->cl_partners_book['street'];
                    $strPsc     = $one->cl_delivery_note->cl_partners_book['zip'];
                    $strMesto   = $one->cl_delivery_note->cl_partners_book['city'];
                    $strStat    = (!is_null($one->cl_delivery_note->cl_partners_book['cl_countries_id'])) ? $one->cl_delivery_note->cl_partners_book->cl_countries['vat_code'] : $one->cl_company->cl_countries['vat_code'];
                }

                $arrStreet2 = explode(' ', $strUlice);
                $strNumberOnly2 = end($arrStreet2);
                array_pop($arrStreet2);
                $strStreetOnly2 = implode(' ', $arrStreet2);



                $arrData[] = [  'produkt' => 'LD',
                                'zakaznicka_reference' => '',
                                'odesilatel_nazev' => $one->cl_company['name'],
                                'odesilatel_sekundarni_nazev' => '',
                                'odesilatel_ulice' => $strStreetOnly,
                                'odesilatel_cislo_popisne' => $strNumberOnly,
                                'odesilatel_misto_urceni' => '',
                                'odesilatel_mesto' => $one->cl_company['city'],
                                'odesilatel_psc' => $one->cl_company['zip'],
                                'odesilatel_stat' => (!is_null($one->cl_company['cl_countries_id'])) ?$one->cl_company->cl_countries['vat_code'] : '',
                                'odesilatel_kontaktni_osoba' => $one->cl_company['contact_person'],
                                'odesilatel_telefon' => $one->cl_company['telefon'],
                                'odesilatel_email' => $one->cl_company['email'],
                                'prijemce_nazev' => $strNazev,
                                'prijemce_sekundarni_nazev' => '',
                                'prijemce_ulice' => $strStreetOnly2,
                                'prijemce_cislo_popisne' => $strNumberOnly2,
                                'prijemce_misto_urceni' => '',
                                'prijemce_mesto' => $strMesto,
                                'prijemce_psc' => $strPsc,
                                'prijemce_stat'=> $strStat,
                                'prijemce_kontaktni_osoba' => (!empty($one->cl_delivery_note->cl_partners_book['person'])) ? $one->cl_delivery_note->cl_partners_book['person'] : $one->cl_delivery_note->cl_partners_book['company'],
                                'prijemce_telefon' => $one->cl_delivery_note->cl_partners_book['phone'],
                                'prijemce_email' => $one->cl_delivery_note->cl_partners_book['email'],
                                'kusy_pocet'    => ($one['package_count'] > 0) ? $one['package_count'] : 1,
                                'kusy_obal'     => ($one['package_type'] == 0) ?
                                                            ((!is_null($one->cl_transport['cl_transport_types_id'])) ?
                                                                $this->ArraysManager->getPackageTypeName($one->cl_transport->cl_transport_types['package_type']) : 'balík') : $this->ArraysManager->getPackageTypeName($one['package_type']),
                                'kusy_popis'     => ($one['package_descr'] == '') ?
                                                            ((!is_null($one->cl_transport['cl_transport_types_id'])) ?
                                                                $one->cl_transport->cl_transport_types['package_descr'] : '') : $one['package_descr'],
                                'kusy_kody'     => '',
                                'kusy_vyska'     => 1,
                                'kusy_sirka'     => 1,
                                'kusy_delka'     => 1,
                                'kusy_vaha'     => ($one['weight'] > 0) ? $one['weight'] : 3,
                                'dobirka_castka'     => (!is_null($one->cl_delivery_note['cl_invoice_id']) &&
                                                         !is_null($one->cl_delivery_note['cl_payment_types_id']) && $one->cl_delivery_note->cl_payment_types['payment_type'] == 2) ?
                                                                $one->cl_delivery_note->cl_invoice['price_e2_vat'] : 0,
                                'dobirka_mena'     => (!is_null($one->cl_delivery_note['cl_currencies_id']) &&
                                                        !is_null($one->cl_delivery_note['cl_payment_types_id']) && $one->cl_delivery_note->cl_payment_types['payment_type'] == 2) ?
                                                                $one->cl_delivery_note->cl_currencies['currency_name'] : '',
                                'dobirka_variabilni_symbol'     => (!is_null($one->cl_delivery_note['cl_invoice_id']) &&
                                                                    !is_null($one->cl_delivery_note['cl_payment_types_id']) &&
                                                                    $one->cl_delivery_note->cl_payment_types['payment_type'] == 2) ?
                                                                            $one->cl_delivery_note->cl_invoice['var_symb'] : '',
                                'sluzba_dokumenty_zpet' => '',
                                'sluzba_pripojisteni' => '',
                                'sluzba_eur_palety_zpet_pocet' => '',
                                'adr_informace' => '',
                                'preprave_hotove' => '',
                                'manipulace_svoz_hydraulicke_celo' => '',
	                            'manipulace_svoz_paletovy_vozik' => '',
                                'manipulace_rozvoz_hydraulicke_celo' => '',
                                'manipulace_rozvoz_paletovy_vozik' => '',
                                'sluzba_vynos_do_patra' => '',
                                'sluzba_odvoz_stareho_spotrebice' => '',
	                            'sms_prijemci' => '',
                                'depo_osobniho_podani' => '',
                                'depo_osobniho_odberu' => '',
                                'sluzba_doklad_o_dodani_pod' => '',
                                'sluzba_informace_o_dodani_iod' => '',
                                'sluzba_pripojisteni_na_110_%_ceny_zbozi' => '',
	                            'sluzba_dodani_proti_dokumentum' => '',
                                'sluzba_celni_odbaveni' => '',
                                'parita_incoterms' => '',
                                'datum_doruceni' => '',
                                'hodnota_zasilky' => $one->cl_delivery_note->cl_invoice['price_e2_vat'] ,
                                'hodnota_zasilky_mena' => (!is_null($one->cl_delivery_note['cl_currencies_id'])) ?
                                                                $one->cl_delivery_note->cl_currencies['currency_name'] : $one->cl_delivery_note->cl_company->cl_currencies['currency_name'],
                                'nepouzito' => '',
                                'nepouzito2' => '',
                                'nepouzito3' => '',
                                'poznamka_k_zasilce' => '',
                                'fakturace_nazev' => '',
                                'fakturace_sekundarni_nazev' => '',
                                'fakturace_ulice' => '',
                                'fakturace_cislo_popisne' => '',
                                'fakturace_misto_urceni' => '',
                                'fakturace_mesto' => '',
                                'fakturace_psc' => '',
	                            'fakturace_stat' => '',
                                'fakturace_kontaktni_osoba' => '',
                                'fakturace_telefon' => '',
                                'fakturace_email' => '',
                                'odesilatel_zakaznik' => '',
                                'prijemce_zakaznik' => '',
                                'fakturace_zakaznik' => '',
                ];
            }
        }
        return $arrData;
    }


    public function preparePPL($data)
    {
        $arrData = [];
        foreach ($data as $key => $one) {
            if (!is_null($one['cl_delivery_note_id'])) {
                //replace ALL viw_ppl.mesto 		WITH "KM"+ALLTRIM(viw_ppl.u_dbranch)+" "+viw_ppl.mesto FOR !EMPTY(viw_ppl.u_dbranch)

                if (!is_null($one->cl_delivery_note->cl_partners_branch_id)) {
                    $strNazev   = $one->cl_delivery_note->cl_partners_branch['b_name'] . ' ' . $one->cl_delivery_note->cl_partners_branch['b_person'];
                    $strUlice   = $one->cl_delivery_note->cl_partners_branch['b_street'];
                    $strPsc     = $one->cl_delivery_note->cl_partners_branch['b_zip'];
                    $strMesto   = $one->cl_delivery_note->cl_partners_branch['b_city'];
                    $strStat    = (!is_null($one->cl_delivery_note->cl_partners_branch['cl_countries_id'])) ? $one->cl_delivery_note->cl_partners_branch->cl_countries['vat_code'] : $one->cl_company->cl_countries['vat_code'];
                }else{
                    $strNazev   = $one->cl_delivery_note->cl_partners_book['company'];
                    $strUlice   = $one->cl_delivery_note->cl_partners_book['street'];
                    $strPsc     = $one->cl_delivery_note->cl_partners_book['zip'];
                    $strMesto   = $one->cl_delivery_note->cl_partners_book['city'];
                    $strStat    = (!is_null($one->cl_delivery_note->cl_partners_book['cl_countries_id'])) ? $one->cl_delivery_note->cl_partners_book->cl_countries['vat_code'] : $one->cl_company->cl_countries['vat_code'];
                }

                if (!is_null($one->cl_delivery_note['cl_store_docs_id']) && !is_null($one->cl_delivery_note->cl_store_docs['cl_commission_id']) && empty($one->cl_delivery_note->cl_store_docs->cl_commission['u_dbranch'])){
                    //$strMesto = $one->cl_delivery_note->cl_partners_book['city'];
                }else{
                    $strMesto = 'KM' . $one->cl_delivery_note->cl_store_docs->cl_commission['u_dbranch'] . ' ' . $one->cl_delivery_note->cl_partners_book['city'];
                }

                $arrData[] = [
                    'variabilni_symbol' => (!is_null($one->cl_delivery_note['cl_invoice_id']) &&
                        !is_null($one->cl_delivery_note['cl_payment_types_id']) &&
                        $one->cl_delivery_note->cl_payment_types['payment_type'] == 2) ?
                        $one->cl_delivery_note->cl_invoice['var_symb'] : '',
                    'dobirka_castka' => (!is_null($one->cl_delivery_note['cl_invoice_id']) &&
                        !is_null($one->cl_delivery_note['cl_payment_types_id']) && $one->cl_delivery_note->cl_payment_types['payment_type'] == 2) ?
                        $one->cl_delivery_note->cl_invoice['price_e2_vat'] : 0,
                    'dobirka_mena' => (!is_null($one->cl_delivery_note['cl_currencies_id']) &&
                        !is_null($one->cl_delivery_note['cl_payment_types_id']) && $one->cl_delivery_note->cl_payment_types['payment_type'] == 2) ?
                        $one->cl_delivery_note->cl_currencies['currency_name'] : '',
                    'prijemce_nazev' => $strNazev,
                    'prijemce_ulice' => $strUlice,
                    'prijemce_psc' => $strPsc,
                    'prijemce_mesto' => $strMesto,
                    'prijemce_stat' => $strStat,
                    'prijemce_kontaktni_osoba' => $one->cl_delivery_note->cl_partners_book['person'],
                    'prijemce_telefon' => $one->cl_delivery_note->cl_partners_book['phone'],
                    'kusy_pocet' => ($one['package_count'] > 0) ? $one['package_count'] : 1,
                    'prijemce_email' => $one->cl_delivery_note->cl_partners_book['email'],
                    'kusy_obal' => ($one['package_type'] == 0) ?
                        ((!is_null($one->cl_transport['cl_transport_types_id'])) ?
                            $this->ArraysManager->getPackageTypeNamePPL($one->cl_transport->cl_transport_types['package_type']) : 'balík') : $this->ArraysManager->getPackageTypeNamePPL($one['package_type']),
                    'kusy_vaha' => ($one['weight'] > 0) ? $one['weight'] : 0,
                    'u_dbranch' => $one->cl_delivery_note->cl_store_docs->cl_commission['u_dbranch']
                ];
            }
        }
        //dump($arrData);
        foreach($arrData as $key=>$one){
            $arrData[$key]['dobirka_mena'] = iconv("utf-8", "windows-1250", $one['dobirka_mena']);
            $arrData[$key]['prijemce_nazev'] = iconv("utf-8", "windows-1250", $one['prijemce_nazev']);
            $arrData[$key]['prijemce_ulice'] = iconv("utf-8", "windows-1250", $one['prijemce_ulice']);
            $arrData[$key]['prijemce_mesto'] = iconv("utf-8", "windows-1250", $one['prijemce_mesto']);
            $arrData[$key]['prijemce_stat'] = iconv("utf-8", "windows-1250", $one['prijemce_stat']);
            $arrData[$key]['prijemce_kontaktni_osoba'] = iconv("utf-8", "windows-1250", $one['prijemce_kontaktni_osoba']);
        }
        //dump($arrData);
        //die;
        return $arrData;
    }


    /**create new cl_transport if there is no open one for given cl_transport_types_id
     * @param $cl_transport_types_id
     * @return int
     */
    public function getOrCreateTransport($cl_transport_types_id){
        $tmpData = $this->findAll()->where('cl_status.s_new = 1 AND cl_status.s_fin = 0 AND cl_status.status_use = ? AND cl_transport_types_id = ?', 'transport', $cl_transport_types_id)->limit(1)->order('id DESC')->fetch();
        if ($tmpData){
            $idRet = $tmpData['id'];
        }else{
            //create new one
            $arrData['cl_transport_types_id']   = $cl_transport_types_id;
            $arrData['transport_date']          = new Nette\Utils\DateTime();

            $nSeries = $this->NumberSeriesManager->getNewNumber('transport');
            $arrData['cl_number_series_id']     = $nSeries['id'];
            $arrData['tn_number']			    = $nSeries['number'];


            if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?','transport',1)->fetch())
            {
                $arrData['cl_status_id']	    = $nStatus->id;
            }

            $dataNew = $this->insert($arrData);
            $idRet = $dataNew['id'];
        }

        return $idRet;
    }
	
}

