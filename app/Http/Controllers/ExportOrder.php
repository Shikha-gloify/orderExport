<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
//use Illuminate\Http\Response;
use App\Order;
use Illuminate\Support\Facades\Response;
use App\Jobs\Exportjob;
use Routes\QueueMonitorRoutes;
use Log;

class ExportOrder extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    public function getcsvdata(Request $postdata){
        $rturn_array =array();
        $today =date('Y-m-d');
        if($postdata['start_date'] > $today){
            return $rturn_array =array('status'=>'201','msg' => "start date should not be  greater than today");
        }
        if($postdata['end_date'] > $today){
            return $rturn_array =array('status'=>'201','msg'=> "end date should not be  greater than today");
            
        }
        $totalcount = 0;
        if($postdata['role_id'] == 1){

            $totalcount = checkcountforsupervisor($postdata->all());
            Log::info('fetching_count'. $totalcount);

        }else if($postdata['role_id'] == '10'){

            Log::info('callfunctionforkiosk');
            $totalcount = checkcountkiosk($postdata->all());
            Log::info('fetching_count'. $totalcount);

        }else{
            $totalcount = checkcount($postdata->all());
        }
        
        if($totalcount > 0){
            Log::info('hittingexportjob' .'csv');
            $result = dispatch(new Exportjob($postdata->all()));
            return $rturn_array =array('status'=>'200','msg' => 'Import has been started' );
        }else{
            return $rturn_array =array('status'=>'201','msg' => 'Data Not found' );
 
        }

      
    }
    
    
    public function downloadcsvfile($ext_id){
        //return response()->download($path);
        $filename =$ext_id.'.csv';
        
        $path = storage_path().'/'.'app'.'/'.$filename;
        if (file_exists($path)) {
            return Response::download($path);
        }

    }

    public  function testt(Request $data){
        $datas=array(
            'role_id'=>$data['role_id'],
            'id_employee'=>$data['id_employee'],
            'start_date'=>$data['start_date'],
            'end_date'=>$data['end_date']
        );
        echo '<pre>'; print_r($data['role_id']); //exit;
        $result = GetCsvmultipleroledetail($datas);
         print_r($result); 
        die;

        $role_id = $data['role_id'];
        $id_employee = $data['id_employee'];
        $toDate =$data['end_date'];
        $fromDate=$data['start_date'];
        $corporate_id = getCorporateIds($id_employee);
        $corporate_details = getCorporatesAll($corporate_id);
        $dataProvider = usercorporatekioskorderssearch($id_employee,$role_id, $corporate_details,$corporate_id,$fromDate,$toDate);

       // echo '<pre>'; print_r($corporate_details); exit;

      

    }
    public  function getkioskcsv(Request $postdata){
       $count =  checkcountkiosk($postdata);
        if($count > 0){
            Log::info('kioskrole' .$count);
        $result = dispatch(new Exportjob($postdata->all()));
        //$result = getcsvkiosk($postdata->all());
        echo $count; print_r($result); exit;

           // return $rturn_array =array('status'=>'200','msg' => 'Import has been started' );
        }else{
           // return $rturn_array =array('status'=>'201','msg' => 'Data Not found' );

        }
    
        
    }

    
    
}