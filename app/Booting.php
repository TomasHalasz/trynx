<?php

declare(strict_types=1);

namespace App;

use Nette\Configurator;


class Booting
{
	public static function boot(): Configurator
	{
        ini_set("pcre.backtrack_limit", "50000000");
		$configurator = new Configurator;

		if (strpos(__DIR__,'beta_html')) {
		    $configurator->setDebugMode(['127.0.0.1', '217.66.185.90']); // enable for your remote IP
		}else{
		   // $configurator->setDebugMode(array('127.0.0.1', '192.168.2.5', '192.168.2.12', '192.168.2.29', '192.168.200.16', '10.211.55.1', '10.211.55.5')); // enable for your remote IP
            $configurator->setDebugMode(['127.0.0.1', '192.168.2.5', '192.168.2.12', '192.168.2.29', '192.168.200.16', '10.211.55.2', '10.211.55.5']); // enable for your remote IP
           // $configurator->setDebugMode(array('127.0.0.1'));
		}

		//$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');

		$configurator->createRobotLoader()
                    ->addDirectory(__DIR__)
                    ->addDirectory(__DIR__ . '/../vendor/others')
                    ->register();


		$configurator->addConfig(__DIR__ . '/config/common.neon');
        $configurator->addConfig(__DIR__ . '/config/archive.neon');
		//klienti.cz beta.klienti.cz
        $tmpServer_adr = array();
        if (file_exists($configFile = __DIR__ . '/config/config.neon')) {
            $configurator->addConfig(__DIR__ . '/config/config.neon');
        }
        if (file_exists($configFile = __DIR__ . '/config/local_1.neon') && strpos(__DIR__,'localhost')) {
            $configurator->addConfig($configFile);
        }elseif (strpos(__DIR__,'beta_html')) {
		    $configFile = __DIR__ . '/config/beta.neon';
		    $configurator->addConfig($configFile);

		}elseif (strpos(__DIR__,'public_html')) {
            $configFile = __DIR__ . '/config/config.neon';
            $configurator->addConfig($configFile);


        }elseif (strpos(__DIR__,'medhelp_html')) {
            $configFile = __DIR__ . '/config/medhelp.neon';
            $configurator->addConfig($configFile);
        }elseif (strpos(__DIR__,'precistec_web')) {
            $configFile = __DIR__ . '/config/precistec.neon';
            $configurator->addConfig($configFile);

        }elseif(strpos(__DIR__,'plk')) {
            //plk.cz
            $configurator->addConfig(__DIR__ . '/config/plk.neon');
        }elseif(strpos(__DIR__,'kucinka')) {
            //buddyshop.cz
            $configurator->addConfig(__DIR__ . '/config/kucinka.neon');
        }elseif(strpos(__DIR__,'perrito')) {
            //perrito.cz
            $configurator->addConfig(__DIR__ . '/config/perrito.neon');
        }else{
            //precistec.cz
            //$configurator->addConfig(__DIR__ . '/config/precistec.neon');
            //define("SERVER_ADR", array());


            //bebidos.cz
            if (file_exists($configFile = __DIR__ . '/config/bebidos.neon')) {
                $configurator->addConfig(__DIR__ . '/config/bebidos.neon');
                $tmpServer_adr = array('local' => '192.168.1.20', 'remote' => '82.142.100.3');
            }
            
        	//define("SERVER_ADR", array('local' => 'localhost', 'remote' => '82.142.100.3'));

			//techbelt
			//$configurator->addConfig(__DIR__ . '/config/techbelt.neon');
   
			///plk
			//$configurator->addConfig(__DIR__ . '/config/plk.neon');

        }
        if (!defined("APP_DIR")) {
            define("APP_DIR", __DIR__);
        }
        if (!defined("SERVER_ADR")) {
            define("SERVER_ADR", $tmpServer_adr);
        }

        /*Customization defined by 3.domain name*/
        if (!defined("CMZ_NAME")) {
            if (isset($_SERVER['SERVER_NAME'])) {
                $tmpArrDomain = str_getcsv($_SERVER['SERVER_NAME'], ".");
                if (count($tmpArrDomain) == 3 && $tmpArrDomain[0] != "www") {
                    define("CMZ_NAME", $tmpArrDomain[0]);
                    //define("CMZ_NAME", "devel");
                } elseif ($_SERVER['SERVER_NAME'] == "localhost") {
                    //define("CMZ_NAME", "morys"); //for testing purposes
                    define("CMZ_NAME", "");
                } else {
                    define("CMZ_NAME", "");
                }
            } else {
                define("CMZ_NAME", "");
            }
        }

		return $configurator;
	}
}


