<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class QrPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

//    /**
//    * @inject
//    * @var \App\Model\PartnersManager
//    */
//    public $DataManager;
	
//   /**
//    * @inject
//    * @var \App\Model\CountriesManager
//    */
//    public $CountriesManager;
	
	public function actionGet()
	{
            $httpRequest = $this->context->getByType('Nette\Http\Request');
            //echo $httpRequest->getMethod();            
            if ($httpRequest->isMethod('GET'))
            {
                $get = $httpRequest->getQuery(); // vrací pole všech parametrů z URL
            }elseif ($httpRequest->isMethod('POST'))
            {
                $get = $httpRequest->getPost(); // vrací pole všech parametrů z URL
            }
            //$id = $httpRequest->getQuery('id'); // vrací GET parametr 'id' (nebo NULL)            
            //dump($get);
            //dump($id);
            //die;
            if (!isset($get['am']) || !isset($get['vs']) || !isset($get['dt']) || !isset($get['cc']) || !isset($get['acc']) || !isset($get['id']) || !isset($get['msg']) ||
                !isset($get['dd']) || !isset($get['vii']) || !isset($get['ini']) || !isset($get['vir']) || !isset($get['inr']) || !isset($get['duzp']) ||
                !isset($get['dppd']) || !isset($get['on']) || !isset($get['sa']) || !isset($get['td']) || !isset($get['tb0']) || !isset($get['t0']) ||
                !isset($get['tb1']) || !isset($get['t1']) || !isset($get['tb2']) || !isset($get['t2']) || !isset($get['ntb']) || !isset($get['fx']))
            {
                echo('Incomplete values');
                $this->terminate();
            }
            
            $qrCode = $this->qrService->getQrInvoice(array(
                                                            'am'		=> (int)$get['am'], //amount
                                                            'vs'		=> $get['vs'], //v. symbol
                                                            'dt'		=> $get['dt'], //due date
                                                            'cc'		=> $get['cc'], //currency code
                                                            'acc'		=> $get['acc'], //bank account
                                                            'id'		=> $get['id'], //invoice number,
                                                            'msg'		=> $get['msg'], //message
                                                            'dd'		=> $get['dd'], //invoice date
                                                            'tp'		=> 0,
                                                            'vii'		=> $get['vii'], //main dic,
                                                            'ini'		=> $get['ini'], //main ico,
                                                            'vir'		=> $get['vir'], //partner dic,
                                                            'inr'		=> $get['inr'], //partner ic,
                                                            'duzp'		=> $get['duzp'], //duzp
                                                            'dppd'		=> $get['dppd'], //datum povinnosti priznat dan
                                                            'on'		=> $get['on'], //číslo objednávky
                                                            'sa'		=> $get['sa'], //1-faktura obsahuje zuctovani zaloh 0-neobsahuje 
                                                            'td'		=> $get['td'], //typ dokladu  0 - nedanovy, 1-opravny danovy, 2-doklad k prijate platbe, 3-splatkovy kalendar
                                                                                                //4-platebni kalendar,5-souhrnny danovy doklad, 9-ostatni danove doklady
                                                            'tb0'		=> $get['tb0'],// zaklad dph v zakladni sazbe
                                                            't0'		=> $get['t0'], //dan v zakladi sazbe
                                                            'tb1'		=> $get['tb1'], //zaklad dph v prvni snizene sazbe
                                                            't1'		=> $get['t1'], //dan v prvni snizene sazbe
                                                            'tb2'		=> $get['tb2'], //zaklad dph v druhe snizene sazbe
                                                            't2'		=> $get['t2'], //dan v druhe snizene sazbe
                                                            'ntb'		=> $get['ntb'], //castka osvozobezenych plneni
                                                            'fx'		=> $get['fx'], //kurz
                                                            'fxa'		=> 1, //prepocet kurzu
                                                            'msg2'              => $get['msg2']
                    ));
                $httpResponse = $this->context->getByType('Nette\Http\Response');
                $httpResponse->setContentType('image/jpg');
		echo($qrCode);
       // $this->session->destroy();
		$this->terminate();		
	}

    
}
