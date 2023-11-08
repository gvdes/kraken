<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelpersController as Helpers;
use App\Http\Controllers\Kraken;
use App\Http\Controllers\SyncController as Sync;
use App\Http\Controllers\Monitor;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocatorController;
use App\Http\Controllers\ProductFinder;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\VmediaController;
use App\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', fn() => response("<h1 style='color:green;padding:10px;border-radius:10px;background:yellow;'>==> Kraken's running <==</h1>",401));
Route::get('/pfinder/{sid}', ProductFinder::class)->where(['sid' => '[0-9]+']);
Route::post('/signin', [Kraken::class,'trySignin']);


Route::middleware('kraken')->group(function(){

    Route::prefix('kraken')->controller(Kraken::class)->group(function (){
        Route::post('firstlogin','firstlogin');
    });

    Route::prefix('store/{sid}')
        ->where(['sid' => '[0-9]+'])
        ->middleware('usestore')
        ->group(function(){
            Route::get('/', [StoreController::class, 'index']);

            Route::prefix('warehouses')->controller(WarehouseController::class)->group(function(){
                Route::get('/', 'index');

                Route::prefix('/{wid}')
                    ->middleware('usewarehouse')
                    ->group(function(){
                        Route::get('/','open');
                        Route::get('structure','structure');
                        Route::post('structure','sectionate');
                        Route::get('products','products');
                        Route::get('resume','resume');
                        Route::get('report/{repid}','report')->where([ 'repid' => '[0-9]+' ]);

                        Route::prefix('section/{lid}')
                            ->controller(LocationController::class)
                            ->group(function(){
                                Route::get('/', 'open');
                                Route::get('structure', 'structure');
                                Route::post('structure','sectionate');
                                Route::get('resume','resume');
                        });
                });

                Route::post('/', 'create');
            });

            Route::prefix('locator')
                // ->middleware('uselocator')
                ->controller(LocatorController::class)
                ->group(function(){
                    Route::get('location/{loc}', 'location');
                    Route::get('product/{code}', 'product');
                    Route::post('toggle', 'toggle');
            });

            Route::prefix('restock')
                // ->middleware(userestock)
                ->controller(RestockController::class)
                ->group(function(){
                    Route::get('/','index');
                    Route::post('/','create');
                    Route::get('/{rid}','find')->where(['rid'=>'[0-9]+']);
                    Route::get('/preview/{rid}','preview')->where(['rid'=>'[0-9]+']);
                });
        });

    Route::prefix('cluster')
    // ->middleware('cluster')
    ->group(function(){

        Route::prefix('accounts')
            ->controller(UsersController::class)
            ->group(function(){
                Route::patch('fullreset','fullReset');
            });

    });

    Route::prefix('vmedia')
        ->controller(VmediaController::class)
        ->group(function(){
            Route::post('addimages','addimages');
            Route::patch('archive','archive');
        });
});

Route::prefix('sync')->controller(Sync::class)->group(function(){
    Route::patch('products', 'products');
});

Route::prefix('monitor')->controller(Monitor::class)->group(function(){
    Route::get('/','index');
});

Route::prefix('helpers')->controller(Helpers::class)->group(function(){
    Route::get('/',fn() => response("it Works!!",401) );
    Route::get('pinger', 'pinger');
    Route::get('genpass/{str}', 'genpass');
    Route::get('twilio/test', 'twiliotest');
    Route::get('genuuid', 'genUuid');
});
