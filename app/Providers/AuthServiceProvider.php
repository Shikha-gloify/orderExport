<?php

namespace App\Providers;

use App\Oath_clients;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.
        //echo '<pre>'; //print_r($this->app['auth']); 
        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->header("authorization")) {
                //$client_id_decode = base64_decode($request->header("authorization"));
                //echo '<pre>';print_r($client_id_decode); exit;
               return Oath_clients::where('client_id', $request->header("authorization"))->first();
               //return true;
            }
        });
    }
}
