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
    
    public function getTotalCollectedValue($order_id,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id){
        if($reschedule_luggage == 1){
            $corporateTypeArray = array(3,4,5);
            $ConveyanceCharge = $this->getOutstationPrice($order_id); 
            $ExtraKmsCharged =$this->getExtraKmPrice($order_id);
            $extra_km = $this->getExtraKmPrice($order_id); 
            $ExtraKmsGST =  $this->Calculate_service_tax($extra_km);
            $conveyance = $this->getOutstationPrice($order_id);
            $ConveyanceGST = in_array($corporate_type, $corporateTypeArray) ? $this->Calculate_coporate_service_tax($conveyance,$corporate_id) : $this->Calculate_service_tax($conveyance);
            $initialamount = (float)$luggage_price+(float)$ConveyanceCharge+(float)$ConveyanceGST+(float)$ExtraKmsCharged+(float)$ExtraKmsGST;
            return $initialamount;
        } else {
            $order_payment_details = \APP\OrderPaymentDetails::select('amount_paid')->where(['id_order'=>$order_id])->first();
            $initialamount_total = isset($order_payment_details) ? $order_payment_details['0']['amount_paid'] : 0;
            return $initialamount_total;
        }
    }
    public function getOutstationPrice($order_id){
        $outstation_charge = \App\OrderZoneDetails::select(['outstationCharge'])->where(['orderId'=>$order_id])->first();
        if(isset($outstation_charge)){
            $outstation_charges = $outstation_charge['0']['outstationCharge']; 
        }else{
            $outstation_charges =0;
        }
        return $outstation_charges  ;    
    }

    public function getExtraKmPrice($order_id){
        $state =\App\OrderZoneDetails::select(['stateId', 'extraKilometer'])->where(['orderId'=>$order_id])->first();
        if($state){
            $km_charge = \App\State::find()
                ->select(['extraKilometerPrice'])
                ->where(['idState'=>$state->stateId])
                ->one();

            $extr_km = ($state) ? $state->extraKilometer : 0;
            $km_charge = ($km_charge) ? $km_charge->extraKilometerPrice : 0;

            $extraKilometer = $extr_km * $km_charge;
        }else{
            $extraKilometer = '';   
        }
        

        return $extraKilometer;        
    }

    public function Calculate_service_tax($baseprice) {
        $service =  (float)$baseprice * (12/100);
        return $service;
    }
   
    function getluggagecount($id_order)
    {
        $order_item_details = \App\OrderItems::query()
        ->join('tbl_luggage_type as lt', 'lt.id_luggage_type', '=', 'tbl_order_items.fk_tbl_order_items_id_luggage_type')
        ->leftjoin('tbl_luggage_type as lto', 'lto.id_luggage_type', '=', 'tbl_order_items.fk_tbl_order_items_id_luggage_type_old')

        ->where('tbl_order_items.fk_tbl_order_items_id_order', '=', $id_order)
        ->get()->toArray();
        $lcount = 0;
        if(!empty($order_item_details) && ($order_item_details !== null)){
            $lcount = count(array_column(array_filter($order_item_details, function($el) {
               
                return $el['deleted_status']==0; }),'deleted_status'));
            return $lcount;
        }
        return $lcount;
    }
    public function getActualDeliveryDate($order_id){
        if(!empty($order_id)){
            $result = \App\OrderHistory::query()
            ->where('fk_tbl_order_history_id_order', '=', $order_id)
            ->where('to_tbl_order_status_id_order_status', '=', '18')
            ->orderBy('id_order_history','desc')->get()->toArray();
           
            if(!empty($result)){
                return  date('Y-m-d h:i:s A',strtotime($result['0']['date_created'])) ;
           
            }
            
        } else {
            return "-";
        }
        
    }

    public function getDeliveryName($id, $corporate_id){
        $dservice_type = ($id) ? $id : 0;
        $delivery_service = \App\DeliveryServiceType::where(['id_delivery_type'=>$dservice_type])->get();
        
        if($corporate_id){
            if($corporate_id == 19 || $corporate_id == 20 || $corporate_id == 30 || $corporate_id == 31 || $corporate_id == 214)
            {
                return $delivery_service['0']['delivery_category']; 
            }else
            {
                return (($dservice_type == 1) ? "Repairs" :
                  (($dservice_type == 2) ? "Reverse Pick Up" :
                   (($dservice_type == 3) ? "Express - Outstation" :
                    (($dservice_type == 4) ? "Express - Fragile" :
                        (($dservice_type == 5) ? "Outstation- Fragile" :
                        (($dservice_type == 6) ? "Normal - Fragile" :
                            (($dservice_type == 7) ? "Normal Delivery" :
                                (($dservice_type == 8) ? "Express" :
                    (($dservice_type == 9) ? "Outstation" :
                    (($dservice_type == 10) ? "Oversized/Fragile" : "")))))))))
                 );
            }    
        }else{
            return "-";
        }
        
    }
    public function getname($subscription_id){
        $sub_detail = \App\SuperSubscriber::where(['subscription_id'=>$subscription_id])->first();
        
        return $sub_detail['subscriber_name']; 
    }

    public function getporter($id_employee)
    {
        $porter_detail = \App\Employee::where(['id_employee'=>$id_employee])->first();
        
        return $porter_detail['name']; 
    }
    public function getporterxname($id_employee)
    {
        $porter_detail = \App\Employee::where(['id_employee'=>$id_employee])->first();
        
        return $porter_detail['name']; 
    }
    public function getCountSms($order_id){
        if(empty($order_id)){
            return 0;
        } else {
            $smsResult = \App\OrderSmsDetails::where(['order_sms_order_id' => $order_id])->get();
            $smsCount = isset($smsResult) ? count($smsResult) : 0;
            return $smsCount;
        }
    }
    
    
    /*public function setCacheData()
    {
        $data = Order::with('customer','corporate','cityname','sector','payment','spot','metadata','airport')->where(['deleted_status' => 0])->offset(0)->limit(100)->get();

        $value = Cache::set('test', $data);

        return $value;
    }*/


    public function Calculate_coporate_service_tax($baseprice, $fk_corporate_id) {
        $service_tax = \App\ThirdpartyCorporate::find()->where(['fk_corporate_id'=>$fk_corporate_id])->select('gst')->first();
        $service =  $baseprice * ($service_tax->gst/100);
       //  $service =  is_numeric($baseprice) * ($service_tax->gst/100);
        return $service;
    } 
    

    public function getCacheData()
    {
        $data = Cache::get('queue_worker');
        return $data;
    }

    //
}








