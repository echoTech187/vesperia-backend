<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FormController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/form-schema', [FormController::class, 'getFormSchema']);
Route::post('/upload-feed', [FormController::class, 'uploadFeed']);
Route::post('/submissions', [FormController::class, 'submitForm']);
Route::get('/submissions', [FormController::class, 'getSubmissions']);
Route::get('/submissions/{id}', [FormController::class, 'getSubmissionDetail']);
