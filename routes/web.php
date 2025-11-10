<?php

use App\Http\Controllers\Admin\AiPromptRuleController;
use App\Http\Controllers\Admin\ProtocolController;
use App\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::post('/protocols/generate-tags', [ProtocolController::class, 'previewGenerateTags'])
        ->name('protocols.generate-tags.preview');
    Route::post('/protocols/{protocol}/generate-tags', [ProtocolController::class, 'generateTags'])
        ->name('protocols.generate-tags');

    Route::resource('protocols', ProtocolController::class)->except(['show']);

    Route::get('/ai-rules', [AiPromptRuleController::class, 'edit'])->name('ai-rules.edit');
    Route::put('/ai-rules', [AiPromptRuleController::class, 'update'])->name('ai-rules.update');

    Route::get('/tags/suggest', [TagController::class, 'suggest'])->name('tags.suggest');
    Route::resource('tags', TagController::class)->except(['show']);
});


