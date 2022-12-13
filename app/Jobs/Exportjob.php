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
        Log::info('exportjobdata' ,$this->postdata);

    }
   
    /**
     * Execute the job.
     *debug 
     * @return void
     */
    public function handle()
    {
        
        try {
            
            Log::info('callfunction',$this->postdata);
            $thing = TableEport::create(
                [
                'start_date' =>$this->postdata['start_date'],
                'end_date' => $this->postdata['end_date'],
                'status' =>  '0',
                'path' =>  '',
                'role_id'=>$this->postdata['role_id'],
                'id_employee'=>$this->postdata['id_employee'],
                ]);
                Cache::set('job_id', $thing->idorderexport);

            if($this->postdata['role_id'] == '1'){

                Log::info('callfunctionforsupervisor');
                $result = getcsvreport($this->postdata);

            }else if($this->postdata['role_id'] == '10'){

                Log::info('callfunctionforkiosk');
                $result = getcsvkiosk($this->postdata);

            }else{
                
                Log::info('callfunctionforother');
                $result = GetCsvmultipleroledetail($this->postdata);
            } 
           // $result = getcsvreport($this->postdata);

            if(isset($result['path'])){
                if (Cache::has('job_id')) {
                    $id = Cache::get('job_id');
                    $ext = TableEport::find($id);
                    $ext->status = '0';
                    $ext->path = $result['path'];
                    $ext->update();
                }
                Log::info('checkreturn' .$result['path']);
            }
           return  $result;
        }
        catch(Exception $e){
            Log::error('job_error ' . $e->getMessage());
        }

    }
}
