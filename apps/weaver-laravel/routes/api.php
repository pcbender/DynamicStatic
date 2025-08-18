use App\Http\Controllers\AuthController;
use App\Support\ApiResponse;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/callback/google', [AuthController::class, 'handleGoogleCallback']);
Route::get('/auth/callback/microsoft', [AuthController::class, 'handleMicrosoftCallback']);

Route::get('/healthz', fn() => ApiResponse::ok([
    'app' => config('app.name'),
    'env' => app()->environment(),
    'time' => now()->toIso8601String(),
    'providers' => [
        'google' => (bool) env('GOOGLE_CLIENT_ID'),
        'microsoft' => (bool) env('MICROSOFT_CLIENT_ID'),
    ],
]));