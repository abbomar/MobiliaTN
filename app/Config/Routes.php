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

$routes->group('api/v1', function ($routes){

    $routes->get('auth/me' , 'AuthenticationController::me');

    $routes->group('store/(:uuid)', function ($routes) {

        // TODO: Add filters to check if store exist and the partner owns this store
        $routes->get('cashier', 'CashierController::index/$1', [ 'filter' => 'auth-filter:MANAGER;DIRECTOR;PARTNER' ]);
        $routes->post('cashier', 'CashierController::create/$1', [ 'filter' => 'auth-filter:MANAGER;DIRECTOR;PARTNER' ]);
        $routes->put('cashier/(:uuid)',  'CashierController::update/$1/$2', [ 'filter' => 'auth-filter:MANAGER;DIRECTOR;PARTNER' ]);

        $routes->get('manager',  'ManagerController::index/$1', [ 'filter' => 'auth-filter:PARTNER'  ]);
        $routes->post('manager', 'ManagerController::create/$1', [ 'filter' => 'auth-filter:PARTNER' ]);
        $routes->put('manager/(:uuid)',  'ManagerController::update/$1/$2',  [ 'filter' => 'auth-filter:PARTNER' ]);

        $routes->get('registry',  'RegistryController::index/$1', [ 'filter' => 'auth-filter:MANAGER;DIRECTOR;PARTNER;CASHIER']);
        $routes->post('registry',  'RegistryController::create/$1', [ 'filter' => 'auth-filter:MANAGER;DIRECTOR;PARTNER' ]);
        $routes->put('registry/(:uuid)', 'RegistryController::update/$1/$2', [ 'filter' => 'auth-filter:MANAGER;DIRECTOR;PARTNER' ]);
        $routes->get('registry/(:uuid)/sumByDate', 'RegistryController::totalSumByDate/$1/$2');
        $routes->post('registry/(:uuid)/close', 'RegistryController::closeRegistry/$1/$2');

    });

    $routes->resource('partner', ['filter' => 'auth-filter:ADMIN', 'controller' => 'PartnerController', 'placeholder' => '(:uuid)'] );
    $routes->resource('director', ['filter' => 'auth-filter:MANAGER', 'controller' => 'DirectorController', 'placeholder' => '(:uuid)'] );

    $routes->resource('store',  ['filter' => 'auth-filter:PARTNER', 'controller' => 'StoreController', 'placeholder' => '(:uuid)'] );

    $routes->group('group', ['filter' => 'auth-filter:ADMIN'], function($routes) {
        $routes->get('/', 'GroupController::index');
        $routes->post('/', 'GroupController::create');
        $routes->post('(:uuid)/appendUsers', 'GroupController::appendUsers/$1');
    });

    $routes->group('transaction',[] , function($routes) {
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
