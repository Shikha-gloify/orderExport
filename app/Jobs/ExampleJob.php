<?php

namespace App\Jobs;
use App\Order;
use App\Customer;
use App\CorporateDetails;
use App\OrderController;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CommonHelper;
use Log;

class ExampleJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        echo "new synch started";
    }

    /**
     * Execute the job.
     *debug 
     * @return void
     */
    public function handle()
    {
        try {
            
            $data = setordercache();
            Log::info('orderquestarted ' . $data['id_order']);

            Cache::set('queue_worker', $data);
        }
        catch(Exception $e){

            Cache::set('job_error', $e->getMessage());
        }

    }
}
