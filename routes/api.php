<?php

use App\Http\Controllers\StoreDocumentController;
use Illuminate\Support\Facades\Route;

Route::post('documents', StoreDocumentController::class)->name('documents.store');
