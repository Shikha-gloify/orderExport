<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use App\TableEport;
use Log;
use Illuminate\Support\Facades\Cache;



class AppServiceProvider extends ServiceProvider
{
    public function boot() 
    {
        Queue::before(function (JobProcessing $event){
            Log::info('job JobProcessing');
            
        });

        Queue::after(function (JobProcessed $event){

            if (Cache::has('job_id')) {
                $id = Cache::get('job_id');
                $ext = TableEport::find($id);
                $ext->status = '1';
                $ext->update();
                Cache::forget('job_id');
                Log::info('export table update');
            }

            Log::info('job processed');
        });
           
        
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\Illuminate\Contracts\Routing\ResponseFactory::class, function() {
            return new \Laravel\Lumen\Http\ResponseFactory();
        });
    }
}
