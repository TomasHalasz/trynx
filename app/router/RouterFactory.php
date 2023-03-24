<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	/*public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');
		return $router;
	}*/
	
	
	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		$router[] = new Route('admin/<presenter>/<action>',
								array('module' => 'Administration', 'presenter' => 'Homepage', 'action' => 'default'));

		$router[] = new Route('[<locale=cs cs|en>/][<dbName=dbCurrent>/]login/<presenter>/<action>',
								    array('module' => 'Login', 'presenter' => 'Homepage', 'action' => 'default'));

		$router[] = new Route('[<locale=cs cs|en>/][<dbName=dbCurrent>/]application/<presenter>/<action>[/<id>][/<center_name>]',
								    array('module' => 'Application', 'presenter' => 'Homepage', 'action' => 'default'));

        $router[] = new Route('[<locale=cs cs|en>/][<dbName=dbCurrent>/]intranet/<presenter>/<action>[/<id>][/<center_name>]',
                                    array('module' => 'Intranet', 'presenter' => 'Homepage', 'action' => 'default'));

		$router[] = new Route('[<locale=cs cs|en>/][<dbName=dbCurrent>/]api/<presenter>/<action>[/<id>]',
								    array('module' => 'API', 'presenter' => 'Homepage', 'action' => 'default'));

        $router[] = new Route('[<locale=cs cs|en>/][<dbName=dbCurrent>/]b2b/<presenter>/<action>[/<id>]',
                                    array('module' => 'B2B', 'presenter' => 'Homepage', 'action' => 'default'));

		$router[] = new Route('sitemap.xml',
								    array('module' => 'Front', 'presenter' => 'Sitemap', 'action' => 'default'));

		$router[] = new Route('[<locale=cs cs|en>/]clanek/<action>[/<id>]',
								    array('module' => 'Front', 'presenter' => 'Article', 'action' => 'default'));

		$router[] = new Route('[<locale=cs cs|en>/]blog/clanek/<title>[/<id>][/<blog_categories_id>]',
								    array('module' => 'Front', 'presenter' => 'BlogDetail', 'action' => 'default'));				

		$router[] = new Route('[<locale=cs cs|en>/]blog/tags[/<name>][/<blog_tags_id>]',
								    array('module' => 'Front', 'presenter' => 'Blog', 'action' => 'tags'));				

		$router[] = new Route('[<locale=cs cs|en>/]blog/kategorie[/<name>][/<blog_categories_id>]',
								    array('module' => 'Front', 'presenter' => 'Blog', 'action' => 'default'));		
		$router[] = new Route('[<locale=cs cs|en>/]<presenter>/<action>[/<id>]',
								    array('module' => 'Front', 'presenter' => 'Homepage', 'action' => 'default'));

		return $router;
	}
}

