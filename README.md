بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
-----------------------------

وَٱعْتَصِمُوا۟ بِحَبْلِ ٱللَّهِ


In the name of ALLAH SWT,
-----------------------------

![alt img](https://avatars.githubusercontent.com/u/86165096?s=256&v=4)

Alhamdulillah because of Allah SWT, this code successfully created piece by piece start from Mar 29, 2017.

This library used for simplifying some code with Laravel framework, hopefully can help us all to build web-app. This code inspired by Muntilan-CMS code developed by [.::bit](https://www.limabit.com), by the way.


1). INSTALL LARAVEL (Max : Version 10)
--------------------------------------------------------------------------------
	composer create-project --prefer-dist laravel/laravel:10.0 canvastack (webappname)
 	cd canvastack/ [cd webappname/]


2). INSTALL LIBRARY WITH JSON FILE SETTING OR VIA COMPOSER CLI:
--------------------------------------------------------------------------------
	WITH JSON FILE:
 	
	"require": {
		"canvastack/canvastack": "dev-master"
	},
	"repositories": [{
		"type" : "vcs",
		"url"  : "git@github.com:canvastack/canvastack.git"
	}]

 	
  	OR VIA COMPOSER
   	
	Just type this code: composer require canvastack/canvastack

3). COMPOSER UPDATE
--------------------------------------------------------------------------------
	composer update


4). ARTISAN PUBLISH
--------------------------------------------------------------------------------
	php artisan vendor:publish --force


5). CHECK DATABASE
--------------------------------------------------------------------------------
	Check file path database/migrations/2014_10_12_000000_create_users_table.php (delete it!)
 	
 	Change db name (DB_DATABASE) in .env file
  	
  	Create your database name in mysql


6). MIGRATION TABLES
--------------------------------------------------------------------------------
	php artisan migrate:refresh --seed


7). CONFIG FILE
--------------------------------------------------------------------------------
	Change baseURL path in config file with your own path [ config/canvas.settings.php in line:35 ]


8). DEMO ACCESS
--------------------------------------------------------------------------------
	url     : http://localhost/webappname
	username: admin@gmail.com
	password: @admin
	
--------------------------------------------------------------------------------
Visit the demo site at [demo.canvastack.com](https://demo.canvastack.com/login)




DOCUMENTATION
--------------------------------------------------------------------------------
	On Progress
