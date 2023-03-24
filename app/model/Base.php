<?php
namespace App\Model;

use Nette\Utils\Arrays;
use Netpromotion\Profiler\Profiler;

class Base
{
    /** @var Nette\Database\Context */
    public $database;
    public $userManager;
    public $user;
    public $companyId = NULL;
    private $session;

    private $arrToNotify =  ['in_complaint', 'in_complaint_items', 'in_complaint_users', 'cl_task'];

    /** @var string */
    public $tableName;

    protected $second_user = NULL;

    /** @var string */
    protected $userAccesTableName;


    /**
     * @inject
     * @var Nette\Http\Request
     */
    public $httpReuest;


    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor)
    {
	    //$this->database = $db;
        $this->userManager = $userManager;
        $this->user = $user;
        $this->session = $session;

        //$mySection = $this->session->getSection('company');
        //if (is_null($mySection['dbName'])) {
        //    $mySection['dbName'] = 'dbCurrent';
        //}
        //if (!defined("DBNAME")) {

        if ($this->user->isLoggedIn()){
            $tmpIdentity = $this->user->getIdentity();
            if (!empty($tmpIdentity->db_active)) {
                $GLOBALS['DBNAME'] = $tmpIdentity->db_active;
            }else{
                $GLOBALS['DBNAME'] = 'dbCurrent';
            }
        }else{
            $GLOBALS['DBNAME'] = 'dbCurrent';
        }
        $dbName = $GLOBALS['DBNAME'];
        //}

        //$this->database = $accessor->get($mySection['dbName']);
        $this->database = $accessor->get($dbName);

        //$database = $accessor->get("db2");
	    if($this->tableName === NULL) {
		    $class = get_class($this);
		    throw new \Nette\InvalidStateException("Název tabulky musí být definován v $class::\$tableName.");
	    }

    }

    public function getDatabase(){
        return $this->database;
    }

    /**
     * Vrací table, ale už filtrovanou o záznamy příslušející jen vybrané firmě nebo uživateli
     * @return type
     */
    protected function getTable() {
            $mySection = $this->session->getSection('company');
            if (is_null($mySection['cl_company_id'])) {
                $company_id = $this->userManager->getCompany($this->user->getId());
            } else {
                $company_id = $mySection['cl_company_id'];
            }
        //$this->session->close();
		$user_id = $this->user->getId();
		if ($this->userManager->isPrivate($this->tableName,$company_id,$user_id))
		{
			if ($this->userAccesTableName != '')
			{
			    //->select($this->tableName.'.*')
				$data = $this->database->table($this->tableName)
					->where($this->tableName.'.cl_company_id = ? AND ('.$this->tableName.'.cl_users_id = ?'
						. ' OR :'.$this->userAccesTableName.'.cl_users_id = ? )',$company_id,$user_id,$user_id);

				//25.04.2016 - from now users with limited access can see only own records, records without cl_user_id will be hidden.
				//the only one case in witch will be showen is when is enabled in userAccessTable
				//OR '.$this->tableName.'.cl_users_id IS NULL

			}else
			{
			    //bdump($this->second_user);
			    //->select($this->tableName.'.*')
                if (is_null($this->second_user)) {
                    $data = $this->database->table($this->tableName)
                        ->where($this->tableName . '.cl_company_id = ? AND (' . $this->tableName . '.cl_users_id = ? OR ' . $this->tableName . '.cl_users_id IS NULL)', $company_id, $user_id);
                }else{
                    $data = $this->database->table($this->tableName)
                        ->where($this->tableName . '.cl_company_id = ? AND (' . $this->tableName . '.cl_users_id = ? OR ' . $this->tableName . '.cl_users_id IS NULL OR 
                                                                                    ' . $this->tableName . '.cl_users_id2 = ?)', $company_id, $user_id,  $user_id);
                }
				//OR '.$this->tableName.'.cl_users_id IS NULL
			}
		}
		else{
		    //->select($this->tableName.'.*')
			$data = $this->database->table($this->tableName)
				->where(array($this->tableName.'.cl_company_id' => $company_id));
		}
		//07.07.2019 - added filter for cl_company_branch_id
        if ($this->hasTableBranchId())
        {
            //$arrBranches = json_decode( $this->user->getIdentity()->company_branches);
            $idBranch = $this->user->getIdentity()->cl_company_branch_id;
            $data->where($this->tableName.'.cl_company_branch_id = ? ', $idBranch);
            //if (count($arrBranches) > 0)
                //$data->where('cl_company_branch_id IN ? OR cl_company_branch_id IS NULL', $arrBranches);
        }

		return $data;
    }

    private function hasTableBranchId(){
        $arrTables = array('cl_sale', 'cl_cash', 'cl_invoice', 'cl_sale_shorts', 'cl_invoice_arrived', 'cl_store_docs', 'cl_delivery_note', 'cl_order', 'cl_offer', 'cl_commission', 'cl_transport');
        if ($this->user->isLoggedIn())
            $idBranch = $this->user->getIdentity()->cl_company_branch_id;
        else
            $idBranch = NULL;

        return in_array($this->tableName, $arrTables) && !is_null($idBranch);
    }

    public function getTableName() {
		return ($this->tableName);
    }


    /**
     * Vrací všechny záznamy z databáze
     * @return \Nette\Database\Table\Selection
     */
    public function findAll() {
	    return $this->getTable();
    }
    
    /**
     * Vrací všechny záznamy z databáze bez ohledu na vlastnickou cl_company_id
     * @return \Nette\Database\Table\Selection
     */
    public function findAllTotal() {
	    return $this->database->table($this->tableName);
    }
    
    
    /**
     * Vrací vyfiltrované záznamy na základě vstupního pole
     * (pole array('name' => 'David') se převede na část SQL dotazu WHERE name = 'David')
     * @param array $by
     * @return \Nette\Database\Table\Selection
     */
    public function findBy(array $by) {
	    return $this->getTable()->where($by);
    }

    /**
     * To samé jako findBy akorát vrací vždy jen jeden záznam
     * @param array $by
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function findOneBy(array $by) {
	    return $this->findBy($by)->limit(1)->fetch();
    }

    /**
     * Vrací záznam s daným primárním klíčem
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function find($id) {
	    return $this->getTable()->get($id);
    }

    /**
     * Upraví záznam
     * @param array $data
     * @param boolean $mark
     */
    public function update($data, $mark = TRUE) {
        if ($mark) {
            if ($this->user->isLoggedIn()) {
                $data['change_by'] = $this->user->getIdentity()->name;
                $data['changed'] = new \Nette\Utils\DateTime;
            } else {
                if (!isset($data['change_by'])) {
                    $data['change_by'] = 'automat';
                    $data['changed'] = new \Nette\Utils\DateTime;
                }
            }
        }

		if ($this->userAccesTableName != '')
		{
			//pokud má tabulka definovná přístupové práva ve své accesstable tak musíme nejprve zkusit získat ID záznamu, 
			//pokud jej získáme můžeme provést aktualizaci
			if ($tmpData = $this->findBy([$this->tableName.'.id'=>$data['id']])->fetch())
			{
                $cData = (array) $data;
                $this->historySave($cData);
                $this->database->table($this->tableName)->where(['id'=>$tmpData['id']])->update($data);
			}
		}
		else
		{
            $cData = (array) $data;
            //bdump($data);
            $this->historySave($cData);
            //bdump($data);
			$this->findBy(['id'=>$data['id']])->update($data);
		}
    }

    /**
     * Save history data
     * @param array $data
     */
    public function historySave($cData) {
        ///save previous values to history table
        $tmpData = $this->find($cData['id']);
        if (!$tmpData)
            return;

        $tmpData = $tmpData->toArray();
        if (is_null($tmpData['changed']))
            return;

        unset($tmpData['created']);
        unset($tmpData['create_by']);
        unset($tmpData['changed']);
        unset($tmpData['change_by']);
        unset($cData['created']);
        unset($cData['create_by']);
        unset($cData['changed']);
        unset($cData['change_by']);

        foreach($cData as $key => $one)
        {
            //bdump(is_numeric($one), $one . ' is_numeric?');
            if (is_object($one) && method_exists($one, 'format')){
                $cData[$key]  = $one->format('Y-m-d H:i:s');
                $one = $cData[$key];
            }
            if (is_bool($one)) {
                $cData[$key] = (int)$one;
            }elseif (is_numeric($one)) {
                $cData[$key] = (float)$one;
                $tmpData[$key] = (float)$tmpData[$key];
            }elseif ($this->check_date($one) && !is_null($tmpData[$key]) ) {
                //$data[$key] = new \DateTime($data[$key]);
                $tmpData[$key] = $tmpData[$key]->format('Y-m-d');
            }
        }
        $arrDiff = array_diff_assoc((array)$cData, $tmpData);

        if (count($arrDiff) > 0)
        {
            $arrDiffN = [];
            $arrDiffNv = [];
            foreach($arrDiff as $key => $one)
            {
                if (array_key_exists($key, $tmpData )) {
                    $arrDiffN[$key] = $tmpData[$key];
                    $arrDiffNv[$key] = $cData[$key];
                }
            }
            $arrData = [];
                $mySection = $this->session->getSection('company');
                if (is_null($mySection['cl_company_id'])) {
                    $company_id = $this->userManager->getCompany($this->user->getId())->id;
                } else {
                    $company_id = $mySection['cl_company_id'];
                }
            //$this->session->close();
            $arrData['cl_company_id'] = $company_id;

            $arrData['table_name'] = $this->getTableName();
            $arrData['parent_id'] = $cData['id'];
            $arrData['value'] = json_encode($arrDiffN);
            $arrData['value_new'] = json_encode($arrDiffNv);
            $tmpUser = "automat";
            $tmpUserId = NULL;
            if ($this->user->isLoggedIn()){
                if (!is_null($this->user->getIdentity()->name)){
                    $tmpUser = $this->user->getIdentity()->name;
                    $tmpUserId = $this->user->getId();
                }
            }
            $arrData['caller_info']     = $this->get_caller_info();
            $arrData['create_by']       = $tmpUser;
            $arrData['cl_users_id']     = $tmpUserId;
            $arrData['created']         = new \Nette\Utils\DateTime;
            //bdump($this->getTableName());
            //bdump($this->arrToNotify,'TEdyyyyyy');
            //bdump(array_search($this->getTableName(), $this->arrToNotify));
            if (is_int(array_search($this->getTableName(), $this->arrToNotify)))
                $arrData['to_send'] = 1;



            $this->database->table('cl_history')->insert($arrData);
        }
        return;
    }
    private function check_date($x) {
        //return (date('Y-m-d H:i:s', strtotime($x)) == $x);

        return (date('Y-m-d', strtotime($x)) == $x);
    }

    function get_caller_info() {
        $c = '';
        $file = '';
        $func = '';
        $class = '';
        $trace = debug_backtrace();
        if (isset($trace[2])) {
            $file = $trace[1]['file'];
            $func = $trace[2]['function'];
            if ((substr($func, 0, 7) == 'include') || (substr($func, 0, 7) == 'require')) {
                $func = '';
            }
        } else if (isset($trace[1])) {
            $file = $trace[1]['file'];
            $func = '';
        }
        if (isset($trace[3]['class'])) {
            $class = $trace[3]['class'];
            $func = $trace[3]['function'];
            $file = $trace[2]['file'];
        } else if (isset($trace[2]['class'])) {
            $class = $trace[2]['class'];
            $func = $trace[2]['function'];
            $file = $trace[1]['file'];
        }
        if ($file != '') $file = basename($file);
        $c = $file . ": ";
        $c .= ($class != '') ? ":" . $class . "->" : "";
        $c .= ($func != '') ? $func . "(): " : "";
        return($c);
    }



    /**
     * Vloží nový záznam a vrátí jeho ID
     * @param array $data
     * @return \Nette\Database\Table\ActiveRow
     */
    public function insert($data) {

            $mySection = $this->session->getSection('company');
            if (is_null($mySection['cl_company_id'])) {
                $company_id = $this->userManager->getCompany($this->user->getId());
            } else {
                $company_id = $mySection['cl_company_id'];
            }
        //$this->session->close();
        if(!is_null($company_id) && $company_id) {
            $data['cl_company_id'] = $company_id;
        }

        if (is_null($mySection['cl_company_id'])) {
            if ($this->userManager->isPrivate($this->tableName, $data['cl_company_id'], $this->user->getId())) {
                if ((!isset($data['cl_users_id']) || empty($data['cl_users_id'])) && isset($this->user->id)) {
                    $data['cl_users_id'] = $this->user->getId();
                }
            }
        }
            //if (isset($this->user->id))
            if ($this->user->isLoggedIn())
            {
                $data['create_by'] = $this->user->getIdentity()->name;
            } else {
                $data['create_by'] = '';
            }

            if ($data['create_by'] === NULL)
            {
                $data['create_by'] = '';
            }


	    $data['created'] = new \Nette\Utils\DateTime;
	    //bdump($data, 'data before insert');
	    return $this->getTable()->insert($data);
    }
    
    /**
     * Vloží nový záznam a vrátí jeho ID. Vkládá čistá data, neřeší vlastnickou firmu apod.
     * @param array $data
     * @return \Nette\Database\Table\ActiveRow
     */
    public function insertPublic($data) {
	    return $this->getTable()->insert($data);
    }    

    /**
     * 
     * @param int $id
     * @return int number of deleted records
     */
    public function delete($id) {
	    $retVal = 0;
	    if ($this->userAccesTableName != '')
	    {
		    //pokud má tabulka definovná přístupové práva ve své accesstable tak musíme nejprve zkusit získat ID záznamu, 
		    //pokud jej získáme můžeme provést aktualizaci
		    if ($tmpData = $this->findBy(array($this->tableName.'.id'=>$id))->fetch())
		    {
			    $retVal = $this->database->table($this->tableName)->where(array('id'=>$tmpData['id']))->delete();	
			    //$this->getTable()->where('id',$id)->delete();
		    }
	    }else
	    {
		    //file_put_contents( __DIR__.'/../../log/dump2.txt', $this->tableName );		    
		    $retVal = $this->getTable()->where('id',$id)->delete();
	    }
	    if ($retVal == 1)
	    {
		//13.09.2017 - save erased value for sync with 2hcs if erased is enabled
		$this->database->table('cl_erased_sync')->insert(array(	'src_table' => $this->tableName, 
									'src_id' => $id,
									'cl_company_id' => $this->user->getIdentity()->cl_company_id,
									'create_by' => $this->user->getIdentity()->name,
									'created' => new \Nette\Utils\DateTime
									));
	    }
		
		
	    return($retVal);
    }    
    
    
	
    /**
     * returns false if user have not access to given company
     * othervise returns activerow
     * @param type $cl_company_id
     * @return type
     */
    public function companyAccess($cl_company_id)
    {
	return $this->database->table('cl_access_company')
				->where('cl_users_id = ? AND cl_company_id = ?', $this->user->getId(), $cl_company_id)
				->limit(1)->fetch();
    }
 
    /**
     * Vloží nový záznam bez omezeni vlastnictvi
     * @param array $data
     * @return \Nette\Database\Table\ActiveRow
     */
    public function insertForeign($data) {
	    if (!isset($data[0])) { // not multi-insert
			if (!isset($data['create_by']) && $this->user->isLoggedIn())
			{
				$data['create_by'] = $this->user->getIdentity()->name;
				$data['created'] = new \Nette\Utils\DateTime;
			}else {
                if (!isset($data['create_by'])) {
                    $data['create_by'] = '';
                    $data['created'] = new \Nette\Utils\DateTime;
                }
            }
					
			
	    }
		return $this->database->table($this->tableName)->insert($data);
	    //return $this->getTable()->insert($data);
    }

    /**
     * Upraví záznam bez omezeni na nutnost vlastnictvi
     * @param array $data
     */
    public function updateForeign($data) {
		if ($this->user->isLoggedIn())
		{
			$data['change_by'] = $this->user->getIdentity()->name;
			$data['changed'] = new \Nette\Utils\DateTime;				
		}else{
			if (!isset($data['change_by']))
			{
				$data['change_by'] = 'automat';
				$data['changed'] = new \Nette\Utils\DateTime;
			}
			if (!isset($data['changed'])){
				$data['changed'] = new \Nette\Utils\DateTime;	
			}
			
		}
		

	    return $this->database->table($this->tableName)->where(array('id' => $data['id']))->update($data);
    }        
    
    
    /**
     * delete record from API module
     * @param int $id
     * @return int number of deleted records
     */
    public function deleteAPI($id, $cl_company_id) {
	try{
	    $retVal = $this->database->table($this->tableName)->where('cl_company_id = ? AND id = ?', $cl_company_id, $id)->delete();
	}catch (Exception $e) {
	    //if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451)
	    //{
		$retVal = 0;
	    //}
	}
	return($retVal);
    }    
        
	
    /*find specified field in table and return
     * @param str $fieldName
     * @return array
     */ 
    public function findField($fieldName)
    {
	$sql = "SHOW COLUMNS FROM `$this->tableName` LIKE '$fieldName'";
	$result = $this->database->query($sql);
	return $result->fetchAll();
	
    }
    
    /**
     * return array for selectboxes  with id and value of records which should be used according to their settings
     * main use is for cl_branches, cl_partners_book_workers
     * @param type $cl_partners_book_id
     * @return type
     */
    public function getUseRecords($cl_partners_book_id,$tableName)
    {
        if ($tableName == "cl_delivery_note_in")
            $tableName = "cl_delivery_note";

        if ($cl_partners_book_id != NULL){
            $tmpBranches = $this->findAll()
                        ->where('cl_partners_book_id = ? AND use_'.$tableName.' = ?', $cl_partners_book_id, 1 )
                        ->fetchPairs('id','b_name');
            if (!$tmpBranches){
            $tmpBranches = $this->findAll()
                        ->where('cl_partners_book_id = ?', $cl_partners_book_id)
                        ->fetchPairs('id','b_name');
            }
        }else{
            $tmpBranches = [];
        }
        return $tmpBranches;
    }


    public function getIndex($id, $finaldata)
    {
        /*//profiler::start();
        $finaldata2 = clone $finaldata;
        $finaldata2 = $finaldata2->select($this->tableName.'.id')->fetchPairs('id','id');
        //profiler::finish('getIndex 1');
        //profiler::start();*/
        //$this->database->query('SET @rank = 0;');
        //$finaldata = $finaldata->select($this->tableName.'.id, @rank:=@rank+1 AS rank');
        //$sqlBuilder = new \Nette\Database\Table\SqlBuilder($this->tableName, $this->database);
        //$sql = $this->database->table()->preprocess($sqlBuilder->buildInsertQuery());
        //$sql = $finaldata->getSql();
        //$param = $finaldata->getSqlBuilder()->getParameters();
        //bdump($sql);
        //bdump($param);
        //bdump($finaldata->id . ' ' . $finaldata->rank);
        $finaldata = $finaldata->select($this->tableName.'.id');
        $ret = 0;
        $found = FALSE;
        foreach ($finaldata as $key => $one){
            $ret++;
            if ($key == $id)
            {
                //$ret = $one->rank;
                //bdump($ret,'rank 2');
                //bdump($key, 'key 2');
                $found = TRUE;
                break;
            }

        }
        if ($found)
            $retVal = $ret;
        else
            $retVal = 1;

        ////profiler::finish('getIndex 2');
        return ($retVal);
        //die;
/*        SET @rank=0;
SELECT rank FROM (
        SELECT `id`, @rank:=@rank+1 AS rank
	FROM	 `cl_invoice`
	WHERE (`cl_invoice`.`cl_company_id` = 98) AND (`cl_company_branch_id` IN (1, 3, 4) OR
    `cl_company_branch_id` IS NULL)
	ORDER BY `inv_date` DESC, `id` DESC, `id` ASC) AS results
WHERE id = 8584*/

    }


    public function lock($id){
        $this->update(['id' => $id, 'locked' => TRUE]);
    }

    public function unlock($id){
        $this->update(['id' => $id, 'locked' => FALSE]);
    }

    public function isLockable(){
        $arrLockable = ['', 'cl_invoice', 'cl_commission', 'cl_invoice_arrived', 'cl_invoice_internal', 'cl_invoice_advance', 'cl_delivery_note', 'cl_offer', 'cl_order'];
        return (boolean)array_search($this->tableName, $arrLockable);
    }

    public function changeStatus($id, $cl_status_id){
        $this->update(['id' => $id, 'cl_status_id' => $cl_status_id]);
    }



    /**
     * Check if associative array contains an Error message
     *
     * @param array $data Usually array returned from some function
     * @return boolean
     * @example ['error' => 'This is an error message'] returns true
     * @example ['success' => ['data' => 'Some valid data to be processed']] return false
     */
    public static function hasError(array $data): bool
    {
        return (\array_key_exists('error', $data)) ? true : false;
    }

    
}