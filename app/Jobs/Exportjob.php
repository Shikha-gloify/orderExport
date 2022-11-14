<?php

namespace App\Jobs;
use App\Order;
use App\Customer;
use App\TableEport;
use App\CorporateDetails;
use App\ExportOrder;
use App\Helpers\CommonHelper;
use Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;

class Exportjob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $postdata;

    public function __construct($postdata)
    {
        $this->postdata = $postdata;
        

    }

    /**
     * Execute the job.
     *debug 
     * @return void
     */
    public function handle()
    {
        
        try {
            $result = getcsvreport($this->postdata);
            $thing = TableEport::create(
            [
            'start_date' =>$this->postdata['start_date'],
            'end_date' => $this->postdata['end_date'],
            'status' =>  '0',
            'path' =>  $result['path'],
            ]);
            Cache::set('job_id', $thing->idorderexport);
            Log::info('checkreturn',$result);
          
        }
        catch(Exception $e){
            Log::error('job_error ' . $e->getMessage());
        }

    }
}
