<?php
namespace App\Model;

use Nette\Utils\Arrays;
use Netpromotion\Profiler\Profiler;
use Nette\Utils\Json;
use Nette\Http\Session;
use Tracy\Debugger;


class ArchiveManager
{
    /** @var Nette\Database\Context */
    public $currentDatabase;
    /** @var Nette\Database\Context */
    public $archiveDatabase;

    /** @var Nette\Database\Connection */
    private $archive;
    /** @var Nette\Database\Connection */
    private $current;

    /** @var \DatabaseAccessor */
    private $accessor;

    /** @var \App\Model\ArraysManager */
    private $ArraysManager;

    /** @var \App\Model\CompaniesManager */
    private $CompaniesManager;

    /** @var \Nette\Security\User */
    private $User;

    /** @var \App\Model\UserManager */
    private $UserManager;

    /** @var \App\Model\StoreMoveManager */
    private $StoreMoveManager;

    /** @var \App\Model\StoreManager */
    private $StoreManager;

    /** @var \App\Model\PriceListManager */
    private $PriceListManager;

    /** @var \Nette\Localization\ITranslator */
    public $Translator;

    /** @var \Nette\Http\Session */
    private $session;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                \App\Model\ArraysManager $arraysManager, \App\Model\CompaniesManager $companiesManager, \Nette\Localization\ITranslator $translator,
                                \App\Model\StoreMoveManager $storeMoveManager)
    {
        $this->accessor = $accessor;
        $this->ArraysManager = $arraysManager;
        $this->CompaniesManager = $companiesManager;
        $this->StoreMoveManager = $storeMoveManager;
        $this->User = $user;
        $this->UserManager = $userManager;
        $this->Translator  = $translator;
        //$this->database = $db;
        //$this->currentDatabase = $accessor->get("dbCurrent");
        //$this->archiveDatabase = $accessor->get("dbCurrent");
        //$database = $accessor->get("db2");
        $this->session = $session;
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getDatabase($dbName){
        //dump($this->accessor->get($dbName));
        return $this->accessor->get($dbName);
    }

    public function getTablesCount($dbName){
        $table = $this->getDatabase($dbName);
        $result =  $table->query("SHOW TABLES;");
        $i = 0;
        foreach($result as $one){
            $i++;
        }
        return ($i);
    }

    public function getSchemaName($dbName){
        $table = $this->getDatabase($dbName);
        $arrDSN = str_getcsv($table->getConnection()->getDSN(),";");
        $schemaName = str_getcsv($arrDSN[1], "=");
        return $schemaName[1];
    }

    public function getTablesSize($dbName){
        $table      = $this->getDatabase($dbName);
        $schemaName = $this->getSchemaName($dbName);
        $result = $table->query('SELECT 
                                ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size 
                                FROM information_schema.tables
                                WHERE information_schema.tables.table_schema = "'.$schemaName.'"
                                GROUP BY table_schema; ')->fetch();
        if ($result)
            $retVal = $result['size'];
        else
            $retVal = 0;
        return ($retVal);
    }

    public function getActiveArchives(){
        $arrArchives = $this->ArraysManager->getArchives();
        foreach($arrArchives as $key => $one)
        {
            $aDB = $this->getDatabase($one);
            if (!$result = $aDB->table('cl_users')->where('id = ?', $this->User->getId())->fetch()){
                unset($arrArchives[$key]);
            }
        }
        return $arrArchives;
    }

    public function createStructure($archive, $current){
        $this->archive = $this->getDatabase($archive);
        $this->current = $this->getDatabase($current);
        /*********************** GRAB SCHEMA FROM CURRENT DATABASE ***********************/
        $result =  $this->current->query("SHOW TABLES;");
        $buf="set foreign_key_checks = 0;\n";
        $constraints='';
        foreach($result as $row)
        {
            $result2 = $this->current->query("SHOW CREATE TABLE ".$row[0].";")->fetch();
            if(preg_match("/[ ]*CONSTRAINT[ ]+.*\n/",$result2[1],$matches))
            {
                $res[1] = preg_replace("/,\n[ ]*CONSTRAINT[ ]+.*\n/","\n",$result2[1]);
                $constraints.="ALTER TABLE ".$row[0]." ADD ".trim($matches[0]).";\n";
            }
            $buf.= $result2[1].";\n";
        }
        //$buf.= $constraints;
        $buf.= "set foreign_key_checks = 1";
        /**************** CREATE TABLES IN ARCHIVE ****************/
        $queries = explode(';',$buf);
        foreach($queries as $query)
        {
            $this->archive->query($query);
        }
    }


    public function dropStructure($archive){
        if ($archive != 'current'){
            $this->archive = $this->getDatabase($archive);
            $result =  $this->archive->query("SHOW TABLES;");
            $buf="set foreign_key_checks = 0;";
            $this->archive->query($buf);
            foreach($result as $row)
            {
                $this->archive->query("DROP TABLE ".$row[0].";");
            }
            $buf = "set foreign_key_checks = 1;";
            $this->archive->query($buf);
        }else{
            $error = 'Aktuální tabulky není možné odstranit';
            throw new \Exception($error);
        }
    }




    public function dump($dbName, $compression = FALSE, $tables = array(), $cl_company_id = NULL){
        if (is_null($cl_company_id)) {
            $dst_dir = APP_DIR . '/../data';
            $cmpName = '';
        }else{
            $dst_dir = $this->ArraysManager->getDataFolder($cl_company_id);
            $cmpName = $this->CompaniesManager->find($cl_company_id)->name . '-';
        }

        $fileName = $cmpName .  $this->getSchemaName($dbName) . '-' . date('d-m-Y_h:i:s');
        $DBH = $this->getDatabase($dbName);


        //create/open files
        if ($compression)
        {
            $fileName .= '.sql.gz';
            $zp = gzopen($dst_dir.'/'.$fileName, "a9");
        }
        else
        {
            $fileName .= '.sql';
            $handle = fopen($dst_dir.'/'.$fileName,'a+');
        }

        //array of all database field types which just take numbers
        $numtypes=array('tinyint','smallint','mediumint','int','bigint','float','double','decimal','real');

        //get all of the tables
        if(empty($tables))
        {
            $pstm1 = $DBH->query('SHOW TABLES');
            foreach ($pstm1 as $row )
            {
                $tables[] = $row[0];
            }
        }
        else
        {
            $tables = is_array($tables) ? $tables : explode(',',$tables);
        }

        $return = "SET GLOBAL FOREIGN_KEY_CHECKS = 0;";
        if ($compression){
            gzwrite($zp, $return);
        } else {
            fwrite($handle,$return);
        }
        //cycle through the table(s)
        $counterTables = 1;
        $totalTables = count($tables);
        if (!is_null($cl_company_id)){
            session_write_close();
        }

        foreach($tables as $table)
        {
            //dump($DBH);
            //dump($table);
            if (!is_null($cl_company_id)){
                $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá záloha') . ' <br>' . $counterTables . ' / ' . $totalTables . ' <br> ' . $table);
            }

            //
            if (is_null($cl_company_id) || $table == 'cl_currencies_rates' || $table == 'cl_users_log' || $table == 'cl_countries' || $table == 'cl_rates_vat') {
                $result = $DBH->query("SELECT * FROM $table;");
            }elseif (substr($table, 0, 3) == 'cl_' || substr($table, 0, 3) == 'in_'){
                if ($table == 'cl_company') {
                    $result = $DBH->query("SELECT * FROM $table WHERE id = $cl_company_id ;");
                }else {
                    $result = $DBH->query("SELECT * FROM $table WHERE cl_company_id = $cl_company_id;");
                }
            }else{
                $result = false;
            }
            if ($result) {
                //dump($result);
                $num_fields = $result->getColumnCount();
                $num_rows = $result->getRowCount();

                $return = "";
                //uncomment below if you want 'DROP TABLE IF EXISTS' displayed
                //$return.= 'DROP TABLE IF EXISTS `'.$table.'`;';

                //table structure
                $pstm2 = $DBH->query("SHOW CREATE TABLE $table");
                $row2 = $pstm2->fetch();
                $ifnotexists = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $row2[1]);
                $return .= "\n\n" . $ifnotexists . ";\n\n";

                if ($compression) {
                    gzwrite($zp, $return);
                } else {
                    fwrite($handle, $return);
                }
                $return = "";

                //insert values
                if ($num_rows) {
                    $count = 0;
                    $counter = 0;
                    $return = "";
                    foreach ($result as $row) {
                        if ($counter == 0) {
                            $return .= 'INSERT INTO `' . "$table" . "` (";
                            $pstm3 = $DBH->query("SHOW COLUMNS FROM $table");
                            $count2 = 0;
                            $type = array();
                            $j = 0;
                            foreach ($pstm3 as $rows) {
                                $col = [];
                                if (stripos($rows[1], '(')) {
                                    $col['type'] = stristr($rows[1], '(', true);
                                } else {
                                    $col['type'] = $rows[1];
                                }
                                if ($rows[2] == "YES") {
                                    $col['default'] = 'NULL';
                                } elseif (!is_null($rows[4])) {
                                    if (in_array($col['type'], $numtypes)) {
                                        $col['default'] = $rows[4];
                                    } else {
                                        $col['default'] = "'" . $this->validMySQL($rows[4]) . "'";
                                    }
                                } else {
                                    if (in_array($col['type'], $numtypes)) {
                                        $col['default'] = "'0'";
                                    } else {
                                        $col['default'] = "''";
                                    }
                                }
                                $return .= "`" . $rows[0] . "`";
                                $count2++;
                                if ($count2 < ($pstm3->getRowCount())) {
                                    $return .= ", ";
                                }

                                $type[$table][] = $col;
                            }

                            $return .= ")" . ' VALUES';

                            if ($compression) {
                                gzwrite($zp, $return);
                            } else {
                                fwrite($handle, $return);
                            }
                            $return = "";
                            $j++;
                            $counter = 50;
                        }


                        $return = "\n(";
                        for ($j = 0; $j < $num_fields; $j++) {
                            if (isset($row[$j])) {
                                //if number, take away "". else leave as string
                                if ((in_array($type[$table][$j]['type'], $numtypes)) && $row[$j] !== '') {
                                    $return .= $row[$j];
                                } elseif (($type[$table][$j]['type'] == 'datetime' || $type[$table][$j]['type'] == 'date') && substr($row[$j], 0, 4) == '-000') {
                                    if ($type[$table][$j]['default'] == 'NULL') {
                                        $return .= "NULL";
                                    } else {
                                        $return .= "'1970-01-01'";
                                    }
                                } else {
                                    //$return.= $DBH->quote($row[$j]);
                                    $return .= "'" . $this->validMySQL($row[$j]) . "'";
                                }
                            } else {
                                //$return.= 'NULL';
                                $return .= $type[$table][$j]['default'];
                            }
                            if ($j < ($num_fields - 1)) {
                                $return .= ',';
                            }
                        }
                        $count++;
                        $counter--;
                        if ($count < ($result->getRowCount()) && $counter > 0) {
                            $return .= "),";
                        } else {
                            $return .= ");\nCOMMIT;";
                        }
                        if ($compression) {
                            gzwrite($zp, $return);
                        } else {
                            fwrite($handle, $return);
                        }
                        $return = "";

                    }
                }


                $return = "\n\n-- ------------------------------------------------ \n\n";
                if ($compression) {
                    gzwrite($zp, $return);
                } else {
                    fwrite($handle, $return);
                }
                $return = "";
            }
        }
        $return = "SET GLOBAL FOREIGN_KEY_CHECKS = 1;";
        if ($compression){
            gzwrite($zp, $return);
        } else {
            fwrite($handle,$return);
        }
        $return = "";

        $fileSize = 0;
        if ($compression)
        {
            gzclose($zp);
            $fileSize = filesize($dst_dir.'/'.$fileName);
        }
        else
        {
            fclose($handle);
            $fileSize = filesize($dst_dir.'/'.$fileName);
        }
        return ($fileName);
    }



    private function validMySQL($var){
        //$var = stripslashes($var);
        //$var = htmlentities($var);
        //$var = strip_tags($var);
        $var = addslashes($var);
        return($var);
    }



    public function moveToArchive($dateTo, $dbName, $tables = [], $presenter){
        $sourceDB = $this->getDatabase('dbCurrent');
        $archiveDB = $this->getDatabase($this->ArraysManager->getArchive($dbName));
        $archTables = $this->ArraysManager->getArchTables();

        if(empty($tables)){
            $pstm1 = $sourceDB->query('SHOW TABLES');
            foreach ($pstm1 as $row ){
                //dump($row[0]);
                if (!array_key_exists($row[0], $archTables) && (substr($row[0], 0,2) == "cl" || substr($row[0], 0,2) == "in")){
                    if (!$this->my_array_search($row[0], $archTables)){
                        $tables[] = $row[0];
                    }
                }
            }
        }else{
            $tables = is_array($tables) ? $tables : explode(',',$tables);
        }

//        dump(session_status());
//        dump(session_id());
        //bdump($this->session->isStarted(), 'started?');
        session_write_close();
        //bdump($this->session->isStarted(), 'closed?');
        //die;
        //session_reset();
//        session_abort();
        //$this->session->start();
        //    dump($this->session->isStarted());
        //dump($this->session->getId());
        //$this->session->close();
        //dump($this->session->isStarted());
        //dump($this->session->getId());
        //$this->session->regenerateId();
        //dump($this->session->getId());
        //die;

//        dump(session_status());
//        dump(session_id());
//die;

            //first common tables for copy all records without delete
            $counterTables = 1;
            $totalTables = count($tables);
            foreach($tables as $one){
                session_write_close();
                $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá_archivace') . ' 1/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables . ' <br> ' . $one);
                //bdump($this->session->isStarted(), 'started?');
                $this->copyRecords($one, $sourceDB, $archiveDB);
//                bdump($this->session->isStarted(), 'started?');
//                die;
             }

            $cntRecords = 0;
            $counterTables = 1;
            $totalTables = count($archTables) - 1;
            //then copy only record filtered by date from - to
            foreach($archTables as $key => $one){

               if ($key != 'cl_store_docs') {
                   session_write_close();
                   $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá_archivace') . ' 2/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables . ' <br> ' . $key);
                   $cntRecords += $this->copyRecords($key, $sourceDB, $archiveDB, $dateTo, $one['date'], $one['tables']);
               }
             }

           //now special case cl_store_docs
            $cl_company_id = $this->UserManager->getCompany($this->User->getId())->id;

            $counterTables = 1;
            $totalTables = 1; //count($archTables);
            foreach($archTables as $key => $one){

                if ($key == 'cl_store_docs') {
                    $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá_archivace') . ' 3/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables . ' <br> ' . $key);
                    $cntRecords += $this->copyRecords($key, $sourceDB, $archiveDB, $dateTo, $one['date'], $one['tables'], ['cl_store_docs', 'cl_store_move', 'cl_store_out']);
                }
            }
            $sourceDB->beginTransaction();
            try {
                //only outcomes - delete all before dateTo
                $tmpStoreMove = $sourceDB->table('cl_store_move')->
                        where('cl_store_move.cl_company_id = ?', $cl_company_id)->
                        where('cl_store_docs.doc_date <= ? AND (cl_store_docs.doc_type = 1)', $dateTo);
                $counterTables = 1;
                $totalTables = count($tmpStoreMove);
                foreach ($tmpStoreMove as $key => $one) {

                    $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá_archivace') . ' 4/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables);
                    $arrStoreOutIn = $sourceDB->table('cl_store_out')->select('cl_store_move_in_id AS id, s_out AS s_out')->where('cl_store_move_id = ?', $one['id'])->fetchPairs('id', 's_out');
                    $arrSOI = [];
                    foreach ($arrStoreOutIn as $keyArr => $oneArr) {
                        $arrSOI[] = $keyArr;
                    }
                    $tmpStoreOutIn = $sourceDB->table('cl_store_move')->
                    select('cl_store_move.id AS id')->
                    where('cl_store_move.cl_company_id = ?', $cl_company_id)->
                    where('cl_store_docs.doc_date <= ? AND (cl_store_docs.doc_type = 0)', $dateTo)->
                    where('cl_store_move.id IN (?)', $arrSOI)->fetchPairs('id', 'id');
                    $arrSOI2 = [];
                    foreach ($tmpStoreOutIn as $key2 => $one2) {
                        $arrSOI2[] = $key2;
                    }
                    $arrStoreOutIn = $sourceDB->table('cl_store_out')->select('SUM(cl_store_out.s_out) AS s_out')->
                    where('cl_store_move_id = ?', $one['id'])->
                    where('cl_store_out.cl_store_move_in_id IN (?)', $arrSOI2)->fetch();
                    if ($arrStoreOutIn['s_out'] != $one['s_out']) {
                        $one->update(['s_out' => $one['s_out'] - $arrStoreOutIn['s_out'],
                            's_out_fin' => $one['s_out'] - $arrStoreOutIn['s_out']]);
                        $toDelete = $sourceDB->table('cl_store_out')->where('cl_store_move_in_id IN (?) AND cl_store_move_id = ?', $arrSOI2, $one['id']);
                        if ($toDelete) {
                            $toDelete->delete();
                        }

                    } else {
                        $one->delete();
                    }
                }

                //update s_in and s_total in cl_store_move
                $tmpStoreMove = $sourceDB->table('cl_store_move')->where('cl_store_move.cl_company_id = ?', $cl_company_id)->
                where('cl_store_docs.doc_date <= ? AND cl_store_docs.doc_type = 0', $dateTo);
                $counterTables = 1;
                $totalTables = count($tmpStoreMove);
                foreach ($tmpStoreMove as $key => $one) {
                    $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá archivace') . ' 5/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables);
                    $tmpStoreOut = $sourceDB->table('cl_store_out')->select('SUM(cl_store_out.s_out) AS s_out')->
                    where('cl_store_out.cl_company_id = ?', $cl_company_id)->
                    where('cl_store_out.cl_store_move_in_id = ', $one['id'])->fetch();
                    $s_in = $one['s_end'] + $tmpStoreOut['s_out'];
                    $one->update(['s_in' => $s_in,
                        's_total' => $s_in]);
                }

                //only incomes - delete only those which haven't any outcome before dateTo and at the same time s_in != s_end  then some outcome was made from them
                $tmpStoreMove = $sourceDB->table('cl_store_move')->
                where('cl_store_move.cl_company_id = ?', $cl_company_id)->
                where('cl_store_docs.doc_date <= ? AND (cl_store_docs.doc_type = 0) AND cl_store_move.s_in != cl_store_move.s_end', $dateTo);
                $counterTables = 1;
                $totalTables = count($tmpStoreMove);
                foreach ($tmpStoreMove as $key => $one) {
                    $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá archivace') . ' 6/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables);
                    $tmpStoreOut = $sourceDB->table('cl_store_out')->select('SUM(cl_store_out.s_out) AS s_out')->
                    where('cl_store_out.cl_company_id = ?', $cl_company_id)->
                    where('cl_store_out.cl_store_move_in_id = ', $one['id'])->fetch();
                    if ($tmpStoreOut['s_out'] == 0) {
                        $one->delete();
                    }

                }


                //die;
                //delete cl_store without moves s_in and s_out
                $tmpStore = $sourceDB->table('cl_store')->
                select('cl_store.id, SUM(:cl_store_move.s_in) AS s_in, SUM(:cl_store_move.s_out) AS s_out')->
                group('cl_store.id');
                $counterTables = 1;
                $totalTables = count($tmpStore);
                foreach ($tmpStore as $key => $one) {
                    $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá archivace') . ' 7/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables);
                    if (($one['s_in'] == 0 && $one['s_out'] == 0) || (is_null($one['s_in']) && is_null($one['s_out']))) {
                        $one->delete();
                    }
                }
                //die;

                //delete empty cl_store_docs in before date
                $tmpStoreDocs = $sourceDB->table('cl_store_docs')->select('cl_store_docs.id, COUNT(:cl_store_move.id) AS count_child')->
                where('cl_store_docs.cl_company_id = ?', $cl_company_id)->
                where('cl_store_docs.doc_date <= ? AND (cl_store_docs.doc_type = 1 OR (cl_store_docs.doc_type = 0))', $dateTo)->
                group('cl_store_docs.id');
                $counterTables = 1;
                $totalTables = count($tmpStoreDocs);
                //dump($tmpStoreDocs);
                //die;
                foreach ($tmpStoreDocs as $one) {
                    $this->UserManager->setProgressBar($counterTables++, $totalTables, $this->User->getId(), $this->Translator->translate('Probíhá archivace') . ' 8/8 <br>' . ($counterTables - 1) . ' / ' . $totalTables);
                    if ($one['count_child'] == 0) {
                        //dump($one['id']);
                        $one->delete();
                    }
                }
                $sourceDB->commit();
            }catch(Exception $e){
                Debugger::log($e, Debugger::ERROR);
                $sourceDB->rollback();
                return ['error' => 'Došlo k chybě, archivace nebyla provedena.'];
            }
            //die;
            //$tmpStoreMove = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_date <= ? AND cl_store_docs.doc_type = 0', $dateTo);

            //

            //$sourceDB->commit();
            //$archiveDB->commit();
            $this->UserManager->resetProgressBar( $this->User->getId());

            return ['success' => 'ok', 'data' => ['records' => $cntRecords]];
      /*  } catch (Exception $e) {
            Debugger::log($e, Debugger::ERROR);
            $sourceDB->rollback();
            $archiveDB->rollback();
            return ['error' => 'Došlo k chybě, archivace nebyla provedena.'];
        }*/
        //die;
        //$sourceDB->rollback();
        //$archiveDB->rollback();

    }

    private function my_array_search($search, $arr, &$result = false){
        foreach($arr as $key => $one){
            if (is_array($one) && count($one) > 1){
                $this->my_array_search($search, $one, $result);
            }else{
                if ($search == $one){
                    $result = true;
                   // dump('found!');
                }elseif (is_array($one) && $search == $key){
                    $result = true;
                  //  dump('found!');
                }
            }
            if ($result){
                return true;
            }
        }
        return $result;
    }

    private function copyRecords($table, $sDB, $aDB, $dateTo = NULL, $dateCN = "", $tables = [], $nodelete = [])
    {
        $cntRecords = 0;
        try{

            $sDB->beginTransaction();
            $aDB->beginTransaction();
            $cl_company_id = $this->UserManager->getCompany($this->User->getId())->id;
            if ($table != 'cl_currencies_rates' && $table != 'cl_users_log' && (substr($table, 0, 3) == 'cl_' || substr($table, 0, 3) == 'in_')){
                if ($table == 'cl_company' ){
                    $result = $sDB->table($table)->where("id = ?", $cl_company_id);
                }elseif ($table == 'cl_countries') {
                    $result = $sDB->table($table);
                }else{
                    //OR cl_company_id IS NULL
                    $result = $sDB->table($table)->where("cl_company_id = ? ", $cl_company_id);
                    if (!is_null($dateTo)){
                        $result = $result->where( $dateCN . '<= ?', $dateTo);
                    }
                }
            }else{
                $result = [];
            }

            $sDB->query("SET FOREIGN_KEY_CHECKS = 0;");
            $aDB->query("SET FOREIGN_KEY_CHECKS = 0;");
            foreach($result as $key => $one){
                $test = $aDB->table($table)->where("id = ?", $key)->fetch();
                //dump($test);
                if (!$test){
                    $tmpInsert = $aDB->table($table)->insert($one);
                    $id = $tmpInsert['id'];
                }else{
                    $tmpUpdate = $test->update($one);
                    $id = $key;
                }
                $cntRecords++;
                foreach($tables as $keyOneTable => $oneTable){
                    if (is_array($oneTable)){
                        $resultChild = [];
                        if (substr($oneTable[0], 0,1) == ":"){
                            if ($test2 = $aDB->table($table)->where("id = ?", $key)->fetch()) {
                                if (!is_null($test2[substr($oneTable[0], 1)])) {
                                    $resultChild = $sDB->table($keyOneTable)->where("id = ?", $test2[substr($oneTable[0], 1)]);
                                }
                            }
                        }else{
                            if ($test2 = $aDB->table(substr($oneTable[0],0,-3))->where($table . "_id = ?", $key)->fetchPairs('id','id')) {
                                //dump($oneTable[0]);
                                //dump($test2);
                                $resultChild = $sDB->table($keyOneTable)->where($oneTable[0] . " IN (?)", $test2);
                            }
                        }

                        $oneTable = $keyOneTable;
                    }else {
                        $resultChild = $sDB->table($oneTable)->where($table . "_id = ?", $id);
                    }
                    foreach($resultChild as $keyChild => $oneChild) {
                        $testChild = $aDB->table($oneTable)->where("id = ?", $keyChild)->fetch();
                        if (!$testChild) {
                            $tmpInsertChild = $aDB->table($oneTable)->insert($oneChild);
                            $idChild = $tmpInsertChild['id'];
                        } else {
                            $tmpUpdate = $testChild->update($oneChild);
                            $idChild = $keyChild;
                        }
                        $cntRecords++;
                        //dump(array_search($oneTable,$nodelete));
                        if (!is_null($dateTo) && array_search($oneTable,$nodelete) === FALSE) {
                            $oneChild->delete();
                        }
                    }
                }
                //dump(array_search($table,$nodelete));
                if (!is_null($dateTo) && array_search($table,$nodelete) === FALSE) {
                     $one->delete();
                }
            }
            $aDB->query("SET FOREIGN_KEY_CHECKS = 1;");
            $sDB->query("SET FOREIGN_KEY_CHECKS = 1;");

            $aDB->commit();
            $sDB->commit();
            return $cntRecords;
        } catch (Exception $e) {
            Debugger::log($e, Debugger::ERROR);
            $aDB->rollback();
            $sDB->rollback();
            return 0;
        }
    }


    
}