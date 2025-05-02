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
use App\Http\Controllers\StoresController;
use App\Http\Controllers\ProvidersController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ComparatorWarehouse;
use App\Http\Controllers\PreorderController;
use App\Http\Controllers\AssistController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ClientController;





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
                Route::post('/', 'create');

                Route::prefix('/{wid}')
                    ->middleware('usewarehouse')
                    ->group(function(){
                        Route::get('/','open');
                        Route::get('structure','structure');
                        Route::post('structure','sectionate');
                        Route::get('products','products');
                        Route::get('resume','resume');
                        Route::post('setminmaxstate','setminmaxstate');
                        Route::get('comparator','comparator');
                        Route::post('comparator/{vswid}','comparator_start')->where([ 'vswid' => '[0-9]+' ]);
                        Route::get('report/{repid}','report')->where([ 'repid' => '[0-9]+' ]);

                        Route::prefix('section/{lid}')
                            ->controller(LocationController::class)
                            ->group(function(){
                                Route::get('/', 'open');
                                Route::get('structure', 'structure');
                                Route::post('structure','sectionate');
                                Route::get('resume','resume');
                        });
                        Route::prefix('comparatool')
                            ->controller(ComparatorWarehouse::class)
                            ->group(function(){
                                Route::get('','index');
                                Route::get('report/{repid}','report');
                            });
                });
            });

            Route::prefix('locator')
                // ->middleware('uselocator')
                ->controller(LocatorController::class)
                ->group(function(){
                    Route::get('location/{loc}', 'location');
                    Route::get('product/{code}', 'product');
                    Route::post('toggle', 'toggle');
                    Route::post('unlink', 'unlink');
                    Route::post('truncate', 'truncate');
            });

            Route::prefix('restock')
                // ->middleware(userestock)
                ->controller(RestockController::class)
                ->group(function(){
                    Route::get('/','index');
                    Route::post('/','create');
                    Route::get('/{rid}','find')->where(['rid'=>'[0-9]+']);
                    // Route::get('/preview/{rid}','preview')->where(['rid'=>'[0-9]+']);
                    Route::get('/preview/{rid}','preview');
                });


            Route::prefix('orders')->controller(PreorderController::class)->group(function(){
                Route::get('/', 'index');
                Route::get('/getOrdersCheckin','getOrdersCheckin');
                Route::get('/getConfig', 'getConfig');
                Route::get('/getOrderforuser', 'getOrderforuser');
                Route::get('/getPrints/{type}', 'getPrints');
                Route::get('/{oid}', 'getOrder');
                Route::post('/getOrders', 'getOrders');
                Route::post('/createOrder', 'createOrder');
                Route::post('/createOrderAnexo', 'createOrderAnexo');
                Route::post('/addProduct', 'addProduct');
                Route::post('/ModifyProduct', 'ModifyProduct');
                Route::post('/removeProduct', 'removeProduct');
                Route::post('/changeStatus','changeStatus');
                Route::post('/changeConfig','changeConfig');
                Route::post('/reprintOrderWarehouse','reprintOrderWarehouse');
            });

            Route::prefix('Printers')->controller(PrinterController::class)->group(function(){
                Route::get('getPrinterStore','getPrinterStore');
                Route::post('testPrint','testPrint');
            });

            Route::prefix('cash')->controller(CashController::class)->group(function(){
                Route::get('/getCash', 'getCash');
                Route::get('getCashAssigned','getCashAssigned');
                Route::post('/OpenCash', 'OpenCash');
                Route::post('/closeBox', 'closeBox');
            });
            Route::prefix('rrhh')->controller(AssistController::class)->group(function(){
                Route::get('/justifications', 'Index');
                Route::get('/form', 'form');
                Route::get('index','index');
                Route::get('/getTurnsWeek', 'getTurnsWeek');
                Route::get('getReportWeek','getReportWeek');
                Route::get('/pingStore/{d}','pingStore')->where(['d' => '[0-9]+']);;
                Route::post('/addTurnsWeek', 'addTurnsWeek');
                Route::post('/deleteTurnUser', 'deleteTurnUser');
                Route::post('/addFile', 'addFile');
                Route::post('/addForm', 'addForm');
                Route::post('getReportUserWeek','getReportUserWeek');
                Route::post('getReportUserWeekFilt','getReportUserWeekFilt');
                Route::post('getRegisDevice/{d}','getRegisDeviceStore');
                Route::post('changeDate/{d}','changeDateStore');
            });
            Route::prefix('users')->controller(UsersController::class)->group(function(){
                Route::get('/getUserForStore', 'getUserForStore');
                Route::get('changePass/{uid}','RessetPass');
            });
            Route::prefix('resp/form')->controller(IndicatorController::class)->group(function(){
                Route::get('/{form}','getFormResp');
                Route::post('/addResponse','addResponse');

            });
        });

    Route::prefix('apps/{sid}')
        ->group(function(){
            Route::prefix('transfers')
            ->controller(App\Http\Controllers\AppTransfers::class)
            ->group(function(){
                Route::get('/', 'index');
                Route::post('/', 'create');
                Route::get('/adminview', 'adminView');
                Route::get('/{tid}', 'open');
                Route::post('/{tid}/push', 'push');
            });
    });


    Route::prefix('cluster')->group(function(){
        Route::prefix('accounts')->controller(UsersController::class)->group(function(){
            Route::patch('fullreset','fullReset');
            Route::get('users','index');
        });
    });

    Route::prefix('cluster')
    ->group(function(){
        Route::prefix('accounts')->middleware('UseUsers')->controller(UsersController::class)->group(function(){
            Route::get('users','getUsers');
            Route::get('getIndex','getIndex');
            Route::get('getUserWor','getUserWorkpoint');
            Route::get('getPosition','getPosition');
            Route::get('changePass/{uid}','RessetPass');
            Route::get('getPermissionsRol/{id}','getPermissionsRol');
            Route::put('changework','changeWork');
            Route::post('updateuser','updateUser');
            Route::post('adduser','addUser');
            Route::post('addArea','addArea');
            Route::post('addPuesto','addPuesto');
            Route::post('modifyPuesto','modifyPuesto');
            Route::post('InsertRCid','InsertRCid');
        });
        Route::prefix('stores')->middleware('UseStores')->controller(StoresController::class)->group(function(){
            Route::get('index','getStores');
            Route::post('addStore','addStore');
            Route::put('updateStore','updateStore');
        });
        Route::prefix('providers')->middleware('UseProviders')->controller(ProvidersController::class)->group(function(){
            Route::get('index','getProviders');
            Route::post('create','create');
            Route::post('update','update');
        });
        Route::prefix('Products')->middleware('UseProducts')->controller(ProductController::class)->group(function(){
            Route::get('index','index');

            Route::get('getProduct/{product}','getProduct');
            Route::post('searchProd','searchProd');

        });
        Route::prefix('Assist')->middleware('UseAssist')->controller(AssistController::class)->group(function(){
            Route::get('index','index');
            Route::get('new','new');
            Route::get('getJustifications','getJustifications');
            Route::get('getReportWeek','getReportWeek');
            Route::get('pingNew/{d}','pingNew');
            Route::get('ping/{d}','ping');
            Route::post('edit','edit');
            Route::post('addProceedings','addProceedings');
            Route::post('addDevice','addDevice');
            Route::post('changeStatus','changeStatus');
            Route::post('getFiltReport','getFiltReport');
            Route::post('getFiltJustifications','getFiltJustifications');
            Route::post('getRegisDevice/{d}','getRegisDevice');
            Route::post('changeDate/{d}','changeDate');
            Route::delete('deleteAttendance/{d}','deleteAttendance');

        });
        Route::prefix('Clients')->middleware('UseClients')->controller(ClientController::class)->group(function(){
            Route::get('getClients','getClients');
            Route::post('editClient','editClient');
            Route::post('replyClient','replyClient');

        });

        Route::prefix('Indicators')->middleware('UseIndicator')->controller(IndicatorController::class)->group(function(){
            Route::get('index','index');
            Route::get('getForms','getForms');
            Route::get('getClass','getClass');
            Route::get('getClassStore','getClassStore');
            Route::get('getUserClass','getUserClass');
            Route::get('compareUserClassification/{userId}','compareUserClassification');
            Route::get('/{form}', 'getForm');
            Route::get('/{id}/viewResponseForm','viewResponseForm');
            Route::post('addForm','addForm');
            Route::post('changeQualified','changeQualified');
            Route::post('changeStatus','changeStatus');
            Route::post('addQuestion','addQuestion');
            Route::post('editQuest','editQuest');
            Route::post('deleteQuest','deleteQuest');
            Route::post('editClass','editClass');
            Route::post('editClassStore','editClassStore');
            Route::post('editUserClass','editUserClass');
            Route::post('editUserStore','editUserStore');
            Route::post('changeUserBonues','changeUserBonues');
            Route::post('getformResponses','getformResponses');
            Route::post('getCalculateClassUser','getCalculateClassUser');
            Route::post('getCalculateClassUserFilter','getCalculateClassUserFilter');
        });
        Route::prefix('cash')->middleware('UseCash')->controller(CashController::class)->group(function(){
            Route::get('Index','Index');
            Route::get('getDocument','getDocument');
            Route::get('getTPV','getTPV');
            Route::get('mosFIle/{id}','mosFIle');
            Route::post('editDocument','editDocument');
            Route::post('addTPV','addTPV');
            Route::post('editTPV','editTPV');
            Route::post('editCash','editCash');
        });

        Route::prefix('Printers')->middleware('UsePrinter')->controller(PrinterController::class)->group(function(){
            Route::get('index','index');
            Route::post('testPrint','testPrint');
            Route::post('editPrint','editPrint');
            Route::post('deletePrint','deletePrint');
        });
        Route::prefix('Indicators')->controller(IndicatorController::class)->group(function(){
            Route::get('getForms','getForms');
            Route::get('getForm/{form}','getFormResp');
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
