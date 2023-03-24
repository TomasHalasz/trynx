<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Partners management.
 */
class PartnersManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_partners_book';
	
	
	/** @var App\Model\Base */
	public $CountriesManager;

//    $this->tableName = 'cl_partners_book';

	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                    CountriesManager $CountriesManager, \Nette\Http\Session $session)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      
	      $this->CountriesManager = $CountriesManager;
	      
	  }    		

	public function GetAres2($ico)
	{
            	//const URL = 'http://2hcssro.savana-hosting.cz/aresproxy/getares.php'; 
		$ares = new \halasz\Ares\Ares();
		$result = $ares->loadData($ico); // return object \halasz\Ares\Data
		$arrResult = $result->toArray();
		if ($tmpCountries = $this->CountriesManager->findAllTotal()->where(array('name' => 'Česko'))->fetch())
		{
			
			$arrResult['cl_countries_id'] = $tmpCountries->id;
		}
		return($arrResult);
	}	
	
	/**
	 * Return due date for given partner
	 * @param type $id  - cl_partners_book.id
	 * @param \Nette\Utils\DateTime $date   - start date
	 * @return type
	 */
	public function getDueDate($id, \Nette\Utils\DateTime $date)
	{
	    $tmpData = $this->find($id);
	    if ($tmpData->due_date > 0){
		$tmpDays = $tmpData->due_date;
	    }else{
		$tmpDays = $tmpData->cl_company->due_date;
	    }

	    $newDate = $date->modify('+'.$tmpDays.' days');
	    return $newDate;
	}
	
	
	public function getPaymentType($id)
	{
	    $tmpData = $this->find($id);
	    if ($tmpData->cl_payment_types_id != null){
		    $tmpType = $tmpData->cl_payment_types_id;
	    }else{
		    $tmpType = $tmpData->cl_company->cl_payment_types_id;
	    }

	    return $tmpType;	    
	}
	
	/**
	 * update header and footer in given DataManager if is setting on cl_partners record
	 * @param type $id
	 * @param type $cl_partners_book_id
	 * @param type $dataManager
	 */
	public function useHeaderFooter($id, $cl_partners_book_id, $dataManager, $force = FALSE, $header = TRUE, $footer = TRUE)
	{
	    //09.03.2019 - headers and footers from partnersbook if are set
	    if ($tmpMainData = $dataManager->find($id)){
			$tmpData = $this->findAll()->where('id = ?', $cl_partners_book_id)->limit(1);
			foreach($tmpData as $one)
			{

                $arrUpdate = array();
                $arrUpdate['id'] = $id;
                $arrUpdate['cl_partners_book_id'] = $cl_partners_book_id;

				if (isset($tmpMainData['header_txt']) && isset($tmpMainData['footer_txt'])){

                    //$arrUpdate = array();
                    //$arrUpdate['id'] = $id;

					if ($header) {
						if ($one['header_app'] == 1) {
							$arrUpdate['header_txt'] = $tmpMainData['header_txt'] . $one['header_txt'];
						} else {
							if (trim($tmpMainData['header_txt']) == '' || $force) {
								$arrUpdate['header_txt'] = $one['header_txt'];
							}
						}
					}
					if ($footer) {
						if ($one['footer_app'] == 1) {
							$arrUpdate['footer_txt'] = $tmpMainData['footer_txt'] . $one['footer_txt'];
						} else {
							if (trim($tmpMainData['footer_txt']) == '' || $force) {
								$arrUpdate['footer_txt'] = $one['footer_txt'];
							}
						}
					}
					//$dataManager->update($arrUpdate);
				}
                $dataManager->update($arrUpdate);
			}
	    }
	}

    /**Set partners active if they have records after given date
     *return [active,notactive] sum
     * @param $data
     * @return array
     */
    public function setNotActive($data){

	    //find active
	    $arrInvoices = $this->findAll()->where(':cl_invoice.inv_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $arrInvoicesArrived = $this->findAll()->where(':cl_invoice_arrived.inv_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $arrDeliverN = $this->findAll()->where(':cl_delivery_note.issue_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $arrCommission = $this->findAll()->where(':cl_commission.cm_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $arrStore = $this->findAll()->where(':cl_store_docs.doc_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $arrOffer = $this->findAll()->where(':cl_offer.offer_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $arrOrder = $this->findAll()->where(':cl_order.od_date >= ?', $data['date_to'])->select('DISTINCT cl_partners_book.id')->fetchPairs('id', 'id');
        $ids = array_merge($arrInvoices, $arrInvoicesArrived);
        $ids = array_merge($ids, $arrDeliverN);
        $ids = array_merge($ids, $arrCommission);
        $ids = array_merge($ids, $arrStore);
        $ids = array_merge($ids, $arrOffer);
        $ids = array_merge($ids, $arrOrder);

        //set all inactive
        $activeData = $this->findAll()->where('active = 1');
        foreach ($activeData as $key => $one){
            $this->update(array('id' => $key, 'active' => 0));
        }

        //set active
        $activeData = $this->findAll()->where('active = 0 AND id IN (?)', $ids);
        foreach ($activeData as $key => $one){
            $this->update(array('id' => $key, 'active' => 1));
        }
        $numActive = $this->findAll()->where('active = 1')->count('id');
        $numNotActive = $this->findAll()->where('active = 0')->count('id');

        return array('active' => $numActive, 'notactive' => $numNotActive);
    }



	
}

