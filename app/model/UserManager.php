<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;

/**
 * Users management.
 */
class UserManager implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'cl_users',
        TABLE_NAME_B2B = 'cl_partners_book_workers',
        TABLE_NAME_SETTINGS = 'cl_tables_setting',
		TABLE_CL_ACCESS_COMPANY = 'cl_access_company',
		COLUMN_CLA_USERS_ID = 'cl_users_id',
		COLUMN_ID = 'id',
		COLUMN_CL_USERS_ID = 'cl_users.id',
		COLUMN_NAME = 'name',
		COLUMN_EMAIL = 'email',
        COLUMN_EMAIL_B2B = 'worker_email',
        COLUMN_PASSWORD_HASH = 'password',
		COLUMN_PASSWORD_HASH_B2B = 'b2b_password',
		COLUMN_ROLE = 'role',
		COLUMN_RANK = 'rank',
		COLUMN_WALLET = 'wallet',
		CONFIRM_KEY = 'confirm_key',
		CONFIRM_EXP = 'confirm_exp',
		COLUMN_LAST_RESPONSE = 'last_response',
		COLUMN_LAST_LOCATION = 'last_location',
		COLUMN_ERASED = 'erased',
        COLUMN_B2B_ENABLED = 'b2b_enabled',
		COLUMN_GRANTS = 'grants',
		COLUMN_GENDER = 'gender',
		COLUMN_LOCALE = 'locale',
		COLUMN_CHAT_ID = 'chat_id',
		COLUMN_COUNTRY = 'country',
		COLUMN_LAST_LOGIN = 'last_login',
		COLUMN_LAST_LOGIN_PREV = 'last_login_prev',
		COLUMN_COUNT_LOGIN = 'count_login',
		COLUMN_CONFIRM_KEY = 'confirm_key',
		COLUMN_ACCESS_TOKEN = 'access_token',
		COLUMN_EMAIL_CONFIRM_KEY = 'eml_confirm_key',
		COLUMN_EMAIL_CONFIRM_EXP = 'eml_confirm_exp',
		COLUMN_EMAIL_CONFIRMED = 'email_confirmed',
		COLUMN_WORK_RATE = 'work_rate',
		COLUMN_ACTIVE = 'active';

	private $arrTrf = array(0 => 'Start', 1 => 'Podnikatel', 2 => 'Servis', 3 => 'Max');

	/** @var Nette\Database\Context */
	private $database;

	/** @var ArraysManager */
	private $ArraysManager;

	private $httpRequest;
	//private $session;
	private $arCompany	= NULL;
	private $arUsersRole	= NULL;
	private $arIsAllowed	= NULL;

	public function __construct(Nette\Database\Context $database, Nette\DI\Container $service,
                                ArraysManager $arraysManager)
	{
		$this->database = $database;
		$this->httpRequest = $service->getService('httpRequest');
        //$this->session = $session;
        $this->ArraysManager = $arraysManager;


	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials): Nette\Security\IIdentity
	{
		list($username, $password) = $credentials;
		$password = self::removeCapsLock($password);

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL, $username)->fetch();

		$b2bUser = false;
		if (!$row) {
		    //21.05.2020 - try to find in B25B users
            $row = $this->database->table(self::TABLE_NAME_B2B)->where(self::COLUMN_EMAIL_B2B, $username)->fetch();
            if (!$row) {
                throw new Nette\Security\AuthenticationException('Přihlášení_se_nepodařilo_zadaný_email_není_registrován', self::IDENTITY_NOT_FOUND);
            }elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH_B2B])) {
                throw new Nette\Security\AuthenticationException('Přihlášení_se_nepodařilo_zadali_jste_špatné_heslo', self::INVALID_CREDENTIAL);
            } elseif (!$row[self::COLUMN_B2B_ENABLED]) {
                throw new Nette\Security\AuthenticationException('Přihlášení_není_možné_účet_byl_zablokován', self::INVALID_CREDENTIAL);
            }elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH_B2B])) {
                $row->update(array(
                    self::COLUMN_PASSWORD_HASH_B2B => Passwords::hash($password),
                ));
            }else {
                $b2bUser = true;
            }

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('Přihlášení_se_nepodařilo_zadali_jste_špatné_heslo', self::INVALID_CREDENTIAL);
		} elseif ($row[self::COLUMN_ERASED]) {
			throw new Nette\Security\AuthenticationException('Přihlášení_není_možné_účet_byl_zablokován', self::INVALID_CREDENTIAL);
		} elseif ($row[self::COLUMN_EMAIL_CONFIRMED] == 0) {
            throw new Nette\Security\AuthenticationException('Registrace_nebyla_dokončena_kliknutím_na_aktivační_odkaz_Zkontrolujte_svou_emailovou_schránku', self::INVALID_CREDENTIAL);
        }
        elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}


		
		//26.06.2014 - login is succesfull, save login datetime and increase counter
		$tmpNow = new Nette\Utils\DateTime;
		//dump($tmpNow);
		//die;
		$row->update(array(self::COLUMN_LAST_LOGIN => $tmpNow, self::COLUMN_COUNT_LOGIN => $row->count_login + 1, self::COLUMN_LAST_LOGIN_PREV => $row->last_login));
		
		//08.03.2015 - update cl_users_log table
		$tmpIp = $this->httpRequest->getRemoteAddress();
		if ($b2bUser){
            $row2 = $this->database->table('cl_users_log')->insert(array('cl_partners_book_workers_id' => $row->id, 'login_date_time' => $tmpNow, 'login_ip' => $tmpIp));
        }else{
            $row2 = $this->database->table('cl_users_log')->insert(array('cl_users_id' => $row->id, 'login_date_time' => $tmpNow, 'login_ip' => $tmpIp));
        }

        $arr = $row->toArray();
        unset($arr[self::COLUMN_PASSWORD_HASH]);

        $result = $this->CompanyBranchCheck($row->id, $row->cl_company_id);
        if (!$b2bUser) {
            $this->database->table('cl_users')->where('id = ?', $row->id)
                            ->update(array('company_branches' => $result['tmpJson'], 'cl_company_branch_id' => $result['activeBranchId']));
            $arr['company_branches'] = $result['tmpJson'];
            $arr['cl_company_branch_id'] = $result['activeBranchId'];
            if ($row[self::COLUMN_ROLE] == 'admin'){
                $role = $row[self::COLUMN_ROLE];
            }else{
                if (!is_null($row->cl_users_role_id)) {
                    $role = $row->cl_users_role['name']; //$row[self::COLUMN_ROLE];
                }else {
                    $role = 'user';
                }
            }
            //18.06.2021 - check and set license for user
            $this->setLicense($row->id);

           //21.04.2021 - removed due to huge ammount of session files
           // $mySection = $this->session->getSection('company');
           // $mySection['cl_company_id'] = NULL;
        }else{
            $role = 'b2b';
        }
        //check cl_company_id access
        $arrCompanies = $this->database->table('cl_access_company')->where('cl_users_id = ?', $row->id )->fetchPairs('cl_company_id','cl_company_id');
        if (!array_key_exists($row['cl_company_id'], $arrCompanies)){
           if (count($arrCompanies) > 0){
               $arr['cl_company_id'] = array_keys($arrCompanies)[0];
               $row->update(array('cl_company_id' => array_keys($arrCompanies)[0]));
           }

        }
        //05.11.2021 - update in_places_id in user profile
        if (!$b2bUser) {
            $arrUsersGroups = json_decode($row['cl_users_groups_id'], true);
            $arrPlaces = [];
            $strPlaces = '';
            if ($arrUsersGroups > 0) {
                $tmpPlaces = $this->database->table('cl_users_groups')->select('in_places_id')->where('id IN (?)', $arrUsersGroups);
                foreach ($tmpPlaces as $key => $one) {
                    $arrOneRecPlaces = json_decode($one['in_places_id'], true);
                    if ($arrOneRecPlaces > 0) {
                        foreach ($arrOneRecPlaces as $key2 => $one2) {
                            $arrPlaces[] = $one2;
                        }
                    }
                }
                $strPlaces = json_encode($arrPlaces);
            }

            $row->update(['in_places_id' => $strPlaces]);
            $arr['in_places_id'] = json_encode($strPlaces);
        }

		return new Nette\Security\Identity($row[self::COLUMN_ID], $role, $arr);
	}


	public function CompanyBranchCheck($cl_users_id, $cl_company_id)
    {
        if (!is_null($cl_company_id)) {
            //07.07.2019 - update json in cl_users.company_branches
            $arrCompanyBranchUsers = $this->database->table('cl_company_branch_users')->where('cl_users_id = ? AND cl_company_id = ?', $cl_users_id, $cl_company_id);
            $arrIds = array();
            $activeBranchId = NULL;
            foreach ($arrCompanyBranchUsers->fetchPairs('cl_company_branch_id') as $key => $one) {
                $activeBranchId = $key;
                $arrIds[$key] = $key;
            }
            if ($tmpDefaultBranch = $arrCompanyBranchUsers->order('default_branch DESC')->fetch())
                $activeBranchId = $tmpDefaultBranch->cl_company_branch_id;
        }else{
            $arrIds = array();
            $activeBranchId = NULL;
        }

        $tmpJson = json_encode($arrIds);
        return array('tmpJson' => $tmpJson, 'activeBranchId' => $activeBranchId);
    }


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password)
	{
		return $this->database->table(self::TABLE_NAME)->insert(array(
			self::COLUMN_NAME => $username,
			self::COLUMN_PASSWORD_HASH => Passwords::hash(self::removeCapsLock($password)),
		));
	}

	/**
	 * Fixes caps lock accidentally turned on.
	 * @return string
	 */
	public static function removeCapsLock($password)
	{
		return $password === Strings::upper($password)
			? Strings::lower($password)
			: $password;
	}
	
	/**
	 * Adds new user from registration form.
	 * @param  array
	 * @return array
	 */
	public function addRegistration($values)
	{
	    if ($this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_EMAIL => $values->email))->fetch()!=NULL)
		throw new \Nette\Security\AuthenticationException ('Registrace pro tento email již byla provedena.');
		
	    $values->password = Passwords::hash(self::removeCapsLock($values->password));
	    //$values['cl_users_id'] = $presenter->user->getId();	    
	    //$values['create_by'] = $presenter->user->getIdentity()->name;
	    $values['created'] = new \Nette\Utils\DateTime();
	    if (isset($values['heslo1']))
            {
                unset($values['heslo1']);
            }
	    if (isset($values['heslo2']))
            {
                unset($values['heslo2']);
            }
	    return $this->database->table(self::TABLE_NAME)->insert($values);
	}

	/**
	 * Generates confirmation string and expiration datetime
	 * @param type $id
	 * @return type
	 */
	public function genConfirmation($id)
	{
	    $urlKey = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
	    //uložíme urlKey a datetime expirace
	    $expDate = new \Nette\Utils\DateTime();
	    $expDate->modify('+3 hour');
	    $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))
		     ->update(array(self::CONFIRM_KEY => $urlKey, self::CONFIRM_EXP =>  $expDate));
	    return ( array('urlKey' => $urlKey, 'expDate' => $expDate));
	}

    /**
     * Generates confirmation string and expiration datetime
     * @param type $id
     * @return type
     */
    public function genConfirmationB2B($id)
    {
        $urlKey = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
        //uložíme urlKey a datetime expirace
        $expDate = new \Nette\Utils\DateTime();
        $expDate->modify('+3 hour');
        $this->database->table(self::TABLE_NAME_B2B)->where(array(self::COLUMN_ID => $id))
                                    ->update(array(self::CONFIRM_KEY => $urlKey, self::CONFIRM_EXP =>  $expDate));
        return ( array('urlKey' => $urlKey, 'expDate' => $expDate));
    }


    /**
	 * Confirm account by key
	 * @param type $id
	 * @return type
	 */
	public function confirm($email,$access_token)
	{
	    //erase CONFIRM_KEY
	    $returnId = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ACCESS_TOKEN . '= ? AND ' . self::COLUMN_EMAIL . '= ? ',$access_token, $email)->fetch();
	    //$return = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ACCESS_TOKEN . '= ? AND ' . self::COLUMN_EMAIL . '= ? ',$access_token, $email)
		//     ->update(array(self::COLUMN_ACCESS_TOKEN => ''));
	    $returnId->update(array(self::COLUMN_ACCESS_TOKEN => '', self::COLUMN_ACTIVE => 1));
	    return ($returnId->id);
	}
	
	/**
	 * Confirm email by key
	 * @param type $id
	 * @return type
	 */
	public function confirmEmail($email,$access_token)
	{
	    //erase CONFIRM_KEY
	    if ($returnId = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL_CONFIRM_KEY . '= ? AND ' . self::COLUMN_EMAIL . '= ? ',$access_token, $email)->fetch())	
	    {
		$returnId->update(array(self::COLUMN_EMAIL_CONFIRM_KEY => '', self::COLUMN_EMAIL_CONFIRMED => 1));

		return ($returnId->id);
	    }else
		return false;

	}	
	
	
	
	public function getAll()
	{
	    return ($this->database->table(self::TABLE_NAME));
	}
	
	public function getAdmins()
	{
	    return ($this->database->table(self::TABLE_NAME)->where(self::COLUMN_ROLE.' LIKE "admin" OR '.self::COLUMN_ROLE.' LIKE "tester"'));
	}	
	
	public function getUser($email)
	{
	    return ($this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL . '= ? ' ,$email)->fetch());
	}

	public function getUserById($id)
	{
	    return ($this->database->table(self::TABLE_NAME)->select('*')->where(self::COLUMN_ID . '= ? ' ,$id)->fetch());
	}
	

	/**
	 * Change password for given confirm_key and values in form
	 * @param type $id
	 * @return type
	 */
	public function changePass($values)
	{
	    //erase CONFIRM_KEY
	    $expDate = new \Nette\Utils\DateTime();
	    //$expDate->modify('+3 hour');
	    $return = $this->database->table(self::TABLE_NAME)->where(self::CONFIRM_KEY . '= ? AND ' . self::CONFIRM_EXP . '>= ?',$values['key'],$expDate)
		     ->update(array(self::CONFIRM_KEY => '', self::COLUMN_PASSWORD_HASH => Passwords::hash(self::removeCapsLock($values->password))
			));
	    return ($return);
	}

    /**
     * Change password for given confirm_key and values in form
     * @param type $id
     * @return type
     */
    public function changePassB2B($values)
    {
        //erase CONFIRM_KEY
        $expDate = new \Nette\Utils\DateTime();
        //$expDate->modify('+3 hour');
        $return = $this->database->table(self::TABLE_NAME_B2B)->where(self::CONFIRM_KEY . '= ? AND ' . self::CONFIRM_EXP . '>= ?',$values['key'],$expDate)
            ->update(array(self::CONFIRM_KEY => '', self::COLUMN_PASSWORD_HASH_B2B => Passwords::hash(self::removeCapsLock($values->password))
            ));
        return ($return);
    }

    /**
	 * Return users Rank
	 * @param type $id
	 * @return type
	 */
	public function getUserRank($id)
	{
	    return($this->database->table(self::TABLE_NAME)->select(self::COLUMN_RANK)->where(self::COLUMN_ID. '=?', $id)->fetch());
	}

	/**
	 * Return amount of user wallet
	 * @param type $id
	 * @return type
	 */
	public function getWallet($id)
	{
		return($this->database->table(self::TABLE_NAME)->select(self::COLUMN_WALLET)->where(self::COLUMN_ID. '=?', $id)->fetch());
	}
	
	/**
	 * Minus amount from wallet
	 * @param type $id
	 */
	public function minusWallet($id,$amount)
	{
//	    dump($id);
	    //dump($amount);
//	    die;
	    return($this->database->table(self::TABLE_NAME)
			    ->where(self::COLUMN_ID. '=?', $id)
			    ->update(array(self::COLUMN_WALLET =>  new \Nette\Database\SqlLiteral(self::COLUMN_WALLET.' - '.$amount)))
			  );
	    
	}
	
	/**
	 * Update users record
	 * @param type $values
	 */
	public function updateUser($values)
	{
	    if (isset($values['password']))
            $values['password']  = Passwords::hash(self::removeCapsLock($values['password']));

        $values['changed'] = new \Nette\Utils\DateTime();
	    
	    if (isset($values['email']))
	    {
            if ($this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL.'=? AND id != ?', $values['email'], $values['id'])->fetch()!=NULL)
                throw new Exception ('email_exists');
            }
	    
	    return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID. '=?',$values['id'])->update($values);	    
	}
	
	/**
	 * Insert new Admin 
	 * @param string $values
	 * @return type
	 */
	public function newAdmin($values)
	{
	    if (isset($values->password) && !empty($values->email))
	    {
		$values->password = Passwords::hash(self::removeCapsLock($values->password));
		$values['role'] = 'admin';
	        return $this->database->table(self::TABLE_NAME)->insert($values);	    
	    }else
		throw new Exception ('Nový uživatel nebyl vytvořen, nebylo zadáno heslo nebo email.','1');
	}	
	
	public function verifyPass($oldPass,$user_id)
	{
	    $row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $user_id)->fetch();	    
	    if (!$row)
		throw new Nette\Security\AuthenticationException('wrong_user', self::IDENTITY_NOT_FOUND);
	    
	    return Passwords::verify($oldPass, $row[self::COLUMN_PASSWORD_HASH]);
	}
	
	/**
	 * return admin users which have setResponse at last 60 sec.
	 * @return type
	 */
	public function getActiveAdmin()
	{
	    $now = new \Nette\Utils\DateTime();
	    $now = $now->modify('-60 sec');
	    return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ROLE.'= ? AND '.self::COLUMN_LAST_RESPONSE.'>= ?',"admin",$now)->select('*');
	}
	
	/**
	 * return users which have setResponse at last 15 sec.
	 * @return type
	 */
	public function getActiveUsers()
	{
	    $now = new \Nette\Utils\DateTime();
	    $now = $now->modify('-25 sec');
	    return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ROLE.'!= ? AND '.self::COLUMN_LAST_RESPONSE.'>= ? ',"admin",$now )->select('*');
	}	
	
	/**
	 * set last response for current logged admin/user
	 * @param type $id
	 */
	public function setResponse($id, $chat_id = NULL)
	{
	    $now = new \Nette\Utils\DateTime();
	    return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->update(array(self::COLUMN_LAST_RESPONSE => $now, self::COLUMN_CHAT_ID => $chat_id));
	}

	/**
	 * set last location for current logged user
	 * @param type $id
	 */
	public function setLocation($value,$id)
	{
	    $now = new \Nette\Utils\DateTime();
	    //$arrValue = array();
	    $arrValue[] = array($now,$value);
	    //get current values from last_location
	    if ($row = $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->fetch())
	    {
		$arrLast_location = json_decode($row->last_location);
		//append new location at top of list
		if (count($arrLast_location)>0)
		{
		 //   dump($arrLast_location);
		    if ($arrLast_location[0][1] != $value)  //only if it's different then last 
		    {
			array_unshift($arrLast_location,array($now,$value));
			unset($arrLast_location[10]);
		    }
		}else
		    $arrLast_location = $arrValue; //very first record
		
		return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->update(array(self::COLUMN_LAST_LOCATION => json_encode($arrLast_location)));			
	    }
	    return;		

	}	
	
	
	/**
	 * check if logged user have grant
	 * @param type $grant
	 */
	public function haveGrant($id,$grant)
	{
	    $result = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID.' = '.$id)->fetch();
	    $grants = json_decode($result->grants,TRUE);
	    $value =  array_search($grant, $grants);
	    return $value;
	}
	
	/**
	 * delete one admin
	 * @param type $id
	 * @return type
	 */
	public function deleteAdmin($id)
	{
		return $this->database->table(self::TABLE_NAME)->where('id = ? AND role = ?', $id, 'admin')->delete();
	}
	
	/**
	 * set user as erased, erased users aren't able to connect
	 * @param type $id
	 */
	public function blockUser($id)
	{ 
	    return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->update(array(self::COLUMN_ERASED => '1'));
	}

	/**
	 * delete one user
	 * @param type $id
	 */
	public function deleteUser($id)
	{ 
	    return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->delete();
	}
        
        
	/**
	 * return user by give facebook id
	 * @param type $id
	 * @return type
	 */
	public function findByFacebookId($id)
	{
	    return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_FBID => $id))->fetch();
	}

	
	/**
	 * insert new user by facebook
	 * 
	 */
	public function registerFromFacebook($fbData, $me)
	{
	    return $this->database->table(self::TABLE_NAME)
			 ->insert(array(self::COLUMN_FBID => $me->id,
					self::COLUMN_NAME => $me->first_name,
					self::COLUMN_SURNAME => $me->last_name,
					self::COLUMN_EMAIL => $me->email,
					self::COLUMN_GENDER => $me->gender,
					self::COLUMN_LOCALE => substr($me->locale,0,2),
					self::COLUMN_COUNTRY => 'nic'));
	}
	
	public function updateFacebookAccessToken($fbid,$fbat)
	{
	    return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_FBID => $fbid))->update(array(self::COLUMN_FBAT => $fbat));
	}

	
	public function activateByAdmin($values)
	{
	    return $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $values))->update(array(self::COLUMN_CONFIRM_KEY => ''));
	}

	public function getCompany($id)
	{
	    if ($this->arCompany == NULL)
	    {
		    $this->arCompany = $this->database->table(self::TABLE_NAME)->where(array(self::COLUMN_CL_USERS_ID => $id))->select('cl_company.*')->fetch();
	    }
	    if ($this->arCompany && !is_null($this->arCompany)){
			return $this->arCompany;
	    }else{
			return FALSE;
	    }
	}
	
	public function getUserCompanies($id)
	{
	    if ($company = $this->database->table(self::TABLE_CL_ACCESS_COMPANY)
					    ->where(array(self::COLUMN_CLA_USERS_ID => $id))
					    ->select('cl_access_company.id AS cl_access_company_id,cl_company.*'))
		return $company;
	    else
		return FALSE;
	}	
	
	public function getUsersInCompany($company_id)
	{
	    if ($UsersInCompany = $this->database->table(self::TABLE_NAME)
					    ->where(array(':cl_access_company.cl_company_id' => $company_id)))
	    	return $UsersInCompany;
	    else
		    return FALSE;
	}		
	
	/*returns users in structury by active/notactive
	 * 05.08.2018
	 */
	public function getUsersAN($company_id)
	{
	    $arrUsers = array();
	    $arrUsers['Aktivní'] = $this->getUsersInCompany($company_id)->where('not_active = 0')->order('name')->fetchPairs('id','name');
	    $arrUsers['Neaktivní'] = $this->getUsersInCompany($company_id)->where('not_active = 1')->order('name')->fetchPairs('id','name');	    
	    
	    return $arrUsers;
	}
	
	
	public function isPrivate($tableName,$company_id,$user_id)
	{

		if ($this->arUsersRole == NULL)
		{
		    $this->arUsersRole = $this->database->table('cl_users')
						->where(array('cl_users.cl_company_id' => $company_id,
							      'cl_users.id' => $user_id))
						->select('cl_users_role.*')
						->fetch();		    
		}
		if ($this->arUsersRole)
		{
		    $data = json_decode($this->arUsersRole->map_role, TRUE);

		    if (isset($data[$tableName]))
		    {
			if ($data[$tableName] == TRUE)
			    $result = 1;
			else
			    $result = 0;
		    }else
		    {
			$result = 0;
		    }
		}else
		    $result = 0;

		return $result;
	    
	}

	public function isModuleAllowed($presenterName, $userId)
    {
        $tmpModule = $this->trfModuleEnable($presenterName,$userId);
        if ($tmpModule['result'] == false){
            return false;
        }else{
            return true;
        }
    }
	
	public function isAllowed($presenterName,$actionName, $userId, $special = NULL)
	{



	    if ($this->arIsAllowed == NULL){
		    $this->arIsAllowed = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $userId)->fetch();
	    }
	    if ($this->arIsAllowed)
	    {
			if ($this->arIsAllowed->cl_users_role_id != NULL)
				$usersRole = json_decode($this->arIsAllowed->cl_users_role->map_role,TRUE);

           // bdump($presenterName, 'presenter');
            //bdump($usersRole,'usersRole');
			$presenterNameAction = strtolower(str_replace(':', '_', $presenterName).'_'.$actionName);
			if (!is_null($special)){
                $presenterNameAction .= '_'.$special;
            }
			//bdump($presenterNameAction, 'presenterNameAction');

			if (isset($usersRole[$presenterNameAction])) {
               //bdump($usersRole[$presenterNameAction], 'find');

                return $usersRole[$presenterNameAction];
            }
			else
			{
			    //dump($presenterNameAction);
				return true;
			}
	    }else {
			return false;
	    }
	}


	/*
	 * 11.12.2018 - return only row with license
	 */
	private function getLicense($user_id)
	{
	    return $this->database->table('cl_users')
                        ->where(array('cl_users.id' => $user_id))
						->where('cl_users_license.license_end >= CURDATE()')
                        ->where(array('cl_users_license.status' => 'PAID'))
                        ->select('cl_users_license.*')
                        ->order('cl_users_license.license_end DESC')
						->limit(1)
                        ->fetch();
	}

    private function saveLicense($user_id, $arrUserModules){
	    $license = $this->getLicense($user_id);

        $this->database->table('cl_users_license')->where(['id' => $license['id']])->update( ['license' => json_encode($arrUserModules)]);

    }

	
    /*
     * serch and set available license to cl_users.cl_users_license_id
     */
    public function setLicense($user_id)
    {
        $tmpCompany = $this->getCompany($user_id);
        if (is_null($tmpCompany->id)){
            return FALSE;
        }

        //1. find company admin
        //2. find licence for company admin
        $companyAdmin = $this->database->table('cl_access_company')
                                        ->where('admin = 1 AND cl_company_id = ?', $tmpCompany->id)->limit(1)->fetch();
        if ($companyAdmin)
        {
            $adminLicense = $this->database->table('cl_users_license')
                                            ->where(array(  'status' => 'PAID',
                                                            'cl_users_id' => $companyAdmin->cl_users_id))
                                            ->where('license_end >= CURDATE()')
                                            ->order('license_end DESC')
                                            ->limit(1)
                                            ->fetch();
            if ($adminLicense)
            {

                $this->updateUser(array('id' => $user_id, 'cl_users_license_id' => $adminLicense->id));
                $retVal = $adminLicense;
            }else{
                $retVal = FALSE;
            }
        }else{
            $retVal = FALSE;
        }

        return $retVal;

    }
	
	/*
	 * return expire date of active license
	 */
	public function trfExpire($user_id)
	{
                if ($result = $this->database->table('cl_users')
                                                ->where(array('cl_users.id' => $user_id))
						                        ->where('cl_users_license.license_end >= CURDATE()')
                                                ->where(array('cl_users_license.status' => 'PAID'))
                                                ->select('cl_users_license.*')
                                                ->order('cl_users_license.license_end DESC')
						                        ->limit(1)
                                                ->fetch())                        
                        
		{
			$retVal = $result->license_end;
		}else{
			//there is no active tarif, we are at free tarif, return expiration of trial period
			if ($row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $user_id)->fetch())
			{
				$retVal = $row->created->modify('+1 month');
			}else{
				$retVal = NULL;
			}
		}		
		
		return $retVal;
	}

    /*
     * return support expire date of active license
     */
    public function supportExpire($user_id)
    {
        if ($result = $this->database->table('cl_users')
            ->where(array('cl_users.id' => $user_id))
            ->where('cl_users_license.license_end >= CURDATE()')
            ->where(array('cl_users_license.status' => 'PAID'))
            ->select('cl_users_license.*')
            ->order('cl_users_license.license_end DESC')
            ->limit(1)
            ->fetch())

        {
            $retVal = $result['support_end'];
        }else{
            //there is no active tarif, we are at free tarif, return expiration of trial period
            if ($row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $user_id)->fetch())
            {
                $retVal = $row->created->modify('+1 month');
            }else{
                $retVal = NULL;
            }
        }

        return $retVal;
    }



    /**
     * return if requested module is enabled for this user
     * @param type $module_name
     * @param type $user_id
     * @return array
     */
    public function trfModuleEnable($module_name,$user_id)
    {
        $dtmNow = new \Nette\Utils\DateTime();
        $tmpUsrLic = $this->getLicense($user_id);
        $reason = [];
        if ($tmpUsrLic) {
            $arrUserModules = json_decode($tmpUsrLic['license'], true);
            $boolResult = TRUE;
            $tmpModules2P = $this->ArraysManager->getModules2P();
            if (isset($tmpModules2P[$module_name])) {
                //module is payed, check if it's not expired
                if (isset($arrUserModules[$module_name])) {
                    //module was active, so deactivate now
                    if ($arrUserModules[$module_name]['exp'] < $dtmNow) {
                        $boolResult = FALSE;
                        $reason[] = 'Licenci_pro_tento_modul_skončila_platnost';
                    }else {
                        //check if user's allowed
                        if (isset($arrUserModules[$module_name]['users'][$user_id])) {
                            $boolResult = TRUE;
                        } else {
                            //user is new then check number of used users
                            if (isset($arrUserModules[$module_name]['users'])) {
                                $arrUsers = $arrUserModules[$module_name]['users'];
                            } else {
                                $arrUsers = [];
                            }
                            if ($arrUserModules[$module_name]['quant'] <= count($arrUsers)) {
                                //quantity exceeded
                                $boolResult = FALSE;
                                $reason[] = 'Je_obsazen_maximální_počet_uživatelů_pro_tuto_licenci';
                            } else {
                                //quantity OK - add new user
                                $arrUserModules[$module_name]['users'][$user_id] = $user_id;
                                $this->saveLicense($user_id, $arrUserModules);
                                //$boolResult = TRUE;
                            }
                        }
                    }

                } else {
                    //module is not defined, it's disabled
                    $boolResult = FALSE;
                    $reason[] = 'Pro_tento_modul_nemáte_zakoupenu_licenci';
                }
            }else{
                //module si not payed, it's enabled
                $boolResult = TRUE;
            }
        }else{
            //if is account still in trial period (it's when there is no license for user) enable otherwise no
            $tmpUsr = $this->getUserById($user_id);
            if ($tmpUsr && $tmpUsr['created'] <= $dtmNow->modify('-1 month'))
            {
                $boolResult = FALSE;
                $reason[] = 'Skončila_zkušební_doba_Objednejte_si_prosím_licenci_pro_požadované_moduly_aplikace';
            }else {
                $boolResult = TRUE;
            }
        }

        return ['result' => $boolResult, 'reason' => $reason];
    }


    /*
     * return number of allowed pricelist by license
     */
    public function trfRecords($user_id)
    {
        if ($tmpLicense = $this->getLicense($user_id))
        {
            $arrLicense = json_decode($tmpLicense['license'], true);
            if (isset($arrLicense['Records'])){
                $result = $arrLicense['Records']['quant'] * 5000;
            }else{
                $result = 5000 ;
            }
        }else{
            $result = 5000;
        }
        return $result;
    }


    /*
     * return number of allowed diskspace by license
     */
    public function trfDiskSpace($user_id)
    {
        if ($tmpLicense = $this->getLicense($user_id))
        {
            $arrLicense = json_decode($tmpLicense['license'], true);
            if (isset($arrLicense['Storage'])){
                $result = $arrLicense['Storage']['quant'] * 500;
            }else{
                $result = 500 ;
            }
        }else{
            $result = 500;
        }
        return $result;
    }


    /*07.07.2018
     * save settings of table
     */
	public function saveTable($type, $table_name, $header_id = NULL, $size = NULL, $cols = NULL, $user_id = NULL, $height = NULL)
	{
        $tmpCompany = $this->getCompany($user_id);
	    if ($tmpUser = $this->database->table(self::TABLE_NAME_SETTINGS)->where('cl_company_id = ? AND cl_users_id = ? AND table_name = ?' ,$tmpCompany['id'], $user_id, $table_name)->fetch())
	    {
		//$arrTable = json_decode($tmpUser['tables_settings'],true);
            $arrTable = json_decode($tmpUser['grid_columns'],true);
		    //bdump($arrTable);
            if ($type == "size")
            {
                $arrTable[$type][$header_id] = (int)$size;
                $saveValue = json_encode($arrTable);
            }elseif ($type == "order")
            {
                $arrTable[$type] = $cols;
                $saveValue = json_encode($arrTable);
            }elseif ($type == "tableheight")
            {
                $arrTable[$type] = (int)$height;
                $saveValue = json_encode($arrTable);
            }

            //$tmpUser->update(array('tables_settings' => $saveValue));
            //bdump($arrTable);
            //bdump($saveValue);
            //bdump($tmpUser->id);
           // $this->database->table(self::TABLE_NAME_SETTINGS)->where('id = ?', $tmpUser->id)->update(array('grid_columns' => $saveValue));
            $tmpUser->update(['grid_columns' => $saveValue]);
		
	    }
	}
	
	/*07.07.2018
	 * get settings of table
	 */
	public function getTableParam($type, $table_name, $user_id)
	{
	    //01.08.2020 - save old values to new table
      /*  if ($tmpUser = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID . '= ?' ,$user_id)->fetch()) {
            $arrTable = json_decode($tmpUser['tables_settings'], true);
            if (!is_null($arrTable)) {
                foreach ($arrTable as $key => $one) {
                    $tmpSettings = $this->database->table(self::TABLE_NAME_SETTINGS)->where('cl_users_id = ? AND table_name = ?', $user_id, $key)->fetch();
                    if ($tmpSettings) {
                        $tmpSettings->update(array('grid_columns' => json_encode($one)));
                    } else {
                        $arrData = array();
                        $tmpCompany = $this->getCompany($user_id);
                        $arrData['cl_company_id'] = $tmpCompany->id;
                        $arrData['cl_user_id'] = $user_id;
                        $arrData['table_name'] = $table_name;
                        $arrData['grid_columns'] = json_encode($one);
                        $this->database->table(self::TABLE_NAME_SETTINGS)->insert($arrData);
                    }
                    $tmpUser->update(array('tables_settings' => ''));
                }
            }
        }
      */
        //die;
	    if ($type == "tableheight")
	    {
			$retVal = 0;
	    }else{
			$retVal = new \Nette\Utils\ArrayHash;
	    }
        $tmpCompany = $this->getCompany($user_id);
	    if ($tmpUser = $this->database->table(self::TABLE_NAME_SETTINGS)->where( 'cl_company_id = ? AND cl_users_id = ? AND table_name = ?' , $tmpCompany['id'], $user_id, $table_name)->fetch())
	    {
	       // bdump($tmpUser);
			//$arrTable = json_decode($tmpUser['tables_settings'],true);
            $arrTable = json_decode($tmpUser['grid_columns'],true);
			//bdump($arrTable, $type);
			//foreach()
			/*if (isset($arrTable[$table_name][$type]))
			{
				$retVal = $arrTable[$table_name][$type];
			}*/
            if (isset($arrTable[$type]))
            {
                $retVal = $arrTable[$type];
            }
            //if (is_array($retVal))
            //    bdump($retVal, ' retval');
	    }
        //bdump($retVal);
	    return $retVal;
	}
	
	/*07.07.2018
	 * reset table name
	 */
	public function resetTable($table_name, $user_id)
	{
        $tmpCompany = $this->getCompany($user_id);
	    if ($tmpTableSet = $this->database->table(self::TABLE_NAME_SETTINGS)->where(  'cl_company_id = ? AND cl_users_id = ? AND table_name = ?',  $tmpCompany->id, $user_id, $table_name)->fetch())
	    {
            //$arrTable = json_decode($tmpUser['tables_settings'],true);
            //bdump($arrTable);
            //bdump($table_name);
            //unset($arrTable[$table_name]);
            //  bdump($arrTable);
            //$saveValue = json_encode($arrTable);
            //$tmpUser->update(array('tables_settings' => $saveValue));
            $tmpTableSet->delete();

	    }
	}

	public function setGridRowsTable($table_name, $userId, $gridRowsValue, $enableAutoPaging){
        $tmpCompany = $this->getCompany($userId);
        if ($enableAutoPaging == 'true'){
            $enableAutoPaging = 1;
        }else{
            $enableAutoPaging = 0;
        }
        /*dump($enableAutoPaging);
        die;*/
        if ($tmpTableSet = $this->database->table(self::TABLE_NAME_SETTINGS)->where(  'cl_company_id = ? AND cl_users_id = ? AND table_name = ?' , $tmpCompany->id, $userId, $table_name)->fetch())
        {
            $tmpTableSet->update(['grid_rows' => $gridRowsValue, 'enable_autopaging' => $enableAutoPaging]);
        }

    }
	
	/*26.07.2018
	 * return worker tax
	 */
	public function getWorkerTax($user_id)
	{
	    $retVal = 0;
	    if ($tmpUser = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID . '= ? ' ,$user_id)->fetch())
	    {
		$retVal = $tmpUser[self::COLUMN_WORK_RATE];
	    }		    
	    return $retVal;
	}


    public function setProgressBar($value = 0,$max = 0, $user_id, $message = "")
    {
        //$section = $this->session->getSection('progressbar');
        $tmpCompany = $this->getCompany($user_id);
        $fileName = $user_id.".json";
        $dataFolder = $this->ArraysManager->getDataFolder($tmpCompany->id);
        $destFile =  $dataFolder . '/progress_bar/' . $fileName;
        /*if ($tmpUser = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID . '= ? ' ,$user_id)->fetch()) {
            $tmpArr=  array('progress_val' => $value, 'progress_max' => $max);
            if ($message != ""){
                $tmpArr['progress_message'] = $message;
            }
            $tmpUser->update($tmpArr);
        }*/
        $tmpArr=  array('progress_val' => $value, 'progress_max' => $max);
        if ($message != ""){
            $tmpArr['progress_message'] = $message;
        }
        $ret = file_put_contents($destFile, json_encode($tmpArr));

    }

    public function resetProgressBar($user_id)
    {
        /*if ($tmpUser = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID . '= ? ' ,$user_id)->fetch()) {
           $tmpArr=  array('progress_val' => 0, 'progress_max' => 0, 'progress_message' => '');
            $tmpUser->update($tmpArr);
        }*/
        $tmpCompany = $this->getCompany($user_id);
        $fileName = $user_id.".json";
        $dataFolder = $this->ArraysManager->getDataFolder($tmpCompany->id);
        $destFile =  $dataFolder . '/progress_bar/' . $fileName;
        $tmpArr=  array('progress_val' => 0, 'progress_max' => 0, 'progress_message' => '');
        $ret = file_put_contents($destFile, json_encode($tmpArr));

    }

    public function getProgressBar($user_id)
    {
        $tmpCompany = $this->getCompany($user_id);
        $fileName = $user_id.".json";
        //bdump($fileName);
        Debugger::log($fileName, 'progressbar');
        if ($tmpCompany){

            $dataFolder = $this->ArraysManager->getDataFolder($tmpCompany->id);
            $destFile =  $dataFolder . '/progress_bar/' . $fileName;
            if (file_exists($destFile))
                $retStr = file_get_contents($destFile);
            else
                $retStr = json_encode(array('progress_val' => 0, 'progress_max' => 0, 'progress_message' => ''));
        }else{
            $retStr =  json_encode(array('progress_val' => 0, 'progress_max' => 0, 'progress_message' => ''));
        }
        //bdump($retStr);
        Debugger::log($retStr, 'progressbar');
        return ($retStr);
    }

    /** return array of user places
     * @param Nette\Security\IIdentity|null $getIdentity
     * @return mixed
     */
    public function getUserPlaces(?Nette\Security\IIdentity $getIdentity)
    {
        $userData = $getIdentity;
        $strInPlaces =  (string)$userData->in_places_id;
        $arrUserPlaces = json_decode($strInPlaces, true);
        $arrUserPlaces = json_decode($arrUserPlaces, true);
        return $arrUserPlaces;
    }

    public function getEmail($cl_users_id, $name = FALSE)
    {
        $retVal = '';
        if (!is_null($cl_users_id) && $tmpUser = $this->getUserById($cl_users_id)){
            $retVal = $tmpUser['email'];
            if ($this->validateEml($retVal) == ''){
                $retVal = $tmpUser['email2'];
                if ($this->validateEml($retVal) == '')
                    $retVal = '';
            }

            if ($name && $retVal != ''){
                $retVal = ['email' => $retVal, 'name' => $tmpUser['name']];
            }elseif ($name && $retVal == ''){
                $retVal = [];
            }

        }elseif ($name){
            $retVal = [];
        }
        return $retVal;
    }

    private function validateEml($eml)
    {
        if (!filter_var($eml, FILTER_VALIDATE_EMAIL)) {
            $eml = "";
        }
        return $eml;
    }

}


