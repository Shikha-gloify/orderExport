<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Order;
use App\Customer;
use App\CorporateDetails;
use App\Jobs\Orderlistjob;
use Routes\QueueMonitorRoutes;
use App\TableEport;
use Log;
use App\Helpers\CommonHelper;


use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
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

    public function dipatchSyncJob(){

        dispatch(new Orderlistjob);

        return "started";
    }
    public function setCacheData()
    {
        //$data = Order::all();
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','metadata','airport','slot','confirmation','porterx','related')->where(['deleted_status' => 0])->orderBy('id_order','desc')->get();
        //->where(['deleted_status' => 0])->orderBy('id_order','desc')->paginate(100);
   
        
        if(isset($data['0'])){
            foreach($data as $x =>$result){
                $upersubscribername ="-";$porter_name="-";$porterx_name="-"; $meet_time_gate=" "; $delivery_datetime="-"; $expected ="-";
                $date_created='0000-00-00'; $order_date="0000-00-00"; $actual_time="-";
                $id_order = $result['id_order'];
                $reschedule_luggage = $result['reschedule_luggage'];
                $luggage_price = $result['luggage_price'];
                $corporate_type = $result['corporate_type'];
                $corporate_id = $result['corporate_id'];
                $dservice_type = $result['dservice_type'];
                $subscription_id = $result['confirmation']['subscription_id'];
                $id_employee = $result['vehical']['fk_tbl_vehicle_slot_allocation_id_employee'];
                $porterx_employee = $result['porterx']['tbl_porterx_allocations_id_employee'];

                
                $amount_paid = $this->getTotalCollectedValue($id_order, $reschedule_luggage,$luggage_price,$corporate_type, $corporate_id);
                $lcount = $this->getluggagecount($id_order);
                $delivery_services_type = $this->getDeliveryName($dservice_type ,$corporate_id);
                if(!empty($subscription_id)){
                    $upersubscribername =$this->getname($subscription_id);
                }
                if(!empty($id_employee)){ $porter_name =$this->getporter($id_employee);}
                   
                if(!empty($porterx_employee)){ $porterx_name =$this->getporterxname($porterx_employee);}
                if($result['order_transfer'] == 2){ $meet_time_gate = date('h:i A', strtotime($result['meet_time_gate']));}
                if(!empty($result['delivery_datetime'])){$delivery_datetime = date("Y-m-d h:i:s A", strtotime($result['delivery_datetime']));}

                $sms_count = $this->getCountSms($id_order);
                $actual_time = $this->getActualDeliveryDate($id_order);
                if($result['delivery_datetime'] !== null){
                    $expected = date("Y-m-d h:i:s A", strtotime($result['delivery_datetime']));
                }
                if(isset($result['date_created'])){
                  $date_created = date('Y-m-d',strtotime($result['date_created'])); 
                }
                if(isset($result['order_date'])){
                    $order_date = date('Y-m-d',strtotime($result['order_date'])); 
                  }
                
                $data[$x]['amount_paids'] = $amount_paid;
                $data[$x]['luggage_count'] =  $lcount;
                $data[$x]['delivery_services_type'] =  $delivery_services_type;
                $data[$x]['super_sub_name'] =  $upersubscribername;
                $data[$x]['porter_name'] =  $porter_name;
                $data[$x]['porterx_employee'] =  $porterx_name;
                $data[$x]['sms_counts'] =  $sms_count;
                $data[$x]['meet_time_gate'] =  $meet_time_gate;
                $data[$x]['delivery_datetime'] =  $delivery_datetime;
                $data[$x]['actual_time'] =  $actual_time;
                $data[$x]['expected'] =  $expected;
                $data[$x]['date_created'] =  $date_created;
                $data[$x]['order_date'] =  $order_date;
                
            }
        }
        $value = Cache::set('test_data', $data);
        
        
        return $value;
        
        //return response()->json($value, 201);

    }
  
    public function lisTtest(){

        // $result = democheck();
        // var_dump($result);
        // die;

        $thing = TableEport::create(
            [
            'start_date' =>'2021-11-11',
            'end_date' => '2021-11-31',
            'status' =>  '0',
            'path' => "ggfdffdgfgff.csv",
            ]);

        //$data = Order::all();
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','metadata','airport','slot','confirmation','porterx','related')
        //->where(['deleted_status' => 0])->orderBy('id_order','desc')->get();
        ->where(['deleted_status' => 0])->orderBy('id_order','desc')->paginate(10);
        Log::info('chec_status' .'ddd');
        // Log::info('job_success ' . $path);
        
        return response()->json($data, 201);

        //return  $resutl;
    }

    public function getCacheData()
    {
        $data = Cache::get('queue_worker');
        return $data;
    }

    //
}








