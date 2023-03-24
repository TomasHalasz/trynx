<?php

namespace MainServices;

use \Nette\Mail\Message,
    \Nette\Templating\FileTemplate,
    \Nette\Latte\Engine;

/**
 * Validator service
 *
 * @author     Tomas Halasz
 * @package    Klienti
 */
class ValidatorService {

    private $db;

    public function __construct(\Nette\Database\Context $database) {
        $this->db = $database;
    }

    /**
     * returns array of valid VAT rates for country and date
     * @param type $country
     * @param type $date
     */
    public function getValidVAT($date,$country = 'CZECH REPUBLIC'){
	$arrVat = array();
	if ($country == 'CZECH REPUBLIC')
	{
	 $arrVat['free'] = 0;
	 $arrVat['snizena1'] = 10;
	 $arrVat['snizena2'] = 15;
	 $arrVat['zakladni'] = 21;
	}
	return $arrVat;
    }
    
    /**
     * returns array of valid payments type
     * @param type $lang
     */
    public function getValidPayments($lang = 'CZECH'){
	$arr = array();
	if ($lang == 'CZECH')
	{
	 $arr['transfer'] = 'převodem';
	 $arr['cash'] = 'hotovost';
	 $arr['cash_on_delivery'] = 'dobírka';
	}
	return $arr;
    }    
    
    
    public function isValidBankAccount($bank_account) {
        if (preg_match("/^([0-9]+[\-])?+[0-9]+$/", $bank_account->getValue())) {
            return true;
        } else {
            return false;
        }
    }

    public function isValidIco($ic, $isControl = true) {
        if($isControl){
            $ic = $ic->getValue();
        }
        $ic = preg_replace('#\s+#', '', $ic);

        if (!preg_match('#^\d{8}$#', $ic)) {
            return FALSE;
        }
        $a = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += $ic[$i] * (8 - $i);
        }
        $a = $a % 11;
        if ($a === 0)
            $c = 1;
        elseif ($a === 10)
            $c = 1;
        elseif ($a === 1)
            $c = 0;
        else
            $c = 11 - $a;

        return (int) $ic[7] === $c;
    }

    public function isExistPartner($access_id, $ico, $name){
        $partner = $this->db->createQuery('SELECT p FROM ApplicationModule\Partner p WHERE p.access_id = :access AND (p.ico = :ico OR p.name = :meno)');
        $partner->setParameter("access", $access_id);
        $partner->setParameter("ico", $ico);
        $partner->setParameter("meno", $name);
        
        return count($partner->getResult()) > 0 ? false : true;
    }
    
    public function isExistPartnerById($access_id, $ico, $name, $id){
        $partner = $this->db->createQuery('SELECT p FROM ApplicationModule\Partner p WHERE (p.access_id = :access AND (p.ico = :ico OR p.name = :meno)) AND p.id <> :id');
        $partner->setParameter("access", $access_id);
        $partner->setParameter("ico", $ico);
        $partner->setParameter("meno", $name);
        $partner->setParameter("id", $id);
        
        return count($partner->getResult()) > 0 ? false : true;
    }
    
    
    public function isExistUserEmail($email, $user_id = null){
	$counter = $this->db->table('cl_users')->where('email = ?', $email)->count();
        return $counter > 0 ? true : false;

    }
    
    public function isExistCompany($ico, $name, $id = null){
        if($id == null){
            $company = $this->db->createQuery('SELECT c FROM LoginModule\Company c WHERE c.ico = :ico OR c.name = :meno');
            $company->setParameter("ico", $ico);
            $company->setParameter("meno", $name);
        } else {
            $company = $this->db->createQuery('SELECT c FROM LoginModule\Company c WHERE (c.ico = :ico OR c.name = :meno) AND c.id <> :id');
            $company->setParameter("ico", $ico);
            $company->setParameter("meno", $name);
            $company->setParameter("id", $id);
        }
        
        return count($company->getResult()) > 0 ? false : true;
    }
    
    public function isExistIdentificationNumber($id_number, $company, $id = null){
        if(is_object($id_number)){
            $id_number = $id_number->getValue();
        }
        if($id == null){
            $id_number = $this->db->getRepository('ApplicationModule\Pricelist')->findBy(array('identification' => $id_number, 'access_id' => $company)); 
        } else {
            $idnumber = $this->db->createQuery('SELECT c FROM ApplicationModule\Pricelist c WHERE c.identification = :id_number AND c.access_id = :access AND c.id <> :id');
            $idnumber->setParameter("id_number", $id_number);
            $idnumber->setParameter("access", $company);
            $idnumber->setParameter("id", $id);
            $id_number = $idnumber->getResult();
        }
        
        return count($id_number) > 0 ? false : true;
    }
    
    public function isExistNumberSeries($model, $company, $id = null){
        if(is_object($model)){
            $model = $model->getValue();
        }
        if($id == null){
            $nSeries = $this->db->getRepository('ApplicationModule\NumberSeries')->findBy(array('model' => $model, 'access_id' => $company)); 
        } else {
            $series = $this->db->createQuery('SELECT c FROM ApplicationModule\NumberSeries c WHERE c.model = :model AND c.access_id = :access AND c.id <> :id');
            $series->setParameter("model", $model);
            $series->setParameter("access", $company);
            $series->setParameter("id", $id);
            $nSeries = $series->getResult();
        }
        
        return count($nSeries) > 0 ? false : true;
    }
    

}

