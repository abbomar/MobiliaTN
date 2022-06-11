<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.


$routes->addPlaceholder('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

$routes->group('api/v1', function ($routes) {

    $routes->post('testSMS' , "TransactionController::testSMS");

    $routes->get('auth/me' , 'AuthenticationController::me');

    $routes->get("stats/adminDetailsStats", "StatsController::adminDetailsStats");
    $routes->get("stats/adminSummaryStats", "StatsController::adminSummaryStats");

    $routes->group('brand', function ($routes) {
        $routes->get("", "BrandController::index");
        $routes->post("", "BrandController::create");
        $routes->put("(:uuid)", "BrandController::update/$1");
        $routes->get("(:uuid)/logo", "BrandController::getImage/$1" );
    });

    $routes->group('store/(:uuid)', function ($routes) {

        $routes->get("stats", 'StoreController::stats/$1');

        $routes->group('cashier', [ 'filter' => 'auth-filter:MANAGER' ] , function ($routes) {
            $routes->get('', 'CashierController::index/$1');
            $routes->post('', 'CashierController::create/$1');
            $routes->put('(:uuid)', 'CashierController::update/$1/$2');
            $routes->delete('(:uuid)', 'CashierController::delete/$1/$2');
        });


        $routes->group('manager', [ 'filter' => 'auth-filter:PARTNER' ] , function ($routes) {
            $routes->get('', 'ManagerController::index/$1');
            $routes->post('', 'ManagerController::create/$1');
            $routes->put('(:uuid)', 'ManagerController::update/$1/$2');
            $routes->delete('(:uuid)', 'ManagerController::delete/$1/$2');
        });


        $routes->group('registry' , [ 'filter' => 'auth-filter:MANAGER' ] , function ($routes){
            $routes->get('',  'RegistryController::index/$1', [ 'filter' => 'auth-filter:MANAGER;CASHIER']);
            $routes->post('',  'RegistryController::create/$1');
            $routes->put('(:uuid)', 'RegistryController::update/$1/$2');
            $routes->delete('(:uuid)', 'RegistryController::delete/$1/$2');
            $routes->get('(:uuid)/sumByDate', 'RegistryController::totalSumByDate/$1/$2');
            $routes->post('(:uuid)/close', 'RegistryController::closeRegistry/$1/$2');
            $routes->get('(:uuid)/stats', 'RegistryController::stats/$1/$2');
            $routes->get('(:uuid)/listTransactions', 'RegistryController::listTransactions/$1/$2');
        });


    });

    $routes->resource('partner', ['filter' => 'auth-filter:ADMIN', 'controller' => 'PartnerController', 'placeholder' => '(:uuid)'] );
    $routes->resource('director', ['filter' => 'auth-filter:PARTNER', 'controller' => 'DirectorController', 'placeholder' => '(:uuid)'] );
    $routes->resource('store',  ['filter' => 'auth-filter:PARTNER;DIRECTOR', 'controller' => 'StoreController', 'placeholder' => '(:uuid)'] );


    $routes->group('group',  function($routes) {
        $routes->get('/', 'GroupController::index');
        $routes->post('/', 'GroupController::create');
        $routes->get('(:uuid)/users', 'GroupController::getGroupUsers/$1');
        $routes->post('(:uuid)/appendUsers', 'GroupController::appendUsers/$1');

        $routes->delete('(:uuid)', 'GroupController::delete/$1');
        //$routes->delete('(:uuid)/blockUsers', 'GroupController::blockUsers/$1');
    });


    $routes->group('transaction', [/*'filter' => 'auth-filter:CASHIER'*/] , function($routes) {
        $routes->get('cashierHistory', 'TransactionController::cashierTransactionsHistory' );
        $routes->get('clientHistory', 'TransactionController::clientTransactionsHistory' );
        $routes->post('initiate', 'TransactionController::initiateTransaction');
        $routes->post('(:uuid)/validate', 'TransactionController::validateTransaction/$1');
    });



});


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
