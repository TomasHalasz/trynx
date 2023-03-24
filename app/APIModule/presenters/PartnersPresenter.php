<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class PartnersPresenter   extends \App\APIModule\Presenters\BaseAPI{
    

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $DataManager;
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;	

    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $UsersManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    public function actionSet()
    {
        parent::actionSet();

        //***tests only***//
        /*\Tracy\Debugger::$productionMode = false;
        \Tracy\Debugger::$showBar = true;
        $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
        $this->dataxml = file_get_contents($dataFolder . '/data.xml');*/
        //***tests only***//


        $xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
        //echo($xml);
        $json = json_encode($xml);
        //echo($json);
        $arrData = json_decode($json,TRUE);
        $arrData = $arrData['partner'];
        //dump($arrData);
        //die;
        //$arrData = json_decode($this->dataxml,true);
        //echo($arrData);
        foreach($arrData as $key => $one){

            $tmpData = $this->DataManager->findAllTotal()->
                            where(['cl_company_id' => $this->cl_company_id])->
                            where('(ico = ? AND ico !="") OR company = ?', $one['ico'], $one['company'] )->
                            limit(1)->fetch();
            if ($tmpData){
                $arrData2 = [];
                //$arrComment = str_getcsv($tmpData['comment'], '[[');
                $arrData2['comment'] = nl2br($one['comment']);

                //$arrData['delivered'] = ($one['Delivered'] == 'true') ? 1 : 0;

                $tmpData->update($arrData2);
            }

            /*foreach($one as $key2 => $one2) {
                echo($key2);
                echo(" : " . $one2);
                echo("<br>");
            }*/
        }
        echo('OK');
        $this->terminate();
    }

    public function actionGet()
    {
        parent::actionGet();
    }


}
