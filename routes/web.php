<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RdmaController;
use App\Http\Controllers\JsonController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DebugController;

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

    Route::get('/host', [RdmaController::class,'returnHost']);
    Route::post('/hosts', [RdmaController::class,'addHost']);
    Route::put('/editHost/{id}', [RdmaController::class,'editHost']);
    Route::put('/updatePass/{id}', [RdmaController::class,'updateHostPass']);
    Route::put('/host/{id}/state/{state}', [RdmaController::class,'updateHostState']);
    Route::delete('/host/{id}', [RdmaController::class,'deleteHost']);

    Route::get('/card', [RdmaController::class,'returnCard']);
    
    Route::get('/rdma', [RdmaController::class,'returnRDMA']);
    Route::delete('/rdma/{id}', [RdmaController::class,'deleteRdma']);

    Route::get('/menu', [RdmaController::class,'returnTestMenu']);

    Route::post('/testTQ', [RdmaController::class,'testTQ']);
    Route::post('/addTQ', [RdmaController::class,'addTQ']);
    Route::post('/testTQ', [RdmaController::class,'testTQ']);

    Route::delete('/TQ/{id}', [RdmaController::class,'deleteTQ']);
    Route::post('/excuteTest', [RdmaController::class,'excuteTest']);
    Route::post('/result', [RdmaController::class,'returnTestResult']);

    Route::post('/exec_cmd', [RdmaController::class,'sshExcuteFromSSH']);
});

