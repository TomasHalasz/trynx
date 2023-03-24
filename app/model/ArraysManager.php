<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;

/**
 * Arrays management.
 */
class ArraysManager
{

    /**Return archive for given key
     * @param string $key
     * @return false|string
     */
    public function getArchive(string $key): string {
        $arr = $this->getArchives();
        (array_key_exists($key, $arr )) ? $retVal = $arr[$key] : $retVal = FALSE;
        return $retVal;
    }

    /** Return array of archives
     * @return array[]
     */
    public function getArchives(): array {
        $arr = ['current'  => 'dbCurrent',
                    '2020'     => 'db2020'
        ];
        return $arr;
    }

    /**Return Tables to archive by date conditions
     * @return array
     */
    public function getArchTables(): array {
        //here are tables whose records will be copied to archive and deleted from original schema by date conditions,
        //any other tables records will be only copied to archive and NOT deleted from original schema
        $arr = ['cl_commission'        => ['tables'    => ['cl_commission_items', 'cl_commission_items_sel', 'cl_commission_task', 'cl_commission_work','cl_paired_docs', 'cl_files', 'cl_emailing', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'cm_date'],
                    'cl_offer'              => ['tables'    => ['cl_offer_items', 'cl_offer_task', 'cl_offer_work', 'cl_paired_docs', 'cl_files', 'cl_emailing', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'offer_date'],
                    'cl_order'              => ['tables'    => ['cl_order_items', 'cl_paired_docs', 'cl_files', 'cl_emailing', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'od_date'],
                    'cl_oro'                => ['tables'    => ['cl_oro_items', 'cl_oro_products'],
                                                'date'      => 'oznameni_za_den'],
                    'cl_delivery_note'      => ['tables'    => ['cl_delivery_note_items', 'cl_delivery_note_items_back', 'cl_delivery_note_payments', 'cl_paired_docs', 'cl_files', 'cl_emailing', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'issue_date'],
                    'cl_delivery_note_in'   => ['tables'    => ['cl_delivery_note_in_items', 'cl_delivery_note_in_items_back', 'cl_paired_docs', 'cl_files', 'cl_emailing', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'issue_date'],
                    'cl_inventory'          => ['tables'    => ['cl_inventory_items', 'cl_inventory_workplaces'],
                                                'date'      => 'date'],
                    'cl_invoice'            => ['tables'    => ['cl_invoice_items', 'cl_invoice_items_back', 'cl_invoice_payments', 'cl_paired_docs', 'cl_files', 'cl_emailing', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'inv_date'],
                    'cl_invoice_arrived'    => ['tables'    => ['cl_invoice_arrived_commission', 'cl_invoice_arrived_payments', 'cl_paired_docs', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'inv_date'],
                    'cl_invoice_advance'    => ['tables'    => ['cl_invoice_advance_items', 'cl_invoice_advance_payments', 'cl_paired_docs', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'inv_date'],
                    'cl_invoice_internal'    => ['tables'    => ['cl_invoice_internal_items', 'cl_invoice_internal_payments', 'cl_paired_docs', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'inv_date'],
                    'cl_partners_event'     => ['tables'    => ['cl_partners_event_users', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'date_rcv'],
                    'cl_transport'          => ['tables'    => ['cl_transport_docs', 'cl_transport_items_back', 'cl_transport_cash', 'cl_paired_docs', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'transport_date'],
                    'cl_sale'               => ['tables'    => ['cl_sale_items', 'cl_paired_docs', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'inv_date'],
                    'cl_eet'                => ['tables'    => [],
                                                'date'      => 'dat_eet'],
                    'cl_bank_trans'         => ['tables'    => ['cl_bank_trans_items'],
                                                'date'      => 'trans_date'],
                    'cl_payment_order'      => ['tables'    => ['cl_payment_order_items'],
                                                'date'      => 'pay_date'],
                    'cl_store_docs'         => ['tables'    => ['cl_store_move', 'cl_store_out' => ['cl_store_move_id'], 'cl_paired_docs', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'doc_date'],
                    'cl_cash'               => ['tables'    => ['cl_paired_docs', 'cl_files', 'cl_documents' => [':cl_documents_id']],
                                                'date'      => 'inv_date'],
        ];
        return $arr;
    }

    
    public function getCalendarPlaneType()
    {
        $arrStatus = [1 => 'Helpdesk',
                   2 => 'Kalendář'];
        return $arrStatus;
    }
    
    public function getPriceMethod()
    {
        $arrStatus = [0 => 'FiFo',
                   1 => 'VAP'];
        return $arrStatus;
    }    
    
    public function getPriceMethodName($id)
    {
	    return $this->getPriceMethod()[$id];
    }


    public function getInventoryDifference()
    {
        $arrStatus = [0 => 'neurčeno',
                            1 => 'manko',
                            2 => 'škoda',
                            3 => 'záměna'];
        return $arrStatus;
    }

    public function getInventoryDifferenceName($id)
    {
        return $this->getInventoryDifference()[$id];
    }

    public function getEshopType()
    {
        $arrStatus = [0 => '2HCS 3.0', 1 => 'Pohoda 2.0', 2 => 'Simple Store'];
        return $arrStatus;
    }

    public function getEshopTypeName($id)
    {
        return $this->getEshopType()[$id];
    }

	
	public function getDaysInflexion($days)
	{
		($days < 2) ? $strDays = "den" : (($days < 5) ? $strDays = "dny" : (($days < 6) ? $strDays = "dnů" :  $strDays = "dní")) ;
		
		return $strDays;
	}

    
    public function getDeliveryPeriodType()
    {
        $arrStatus = [0 => 'dny',
                   1 => 'týdny',
                   2 => 'měsíce'];
        return $arrStatus;
    }    
    
    public function getDeliveryPeriodTypeName($id)
    {
    	return $this->getDeliveryPeriodType()[$id];
    }            
    
    public function getDimUnits()
    {
        $arrStatus = [0 => 'cm',
                   1 => 'mm',
                   2 => 'dm',
                   3 => 'm',
                   4 => 'km'
        ];
        return $arrStatus;
    }    
    
    public function getDimUnitName($id)
    {
	return $this->getDimUnits()[$id];
    }            
    
    
    public function getWeightUnits()
    {
        $arrStatus = [0 => 'kg',
                   1 => 'gr',
                   2 => 'mg',
                   3 => 't'
        ];
        return $arrStatus;
    }    

    public function getWeightUnitName($id)
    {
	    return $this->getWeightUnits()[$id];
    }

    public function getWeightToBase($id=0){
        $arrStatus = [ 0 => 1,
                       1 => 1000,
                       2 => 1000*1000,
                       3 => 0.001];

        return $arrStatus[$id];
    }


    public function getVolumeUnits()
    {
        $arrStatus = [  0 => 'l',
                        1 => 'ml',
                        2 => 'dcl',
                        3 => 'hl',
                        4 => 'm3'
        ];
        return $arrStatus;
    }

    public function getVolumeUnitName($id)
    {
        return $this->getVolumeUnits()[$id];
    }

    public function getVolumeRatesToBase($id = 0)
    {
        $arrStatus = [  0 => 1,
            1 => 1000,
            2 => 10,
            3 => 100,
            4 => 1
        ];
        return $arrStatus[$id];
    }





    public function getOroPohybTypes()
    {
        $arrStatus = [0 => 'volný oběh',
                        1 => 'vrácení volného oběhu'
        ];
        return $arrStatus;
    }

    public function getOroPohybCodes()
    {
        $arrStatus = [0 => 'VolnyObeh',
                    1 => 'VraceniVolnehoObehu'
        ];
        return $arrStatus;
    }

    public function getOroPohybName($id)
    {
        return $this->getOroPohybTypes()[$id];
    }

    public function getOroPohybCode($id)
    {
        return $this->getOroPohybCodes()[$id];
    }


    public function getAfterLoginName($type)
    {
		$arrResult = $this->getAfterLogin();
		return $arrResult[$type][0];
    }    

    public function getAfterLoginPresenter($type)
    {
		$arrResult = $this->getAfterLogin();
		return $arrResult[$type][1];
    }        
	
    public function getAfterLoginArr()
    {    
	    $arrResult = $this->getAfterLogin();
	    $arrStatus = [];
	    foreach($arrResult as $key => $one)
	    {
		$arrStatus[$key] = $one[0]; 
	    }
	    return $arrStatus;	
    }
    
    public function getAfterLogin()
    {
	//'0-uvodni,1-faktury,2-zakazky,3-sklad,4-prodejna,5-objednavky,6-helpdesk,7-vseznalek
		$arrStatus = [NULL => ['', ''],
				   '0' => ['Úvodní', ':Application:Homepage:'],
				   '1' => ['Faktury', ':Application:Invoice:'],
				   '8' => ['Faktury přijaté', ':Application:InvoiceArrived:'],
				   '2' => ['Zakázky',  ':Application:Commission:'],
				   '3' => ['Sklad', ':Application:Store:'],
				   '4' => ['Prodejna', ':Application:Sale:'],
				   '5' => ['Objednávky', ':Application:Order:'],
				   '6' => ['Helpdesk', ':Application:Helpdesk:'],
                    '19' => ['Úkoly', ':Application:Task:'],
				   '7' => ['Všeználek', ':Application:Kdb:'],
                    '14' => ['Intranet - úvod', ':Intranet:Homepage:'],
                   '9' => ['Intranet - Docházka', ':Intranet:Staff:'],
                    '10' => ['Intranet - Majetek evidence', ':Intranet:Estate:'],
                    '17' => ['Intranet - Majetek pohyby', ':Intranet:EstateMoves:'],
                    '18' => ['Intranet - Majetek půjčovna', ':Intranet:Rental:'],
                    '16' => ['Intranet - Majetek rezervace', ':Intranet:Reservation:'],
                    '11' => ['Intranet - Zakázky', ':Intranet:Commission:'],
                    '12' => ['Intranet - Zaměstnanci plán', ':Intranet:StaffPlan:'],
                    '13' => ['Intranet - Dokumentace', ':Intranet:Folders:'],
                    '15' => ['Intranet - Reklamace', ':Intranet:Complaint:'],
        ];
		return $arrStatus;	
    }	


    public function getModNames4Rights(){
        $arr = [ ['group' => 'Faktury', 'items' => [
                            ['presenter' => 'application_invoice', 'data_table' => 'cl_invoice', 'name' => 'Faktury vydané'],
                            ['presenter' => 'application_invoice_paymentlistgrid', 'data_table' => 'cl_invoice_payment', 'name' => 'Úhrady faktur vydaných'],
                            ['presenter' => 'application_invoicearrived', 'data_table' => 'cl_invoice_arrived', 'name' => 'Faktury přijaté'],
                            ['presenter' => 'application_invoicearrived_paymentlistgrid', 'data_table' => 'cl_invoice_arrived_payment', 'name' => 'Úhrady faktur přijatých'],
                            ['presenter' => 'application_invoiceadvance', 'data_table' => 'cl_invoice_advance', 'name' => 'Zálohové faktury'],
                            ['presenter' => 'application_invoiceadvance_paymentlistgrid', 'data_table' => 'cl_invoice_advance_payment', 'name' => 'Úhrady zálohových faktur'],
                            ['presenter' => 'application_invoiceinternal', 'data_table' => 'cl_invoice_internal', 'name' => 'Interní doklady'],
                            ['presenter' => 'application_banktrans', 'data_table' => 'cl_bank_trans', 'name' => 'Banka'],
                            ['presenter' => 'application_paymentorder', 'data_table' => 'cl_payment_order', 'name' => 'Platební příkaz']
                        ]],
                ['group' => 'Sklady', 'items' => [
                            ['presenter' => 'application_store', 'data_table' => NULL, 'name' => 'Sklad příjem a výdej'],
                            ['presenter' => 'application_storereview', 'data_table' => NULL, 'name' => 'Přehled skladu'],
                            ['presenter' => 'application_inventory', 'data_table' => NULL, 'name' => 'Fyzická inventura'],
                        ]],
                ['group' => 'Dodací listy a doprava', 'items' => [
                            ['presenter' => 'application_deliverynote', 'data_table' => 'cl_delivery_note', 'name' => 'Dodací listy'],
                            ['presenter' => 'application_deliverynotein', 'data_table' => 'cl_delivery_note_in', 'name' => 'Dodací listy přijaté'],
                            ['presenter' => 'application_deliverynote_paymentlistgrid', 'data_table' => 'cl_delivery_note_payment', 'name' => 'Úhrady dodacích listů'],
                            ['presenter' => 'application_transport', 'data_table' => 'cl_transport', 'name' => 'Doprava'],
                        ]],
                ['group' => 'Prodejna a doklady', 'items' => [
                            ['presenter' => 'application_sale', 'data_table' => NULL, 'name' => 'Prodejna'],
                            ['presenter' => 'application_salereview', 'data_table' => NULL, 'name' => 'Prodejky'],
                            ['presenter' => 'application_pricelistview', 'data_table' => NULL, 'name' => 'Ceník v prodejně'],
                        ]],
                ['group' => 'Pokladna', 'items' => [
                            ['presenter' => 'application_cash', 'data_table' => 'cl_cash', 'name' => 'Pokladna'],
                        ]],
                ['group' => 'Zakázky', 'items' => [
                            ['presenter' => 'application_commission', 'data_table' => 'cl_commission', 'name' => 'Zakázky'],
                            ['presenter' => 'application_expedition', 'data_table' => 'cl_expedition', 'name' => 'Expedice'],
                            ['presenter' => 'application_planecalendar', 'data_table' => 'cl_calendar_plane', 'name' => 'Plánovací kalendář'],
                        ]],
                ['group' => 'Nabídky', 'items' => [
                            ['presenter' => 'application_offer', 'data_table' => 'cl_offer', 'name' => 'Nabídky'],
                        ]],
                ['group' => 'Objednávky', 'items' => [
                            ['presenter' => 'application_order', 'data_table' => 'cl_order', 'name' => 'Objednávky'],
                        ]],
                ['group' => 'Helpdesk', 'items' => [
                            ['presenter' => 'application_helpdesk', 'data_table' => 'cl_partners_event', 'name' => 'Helpdesk'],
                            ['presenter' => 'application_helpdesksimple', 'data_table' => NULL, 'name' => 'Helpdesk - jednoduchý zápis'],
                            ['presenter' => 'application_helpdeskbilling', 'data_table' => NULL, 'name' => 'Helpdesk - vyúčtování'],
                            ['presenter' => 'application_task', 'data_table' => 'cl_task', 'name' => 'Úkoly'],
                        ]],
                ['group' => 'Všeználek', 'items' => [
                            ['presenter' => 'application_kdb', 'data_table' => 'cl_kdb', 'name' => 'Všeználek'],
                        ]],
                ['group' => 'Agenda', 'items' => [
                            ['presenter' => 'intranet_emailing', 'data_table' => 'in_emailing', 'name' => 'Hromadné Emaily'],
                            ['presenter' => 'application_emailhistory', 'data_table' => 'cl_emailing', 'name' => 'Historie emailů - aplikace'],
                        ]],
                ['group' => 'Seznamy', 'items' => [
                            ['presenter' => 'application_partners', 'data_table' => NULL, 'name' => 'Adresář partnerů'],
                            ['presenter' => 'application_partnersgroups', 'data_table' => NULL, 'name' => 'Skupiny partnerů'],
                            ['presenter' => 'application_partnerscategory', 'data_table' => NULL, 'name' => 'Helpdesk - kategorie požadavků'],
                            ['presenter' => 'application_eventtypes', 'data_table' => NULL, 'name' => 'Helpdesk - událostí'],
                            ['presenter' => 'application_eventmethod', 'data_table' => NULL, 'name' => 'Helpdesk - způsoby řešení událostí'],
                            ['presenter' => 'application_project', 'data_table' => NULL, 'name' => 'Projekty'],
                            ['presenter' => 'application_taskcategory', 'data_table' => NULL, 'name' => 'Druhy úkolů'],
                            ['presenter' => 'application_pricelist', 'data_table' => NULL, 'name' => 'Číselník položek'],
                            ['presenter' => 'application_pricelistgroup', 'data_table' => NULL, 'name' => 'Ceníkové skupiny'],
                            ['presenter' => 'application_pricelistcategories', 'data_table' => NULL, 'name' => 'Kategorie ceníku'],
                            ['presenter' => 'application_pricesgroups', 'data_table' => NULL, 'name' => 'Cenové skupiny'],
                            ['presenter' => 'application_storage', 'data_table' => NULL, 'name' => 'Sklady'],

                            ['presenter' => 'application_center', 'data_table' => NULL, 'name' => 'Střediska'],
                            ['presenter' => 'application_eshops', 'data_table' => NULL, 'name' => 'Eshopy'],
                            ['presenter' => 'application_bankaccounts', 'data_table' => NULL, 'name' => 'Bankovní účty'],
                            ['presenter' => 'application_currencies', 'data_table' => NULL, 'name' => 'Měny a kurzy'],
                            ['presenter' => 'application_cashdef', 'data_table' => NULL, 'name' => 'Pokladny'],
                            ['presenter' => 'application_kdbcategory', 'data_table' => NULL, 'name' => 'Kategorie pro Všeználka'],
                            ['presenter' => 'application_emailingtext', 'data_table' => NULL, 'name' => 'Šablony emailů'],
                            ['presenter' => 'application_paymenttypes', 'data_table' => NULL, 'name' => 'Druhy plateb'],
                            ['presenter' => 'application_transporttypes', 'data_table' => NULL, 'name' => 'Druhy dopravy'],
                            ['presenter' => 'application_invoicetypes', 'data_table' => NULL, 'name' => 'Druhy dokladů'],
                            ['presenter' => 'application_status', 'data_table' => NULL, 'name' => 'Stavy záznamů'],
                            ['presenter' => 'application_numberseries', 'data_table' => NULL, 'name' => 'Číslování dokladů'],
                            ['presenter' => 'application_headersfooters', 'data_table' => NULL, 'name' => 'Záhlaví a zápatí'],
                            ['presenter' => 'application_texts', 'data_table' => NULL, 'name' => 'Často používané texty'],
                            ['presenter' => 'application_ratesvat', 'data_table' => NULL, 'name' => 'Sazby DPH'],
                            ['presenter' => 'application_saleshorts', 'data_table' => NULL, 'name' => 'Rychlé volby prodejny'],
                            ['presenter' => 'application_companybranch', 'data_table' => NULL, 'name' => 'Vlastní pobočky'],
                            ['presenter' => 'application_users', 'data_table' => NULL, 'name' => 'Uživatelé'],
                            ['presenter' => 'application_usersgroups', 'data_table' => NULL, 'name' => 'Skupiny uživatelů'],
                            ['presenter' => 'application_usersrole', 'data_table' => NULL, 'name' => 'Skupiny oprávnění uživatelů'],
                            ['presenter' => 'application_workplaces', 'data_table' => NULL, 'name' => 'Pracoviště'],
                            ['presenter' => 'application_filemanager', 'data_table' => NULL, 'name' => 'Správce souborů'],
                            ['presenter' => 'application_reportmanager', 'data_table' => NULL, 'name' => 'Správce sestav'],

                        ]],
                ['group' => 'Úvod a nastavení', 'items' => [
                            ['presenter' => 'application_homepage', 'data_table' => NULL, 'name' => 'Úvodní stránka'],
                            ['presenter' => 'application_settings', 'data_table' => 'cl_company', 'name' => 'Nastavení aplikace'],
                        ]],
                ['group' => 'Intranet', 'items' => [
                            ['presenter' => 'intranet_estate', 'data_table' => 'in_estate', 'name' => 'Majetek'],
                            ['presenter' => 'intranet_estatemoves', 'data_table' => NULL, 'name' => 'Majetek - pohyby'],
                            ['presenter' => 'intranet_commission', 'data_table' => NULL, 'name' => 'Zakázky'],
                            ['presenter' => 'intranet_complaint', 'data_table' => 'in_complaint', 'name' => 'Reklamace'],
                            ['presenter' => 'intranet_folders', 'data_table' => 'in_folders', 'name' => 'Dokumenty'],
                            ['presenter' => 'intranet_staffplan', 'data_table' => NULL, 'name' => 'Zaměstnanci - plán'],
                            ['presenter' => 'intranet_rental', 'data_table' => 'in_rental', 'name' => 'Půjčovna'],
                            ['presenter' => 'intranet_reservation', 'data_table' => NULL, 'name' => 'Rezervace'],
                            ['presenter' => 'intranet_instructions', 'data_table' => NULL, 'name' => 'Pokyny'],
                            ['presenter' => 'intranet_notifications', 'data_table' => NULL, 'name' => 'Oznámení'],
                ]],
                ['group' => 'Intranet - seznamy', 'items' => [
                            ['presenter' => 'intranet_lectors', 'data_table' => NULL, 'name' => 'Školitelé / lékaři'],
                            ['presenter' => 'intranet_nations', 'data_table' => NULL, 'name' => 'Národnosti'],
                            ['presenter' => 'intranet_network', 'data_table' => NULL, 'name' => 'Síť'],
                            ['presenter' => 'intranet_places', 'data_table' => NULL, 'name' => 'Místa'],
                            ['presenter' => 'intranet_profession', 'data_table' => NULL, 'name' => 'Profese'],
                            ['presenter' => 'intranet_staff', 'data_table' => NULL, 'name' => 'Zaměstnanci'],
                            ['presenter' => 'intranet_staffrole', 'data_table' => NULL, 'name' => 'Role a skupiny zaměstnanců'],
                            ['presenter' => 'intranet_training', 'data_table' => NULL, 'name' => 'Školení a prohlídky'],
                            ['presenter' => 'intranet_trainingtypes', 'data_table' => NULL, 'name' => 'Typy školení a prohlídek'],
                            ['presenter' => 'intranet_workstypes', 'data_table' => NULL, 'name' => 'Typy práce'],
                            ['presenter' => 'intranet_estatetype', 'data_table' => NULL, 'name' => 'Typy majetku'],
                    ]],
            ];
        return $arr;
    }


    public function getLangName($type)
    {
		$arrResult = $this->getLanguages();
		return $arrResult[$type];
    }    
	
    public function getLanguages()
    {
		$arrStatus = ['cs' => 'Česky',
				    'en' => 'English',
                    'pl' => 'Polsky',
				    'de' => 'German',
				    'ru' => 'Pусский',
                    'sk' => 'Slovensky'
        ];
		return $arrStatus;	
    }


    public function getPDFnameType($type){
        $arrResult = $this->getPdfNameTypes();
        return $arrResult[$type];
    }
    public function getPdfNameTypes(){
        $arrTypes = [   0 => 'Název firmy - číslo dokladu',
                        1 => 'Číslo dokladu - název firmy'];
        return $arrTypes;
    }

    /** returns subfolder for given file
     * @param $file
     * @return string
     */
    public function getSubFolder($file, $fileType = NULL)
    {
        //bdump($fileType);
		//bdump($file);
        if (!is_null($fileType)){
            $retVal = str_replace('cl_', '', $fileType);
            $retVal = str_replace('_id', '', $retVal);
			$retVal = str_replace('in_', '', $retVal);
        }else {
            if (isset($file['cl_inventory_id']) && !is_null($file['cl_inventory_id'])) {
                $retVal = 'inventory';
            } elseif (isset($file['cl_kdb_id']) && !is_null($file['cl_kdb_id'])) {
                $retVal = 'kdb';
            } elseif (isset($file['cl_partners_book_id']) && !is_null($file['cl_partners_book_id']) ) {
                $retVal = 'partners_book_main';
			} elseif (isset($file['cl_partners_book_main_id']) && !is_null($file['cl_partners_book_main_id']) ) {
				$retVal = 'partners_book_main';
            } elseif (isset($file['cl_partners_event_id']) && !is_null($file['cl_partners_event_id']) ) {
                $retVal = 'partners_event';
            } elseif (isset($file['cl_commission_id']) && !is_null($file['cl_commission_id']) ) {
                $retVal = 'commission';
            } elseif (isset($file['cl_offer_id']) && !is_null($file['cl_offer_id']) ) {
                $retVal = 'offer';
            } elseif (isset($file['cl_invoice_arrived_id']) && !is_null($file['cl_invoice_arrived_id']) ) {
                $retVal = 'invoice_arrived';
            } elseif (isset($file['cl_invoice_advance_id']) && !is_null($file['cl_invoice_advance_id']) ) {
                $retVal = 'invoice_advance';
            } elseif (isset($file['cl_invoice_internal_id']) && !is_null($file['cl_invoice_internal_id']) ) {
                $retVal = 'invoice_internal';
            } elseif (isset($file['cl_invoice_id']) &&  !is_null($file['cl_invoice_id']) ) {
                $retVal = 'invoice';
            } elseif (isset($file['cl_order_id']) && !is_null($file['cl_order_id']) ) {
                $retVal = 'order';
            } elseif (isset($file['cl_pricelist_id']) && !is_null($file['cl_pricelist_id']) ) {
                $retVal = 'pricelist';
            } elseif (isset($file['cl_pricelist_image_id']) && !is_null($file['cl_pricelist_image_id']) ) {
                $retVal = 'pricelist_image';
            } elseif (isset($file['cl_delivery_note_id']) && !is_null($file['cl_delivery_note_id']) ) {
                $retVal = 'delivery_note';
            } elseif (isset($file['cl_delivery_note_in_id']) && !is_null($file['cl_delivery_note_in_id']) ) {
                $retVal = 'delivery_note_in';
			} elseif (isset($file['cl_transport_id']) && !is_null($file['cl_transport_id']) ) {
				$retVal = 'transport';
            } elseif (isset($file['cl_cash_id']) && !is_null($file['cl_cash_id']) ) {
                $retVal = 'cash';
            } elseif (isset($file['cl_store_docs_id']) && !is_null($file['cl_store_docs_id']) ) {
                $retVal = 'store_docs';
            } elseif (isset($file['in_profession_id']) && !is_null($file['in_profession_id']) ) {
                $retVal = 'profession';
            } elseif (isset($file['in_lectors_id']) && !is_null($file['in_lectors_id']) ) {
                $retVal = 'lectors';
            } elseif (isset($file['in_training_types_id']) && !is_null($file['in_training_types_id']) ) {
                $retVal = 'training_types';
            } elseif (isset($file['in_training_id']) && !is_null($file['in_training_id']) ) {
                $retVal = 'training';
            } elseif (isset($file['in_staff_id']) && !is_null($file['in_staff_id']) ) {
                $retVal = 'staff';
            } elseif (isset($file['in_folder_id']) && !is_null($file['in_folder_id']) ) {
                $retVal = 'folder';
            } elseif (isset($file['in_estate_id']) && !is_null($file['in_estate_id']) ) {
                $retVal = 'estate';
            } elseif (isset($file['in_places_id']) && !is_null($file['in_places_id']) ) {
                $retVal = 'places';
            } elseif (isset($file['cl_sale_id']) && !is_null($file['cl_sale_id']) ) {
                $retVal = 'sale';
            } elseif (isset($file['cl_b2b_order_id']) && !is_null($file['cl_b2b_order_id']) ) {
                $retVal = 'b2b_order';
            } elseif (isset($file['in_emailing_id']) && !is_null($file['in_emailing_id']) ) {
                $retVal = 'emailing';
            } elseif (isset($file['in_complaint_id']) && !is_null($file['in_complaint_id']) ) {
                $retVal = 'complaint';
            } elseif (isset($file['cl_bank_trans_id']) && !is_null($file['cl_bank_trans_id']) ) {
                $retVal = 'bank_trans';
            } elseif (isset($file['cl_task_id']) && !is_null($file['cl_task_id']) ) {
                $retVal = 'task';
            } elseif (isset($file['cl_payment_order_id']) && !is_null($file['cl_payment_order_id']) ) {
                $retVal = 'payment_order';
            } elseif (isset($file['cl_users_id']) && !is_null($file['cl_users_id']) ) { ///!!! MUST BE at the end!!!
                $retVal = 'users';
            } elseif ($file == 'log'){
                $retVal = 'log';
            } else {
                $retVal = '';
            }

        }
        return $retVal;
    }


    public function getDataFolder($company_id){
        $folder =  __DIR__ . "/../../data/".$company_id;
        $arrFolders = ['pfx',              'order',            'order', 'store',
                            'invoice',          'invoice_arrived',  'delivery_note',
                            'store_docs',       'cash',             'partners_event',
                            'kdb',              'offer',            'pricelist',
                            'pricelist_image',  'partners_book',    'profession',
                            'lectors',          'training_types',   'staff',
                            'folder',           'staff',            'folder',
                            'estate',           'places',           'commission',
                            'sale',				'report',			'transport',
                            'invoice_advance',  'progress_bar',     'inventory',
                            'b2b_order',        'emailing',         'complaint',
                            'bank_trans',       'invoice_internal', 'task',
                            'payment_order',    'delivery_note_in'];

        if (!file_exists($folder)) {
            mkdir($folder);
        }
        foreach($arrFolders as $one)
        {
            $folder2 = $folder.'/'.$one.'/';
            //bdump($folder2);
            if (!file_exists($folder2))
            {

                mkdir($folder2);
                $folder2 = $folder.'/'.$one.'/import/';
                mkdir($folder2);
            }
        }
        return $folder;
    }

    
    public function getPriorityName($type)
    {
		$arrResult = $this->getPriority();
		return $arrResult[$type];
    }    
	
    public function getPriority()
    {
		$arrStatus = [NULL => '',
					0 => 'Nízká',
					'1' => 'Střední',
					'2' => 'Vysoká !'
        ];
		return $arrStatus;	
    }	
    
    public function getTariffTypeName($type)
    {
		$arrResult = $this->getTariffType();
		return $arrResult[$type];
    }    
	
    public function getTariffType()
    {
		$arrStatus = [NULL => '',
					'1' => 'Podnikatel',
					'2' => 'Servis',
					'3' => 'Max',
        ];
		return $arrStatus;	
    }	
	


	
    public function getPaymentTypeName($type)
    {
		$arrResult = $this->getPaymentType();
		return $arrResult[$type];
    }    
	
    public function getPaymentType()
    {
		$arrStatus = [NULL => '',
					    0 => 'Převod'
        ];
		//,'1' => 'GoPay'
		return $arrStatus;	
    }		
    
    
    public function getPaymentStatusName($type)
    {
		$arrResult = $this->getPaymentStatus();
		return $arrResult[$type];
    }    
	
    public function getPaymentStatus()
    {
		$arrStatus = [NULL => 'Nezaplaceno',
                            'PAID' => 'Zaplaceno',
                            'CANCELED' => 'Zrušeno'
        ];
		//,'1' => 'GoPay'
		return $arrStatus;	
    }


    public function getModules2PName($type)
    {
        $arrResult = $this->getModules2P();
        return $arrResult[$type];
    }

    public function getModules2P($cmpNames = FALSE)
    {
        $arrStatus = array( 'Application:Invoice' => 'Faktury vydané',
                            'Application:InvoiceAdvance' => 'Faktury zálohové',
                            'Application:InvoiceArrived' => 'Faktury přijaté',
                            'Application:Store' => 'Sklad',
                            'Application:BankTrans' => 'Banka',
                            'Application:DeliveryNote' => 'Dodací listy',
                            'Application:DeliveryNoteIn' => 'Dodací listy přijaté',
                            'Application:Transport' => 'Doprava',
                            'Application:Sale' => 'Prodejna',
                            'Application:Cash' => 'Pokladna',
                            'Application:Order' => 'Objednávky',
                            'Application:Commission' => 'Zakázky',
                            'Application:Expedition' => 'Expedice',
                            'Application:Offer' => 'Nabídky',
                            'Application:Helpdesk' => 'Helpdesk',
                            'Application:Kdb' => 'Všeználek',
                            'Intranet:Emailing' => 'Hromadné emaily',
                            'Intranet:Notifications' => 'Oznámení',
                            'Intranet:Folders' => 'Dokumentace',
                            'Intranet:Staff' => 'Zaměstnanci',
                            'Intranet:Estate' => 'Majetek',
                            'Intranet:Complaint' => 'Reklamace',
                            'Intranet:Rental' => 'Půjčovna',
                            'Storage' => '+500 MB',
                            'Records' => '+5000 záznamů'
        );

        if ($cmpNames){
            $newArr = [];
            foreach($arrStatus as $key => $one){
                $newKey = str_replace(':', '_',$key);
                $newArr[$newKey] = $one;
            }
            $arrStatus = $newArr;
        }

        return $arrStatus;
    }




    public function getModulesName($presenterName)
    {
		if (!is_null($presenterName))
		{
			$arrResult = $this->getModules();
			return $arrResult[$presenterName];
		}else{
                return '';
		}
                
    }    	

	public function getModules($cmpNames = FALSE)
	{
		$arrPresenter = ['Application:Invoice' => 'Faktury vydané',
								'Application:InvoiceArrived' => 'Faktury přijaté',
								'Application:Store' => 'Sklad',
								'Application:DeliveryNote' => 'Dodací_listy',
                                'Application:DeliveryNoteIn' => 'Dodací_listy_přijaté',
								'Application:Sale' => 'Prodejna',
								'Application:Cash' => 'Pokladna',
								'Application:Order' => 'Objednávky',
								'Application:Commission' => 'Zakázky',
								'Application:Offer' => 'Nabídky',
								'Application:Helpdesk' => 'Helpdesk',
								'Application:Kdb' => 'Všeználek',
								'Company' => 'Další firma',
								'Storage' => '+ 500 MB',
								'Records' => '+ 5000 záznamů'
        ];
		if ($cmpNames){
			$newArr = [];
			foreach($arrPresenter as $key => $one){
				$newKey = str_replace(':', '_',$key);
				$newArr[$newKey] = $one;
			}
			$arrPresenter = $newArr;
		}
		return $arrPresenter;
	}
	
	
	public function getMoPrices($cmpNames = FALSE)
	{
		$arrPresenter = ['Application:Invoice' => ['price' => 150, 'price2' => 50],
                                'Application:InvoiceAdvance' => ['price' => 150, 'price2' => 50],
                                'Application:InvoiceArrived' => ['price' => 150, 'price2' => 50],
                                'Application:BankTrans' => ['price' => 150, 'price2' => 50],
                                'Application:Store' => ['price' => 150, 'price2' => 50],
								'Application:DeliveryNote' => ['price' => 150, 'price2' => 50],
                                'Application:DeliveryNoteIn' => ['price' => 150, 'price2' => 50],
                                'Application:Transport' => ['price' => 150, 'price2' => 50],
								'Application:Sale' => ['price' => 150, 'price2' => 50],
								'Application:Cash' => ['price' => 150, 'price2' => 50],
								'Application:Order' => ['price' => 150, 'price2' => 50],
								'Application:Commission' => ['price' => 150, 'price2' => 50],
                                'Application:Expedition' => ['price' => 150, 'price2' => 50],
								'Application:Offer' => ['price' => 150, 'price2' => 50],
								'Application:Helpdesk' => ['price' => 300, 'price2' => 50],
								'Application:Kdb' => ['price' => 300, 'price2' => 50],
                                'Intranet:Emailing' => ['price' => 300, 'price2' => 50],
                                'Intranet:Notifications' => ['price' => 300, 'price2' => 50],
                                'Intranet:Folders' => ['price' => 300, 'price2' => 50],
                                'Intranet:Staff' => ['price' => 300, 'price2' => 50],
                                'Intranet:Estate' => ['price' => 300, 'price2' => 50],
                                'Intranet:Complaint' => ['price' => 300, 'price2' => 50],
                                'Intranet:Rental' => ['price' => 300, 'price2' => 50],
								'Storage' => 50,
								'Records' => 50
        ];
        if ($cmpNames){
            $newArr = [];
            foreach($arrPresenter as $key => $one){
                $newKey = str_replace(':', '_',$key);
                $newArr[$newKey] = $one;
            }
            $arrPresenter = $newArr;
        }
		return $arrPresenter;
	}



	public function getMoPrice($name)
	{
		return $this->getMoPrices()[$name];
	}
	
	
	/** Vrácení českého názvu měsíce
	* @param int 1-12
	* @return string
	* @copyright Jakub Vrána, http://php.vrana.cz/
	*/
	public function cesky_mesic($mesic) {
		static $nazvy = [1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
		return $nazvy[$mesic];
	}
	public function cesky_mesic_small($mesic) {
		static $nazvy = [1 => 'led', 'úno', 'bře', 'dub', 'kvě', 'čvn', 'čvc', 'srp', 'zář', 'říj', 'lis', 'pro'];
		return $nazvy[$mesic];
	}		


	/** Vrácení českého názvu dne v týdnu
	* @param int 0-6, 0 neděle
	* @return string
	* @copyright Jakub Vrána, http://php.vrana.cz/
	*/
	public function cesky_den($den) {
        $nazvy = $this->getDaysOfWeek();
		return $nazvy[$den];
	}

    public function cesky_den_small($den) {
        $nazvy = $this->getDaysOfWeek();
        return iconv_substr($nazvy[$den],0,2);
    }


	public function getDaysOfWeek() {
        static $nazvy = array(1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek', 5 => 'pátek', 6 => 'sobota', 0 => 'neděle', NULL => '');
        return ($nazvy);
    }

    public function getStatusName($status)
    {
        $arrResult = $this->getStatusAll();
        if (isset($arrResult[$status]))
        {
            $ret = $arrResult[$status];
        }else{
            $ret = "";
        }
        return $ret;
    }

    public function getStatusAll()
    {
        $arrStatus = [
            'commission'        => 'Zakázka',
            'invoice'           => 'Faktura vydaná',
            'invoice_correction'=> 'Opravný daňový doklad',
            'invoice_advance'   => 'Zálohová faktura',
            'invoice_arrived'   => 'Faktura přijatá',
            'invoice_arrived_correction' => 'Opravný daňový doklad přijatý',
            'invoice_arrived_advance'   => 'Zálohová faktura přijatá',
            'invoice_internal'  => 'Interní doklad',
            'invoice_tax'       => 'Daňový doklad k zaplacené záloze',
            'ireminder'         => 'Upomínky nezaplacených faktur',
            'areminder'         => 'Upomínky nezaplacených záloh',
            'pricelist'         => 'Ceník',
            'pricelist_ean'     => 'Ceník EAN kódy',
            'order'             => 'Objednávka',
            'offer'             => 'Nabídka',
            'store_in'          => 'Sklad příjem',
            'store_out'         => 'Sklad výdej',
            'partners_event'    => 'Helpdesk',
            'kdb'               => 'Všeználek',
            'sale'              => 'Prodejka',
            'delivery_note'     => 'Dodací list',
            'delivery_note_in'  => 'Dodací list přijatý',
            'cash_in'           => 'Pokladna příjem',
            'cash_out'          => 'Pokladna výdej',
            'cash'              => 'Pokladna',
            'task'              => 'Úkoly',
            'payment_order'     => 'Platební příkaz',
			'transport'         => 'Doprava',
            'inventory'         => 'Inventura',
            'b2b_order'         => 'B2B objednávka',
            'emailing'          => 'Hromadná pošta',
            'instructions'      => 'Intranet - Pokyny',
            'rental'            => 'Intranet - Půjčovna',
            'reservation'       => 'Intranet - Rezervace',
            'estate'            => 'Intranet - Majetek',
            'complaint'         => 'Intranet - Reklamace',
            'oro'               => 'ORO - celní správa',
            ''                  => '',
			0                  => ''
        ];
        asort($arrStatus);
        //bdump($arrStatus);
        return $arrStatus;
    }
	
	public function getReportName($name)
	{
		$arrResult = $this->getReportAll();
		return $arrResult[$name];
	}
	
	public function getReportAll()
	{
		$arrReports = [
			'invoicev2.latte' => 'Faktura vydaná',
			'advancev1.latte' => 'Zálohová faktura',
			'correctionv1.latte'=> 'Opravný daňový doklad',
			'DeliveryNotev2.latte'=>  'Dodací list',
            'DeliveryNoteInv2.latte'=>  'Dodací list přijatý',
			'TransportNote.latte'=>  'Závozová karta',
			'commissionv1.latte'=>   'Zakázkový list',
			'offerv1.latte'=>   'Nabídkový list',
			'orderv1.latte'=>   'Objednávka',
			'saledoc.latte'=>   'Prodejka',
			'saledoc65.latte'=>  'Prodejka 65mm',
			'cashdoc.latte'=>   'Pokladní doklad',
			'correction.latte'=>   'Opravný doklad',
			'pdfStoreIn.latte'=>   'Příjemka',
			'pdfStoreOut.latte'=>   'Výdejka',
			'pdfPlacement.latte'=>   'Umístěnka',
            'rptWorks.latte'    =>   'Práce na zakázce',
            'rptTasks.latte'    =>   'Úkoly na zakázce',
            'rptOneTask.latte'  =>   'Úkolový list',
            'b2bBasket.latte'  =>   'B2B objednávka',
            'complaintv1.latte' => 'Reklamační protokol',
            'complaintv2.latte' => 'Reklamační protokol interní',
			'' => ''
        ];
		asort($arrReports);
		//bdump($arrStatus);
		return $arrReports;
	}
	
	
	public function getReportFileName($name)
	{
		$arrResult = $this->getReportFileAll();
		return $arrResult[$name];
	}
	
	public function getReportFileAll()
	{
		$arrReports = [
			'invoicev2.latte' =>  __DIR__ . '/../ApplicationModule/templates/Invoice/invoicev2.latte',
			'advancev1.latte' =>  __DIR__ . '/../ApplicationModule/templates/Invoice/advancev1.latte',
			'correctionv1.latte'=>   __DIR__ . '/../ApplicationModule/templates/Invoice/correctionv1.latte',
            'complaintv1.latte'=>   __DIR__ . '/../IntranetModule/templates/Complaint/complaintv1.latte',
            'complaintv2.latte'=>   __DIR__ . '/../IntranetModule/templates/Complaint/complaintv2.latte',
			'DeliveryNotev2.latte'=>   __DIR__ . '/../ApplicationModule/templates/DeliveryNote/DeliveryNotev2.latte',
            'DeliveryNoteInv2.latte'=>   __DIR__ . '/../ApplicationModule/templates/DeliveryNoteIn/DeliveryNoteInv2.latte',
			'TransportNote.latte'=>   __DIR__ . '/../ApplicationModule/templates/Transport/TransportNote.latte',
			'commissionv1.latte'=>   __DIR__ . '/../ApplicationModule/templates/Commission/commissionv1.latte',
			'offerv1.latte'=>   __DIR__ . '/../ApplicationModule/templates/Offer/offerv1.latte',
			'orderv1.latte'=>   __DIR__ . '/../ApplicationModule/templates/Order/orderv1.latte',
			'saledoc.latte'=>   __DIR__ . '/../ApplicationModule/templates/Sale/saledoc.latte',
			'saledoc65.latte'=>   __DIR__ . '/../ApplicationModule/templates/Sale/saledoc65.latte',
			'cashdoc.latte'=>   __DIR__ . '/../ApplicationModule/templates/Cash/cashdoc.latte',
			'correction.latte'=>   __DIR__ . '/../ApplicationModule/templates/SaleReview/correction.latte',
			'pdfStoreIn.latte'=>   __DIR__ . '/../ApplicationModule/templates/Store/pdfStoreIn.latte',
			'pdfStoreOut.latte'=>   __DIR__ . '/../ApplicationModule/templates/Store/pdfStoreOut.latte',
			'pdfPlacement.latte'=>   __DIR__ . '/../ApplicationModule/templates/Store/pdfPlacement.latte',
            'rptWorks.latte'=>   __DIR__ . '/../ApplicationModule/templates/Commission/rptWorks.latte',
            'rptTasks.latte'=>   __DIR__ . '/../ApplicationModule/templates/Commission/rptTasks.latte',
            'rptOneTask.latte'=>   __DIR__ . '/../ApplicationModule/templates/Commission/rptOneTask.latte',
            'b2bBasket.latte'=>   __DIR__ . '/../B2BModule/templates/Basket/b2bBasket.latte',
			'' => ''
        ];
		return $arrReports;
	}
    


    public function getInvoiceTypeName($type)
    {
        $arrResult = $this->getInvoiceTypes();
        return $arrResult[$type];
    }

    public function getInvoiceTypes()
    {
        $arrStatus = [1 => 'Faktura vydaná',
                            '4' => 'Faktura přijatá',
                            '2' => 'Opravný daňový doklad',
                            '3' => 'Zálohová faktura',
                            '5' => 'Pokladna příjem',
                            '6' => 'Pokladna výdej',
                            '7' => 'Reklamace',
                            '8' => 'Interní doklad',
                            '0' => ''
        ];
        return $arrStatus;
    }


    public function getTransportTypeName($type)
    {
        $arrResult = $this->getTransportTypes();
        return $arrResult[$type];
    }

    public function getTransportTypes()
    {
        $arrStatus = [0 => 'Vlastní',
        					1 => 'TopTrans',
                            2 => 'Geis Parcel',
                            3 => 'Geis Cargo',
                            4 => 'Česká pošta',
                            5 => 'Slovenská pošta',
                            6 => 'DHL',
                            7 => 'PPL'
        ];
        return $arrStatus;
    }


    public function getSaleTypeName($type)
    {
        $arrResult = $this->getSaleTypes();
        return $arrResult[$type];
    }

    public function getSaleTypes()
    {
        $arrStatus = [0 => 'Prodejka',
                            1 => 'Opravný daňový doklad'
        ];
        return $arrStatus;
    }

    public function getImportTypeName($type)
    {
        $arrResult = $this->getImportType();
        return $arrResult[$type];
    }

    public function getImportType()
    {
        $arrStatus = [0 => 'GPC/ABO',
                            1 => 'CSV - FIO',
                            2 => 'XML - Pohoda',
                            3 => 'OFX',
                            4 => 'JSON Česká Spořitelna',
        ];
        return $arrStatus;
    }

    public function getExportTypeName($type)
    {
        $arrResult = $this->getExportType();
        return $arrResult[$type];
    }

    public function getExportType()
    {
        $arrStatus = [0 => 'XML - Pohoda'
        ];
        return $arrStatus;
    }



    public function getEETStatusName($type)
    {
        $arrResult = $this->getEETStatusTypes();
        return $arrResult[$type];
    }

    public function getEETStatusTypes()
    {
        $arrStatus = [NULL => 'Neodesláno',
                            0 => 'Neodesláno',
                            1 => 'Vrácena chyba',
                            2 => 'Odesláno s varováním',
                            3 => 'Odesláno'
        ];
        return $arrStatus;
    }

    public function getAvailabilityName($type)
    {
        $arrResult = $this->getAvailability();
        return $arrResult[$type];
    }

    public function getAvailability()
    {
        $arrStatus = [NULL => 'neznámo',
            0 => 'není skladem',
            1 => 'na cestě',
            2 => 'skladem',
            3 => '+100'
        ];
        return $arrStatus;
    }



    public function getEETColours()
    {
        $arrStatus = [NULL => '#BA000B',
                            0 => '#BA000B',
                            1 => '#FD8206',
                            2 => '#33CD7A',
                            3 => '#1A924C'];
        return $arrStatus;

    }

    public function getPaymentTypeAppName($type)
    {
        $arrResult = $this->getPaymentTypesApp();
        Debugger::log('given type: ' . $type);
        Debugger::log('for return: ' . $arrResult[$type]);
        return $arrResult[$type];
    }

    public function getPaymentTypesApp()
    {
        $arrStatus = [0 => 'Převod',
            '1' => 'Hotovost',
            '2' => 'Dobírka',
            '3' => 'Karta',
            '4' => 'Složenka',
            '5' => 'Záloha',
            '6' => 'Inkaso',
            '7' => 'Šek',
            '8' => 'Zápočet'
        ];
        return $arrStatus;
    }


    public function getPackageTypeNamePPL($type)
    {
        $arrResult = $this->getPackageTypesPPL();
        return $arrResult[$type];
    }

    public function getPackageTypesPPL()
    {
        $arrStatus = [0 => '',
            1 => 'BAL',
            2 => 'PAL',
            3 => 'přepravka',
            4 => 'obálka'
        ];
        return $arrStatus;
    }


    public function getPackageTypeName($type)
    {
        $arrResult = $this->getPackageTypes();
        return $arrResult[$type];
    }

    public function getPackageTypes()
    {
        $arrStatus = [0 => '',
            1 => 'balík',
            2 => 'paleta',
            3 => 'přepravka',
            4 => 'obálka'
        ];
        return $arrStatus;
    }

    public function getPaymentTypePohodaName($type)
    {
        $arrResult = $this->getPaymentTypesPohoda();
        return $arrResult[$type];
    }

    public function getPaymentTypesPohoda()
    {
        $arrStatus = [0 => 'draft',
            '1' => 'cash',
            '2' => 'delivery',
            '3' => 'creditcard',
            '4' => 'postal',
            '5' => 'advance',
            '6' => 'encashment',
            '7' => 'cheque',
            '8' => 'compensation'
        ];
        return $arrStatus;
    }

    public function getCurrenciesCodes(){
        return [        ''  => 'Bez kódu měny',
            'AFN' => 'Afghánský afghání - AFN',
            'ALL' => 'Albánský lek - ALL',
            'DZD' => 'Alžírský dinár - DZD',
            'USD' => 'Americký dolar - USD',
            'AOA' => 'Angolská kwanza - AOA',
            'ARS' => 'Argentinské peso - ARS',
            'AMD' => 'Arménský dram - AMD',
            'AUD' => 'Australský dolar - AUD',
            'AZN' => 'Ázerbájdžánský manat - AZN',
            'BSD' => 'Bahamský dolar - BSD',
            'BHD' => 'Bahrajnský dinár - BHD',
            'BDT' => 'Bangladéšská taka - BDT',
            'BBD' => 'Barbadoský dolar - BBD',
            'BZD' => 'Belizský dolar - BZD',
            'BYR' => 'Běloruský rubl - BYR',
            'BTN' => 'Bhútánský ngultrum - BTN',
            'BOB' => 'Bolivijský boliviano - BOB',
            'BWP' => 'Botswanská pula - BWP',
            'BRL' => 'Brazilský real - BRL',
            'BND' => 'Brunejský dolar - BND',
            'BGN' => 'Bulharský lev - BGN',
            'BIF' => 'Burundský frank - BIF',
            'XOF' => 'CFA frank - XOF',
            'CZK' => 'Česká koruna - CZK',
            'CNY' => 'Čínský jüan - CNY',
            'DKK' => 'Dánská koruna - DKK',
            'DOP' => 'Dominikánské peso - DOP',
            'DJF' => 'Džibutský frank - DJF',
            'EGP' => 'Egyptská libra - EGP',
            'AED' => 'Emirátský dirham - AED',
            'ERN' => 'Eritrejská nakfa - ERN',
            'EEK' => 'Estonská koruna - EEK',
            'ETB' => 'Etiopský birr - ETB',
            'EUR' => 'Euro - EUR',
            'FJD' => 'Fidžijský dolar - FJD',
            'PHP' => 'Filipínské peso - PHP',
            'GMD' => 'Gambijský dalasi - GMD',
            'GHS' => 'Ghanský cedi - GHS',
            'GEL' => 'Gruzínské lari - GEL',
            'GTQ' => 'Guatemalský quetzal - GTQ',
            'GNF' => 'Guinejský frank - GNF',
            'GYD' => 'Guyanský dolar - GYD',
            'HTG' => 'Haitský gourde - HTG',
            'HNL' => 'Honduraská lempira - HNL',
            'CLP' => 'Chilské peso - CLP',
            'HRK' => 'Chorvatská kuna - HRK',
            'INR' => 'Indická rupie - INR',
            'IDR' => 'Indonéská rupie - IDR',
            'IQD' => 'Irácký dinár - IQD',
            'IRR' => 'Íránský rial - IRR',
            'ISK' => 'Islandská koruna - ISK',
            'ILS' => 'Izraelský šekel - ILS',
            'JMD' => 'Jamajský dolar - JMD',
            'JPY' => 'Japonský jen - JPY',
            'YER' => 'Jemenský rial - YER',
            'ZAR' => 'Jihoafrický rand - ZAR',
            'KRW' => 'Jihokorejský won - KRW',
            'JOD' => 'Jordánský dinár - JOD',
            'KHR' => 'Kambodžský riel - KHR',
            'CAD' => 'Kanadský dolar - CAD',
            'CVE' => 'Kapverdské escudo - CVE',
            'QAR' => 'Katarský rial - QAR',
            'KZT' => 'Kazašské tenge - KZT',
            'KES' => 'Keňský šilink - KES',
            'COP' => 'Kolumbijské peso - COP',
            'KMF' => 'Komorský frank - KMF',
            'BAM' => 'Konvertibilní marka - BAM',
            'CDF' => 'Konžský frank - CDF',
            'CRC' => 'Kostarický colón - CRC',
            'CUC' => 'Kubánské konvertibilní peso - CUC',
            'CUP' => 'Kubánské peso - CUP',
            'KWD' => 'Kuvajtský dinár - KWD',
            'KGS' => 'Kyrgyzský som - KGS',
            'LAK' => 'Laoský kip - LAK',
            'LSL' => 'Lesothský loti - LSL',
            'LBP' => 'Libanonská libra - LBP',
            'LRD' => 'Liberijský dolar - LRD',
            'GBP' => 'Libra šterlinků - GBP',
            'LYD' => 'Libyjský dinár - LYD',
            'LTL' => 'Litevský litas - LTL',
            'LVL' => 'Lotyšský lat - LVL',
            'HUF' => 'Maďarský forint - HUF',
            'MKD' => 'Makedonský denár - MKD',
            'MYR' => 'Malajsijský ringgit - MYR',
            'MWK' => 'Malawiská kwacha - MWK',
            'MVR' => 'Maledivská rupie - MVR',
            'MGA' => 'Malgašský ariary - MGA',
            'MAD' => 'Marocký dirham - MAD',
            'MUR' => 'Mauricijská rupie - MUR',
            'MRO' => 'Mauritánská ukíjá - MRO',
            'MXN' => 'Mexické peso - MXN',
            'MDL' => 'Moldavské leu - MDL',
            'MNT' => 'Mongolský tugrik - MNT',
            'MZN' => 'Mosambický metical - MZN',
            'MMK' => 'Myanmarský kyat - MMK',
            'NAD' => 'Namibijský dolar - NAD',
            'NPR' => 'Nepálská rupie - NPR',
            'NGN' => 'Nigerijská naira - NGN',
            'NIO' => 'Nikaragujská córdoba - NIO',
            'NOK' => 'Norská koruna - NOK',
            'TRY' => 'Nová turecká lira - TRY',
            'NZD' => 'Novozélandský dolar - NZD',
            'OMR' => 'Ománský rial - OMR',
            'PKR' => 'Pákistánská rupie - PKR',
            'PAB' => 'Panamská balboa - PAB',
            'PGK' => 'Papujsko-guinejská kina - PGK',
            'PYG' => 'Paraguayský guaraní - PYG',
            'PEN' => 'Peruánský nuevo sol - PEN',
            'PLN' => 'Polský złoty - PLN',
            'RON' => 'Rumunské leu - RON',
            'RUB' => 'Ruský rubl - RUB',
            'RWF' => 'Rwandský frank - RWF',
            'WST' => 'Samojská tala - WST',
            'SAR' => 'Saúdský rial - SAR',
            'KPW' => 'Severokorejský won - KPW',
            'SCR' => 'Seychelská rupie - SCR',
            'SLL' => 'Sierraleonský leone - SLL',
            'SGD' => 'Singapurský dolar - SGD',
            'SOS' => 'Somálský šilink - SOS',
            'RSD' => 'Srbský dinár - RSD',
            'SDG' => 'Súdánská libra - SDG',
            'SRD' => 'Surinamský dolar - SRD',
            'STD' => 'Svatotomášská dobra - STD',
            'SZL' => 'Svazijský lilangeni - SZL',
            'SYP' => 'Syrská libra - SYP',
            'SBD' => 'Šalamounský dolar - SBD',
            'LKR' => 'Šrílanská rupie - LKR',
            'SEK' => 'Švédská koruna - SEK',
            'CHF' => 'Švýcarský frank - CHF',
            'TJS' => 'Tádžický somoni - TJS',
            'TZS' => 'Tanzanský šilink - TZS',
            'THB' => 'Thajský baht - THB',
            'TOP' => 'Tonžská paanga - TOP',
            'TTD' => 'Trinidadsko-tobagský dolar - TTD',
            'TND' => 'Tuniský dinár - TND',
            'TMT' => 'Turkmenský manat - TMT',
            'UGX' => 'Ugandský šilink - UGX',
            'UAH' => 'Ukrajinská hřivna - UAH',
            'UYU' => 'Uruguayské peso - UYU',
            'UZS' => 'Uzbecký som - UZS',
            'VUV' => 'Vanuatský vatu - VUV',
            'VEF' => 'Venezuelský bolívar - VEF',
            'VND' => 'Vietnamský dong - VND',
            'XCD' => 'Východokaribský dolar - XCD',
            'ZMK' => 'Zambijská kwacha - ZMK',
            'ZWD' => 'Zimbabwský dolar - ZWD'
                        ];
    }



    public function getCountriesCodes() {
        $arrResult = [];
        foreach($this->getCountries() as $key=>$one)
            $arrResult[$key] = $key . ' - ' . $one;
        return $arrResult;
    }

    public function getCountries() {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua And Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia And Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Columbia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote D\'Ivorie (Ivory Coast)',
            'HR' => 'Croatia (Hrvatska)',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'CD' => 'Democratic Republic Of Congo (Zaire)',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'TP' => 'East Timor',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'FX' => 'France, Metropolitan',
            'GF' => 'French Guinea',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard And McDonald Islands',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macau',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar (Burma)',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'AN' => 'Netherlands Antilles',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'KP' => 'North Korea',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts And Nevis',
            'LC' => 'Saint Lucia',
            'PM' => 'Saint Pierre And Miquelon',
            'VC' => 'Saint Vincent And The Grenadines',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome And Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovak Republic',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia And South Sandwich Islands',
            'KR' => 'South Korea',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard And Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad And Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks And Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'UK' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Minor Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatican City (Holy See)',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'VG' => 'Virgin Islands (British)',
            'VI' => 'Virgin Islands (US)',
            'WF' => 'Wallis And Futuna Islands',
            'EH' => 'Western Sahara',
            'WS' => 'Western Samoa',
            'YE' => 'Yemen',
            'YU' => 'Yugoslavia',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
            '' => ''
        ];
    }
	
	/** convert select data to array
	 * @param $data
	 * @return array
	 */
    public function select2array($data)
	{
		$arrRet = [];
		foreach($data as $key => $one){
			$arrRet[$key] = $one->toArray();
		}
		return $arrRet;
	}

    /** replace all spaces in array with underscore
     * it's for translation for selectboxes
     * @param $arr
     * @return mixed
     */
	public function arrSpaceToUnderscore($arr)
    {
        foreach($arr as $key => $one){
            $arr[$key] = str_ireplace(" ","_",$one);
        }
        return $arr;
    }


    public function getDocNumberName($tableName)
    {
        $arr = ['cl_invoice'    => 'inv_number',
                'in_complaint'  => 'co_number',
                'cl_task'       => 'task_number'];
        return $arr[$tableName];
    }

}

