# MobileID
Laravel mobile ID authentication

## Installation
```
composer require kullar84/mobileid
```
The package is now installed. If you are using laravel version &lt; 5.5, add the following line in your `config/app.php` providers section:
```
kullar84\mobileid\MobileIDServiceProvider::class,
```

Create new middleware group to Kernel.php
```
protected $middlewareGroups = [
        'session' => [
            \Illuminate\Session\Middleware\StartSession::class,
        ],
    ];
```

Add new routes that uses this middleware
```
Route::group(['middleware' => 'session'], function () {
    Route::post('mlogin', 'AuthController@mlogin')->name('mlogin');
    Route::post('domlogin', 'AuthController@doMLogin')->name('domlogin');
});

```

Create required methods
```
use kullar84\mobileid\MobileIDAuthenticate;


protected function mlogin(Request $request)
{
	$this->validate(
		$request, [
			'phone' => 'required|numeric|exists:users,phone',
		]
	);

	$phone = $request->input('phone');

	$auth = new MobileIDAuthenticate();

	$response = $auth->startAuth($phone);

	return response()->json($response);
}

protected function doMLogin(Request $request)
{
	//https://www.id.ee/?id=36373
	$auth = new MobileIDAuthenticate();
	$response = $auth->checkAuthStatus();

	if ($response->isError()) {
		return response()->json(['error' => true, 'message' => $response->error]);
	} elseif ($response->isPending()) {
		return response()->json(['pending' => true, 'message' => 'Please wait! You will be redirected shortly']);
	} elseif ($response->isSuccess()) {
		$phone = $request->input('phone');

		$user = User::findUserByPhone($phone);

		if ($user !== null) {
			$token = $user->createToken('Token')->accessToken;

			return $this->respondWithToken($token);
		}
	}

	return response()->json(['message' => 'Unauthorized'], 422);
}
	
```