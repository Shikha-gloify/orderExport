<?php
//namespace App\Helpers;

use App\Order;
use App\Customer;
use App\CorporateDetails;
use App\OrderSmsDetails;
use App\Employee;
use App\OrderZoneDetails;
use App\OrderPaymentDetails;
use App\OrderItems;
use App\OrderHistory;
use App\SuperSubscriber;
use App\ThirdpartyCorporate;
use Illuminate\Support\Facades\Cache;



function democheck(){
    $data = Order::where(['deleted_status' => 0])->orderBy('id_order','desc')->offset(0)->limit('1')->get();
    //$data="checkkkkkkkkkkk";
    return  $data;
}
function setordercache(){
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

            
            $amount_paid = getTotalCollectedValue($id_order, $reschedule_luggage,$luggage_price,$corporate_type, $corporate_id);
            $lcount = getluggagecount($id_order);
            $delivery_services_type = getDeliveryName($dservice_type ,$corporate_id);
            if(!empty($subscription_id)){
                $upersubscribername =getname($subscription_id);
            }
            if(!empty($id_employee)){ $porter_name =getporter($id_employee);}
               
            if(!empty($porterx_employee)){ $porterx_name =getporterxname($porterx_employee);}
            if($result['order_transfer'] == 2){ $meet_time_gate = date('h:i A', strtotime($result['meet_time_gate']));}
            if(!empty($result['delivery_datetime'])){$delivery_datetime = date("Y-m-d h:i:s A", strtotime($result['delivery_datetime']));}

            $sms_count = getCountSms($id_order);
            $actual_time = getActualDeliveryDate($id_order);
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
    //$value = Cache::set('test_data', $data);
    
  return $data;
}

function getTotalCollectedValue($order_id,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id){
    if($reschedule_luggage == 1){
        $corporateTypeArray = array(3,4,5);
        $ConveyanceCharge = getOutstationPrice($order_id); 
        $ExtraKmsCharged =getExtraKmPrice($order_id);
        $extra_km = getExtraKmPrice($order_id); 
        $ExtraKmsGST =  Calculate_service_tax($extra_km);
        $conveyance = getOutstationPrice($order_id);
        $ConveyanceGST = in_array($corporate_type, $corporateTypeArray) ? Calculate_coporate_service_tax($conveyance,$corporate_id) : Calculate_service_tax($conveyance);
        $initialamount = (float)$luggage_price+(float)$ConveyanceCharge+(float)$ConveyanceGST+(float)$ExtraKmsCharged+(float)$ExtraKmsGST;
        return $initialamount;
    } else {
        $order_payment_details = OrderPaymentDetails::select('amount_paid')->where(['id_order'=>$order_id])->first();
        $initialamount_total = isset($order_payment_details) ? $order_payment_details['0']['amount_paid'] : 0;
        return $initialamount_total;
    }
}
function getOutstationPrice($order_id){
    $outstation_charge =OrderZoneDetails::select(['outstationCharge'])->where(['orderId'=>$order_id])->first();
    if(isset($outstation_charge)){
        $outstation_charges = $outstation_charge['0']['outstationCharge']; 
    }else{
        $outstation_charges =0;
    }
    return $outstation_charges  ;    
}

 function getExtraKmPrice($order_id){
    $state =OrderZoneDetails::select(['stateId', 'extraKilometer'])->where(['orderId'=>$order_id])->first();
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

 function Calculate_service_tax($baseprice) {
    $service =  (float)$baseprice * (12/100);
    return $service;
}

function getluggagecount($id_order)
{
    $order_item_details = OrderItems::query()
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
 function getActualDeliveryDate($order_id){
    if(!empty($order_id)){
        $result = OrderHistory::query()
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

 function getDeliveryName($id, $corporate_id){
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
 function getname($subscription_id){
    $sub_detail = SuperSubscriber::where(['subscription_id'=>$subscription_id])->first();
    
    return $sub_detail['subscriber_name']; 
}

 function getporter($id_employee)
{
    $porter_detail = Employee::where(['id_employee'=>$id_employee])->first();
    
    return $porter_detail['name']; 
}
 function getporterxname($id_employee)
{
    $porter_detail = Employee::where(['id_employee'=>$id_employee])->first();
    
    return $porter_detail['name']; 
}
 function getCountSms($order_id){
    if(empty($order_id)){
        return 0;
    } else {
        $smsResult = \App\OrderSmsDetails::where(['order_sms_order_id' => $order_id])->get();
        $smsCount = isset($smsResult) ? count($smsResult) : 0;
        return $smsCount;
    }
}

function Calculate_coporate_service_tax($baseprice, $fk_corporate_id) {
    $service_tax = ThirdpartyCorporate::select('gst')->where(['fk_corporate_id'=>$fk_corporate_id])->first();
    $service =  $baseprice * ($service_tax->gst/100);
   //  $service =  is_numeric($baseprice) * ($service_tax->gst/100);
    return $service;
} 


function get_email_template($id = '')
{
    $email_template = MeEmailTemplate::whereIn('email_template_id', $id)->where('email_template_status', 1)->get();
    return $email_template;
}


