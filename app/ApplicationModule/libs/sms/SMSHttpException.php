<?php

	namespace SMSManager;

	class SMSHttpException extends \Exception {
		
		public function __construct($code) {
			switch ($code) {
				case 101:
					$error = 'Bad request (missing XMLDATA)';
					break;
				case 102:
					$error = 'Špatný formát';
					break;
				case 103:
					$error = 'Chybné jméno nebo heslo';
					break;
				case 104:
					$error = 'Invalid parameter gateway';
					break;
				case 105:
					$error = 'Nízký kredit';
					break;
				case 109:
					$error = 'The requirement does not contain required data';
					break;
				case 201:
					$error = 'Neplatné telefonní číslo';
					break;
				case 202:
					$error = 'Příliš dlouhá, nebo žádná zpráva';
					break;
				case 203:
					$error = 'Chybný parametr odeslání';
					break;
				case 500:
				case 503:
					$error = 'System error';
					break;
				default:
					$error = 'Unkown error code';
			}

			parent::__construct($error, $code);
		}

	}