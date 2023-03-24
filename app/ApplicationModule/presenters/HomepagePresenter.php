<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;


class HomepagePresenter extends \App\Presenters\BaseAppPresenter {

    
    /**
    * @inject
    * @var \App\Model\PartnersEventManager
    */
    public $PartnersEventManager;            
    
    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\PriceListLimitsManager
     */
    public $PriceListLimitsManager;

    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;        	
    
    /**
    * @inject
    * @var \App\Model\InvoiceManager
    */
    public $InvoiceManager;

    /**
     * @inject
     * @var \App\Model\SaleManager
     */
    public $SaleManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;

    /**
    * @inject
    * @var \App\Model\BankAccountsManager
    */
    public $BankAccountsManager;          
    
    /**
    * @inject
    * @var \App\Model\OrderManager
    */
    public $OrderManager;          
	
    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\NotificationsManager
     */
    public $notificationsManager;


    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $priceListManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $partnersManager;

    /**
    * @inject
    * @var \Nette\Database\Context
    */
    public $database;          	

   	
    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.Homepage']);

    }	
    
	
	public function actionChangeTariff()
	{
		//dump($this->restoreRequest($backlink));
		//dump($backlink);
	}	
	public function renderChangeTariff($module_name, $message = [])
	{
		$this->template->module_name = $this->ArraysManager->getModules2PName($module_name);
		$this->template->message = $message;

	}


    protected function createComponentNotifications()
    {
        $tmpResult = $this->notificationsManager->findValid();

        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new NotificationsControl($this->translator,
                                        $tmpResult,
                                        $this->translator->translate("Oznámení"),'notifications.latte', $this->notificationsManager, $this->user);
    }




    protected function createComponentInvoices()
    {
        if ($this->isAllowed('InvoicePresenter', 'enabled')) {
            $tmpResult['invoices'] = true;
            $tmpResult['invoicearrived'] = false;
        }else{
            $tmpResult['invoices'] = false;
            $tmpResult['message'] = $this->translator->translate('nemáte_přístup_do_modulu_faktury');
        }
        return new InvoicesControl($tmpResult, 'Vydané_faktury_po_splatnosti','invoices.latte', $this->InvoiceManager, $this->translator);
    }

    protected function createComponentInfoVat()
    {
        if ($this->isAllowed('InvoicePresenter', 'enabled')) {
            $tmpResult['infoVat'] = true;
        }else{
            $tmpResult['infoVat'] = false;
            $tmpResult['message'] = $this->translator->translate('nemáte_přístup_do_modulu_faktury');
        }
        return new InfoVatControl($tmpResult, 'Info DPH','infoVat.latte', $this->InvoiceManager, $this->InvoiceArrivedManager, $this->SaleManager, $this->translator);
    }



    protected function createComponentInvoicearrived()
    {
        if ($this->isAllowed('InvoiceArrivedPresenter', 'enabled')) {
            $tmpResult['invoicearrived'] = true;
            $tmpResult['invoices'] = false;
        }else{
            $tmpResult['invoicearrived'] = false;
            $tmpResult['message'] = $this->translator->translate('nemáte_přístup_do_modulu_faktury_přijaté');
        }
        return new InvoicesControl($tmpResult, 'Přijaté_faktury_po_splatnosti','invoices.latte', $this->InvoiceArrivedManager, $this->translator);
    }

    protected function createComponentCommissionBox()
    {
        if ($this->isAllowed('CommissionPresenter', 'enabled')) {
            $tmpResult['commissionBox'] = true;
        }else{
            $tmpResult['commissionBox'] = false;
            $tmpResult['message'] = $this->translator->translate('nemáte_přístup_do_modulu_faktury_přijaté');
        }
        return new CommissionBoxControl($tmpResult, 'Dnešní_zakázky','commissionBox.latte', $this->CommissionManager, $this->translator);
    }



    protected function createComponentOrders()
    {
        if ($this->getUser()->getIdentity()->store_manager == 1) {
            $tmpResult['automatic_orders'] = $this->StorageManager->getStoragesToAutomaticOrder();
        }else{
            $tmpResult['automatic_orders'] = false;
            $tmpResult['message'] = $this->translator->translate('nejste_správce_skladu');
        }
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new OrdersControl($tmpResult,$this->translator->translate("Objednávky"),'Orders.latte', $this->StoreManager,
                                $this->StorageManager, $this->OrderManager, $this->ArraysManager, $this->translator);
    }



    protected function createComponentLimits()
    {
        if ($this->getUser()->getIdentity()->store_manager == 1) {
            $tmpResult['limits'] = true;
        }else{
            $tmpResult['limits'] = false;
            $tmpResult['message'] = $this->translator->translate('nejste_správce_skladu');
        }
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new LimitsControl($tmpResult,$this->translator->translate("Limity"),'Limits.latte', $this->PriceListLimitsManager, $this->translator);
    }


    protected function createComponentEventsList()
     {
//	$showData['helpdesk'] = $this->PartnersEventManager->findAll()->
//					select('"helpdesk" AS type,cl_users.nick_name, cl_partners_book.company, cl_partners_event.date_rcv,cl_partners_event.id')
//					->order('date_rcv DESC')->limit(5)->fetchAll();
//	$showData['invoice'] = $this->InvoiceManager->findAll()->
//					select('"invoice" AS type,cl_users.nick_name, cl_partners_book.company, cl_invoice.inv_date,cl_invoice.id,cl_currencies.currency_name')
//					->order('inv_date DESC')->limit(5)->fetchAll();
    $database = $this->InvoiceManager->getDatabase();
	$result = $database->query('(SELECT cl_partners_event.id,cl_partners_event.date_rcv AS date, cl_users.user_image AS user_image,cl_users.id AS cl_users_id, cl_users.nick_name AS nick_name,'
						. 'cl_partners_event.work_label AS title, '
						. 'cl_partners_book.company, '
						. '"helpdesk" AS type, '
						. '0 AS price_e2, '
						. '0 AS price_e2_vat, "        " AS currency_name '
						. 'FROM cl_partners_event '
						. 'LEFT JOIN cl_users ON cl_users.id = cl_partners_event.cl_users_id '
						. 'LEFT JOIN cl_partners_book ON cl_partners_book.id = cl_partners_event.cl_partners_book_id '
						. ' WHERE cl_partners_event.cl_company_id = '.$this->getUser()->getIdentity()->cl_company_id.'  ORDER BY date DESC LIMIT 5) UNION '.
						'(SELECT cl_invoice.id,cl_invoice.inv_date AS date, cl_users.user_image AS user_image, cl_users.id AS cl_users_id, cl_users.nick_name AS nick_name, '
						. 'cl_invoice.inv_title AS title, '
						. 'cl_partners_book.company, '
						. '"invoice" AS type, '
						. 'cl_invoice.price_e2, '
						. 'cl_invoice.price_e2_vat, cl_currencies.currency_name '
						. 'FROM cl_invoice '
						. 'LEFT JOIN cl_users ON cl_users.id = cl_invoice.cl_users_id '
						. 'LEFT JOIN cl_partners_book ON cl_partners_book.id = cl_invoice.cl_partners_book_id '
						. 'LEFT JOIN cl_currencies ON cl_currencies.id = cl_invoice.cl_currencies_id'
						. ' WHERE cl_invoice.cl_company_id = '.$this->getUser()->getIdentity()->cl_company_id.'  ORDER BY date DESC LIMIT 5)  ');

	//dump($result->fetch() == FALSE );
	//die;
       // $translator = clone $this->translator;
       // $translator->setPrefix([]);
	 return new EventsListControl($this->translator, $result,$this->translator->translate("Poslední_události"),'eventsList.latte');
     }                
     

    protected function createComponentGraphVolume()
     {
		$tmpshowData['invoice'] = $this->InvoiceManager->findAll()->select('DATE(CONCAT(YEAR(inv_date),"/",MONTH(inv_date),"/","01")) AS inv_date2,inv_date,SUM(price_e2_vat*currency_rate) AS price_e2_vat')
						->where('inv_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
						->order('inv_date2 DESC')
						->group('inv_date2')->limit(12)->fetchPairs('inv_date2','price_e2_vat');
		$tmpshowData['commission'] = $this->CommissionManager->findAll()->select('DATE(CONCAT(YEAR(cm_date),"/",MONTH(cm_date),"/","01")) AS cm_date2,cm_date,SUM(price_e2_vat*currency_rate) AS price_e2_vat')
						->where('cm_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
						->order('cm_date2 DESC')
						->group('cm_date2')->limit(12)->fetchPairs('cm_date2','price_e2_vat');		
		$tmpshowData['order'] = $this->OrderManager->findAll()->select('DATE(CONCAT(YEAR(od_date),"/",MONTH(od_date),"/","01")) AS od_date2,od_date,SUM(price_e2_vat*currency_rate) AS price_e2_vat')
						->where('od_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
						->order('od_date2 DESC')
						->group('od_date2')->limit(12)->fetchPairs('od_date2','price_e2_vat');				

		$showDataInvoice = array();
		foreach($tmpshowData['invoice'] as $key => $one)
		{
			$showDataInvoice[] = array($key,$one);
		}
		
		$showDataCommission = array();
		foreach($tmpshowData['commission'] as $key => $one)
		{
			$showDataCommission[] = array($key,$one);
		}		
		
		$showDataOrder = array();
		foreach($tmpshowData['order'] as $key => $one)
		{
			$showDataOrder[] = array($key,$one);
		}
		$showData['invoice'] = json_encode($showDataInvoice);
		$showData['commission'] = json_encode($showDataCommission);
		$showData['order'] = json_encode($showDataOrder);
		
		//dump($showData);
         //$translator = clone $this->translator;
         //$translator->setPrefix([]);
		 return new GraphControl($this->translator, $showData,$this->translator->translate("Finanční_objemy"), 'graphVolume', 'graph.latte');
     }                               
	 
    protected function createComponentGraphHelpdesk()
     {
		$tmpshowData['hdCount'] = $this->PartnersEventManager->findAll()->select('DATE(CONCAT(YEAR(date_to),"/",MONTH(date_to),"/","01")) AS date_to2,date_to,COUNT(1) AS hd_count')
						->where('finished = 1 AND date_to IS NOT NULL')
						->where('date_to >= DATE_SUB(NOW(),INTERVAL 12 MONTH)')
						->group('date_to2')->limit(12)->fetchPairs('date_to2','hd_count');
		$tmpshowData['hdSum'] = $this->PartnersEventManager->findAll()->select('DATE(CONCAT(YEAR(date_to),"/",MONTH(date_to),"/","01")) AS date_to2,date_to,SUM(work_time/60) AS hd_sum')
						->where('finished = 1 AND date_to IS NOT NULL')
						->where('date_to >= DATE_SUB(NOW(),INTERVAL 12 MONTH)')
						->group('date_to2')->limit(12)->fetchPairs('date_to2','hd_sum');

		$showDataCount = array();
		foreach($tmpshowData['hdCount'] as $key => $one)
		{
			$showDataCount[] = array($key,$one);
		}
		$showData['hdCount'] = json_encode($showDataCount);
		
		$showDataSum = array();
		foreach($tmpshowData['hdSum'] as $key => $one)
		{
			$showDataSum[] = array($key,$one);
		}
		$showData['hdSum'] = json_encode($showDataSum);		

		//dump($showData);
        // $translator = clone $this->translator;
        // $translator->setPrefix([]);
		 return new GraphControl($this->translator, $showData,"Helpdesk", 'graphHelpdesk', 'graph.latte');
     }                               	 

    protected function createComponentInfoBox()
     {
         $translator =  $this->translator;
         //$translator->setPrefix([]);
         //$translator = $translator->setPrefix(['applicationModule.Homepage']);

		$tmpUser = $this->UserManager->getUserById($this->user->getId());
		$lcSetting = $this->settings->name;
		if ($this->template->activeArchive != "dbCurrent"){
            $lcSetting .= ' - !! ' . $translator->translate('jste_v_archivu_!!');
        }
		$showData[] = [$translator->translate('Aktivní_firma'), $lcSetting];


		$showData[] = [$translator->translate('Přihlášený_uživatel'),$tmpUser->name];
        if (!is_null($tmpUser->last_login_prev))
        {
            $showData[] = [$translator->translate('Poslední_přihlášení'),date('d.m.Y H:i:s',strtotime($tmpUser->last_login_prev))];
        }

        $tmpExpDate = $this->UserManager->trfExpire($this->user->getId());
        $checkDate = new DateTime();
        if ($tmpExpDate < $checkDate->modify('+1 month')){
            $tmpColor = "red";
        }else{
            $tmpColor = "green";
        }
		$showData[] = ['<span style="color:'.$tmpColor.'">' . $translator->translate('Tarif_a_expirace') . '</span>', '<span style="color:'.$tmpColor.'">' . $tmpExpDate->format('d.m.Y') . '</span>'];

         $tmpExpDate = $this->UserManager->supportExpire($this->user->getId());
         $checkDate = new DateTime();
         if ($tmpExpDate < $checkDate->modify('+1 month')){
             $tmpColor = "red";
         }else{
             $tmpColor = "green";
         }
        $showData[] = ['<span style="color:'.$tmpColor.'">' . $translator->translate('Expirace_podpory') . '</span>', '<span style="color:'.$tmpColor.'">' . $tmpExpDate->format('d.m.Y') . '</span>'];
		$tmpSize = round($this->FilesManager->findAllTotal()->where('cl_company_id = ?', $tmpUser->cl_company_id)
							    ->sum('file_size') / 1024 / 1000,0);		
		$tmpRecords = $this->priceListManager->findAll()->count('id') + $this->partnersManager->findAll()->count('id');
		$licenseSize = $this->UserManager->trfDiskSpace($this->user->getId());
		if ($tmpSize * 1.1 > $licenseSize){
		    $tmpColor = "red";
        } elseif (($tmpSize * 1.25) > $licenseSize){
            $tmpColor = "orange";
        }else{
            $tmpColor = "green";
        }

		$showData[] = ['<span style="color:'.$tmpColor.'">'.
                            $translator->translate('Obsazené_a_volné_místo') . '</span>',
                            '<span style="color:'.$tmpColor.'">' . number_format($tmpSize,0,"."," "). $translator->translate('MB_ze') . number_format($licenseSize,0,"."," ").$translator->translate('MB'). '</span>'];
        $tarifRecords = $this->UserManager->trfRecords($this->user->getId());
        if (($tmpRecords * 1.1) > $tarifRecords){
            $tmpColor = "red";
        } elseif (($tmpRecords * 1.25) > $tarifRecords){
		    $tmpColor = "orange";
        }else{
            $tmpColor = "green";
        }

		$showData[] = ['<span style="color:'.$tmpColor.'">'.
                        $translator->translate('Záznamy_a_maximální_počet') . '</span>',  '<span style="color:'.$tmpColor.'">' . number_format($tmpRecords,0,"."," ") .
                        " / " .  number_format($tarifRecords,0,"."," ") . '</span>'];
		$link = $this->link('//:Front:Article:changelist', ['modal' => true]);
		$showData[] = [$translator->translate('Verze'), $this->parameters['app_version'] .
                        ' &nbsp; <a href=' . $link . ' title="otevře v novém okně přehled změn" class="modalClick" data-href="' . $link . '" data-title="Přehled novinek, oprav a změn v aplikaci")>seznam novinek</a>'];
		$link2 = "https://2hcs.cz/aktualni-stav-systemu-trynx/";
        $showData[] = [$translator->translate('Stav_systému_a_plán_odstávek'), '<a href=' . $link2 . ' title="' . $translator->translate('otevře_v_novém_okně_aktuální_stav_a_plán_odstávek_systému') . '" class="" target="_new" data-href="' . $link2 . '" data-title="Stav serveru")>' . $translator->translate("akutální_stav_a_plán_odstávek_serveru") . '</a>'];

		$showData[] = [FALSE, FALSE];
/*		$showData[] = [$translator->translate('Nezaplacené_faktury'),$this->InvoiceManager->findAll()->where('price_payed <> price_e2_vat')
					->sum('(price_e2_vat - price_payed) * currency_rate')];
		$showData[] = [$translator->translate('Faktury_po_splatnosti'),$this->InvoiceManager->findAll()->where('price_payed <> price_e2_vat AND due_date < NOW()')
					->sum('(price_e2_vat - price_payed) * currency_rate')];
		$showData[] = [$translator->translate('Nové_zakázky'), $this->CommissionManager->findAll()->where('cl_status.s_new = 1')
					->sum('price_e2_vat * currency_rate')];
*/

        // $translator = clone $this->translator;
        // $translator->setPrefix([]);
		return new InfoBoxControl($this->translator, $showData,$this->translator->translate("Licence"),'infoBox.latte', $this->settings);
     }                                    
    
    public function renderDefault() {
		$this->genMessages();
		$tmpUser = $this->UserManager->getUserById($this->user->getId());
		$tmpArr = json_decode($tmpUser->homepage_boxes, 'false');

	//	die;
		if (is_null($tmpArr))
		{
			$tmpArr['col1'] = [['infoVat',1], ['graphVolume',1], ['infoBox',1], ['invoices',1], ['invoicearrived',1], ['orders',1], ['commissionBox',1]];
			$tmpArr['col2'] = [['graphHelpdesk', 1], ['eventsList',1], ['limits',1]];
			$tmpJson = ['col1' => $tmpArr['col1'], 'col2' => $tmpArr['col2']];
			$this->UserManager->updateUser(['id' => $this->user->getId(), 'homepage_boxes' => json_encode($tmpJson)]);
		}

        //04.2022 - new module - default show
        if (!in_array('invoices',array_column($tmpArr['col1'],0)) && !in_array('invoices',array_column($tmpArr['col2'],0)))
        {
            $tmpArr['col1'][] = ['invoices',1];
            $tmpJson = ['col1' => $tmpArr['col1'], 'col2' => $tmpArr['col2']];
            $this->UserManager->updateUser(['id' => $this->user->getId(), 'homepage_boxes' => json_encode($tmpJson)]);
        }
        if (!in_array('invoicearrived',array_column($tmpArr['col1'],0)) && !in_array('invoicearrived',array_column($tmpArr['col2'],0)))
        {
            $tmpArr['col1'][] = ['invoicearrived',1];
            $tmpJson = ['col1' => $tmpArr['col1'], 'col2' => $tmpArr['col2']];
            $this->UserManager->updateUser(['id' => $this->user->getId(), 'homepage_boxes' => json_encode($tmpJson)]);
        }
        if (!in_array('commissionBox',array_column($tmpArr['col1'],0)) && !in_array('commissionBox',array_column($tmpArr['col2'],0)))
        {
            $tmpArr['col1'][] = ['commissionBox',1];
            $tmpJson = ['col1' => $tmpArr['col1'], 'col2' => $tmpArr['col2']];
            $this->UserManager->updateUser(['id' => $this->user->getId(), 'homepage_boxes' => json_encode($tmpJson)]);
        }

        if (!in_array('infoVat', array_column($tmpArr['col1'], 0)) && !in_array('infoVat', array_column($tmpArr['col2'], 0)))
        {

            //$tmpArr['col1'][] = ['infoVat',1];
            //
            $wrkArr[] = ['infoVat',1];
            foreach($tmpArr['col1'] as $key => $one){
                $wrkArr[] = $one;
            }

            $tmpArr['col1'] = $wrkArr;


            $tmpJson = ['col1' => $tmpArr['col1'], 'col2' => $tmpArr['col2']];
            $this->UserManager->updateUser(['id' => $this->user->getId(), 'homepage_boxes' => json_encode($tmpJson)]);
           // die;
        }

		$this->template->col1 = $tmpArr['col1'];
		$this->template->col2 = $tmpArr['col2'];
		$this->template->enableAutoPaging = true;

    }
    
    /**
     * generate messages to user by defined conditions and rules
     */
    public function genMessages()
    {
		if ($data = $this->CompaniesManager->getTable()->fetch())
		{
			if ($data['name'] == '' || $data['street'] == '' || $data['city'] == '')
			{
				$dataMess = [];
				$url = $this->link('Settings:default');
				$dataMess['cl_users_id'] = $this->user->getId();
                $dataMess['message'] = $this->translator->translate('Nemáte_doplněny_důležité_údaje_o_firmě_doplňte_je') . '<a href="'.$url.'">'. $this->translator->translate('zde') . '</a>.';
				$this->MessagesManager->insertMessage($dataMess,$this->user->getId());		
			}
			$bankAccount = $this->BankAccountsManager->findAll()->where('default_account = ?',1)->fetch();
			if (!$bankAccount || $bankAccount['account_number'] == '')
			{
				$dataMess = [];
				$url = $this->link('Settings:default');
				$dataMess['cl_users_id'] = $this->user->getId();
				$dataMess['message'] = $this->translator->translate('Nemáte_zadán_bankovní_účet_doplňte_jej') . '<a href="'.$url.'">' . $this->translator->translate('zde') . '</a>.';
				$this->MessagesManager->insertMessage($dataMess,$this->user->getId());		
			}


		}
    }
	
	public function handleUpdateBoxes($data = [])
	{
		$tmpUser = $this->UserManager->getUserById($this->user->getId());
		//dump($data);
		//die;
		if (!isset($data[0]))
			$data[0] = [];
		
		if (!isset($data[1]))
			$data[1] = [];
		
		$tmpData = ['col1' => $data[0],'col2' => $data[1]];
		$tmpUser->update(['homepage_boxes' => json_encode($tmpData)]);
		//$this->sendJson($tmpData);
		$this->terminate();
	}

}
