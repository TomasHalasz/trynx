<?php

namespace App\Model;

use Nette,
    Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Nette\Utils\Image;

/**
 * Companies management.
 */
class CompaniesManager extends Base
{
    const COLUMN_ID = 'id';
    public $tableName = 'cl_company';


    /** @var ArraysManager */
    public $ArraysManager;

    /** @var \App\Model\UserManager */
    public $UserManager;
    public $session;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                ArraysManager $ArraysManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->ArraysManager = $ArraysManager;
        $this->UserManager = $userManager;
        $this->session = $session;
        $this->db = $db;
    }


    /**
     * Vrací table bez filtru na vlastnickou firmu
     * @return type
     */
    public function getTable()
    {


            $mySection = $this->session->getSection('company');

            if (is_null($mySection['cl_company_id'])) {
                if (!is_null($this->user->getId())) {
                    $userId = $this->user->getId();
                    $companyId = $this->user->getIdentity()->cl_company_id;
                    $ret = $this->database->table($this->tableName)->select('cl_company.*')
                        ->where(':cl_access_company.cl_users_id = ? AND cl_company.id = ?', $userId, $companyId);
                } else {
                    $userId = 0;
                    $companyId = 0;
                    $ret = $this->database->table($this->tableName)->select('cl_company.*')
                        ->where(':cl_access_company.cl_users_id = 0 AND cl_company.id = 0');
                }
            } else {
                $companyId = $mySection['cl_company_id'];
                $ret = $this->database->table($this->tableName)->select('cl_company.*')
                    ->where('id = ?', $companyId);
            }

       // $this->session->close();
        //bdump($userId);
        //bdump($companyId);
        return $ret;
        //AND :cl_users.id = :cl_access_company.cl_users_id
    }

    /**
     * Vrací table bez filtru na vlastnickou firmu
     * @return type
     */
    protected function getTableUpdate()
    {
        return $this->database->table($this->tableName);
    }


    /**
     * Vrací vyfiltrované záznamy na základě vstupního pole
     * (pole array('name' => 'David') se převede na část SQL dotazu WHERE name = 'David')
     * @param array $by
     * @return \Nette\Database\Table\Selection
     */
    public function findByUpdate(array $by)
    {
        return $this->getTableUpdate()->where($by);
    }


    /**
     * Upraví záznam
     * @param array $data
     */
    public function update($data, $mark = FALSE)
    {

        if ($mark) {
            $data['change_by'] = $this->user->getIdentity()->name;
            $data['changed'] = new \Nette\Utils\DateTime;
        }
        if ($this->companyAccess($data['id'])) {
            $this->findByUpdate(array('id' => $data['id']))->update($data);
        } else {
            return false;
        }
    }

    /** return cl_users_id for given company_id
     * @param $cl_company_id
     * @return int
     */
    public function getAdminId($cl_company_id) : int
    {
       if ($ret =  $this->database->table($this->tableName)->select(':cl_access_company.cl_users_id AS id')
                    ->where(':cl_access_company.admin = 1 AND :cl_access_company.cl_company_id = ?', $cl_company_id)
                    ->limit(1)->fetch()){
           $retId = $ret['id'];
       }else{
           $retId = NULL;
       }
       return $retId;
    }

    /**
     * set current moment as end of synchronization
     * @param type $cl_company_id
     */
    public function setSyncEnd($cl_company_id)
    {
        $this->getTableUpdate()->where('id', $cl_company_id)->update(array('sync_last' => new \Nette\Utils\DateTime()));
    }


    /**
     * Vloží nový záznam a vrátí jeho ID
     * @param array $data
     * @return \Nette\Database\Table\ActiveRow
     */
    public function insert($data)
    {
        $data['create_by'] = $this->user->getIdentity()->name;
        $data['created'] = new \Nette\Utils\DateTime;
        $new = $this->getTableUpdate()->insert($data);

        //insert also to cl_access_company
        $data2 = new \Nette\Utils\ArrayHash;
        $data2['cl_company_id'] = $new->id;
        $data2['admin'] = 1;
        $data2['cl_users_id'] = $this->user->getId();
        $this->database->table('cl_access_company')->insert($data2);

        return $new;
    }

    public function delete($id)
    {
        if ($this->companyAccess($id)) {
            $this->getTableUpdate()->where('id', $id)->delete();
        }
    }


    /**
     * returns false if user is not admin in cl_access_company
     * othervise returns active row
     * @param type $cl_company_id
     * @return type
     */
    public function companyAdmin($cl_company_id, $user_id = NULL)
    {
        if (is_null($user_id)) {
            $user_id = $this->user->getId();
        }
        return $this->database->table('cl_access_company')
            ->where('cl_users_id = ? AND cl_company_id = ? AND admin = 1', $user_id, $cl_company_id)
            ->limit(1)->fetch();
    }




    public function getDataFolder($company_id)
    {
        return $this->ArraysManager->getDataFolder($company_id);
    }

    /**Return data for signing EET message. It solves company or branches
     * @param $company_branch_id
     * @return array
     */
    public function getDataForSignEET($company_branch_id)
    {
        $tmpCompany = $this->getTable()->fetch();
        $arrRet = array();
        if (!is_null($company_branch_id)) {
            $tmpBranch = $this->getTable()->select(':cl_company_branch.*')->where(':cl_company_branch.id = ?', $company_branch_id)->limit(1)->fetch();
        } else {
            $tmpBranch = false;
        }
        if ($tmpBranch && ($tmpBranch['eet_active'] == 1 || $tmpCompany['eet_test'] == 1)) {
            $arrRet['dic_popl'] = $tmpBranch['b_dic'];
            $arrRet['eet_pfx'] = $tmpBranch['eet_pfx'];
            $arrRet['eet_pass'] = $tmpBranch['eet_pass'];
            $arrRet['eet_id_provoz'] = $tmpBranch['eet_id_provoz'];
            $arrRet['eet_id_poklad'] = $tmpBranch['eet_id_poklad'];
            $arrRet['eet_test'] = $tmpBranch['eet_test'];
            $arrRet['eet_ghost'] = $tmpBranch['eet_ghost'];
        } elseif ($tmpCompany['eet_active'] == 1 || $tmpCompany['eet_test'] == 1) {
            $arrRet['dic_popl'] = $tmpCompany['dic'];
            $arrRet['eet_pfx'] = $tmpCompany['eet_pfx'];
            $arrRet['eet_pass'] = $tmpCompany['eet_pass'];
            $arrRet['eet_id_provoz'] = $tmpCompany['eet_id_provoz'];
            $arrRet['eet_id_poklad'] = $tmpCompany['eet_id_poklad'];
            $arrRet['eet_test'] = $tmpCompany['eet_test'];
            $arrRet['eet_ghost'] = 0;
        } else {
            $arrRet = FALSE;
        }

        return $arrRet;
    }

    public function getStamp()
    {
        $user_id = $this->user->getId();
        //$company_id = $rowUser->cl_company_id;
        $settings = $this->getTable()->fetch();
        //$settings = $this->find($company_id);
        if (!is_null($user_id) && $rowUser = $this->UserManager->getUserById($user_id)) {
            if ($rowUser['picture_stamp'] != '') {
                $img = $rowUser['picture_stamp'];
            } else {
                $img = $settings->picture_stamp;
            }
        } else {
            $img = $settings->picture_stamp;
        }

        $ret = '';
        if ($img != '') {
            $file = __DIR__ . "/../../data/pictures/" . $img;
            if (is_file($file)) {
                $image = Image::fromFile($file);
                // $image->send(Image::JPEG);
                $ret = $image->toString();
            }
        }
        return ($ret);
        // $this->terminate();
    }

    public function getLogo()
    {
        //$user_id = $this->user->getId();
        //$company_id = $this->UserManager->getUserById($user_id)->cl_company_id;
        //$settings = $this->find($company_id);
        $settings = $this->getTable()->fetch();
        $img = $settings->picture_logo;
        $ret = '';
        if ($img != '') {
            $file = __DIR__ . "/../../data/pictures/" . $img;
            if (is_file($file)) {
                $image = Image::fromFile($file);
                // $image->send(Image::JPEG);
                $ret = $image->toString();
                $ret = (string)$image;
            }
        }
        return ($ret);
    }

    private function makeCountries()
    {
        $this->db->query('INSERT INTO `cl_countries` (`id`, `name`, `acronym`, `country_name`, `cl_company_id`, `currency`, `vat`, `cl_users_id`, `create_by`, `created`, `change_by`, `changed`) VALUES
(1, \'Česko\', \'CZE\', \'Česká republika\', NULL, \'CZK\', 0, NULL, \'\', NULL, \'Tomáš Halász\', \'2015-07-09 10:05:41\'),
(2, \'Spojené státy\', \'USA\', \'Spojené státy\', NULL, \'USD\', 0, NULL, \'\', NULL, \'\', NULL),
(3, \'Slovensko\', \'SVK\', \'Slovensko\', NULL, \'EUR\', 0, NULL, \'\', NULL, \'\', NULL),
(5, \'Polsko\', \'POL\', \'Polsko\', NULL, \'EUR\', 0, NULL, \'Tomáš Halász\', \'2015-07-09 10:05:55\', \'Tomáš Halász\', \'2015-07-09 10:08:10\'),
(13, \'Německo\', \'DEU\', \'Německo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(14, \'Rakousko\', \'AUT\', \'Rakousko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(15, \'Rumunsko\', \'ROU\', \'Rumunsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(16, \'Francie\', \'FRA\', \'Francie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(17, \'Řecko\', \'GRC\', \'Řecko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(18, \'Slovinsko\', \'SVN\', \'Slovinsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(19, \'Norsko\', \'NOR\', \'Norsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(20, \'Nizozemsko\', \'NLD\', \'Nizozemsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(21, \'Monako\', \'MCO\', \'Monako\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(22, \'Lucembursko\', \'LUX\', \'Lucembursko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(23, \'Island\', \'ISL\', \'Island\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(24, \'Irsko\', \'IRL\', \'Irsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(25, \'Chorvatsko\', \'HRV\', \'Chorvatsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(26, \'Dánsko\', \'DNK\', \'Dánsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(27, \'China\', \'\', \'\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(28, \'Belgie\', \'BEL\', \'Belgie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(29, \'Spojené království\', \'GBR\', \'Spojené království\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(30, \'Ukrajina\', \'UKR\', \'Ukrajina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(31, \'Švédsko\', \'SWE\', \'Švédsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(32, \'Švýcarsko\', \'CHE\', \'Švýcarsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(33, \'Španělsko\', \'ESP\', \'Španělsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(35, \'Afghánistán\', \'AFG\', \'Afghánistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(36, \'Agentura spoj. národů\', \'UNA\', \'Agentura spoj. národů\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(37, \'Alandské ostrovy\', \'ALA\', \'Alandské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(38, \'Albánie\', \'ALB\', \'Albánie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(39, \'Alžírsko\', \'DZA\', \'Alžírsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(40, \'Americká Samoa\', \'ASM\', \'Americká Samoa\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(41, \'Americké Panenské ostr.\', \'VIR\', \'Americké Panenské ostr.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(42, \'Andorra\', \'AND\', \'Andorra\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(43, \'Angola\', \'AGO\', \'Angola\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(44, \'Anguilla\', \'AIA\', \'Anguilla\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(45, \'Antarktida\', \'ATA\', \'Antarktida\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(46, \'Antigua a Barbuda\', \'ATG\', \'Antigua a Barbuda\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(47, \'Argentina\', \'ARG\', \'Argentina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(48, \'Arménie\', \'ARM\', \'Arménie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(49, \'Aruba\', \'ABW\', \'Aruba\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(50, \'Austrálie\', \'AUS\', \'Austrálie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(51, \'Ázerbájdžán\', \'AZE\', \'Ázerbájdžán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(52, \'Bahamy\', \'BHS\', \'Bahamy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(53, \'Bahrajn\', \'BHR\', \'Bahrajn\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(54, \'Bangladéš\', \'BGD\', \'Bangladéš\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(55, \'Barbados\', \'BRB\', \'Barbados\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(57, \'Belize\', \'BLZ\', \'Belize\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(58, \'Bělorusko\', \'BLR\', \'Bělorusko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(59, \'Benin \', \'BEN\', \'Benin \', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(60, \'Bermudy\', \'BMU\', \'Bermudy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(61, \'Bhútán\', \'BTN\', \'Bhútán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(62, \'Bolívarovská republika Venezuela\', \'VEN\', \'Bolívarovská republika Venezuela\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(63, \'Bonaire, Svatý Eustach\', \'BES\', \'Bonaire, Svatý Eustach\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(64, \'Bosna a Hercegovina\', \'BIH\', \'Bosna a Hercegovina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(65, \'Botswana\', \'BWA\', \'Botswana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(66, \'Bouvetův ostrov\', \'BVT\', \'Bouvetův ostrov\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(67, \'Brazílie\', \'BRA\', \'Brazílie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(71, \'Britské Panenské ostrovy\', \'VGB\', \'Britské Panenské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(72, \'Britské zámořské území\', \'GBO\', \'Britské zámořské území\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(73, \'Brit.indickooceán.území\', \'IOT\', \'Brit.indickooceán.území\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(75, \'Brunej Darussalam\', \'BRN\', \'Brunej Darussalam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(76, \'Bulharsko\', \'BGR\', \'Bulharsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(77, \'Burkina Faso\', \'BFA\', \'Burkina Faso\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(78, \'Burundi\', \'BDI\', \'Burundi\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(79, \'Cookovy ostrovy\', \'COK\', \'Cookovy ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(80, \'Curaçao\', \'CUW\', \'Curaçao\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(81, \'Čad\', \'TCD\', \'Čad\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(82, \'Černá Hora\', \'MNE\', \'Černá Hora\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(84, \'China\', \'CHN\', \'Čína\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(86, \'Dominika\', \'DMA\', \'Dominika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(87, \'Dominikánská republika\', \'DOM\', \'Dominikánská republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(88, \'Džibutsko\', \'DJI\', \'Džibutsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(89, \'Egypt\', \'EGY\', \'Egypt\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(90, \'Ekvádor\', \'ECU\', \'Ekvádor\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(91, \'Eritrea\', \'ERI\', \'Eritrea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(92, \'Estonsko\', \'EST\', \'Estonsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(93, \'Etiopie\', \'ETH\', \'Etiopie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(94, \'Faerské ostrovy\', \'FRO\', \'Faerské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(95, \'Falklandské os. (Malvíny)\', \'FLK\', \'Falklandské os. (Malvíny)\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(96, \'Fidži\', \'FJI\', \'Fidži\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(97, \'Filipíny\', \'PHL\', \'Filipíny\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(98, \'Finsko\', \'FIN\', \'Finsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(100, \'Francouzská Guyana\', \'GUF\', \'Francouzská Guyana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(101, \'Francouzská jižní území\', \'ATF\', \'Francouzská jižní území\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(102, \'Francouzská Polynésie\', \'PYF\', \'Francouzská Polynésie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(103, \'Gabon\', \'GAB\', \'Gabon\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(104, \'Gambie\', \'GMB\', \'Gambie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(105, \'Ghana\', \'GHA\', \'Ghana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(106, \'Gibraltar\', \'GIB\', \'Gibraltar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(107, \'Grenada\', \'GRD\', \'Grenada\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(108, \'Grónsko\', \'GRL\', \'Grónsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(109, \'Gruzie\', \'GEO\', \'Gruzie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(110, \'Guadeloupe\', \'GLP\', \'Guadeloupe\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(111, \'Guam\', \'GUM\', \'Guam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(112, \'Guatemala\', \'GTM\', \'Guatemala\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(113, \'Guernsey\', \'GGY\', \'Guernsey\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(114, \'Guinea\', \'GIN\', \'Guinea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(115, \'Guinea-Bissau\', \'GNB\', \'Guinea-Bissau\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(116, \'Guyana\', \'GUY\', \'Guyana\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(117, \'Haiti\', \'HTI\', \'Haiti\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(119, \'Honduras\', \'HND\', \'Honduras\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(120, \'Hongkong\', \'HKG\', \'Hongkong\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(121, \'Chile\', \'CHL\', \'Chile\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(123, \'Indie\', \'IND\', \'Indie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(124, \'Indonésie\', \'IDN\', \'Indonésie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(125, \'Irák\', \'IRQ\', \'Irák\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(126, \'Írán\', \'IRN\', \'Írán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(129, \'Itálie\', \'ITA\', \'Itálie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(130, \'Izrael\', \'ISR\', \'Izrael\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(131, \'Jamajka\', \'JAM\', \'Jamajka\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(132, \'Japonsko\', \'JPN\', \'Japonsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(133, \'Jemen\', \'YEM\', \'Jemen\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(134, \'Jersey\', \'JEY\', \'Jersey\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(135, \'Jižní Afrika\', \'ZAF\', \'Jižní Afrika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(136, \'Jižní Súdán\', \'SSD\', \'Jižní Súdán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(137, \'Jordánsko\', \'JOR\', \'Jordánsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(139, \'Kajmanské ostrovy\', \'CYM\', \'Kajmanské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(140, \'Kambodža\', \'KHM\', \'Kambodža\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(141, \'Kamerun\', \'CMR\', \'Kamerun\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(142, \'Kanada\', \'CAN\', \'Kanada\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(143, \'Kapverdy\', \'CPV\', \'Kapverdy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(144, \'Katar\', \'QAT\', \'Katar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(145, \'Kazachstán\', \'KAZ\', \'Kazachstán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(146, \'Keňa\', \'KEN\', \'Keňa\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(147, \'Kiribati\', \'KIR\', \'Kiribati\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(148, \'Kokosové(Keelingovy)os.\', \'CCK\', \'Kokosové(Keelingovy)os.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(149, \'Kolumbie\', \'COL\', \'Kolumbie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(150, \'Komory\', \'COM\', \'Komory\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(151, \'Kongo, republika\', \'COG\', \'Kongo, republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(152, \'Kongo,demokratická rep.\', \'COD\', \'Kongo,demokratická rep.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(153, \'Korea, lid. dem. rep.\', \'PRK\', \'Korea, lid. dem. rep.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(154, \'Korejská republika\', \'KOR\', \'Korejská republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(155, \'Kosovo\', \'XXK\', \'Kosovo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(156, \'Kostarika\', \'CRI\', \'Kostarika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(157, \'Kuba\', \'CUB\', \'Kuba\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(158, \'Kuvajt\', \'KWT\', \'Kuvajt\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(159, \'Kypr\', \'CYP\', \'Kypr\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(160, \'Kyrgyzstán\', \'KGZ\', \'Kyrgyzstán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(161, \'Laoská lid.dem.repub.\', \'LAO\', \'Laoská lid.dem.repub.\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(162, \'Lesotho\', \'LSO\', \'Lesotho\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(163, \'Libanon\', \'LBN\', \'Libanon\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(164, \'Libérie\', \'LBR\', \'Libérie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(165, \'Libye\', \'LBY\', \'Libye\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(166, \'Lichtenštejnsko\', \'LIE\', \'Lichtenštejnsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(167, \'Litva\', \'LTU\', \'Litva\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(168, \'Lotyšsko\', \'LVA\', \'Lotyšsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(170, \'Macao\', \'MAC\', \'Macao\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(171, \'Madagaskar\', \'MDG\', \'Madagaskar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(172, \'Maďarsko\', \'HUN\', \'Maďarsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(173, \'Makedonie\', \'MKD\', \'Makedonie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(174, \'Malajsie\', \'MYS\', \'Malajsie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(175, \'Malawi\', \'MWI\', \'Malawi\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(176, \'Maledivy\', \'MDV\', \'Maledivy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(177, \'Mali\', \'MLI\', \'Mali\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(178, \'Malta\', \'MLT\', \'Malta\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(179, \'Maroko\', \'MAR\', \'Maroko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(180, \'Marshallovy ostrovy\', \'MHL\', \'Marshallovy ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(181, \'Martinik\', \'MTQ\', \'Martinik\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(182, \'Mauricius\', \'MUS\', \'Mauricius\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(183, \'Mauritánie\', \'MRT\', \'Mauritánie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(184, \'Mayotte\', \'MYT\', \'Mayotte\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(186, \'Mexiko\', \'MEX\', \'Mexiko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(187, \'Mikronésie\', \'FSM\', \'Mikronésie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(189, \'Moldavská republika\', \'MDA\', \'Moldavská republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(191, \'Mongolsko\', \'MNG\', \'Mongolsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(192, \'Montserrat\', \'MSR\', \'Montserrat\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(193, \'Mosambik\', \'MOZ\', \'Mosambik\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(194, \'Myanmar\', \'MMR\', \'Myanmar\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(195, \'Namibie\', \'NAM\', \'Namibie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(196, \'Nauru\', \'NRU\', \'Nauru\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(198, \'Nepál\', \'NPL\', \'Nepál\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(199, \'neutrální zóna\', \'NTZ\', \'neutrální zóna\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(200, \'Niger\', \'NER\', \'Niger\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(201, \'Nigérie\', \'NGA\', \'Nigérie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(202, \'Nikaragua\', \'NIC\', \'Nikaragua\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(203, \'Niue\', \'NIU\', \'Niue\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(204, \'Nizozemské Antily\', \'ANT\', \'Nizozemské Antily\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(207, \'Nová Kaledonie\', \'NCL\', \'Nová Kaledonie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(208, \'Nový Zéland\', \'NZL\', \'Nový Zéland\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(209, \'Omán\', \'OMN\', \'Omán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(213, \'Ostrov Man\', \'IMN\', \'Ostrov Man\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(214, \'Ostrov Norfolk\', \'NFK\', \'Ostrov Norfolk\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(215, \'Ostrovy Severní Mariany\', \'MNP\', \'Ostrovy Severní Mariany\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(216, \'Ostrovy Turks a Caicos\', \'TCA\', \'Ostrovy Turks a Caicos\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(217, \'ostrovy USA v Tichém oceánu\', \'PUS\', \'ostrovy USA v Tichém oceánu\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(218, \'Pákistán\', \'PAK\', \'Pákistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(219, \'Palau\', \'PLW\', \'Palau\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(220, \'Palestina\', \'XAB\', \'Palestina\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(222, \'Panama\', \'PAN\', \'Panama\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(223, \'Papua Nová Guinea\', \'PNG\', \'Papua Nová Guinea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(224, \'Paraguay\', \'PRY\', \'Paraguay\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(225, \'Peru\', \'PER\', \'Peru\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(226, \'Pitcairn\', \'PCN\', \'Pitcairn\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(227, \'Pobřeží slonoviny\', \'CIV\', \'Pobřeží slonoviny\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(229, \'Portoriko\', \'PRI\', \'Portoriko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(230, \'Portugalsko\', \'PRT\', \'Portugalsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(232, \'Republika Kosovo\', \'RKS\', \'Republika Kosovo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(233, \'Réunion\', \'REU\', \'Réunion\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(234, \'Rovníková Guinea\', \'GNQ\', \'Rovníková Guinea\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(236, \'Ruská federace\', \'RUS\', \'Ruská federace\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(237, \'Rwanda\', \'RWA\', \'Rwanda\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(240, \'Saint Pierre a Miquelon\', \'SPM\', \'Saint Pierre a Miquelon\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(241, \'Salvador\', \'SLV\', \'Salvador\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(242, \'Samoa\', \'WSM\', \'Samoa\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(243, \'San Marino\', \'SMR\', \'San Marino\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(244, \'Saúdská Arábie\', \'SAU\', \'Saúdská Arábie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(245, \'Senegal\', \'SEN\', \'Senegal\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(246, \'Seychely\', \'SYC\', \'Seychely\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(247, \'Sierra Leone\', \'SLE\', \'Sierra Leone\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(248, \'Singapur\', \'SGP\', \'Singapur\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(251, \'Somálsko\', \'SOM\', \'Somálsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(252, \'Spojené arabské emiráty\', \'ARE\', \'Spojené arabské emiráty\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(255, \'Srbsko\', \'SRB\', \'Srbsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(256, \'Srbsko a Černá Hora\', \'SCG\', \'Srbsko a Černá Hora\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(257, \'Srí Lanka\', \'LKA\', \'Srí Lanka\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(258, \'Středoafrická republika\', \'CAF\', \'Středoafrická republika\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(259, \'Súdán\', \'SDN\', \'Súdán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(260, \'Surinam\', \'SUR\', \'Surinam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(261, \'Svalbard a Jan Mayen\', \'SJM\', \'Svalbard a Jan Mayen\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(262, \'Svatá Helena,Ascension\', \'SHN\', \'Svatá Helena,Ascension\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(263, \'Svatá Lucie\', \'LCA\', \'Svatá Lucie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(264, \'Svatý Bartoloměj\', \'BLM\', \'Svatý Bartoloměj\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(265, \'Svatý Kryštof a Nevis\', \'KNA\', \'Svatý Kryštof a Nevis\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(266, \'Svatý Martin\', \'MAF\', \'Svatý Martin\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(268, \'Svatý stolec (Vatik.mě)\', \'VAT\', \'Svatý stolec (Vatik.mě)\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(269, \'Svazijsko\', \'SWZ\', \'Svazijsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(270, \'Svazová republika Jugoslávie\', \'YUG\', \'Svazová republika Jugoslávie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(271, \'Sv. Vincenc a Grenadiny\', \'VCT\', \'Sv. Vincenc a Grenadiny\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(272, \'Sv.Tomáš a Princ.ostrov\', \'STP\', \'Sv.Tomáš a Princ.ostrov\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(273, \'Syrská arabská republik\', \'SYR\', \'Syrská arabská republik\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(274, \'Šalomounovy ostrovy\', \'SLB\', \'Šalomounovy ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(278, \'Tádžikistán\', \'TJK\', \'Tádžikistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(279, \'Tanzanie\', \'TZA\', \'Tanzanie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(280, \'Thajsko\', \'THA\', \'Thajsko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(281, \'Tchaj-wan, čín.provinc\', \'TWN\', \'Tchaj-wan, čín.provinc\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(282, \'Tichomořské ostrovy\', \'PCI\', \'Tichomořské ostrovy\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(283, \'Togo\', \'TGO\', \'Togo\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(284, \'Tokelau\', \'TKL\', \'Tokelau\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(285, \'Tonga\', \'TON\', \'Tonga\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(287, \'Tunisko\', \'TUN\', \'Tunisko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(288, \'Turecko\', \'TUR\', \'Turecko\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(289, \'Turkmenistán\', \'TKM\', \'Turkmenistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(290, \'Tuvalu\', \'TUV\', \'Tuvalu\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(291, \'Uganda\', \'UGA\', \'Uganda\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(295, \'Uruguay\', \'URY\', \'Uruguay\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(296, \'Uzbekistán\', \'UZB\', \'Uzbekistán\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(298, \'Vanuatu\', \'VUT\', \'Vanuatu\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(299, \'Vietnam\', \'VNM\', \'Vietnam\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(300, \'Východní Timor\', \'TLS\', \'Východní Timor\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(302, \'Zaire\', \'ZAR\', \'Zaire\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(303, \'Zambie\', \'ZMB\', \'Zambie\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL),
(306, \'Zimbabwe\', \'ZWE\', \'Zimbabwe\', NULL, NULL, 0, NULL, \'\', NULL, \'\', NULL);
COMMIT;');
        return;
    }


}

