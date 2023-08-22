<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HostsController;
use App\Http\Controllers\CardsController;
use App\Http\Controllers\RdmaController;
use App\Http\Controllers\CmdsController;
use App\Http\Controllers\TestsController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/',function(){
    return view('index');
});
Route::post('/UserVerify', [UserController::class,'login']);
Route::middleware('WebToken')->group(function () {
    Route::get('/users', [UserController::class,'returnUser']);
    Route::post('/users', [UserController::class,'addUser']);
    Route::put('/users/{id}/state/{state}', [UserController::class,'updateUser']);
    Route::put('/users/{id}', [UserController::class,'editUser']);
    Route::delete('/users/{id}', [UserController::class,'deleteUser']);

    Route::get('/host', [HostsController::class,'returnHost']);
    Route::post('/hosts', [HostsController::class,'addHost']);
    Route::put('/editHost/{id}', [HostsController::class,'updateHostAndPass']);
    Route::put('/updatePass/{id}', [HostsController::class,'updateHostAndPass']);
    Route::put('/host/{id}/state/{state}', [HostsController::class,'updateHostState']);
    Route::delete('/host/{id}', [HostsController::class,'deleteHost']);

    Route::get('/card', [CardsController::class,'returnCard']);
    
    Route::get('/rdma', [RdmaController::class,'returnRDMA']);
    Route::delete('/rdma/{id}', [RdmaController::class,'deleteRdma']);

    Route::get('/menu', [TestsController::class,'returnTestMenu']);
    Route::post('/testTQ', [TestsController::class,'testTQ']);
    Route::post('/addTQ', [TestsController::class,'addTQ']);
    Route::delete('/delTQ', [TestsController::class,'deleteTQ']);
    Route::post('/excuteTest', [TestsController::class,'executeTest']);
    Route::post('/result', [TestsController::class,'returnTestResult']);

    Route::post('/exec_cmd', [CmdsController::class,'sshExcuteFromSSH']);
});

