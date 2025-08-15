use App\Http\Controllers\AuthController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/callback/google', [AuthController::class, 'handleGoogleCallback']);
Route::get('/auth/callback/microsoft', [AuthController::class, 'handleMicrosoftCallback']);