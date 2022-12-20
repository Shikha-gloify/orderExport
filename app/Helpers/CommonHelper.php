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
use App\DeliveryServiceType;
use App\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
Use App\Models\CorporateUser;
Use App\Models\CorporateEmployeeAirport;
Use App\Models\CorporateEmployeeRegion;
Use App\Models\EmployeeAirportRegion;


//use Log;




function democheck(){
    $data = Order::where(['deleted_status' => 0])->orderBy('id_order','desc')->offset(0)->limit('1')->get();
    //$data="checkkkkkkkkkkk";
    log::info('commonhelper');
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
    Log::info('datafetched',$data['0']['actual_time']);
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
        $km_charge = State::select(['extraKilometerPrice'])
            ->where(['idState'=>$state->stateId])->first();
            

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
    $delivery_service = DeliveryServiceType::where(['id_delivery_type'=>$dservice_type])->first();
    
    if($corporate_id){
        if($corporate_id == 19 || $corporate_id == 20 || $corporate_id == 30 || $corporate_id == 31 || $corporate_id == 214)
        {
            return $delivery_service['delivery_category']; 
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
        $smsResult = OrderSmsDetails::where(['order_sms_order_id' => $order_id])->get();
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

function getServiceType($id_order){
    $order_details = Order::where(['id_order' => $id_order])->first();
    if($order_details){
       if($order_details->order_transfer == 1){
         if($order_details->service_type == 1){
           return 'To City';
         }else{
           return 'From City';
         }
       }else{
         if($order_details->service_type == 1){
           return 'To Airport';
         }else{
           return 'From Airport';
         }
       }
    }

 }
function getAmountCollected($id){
    $id = ($id) ? $id : 0;
    $orders = Order::select(['express_extra_amount', 'amount_paid'])->where(['id_order'=>$id])->first();
   //print_r($region['region_name']);exit;
    if(!empty($orders))
    {
        $express_extra_amount = $orders['express_extra_amount'];
        $amount_collected = $orders['express_extra_amount'] + $orders['amount_paid'] + $orders['express_extra_amount'] * (12/100);
        return $amount_collected;  
    }else
    {
        return "-"; 
    }
}

// export part for csv
function getcsvreport($postdata){
    Log::info('commonhelper' ,$postdata);
    $final_array =array();
    $fromDate=$postdata['start_date'].'00:00:00';
    $toDate=$postdata['end_date'].'23:59:59';
    try {
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','metadata','airport','slot','confirmation','porterx','related')
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', $fromDate)
        ->where('date_created', '<=', $toDate)
        ->orderBy('id_order','desc')->get()->toArray();
   
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
                $data[$x]['customer_name'] =  $result['customer']['name'];
                $data[$x]['corporate_name'] =  $result['corporate']['name'];
                $data[$x]['city_name'] =  $result['cityname']['region_name'];
                $data[$x]['vehical'] =  $result['vehical'];
                $data[$x]['payment_type'] =  $result['payment']['payment_type'];
                $data[$x]['payment_status'] =  $result['payment']['payment_status'];
                $data[$x]['spot_area'] =  $result['spot']['area'];
                $data[$x]['spot_pincode'] =  $result['spot']['pincode'];
                $data[$x]['spot_landmark'] =  $result['spot']['landmark'];
                $data[$x]['spot_address_line_1'] =  $result['spot']['address_line_1'];
                $data[$x]['spot_address_line_2'] =  $result['spot']['address_line_2'];
                $data[$x]['pickupPersonName'] =  $result['metadata']['pickupPersonName'];
                $data[$x]['pickupPersonAddressLine1'] =  $result['metadata']['pickupPersonAddressLine1'];
                $data[$x]['pickupPersonAddressLine2'] =  $result['metadata']['pickupPersonAddressLine2'];
                $data[$x]['pickupArea'] =  $result['metadata']['pickupArea'];
                $data[$x]['pickupPincode'] =  $result['metadata']['pickupPincode'];
                $data[$x]['dropPersonName'] =  $result['metadata']['dropPersonName'];
                $data[$x]['dropPersonAddressLine1'] =  $result['metadata']['dropPersonAddressLine1'];
                $data[$x]['dropPersonAddressLine2'] =  $result['metadata']['dropPersonAddressLine2'];
                $data[$x]['droparea'] =  $result['metadata']['droparea'];
                $data[$x]['dropPincode'] =  $result['metadata']['dropPincode'];
                $data[$x]['airport_name'] =  $result['airport']['airport_name'];
                $data[$x]['id_slots'] =  $result['slot']['id_slots'];
                $data[$x]['slot_name'] =  $result['slot']['slot_name'];
                $data[$x]['slot_start_time'] =  $result['slot']['slot_start_time'];
                $data[$x]['slot_end_time'] =  $result['slot']['slot_end_time'];
                $data[$x]['time_description'] =  $result['slot']['time_description'];
                $data[$x]['delivery_description'] =  $result['slot']['delivery_description'];
                $data[$x]['delivery_time'] =  $result['slot']['delivery_time'];
                $data[$x]['subscription_id'] =  $result['confirmation']['subscription_id'];
                $data[$x]['confirmation_number'] =  $result['confirmation']['confirmation_number'];

            }
        }

        $new_array = array();
        foreach($data as $val){
            $terminal="-"; $string ="-"; $delivery_type = "-";$ord="-";
            if($val['corporate_type'] != 2){
                if($val['delivery_type'] ==1){
                    $deliver ='Local';
                }else{
                    $deliver='Outstation';
                }
            }

            if($val['terminal_type'] == 1){
                $terminal= 'International Travel';
            }else if($val['terminal_type'] == 2){ 
                    $terminal ='Domestic Travel';
            }
            if($val['service_type'] == 1){
                if($val['airport_service'] == 1){
                    $string = "Airport : DropOff Point";
                } else if($val['airport_service'] == 2){
                    $string = "Door Step Pickup";
                }
            } else if($val['service_type'] == 2){
                if($val['airport_service'] == 1){
                    $string = "Airport : Pickup Point";
                } else if($val['airport_service'] == 2){
                    $string = "Door Step Delivery";
                }
            }
            if($val['order_transfer'] != null && $val['order_transfer'] == 1){
                $ord ='City Transfer';
            }elseif($val['order_transfer'] != null && $val['order_transfer'] == 2){
                $ord ='Airport Transfer';
            } 
                    
            $new_array1 = array(
                'corporate_name'=>$val['corporate']['name'],
                'transfer_type'=>$val['order_type_str'],
                'terminal_type'=>$terminal,
                'customer_name'=>$val['customer_name'],
                'order_number'=>$val['order_number'],
                'city_name'=>$val['city_name'],
                'airport'=>$val['airport_name'],
                'airport_service'=>$string ,
                'delivery_type'=>$deliver,
                'order_transfer'=>$ord,
                'sector'=>$val['sector_name'],
                'date_of_booking'=>$val['date_created'],
                'date_of_service'=>$val['order_date'],
                'date_of_delivery'=>$val['delivery_datetime'],
                'delivery_date_actual_time'=>$val['delivery_datetime'],
                'delivery_date_time_expected'=>$val['expected'],
                'flight_number'=>$val['flight_number'],
                'pnr_number'=>$val['pnr_number'],
                'no_of_bags'=>$val['luggage_count'],
                'payment_mode'=>isset($val['payment_method'])? $val['payment_method'] : $val['payment_mode_excess'],
                'amount_collected_by_portermeet_time_gate'=>isset($val['excess_bag_amount']) ? $val['excess_bag_amount'] : '0',
                'gate_meeting_time'=>$val['meet_time_gate'],
                'service_type'=>getServiceType($val['id_order']),
                'delivery_service_type'=>$val['delivery_services_type'],
                'slot'=>$val['time_description'],
                'porter'=>$val['porter_name'],
                'porterx'=>$val['porterx_employee'],
                'assigned_person'=>isset($val['travell_passenger_name']) ? $val['travell_passenger_name'] : $val['customer_name'],
                'order_status'=>$val['order_status'],
                'related_order_status'=>$val['related']['order_status'],
                'amount_collected'=>getAmountCollected($val['id_order']),
                'total_value'=>$val['amount_paids'],
                'payment_type'=>$val['payment_type'],
                'area'=>$val['spot_area'],
                'pincode'=>$val['spot_pincode'],
                'landmark'=>$val['spot_landmark'],
                'address_line_1'=>$val['spot_address_line_1'],
                'address_line_2'=>$val['spot_address_line_2'],
                'pickup_person_name'=>$val['pickupPersonName'],
                'pickup_address_line1'=>$val['pickupPersonAddressLine1'],
                'pickup_address_line2'=>$val['pickupPersonAddressLine2'],
                'pickup_area'=>$val['pickupArea'],
                'pickup_pincode'=>$val['pickupPincode'],
                'drop_person_name'=>$val['dropPersonName'],
                'drop_address_line_1'=>$val['dropPersonAddressLine1'],
                'drop_address_line_2'=>$val['dropPersonAddressLine2'],
                'drop_area'=>$val['droparea'],
                'drop_pincode'=>$val['dropPincode'],
                'order_sms_sent'=>$val['sms_counts']
            );
            array_push($new_array ,$new_array1);

        }
        $final_array =array('Coroporate Name','Transfer Type','Travel Type','Customer Name','Order Number','City Name','Airport','Airport Service','Delivery Type','Order Transfer','Sector', 'Date Of Booking','Date Of Service','Date of Delivery','Delivery Date & Time(Actual)','Delivery Date & Time(Expected)','Flight number','PNR number','Number of bags','Payment Mode','Amount collected by Porter','Gate Meeting Time','Service Type','Delivery Service Type','Slot','Porter','Porterx','Assigned Person','Order Status','Related Order Status','Amount collected','Total Value','Payment Mode','Payment Status','Area','Pincode','Landmark','Address Line1','Address Line2','Address Line2','Pick Up Person Name','Pick Up Address Line 1','Pick Up Address Line 2','Pick Up Area','Pick Up Pincode','Drop Person Name','Drop Address Line 1','Drop Address Line 2','Drop Area','Drop Pincode','Order SMS Sent');


        // Path to the project's root folder    
        // Path to the 'storage/app' folder   
   
       
        $filename = 'OrderExport'.time().'.csv';
    
        $path = base_path().'/storage/app/'.$filename;
        $csv = fopen($path , 'w');
        fputcsv($csv, $final_array);
        foreach ($new_array as $x =>$result){
            fputcsv($csv, $result);
        }

        $close = fclose($csv);

        Log::info('job_success ' . $path);

    }catch(Exception $e){
        Log::error('jobexporterror ' . $e->getMessage());
    }
    
    $return_array =array(
        'path'=>$filename
    );

    return $return_array;
    
    
}

function getCorporateIds($employeeId = FALSE) {
    $corporate_id = array();
    if ($employeeId) {
        $corporate_details = CorporateUser::where(['fk_tbl_employee_id' => $employeeId])->get();
        foreach($corporate_details as $value){
            $corporate_id[] = $value['corporate_id'];
        }
        return ($corporate_details) ? $corporate_id : '';
    }else{
        return '';
    }
}

function getCorporatesAll($corporateId = FALSE) {
    $corporate_id = array();
    if ($corporateId) {
        $corporate_details = ThirdpartyCorporate::select('fk_corporate_id')->whereIn('thirdparty_corporate_id', $corporateId)->get();
        if($corporate_details){
            foreach($corporate_details as $value){
                $corporate_id[] = $value['fk_corporate_id'];
            }
        }
        return ($corporate_id) ? $corporate_id : '';
    }else{
        return '';
    }
}

function usercorporatekioskorderssearch($emp_id,$roleid, $user_corporate_id, $corporate_id = NULL,$fromDate,$toDate){
    $fromDate=$fromDate.' 00:00:00';
    $toDate=$toDate.' 23:59:59';
    if(($roleid == 15) || $roleid == 12 || $roleid == 13 || $roleid == 14){
        $airportArray = array();
        $kioskAirportId = CorporateEmployeeAirport::select('fk_tbl_airport_id')->where('fk_tbl_employee_id', $emp_id)->get();
        foreach($kioskAirportId as $value){
            array_push($airportArray,$value['fk_tbl_airport_id']);
        }
      
        $cityArray = array();
        $kioskCityId = CorporateEmployeeRegion::select('fk_tbl_region_id')->where('fk_tbl_employee_id',$emp_id)->get();
        foreach($kioskCityId as $value){
            array_push($cityArray,$value['fk_tbl_region_id']);
        }
   
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(function($query) use ($cityArray ,$airportArray,$roleid,$user_corporate_id) {
            $query->where(function($query) use ($cityArray ,$airportArray) {
                $query->WhereIn('city_id', $cityArray);
                $query->orWhereIn('fk_tbl_airport_of_operation_airport_name_id', $airportArray);
               
            });
            $query->WhereIn('corporate_id',$user_corporate_id);
            if ($roleid == 14) {
                return  $query->orWhere(['created_by' => $roleid]);
            } 
        })
        
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        
        ->orderBy('id_order','desc')->get()->toArray();
    }
    else if($roleid == 11){
        $airportArray = array();
        $kioskAirportId = EmployeeAirportRegion::select('fk_tbl_airport_of_operation_airport_name_id')->where('fk_tbl_employee_id',$emp_id)->get();
        foreach($kioskAirportId as $value){
            array_push($airportArray,$value['fk_tbl_airport_of_operation_airport_name_id']);
        }
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', $fromDate)
        ->where('date_created', '<=', $toDate)
        ->where(function($query) use ($airportArray ,$roleid) {
            if($airportArray){
                return  $query->WhereIn('fk_tbl_airport_of_operation_airport_name_id', $airportArray);
            }else{
                return  $query->Where('created_by',$roleid);
            }
        })
        ->orderBy('id_order','desc')->get()->toArray();
    } else{
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', $fromDate)
        ->where('date_created', '<=', $toDate)
        ->where('created_by', $roleid)
        ->orderBy('id_order','desc')->get()->toArray();
    }

    return $data;

}
function GetCsvmultipleroledetail($postdata){
    Log::info('multiplerole',$postdata);
    $return_array =array();
    $final_array =array();
    $role_id = $postdata['role_id'];
    $id_employee = $postdata['id_employee'];
    $toDate = $postdata['end_date'];
    $fromDate = $postdata['start_date'];
   
    $corporate_ids = getCorporateIds($id_employee);
    $corporate_details = getCorporatesAll($corporate_ids);
    $data = usercorporatekioskorderssearch($id_employee,$role_id, $corporate_details,$corporate_ids,$fromDate,$toDate);
    try{
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
                $data[$x]['customer_name'] =  $result['customer']['name'];
                $data[$x]['corporate_name'] =  $result['corporate']['name'];
                $data[$x]['city_name'] =  $result['cityname']['region_name'];
                $data[$x]['vehical'] =  $result['vehical'];
                $data[$x]['payment_type'] =  $result['payment']['payment_type'];
                $data[$x]['payment_status'] =  $result['payment']['payment_status'];
                $data[$x]['spot_area'] =  $result['spot']['area'];
                $data[$x]['spot_pincode'] =  $result['spot']['pincode'];
                $data[$x]['spot_landmark'] =  $result['spot']['landmark'];
                $data[$x]['spot_address_line_1'] =  $result['spot']['address_line_1'];
                $data[$x]['spot_address_line_2'] =  $result['spot']['address_line_2'];
                $data[$x]['pickupPersonName'] =  $result['metadata']['pickupPersonName'];
                $data[$x]['pickupPersonAddressLine1'] =  $result['metadata']['pickupPersonAddressLine1'];
                $data[$x]['pickupPersonAddressLine2'] =  $result['metadata']['pickupPersonAddressLine2'];
                $data[$x]['pickupArea'] =  $result['metadata']['pickupArea'];
                $data[$x]['pickupPincode'] =  $result['metadata']['pickupPincode'];
                $data[$x]['dropPersonName'] =  $result['metadata']['dropPersonName'];
                $data[$x]['dropPersonAddressLine1'] =  $result['metadata']['dropPersonAddressLine1'];
                $data[$x]['dropPersonAddressLine2'] =  $result['metadata']['dropPersonAddressLine2'];
                $data[$x]['droparea'] =  $result['metadata']['droparea'];
                $data[$x]['dropPincode'] =  $result['metadata']['dropPincode'];
                $data[$x]['airport_name'] =  $result['airport']['airport_name'];
                $data[$x]['id_slots'] =  $result['slot']['id_slots'];
                $data[$x]['slot_name'] =  $result['slot']['slot_name'];
                $data[$x]['slot_start_time'] =  $result['slot']['slot_start_time'];
                $data[$x]['slot_end_time'] =  $result['slot']['slot_end_time'];
                $data[$x]['time_description'] =  $result['slot']['time_description'];
                $data[$x]['delivery_description'] =  $result['slot']['delivery_description'];
                $data[$x]['delivery_time'] =  $result['slot']['delivery_time'];
                $data[$x]['subscription_id'] =  $result['confirmation']['subscription_id'];
                $data[$x]['confirmation_number'] =  $result['confirmation']['confirmation_number'];

            }
            $new_array = array();
            
            foreach($data as $val){
                $terminal="-"; $string ="-"; $delivery_type = "-";$ord="-";
                if($val['corporate_type'] != 2){
                    if($val['delivery_type'] ==1){
                        $deliver ='Local';
                    }else{
                        $deliver='Outstation';
                    }
                }

                if($val['terminal_type'] == 1){
                    $terminal= 'International Travel';
                }else if($val['terminal_type'] == 2){ 
                        $terminal ='Domestic Travel';
                }
                if($val['service_type'] == 1){
                    if($val['airport_service'] == 1){
                        $string = "Airport : DropOff Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Pickup";
                    }
                } else if($val['service_type'] == 2){
                    if($val['airport_service'] == 1){
                        $string = "Airport : Pickup Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Delivery";
                    }
                }
                if($val['order_transfer'] != null && $val['order_transfer'] == 1){
                    $ord ='City Transfer';
                }elseif($val['order_transfer'] != null && $val['order_transfer'] == 2){
                    $ord ='Airport Transfer';
                } 
                        
                $new_array1 = array(
                    'corporate_name'=>$val['corporate']['name'],
                    'transfer_type'=>$val['order_type_str'],
                    'terminal_type'=>$terminal,
                    'customer_name'=>$val['customer_name'],
                    'order_number'=>$val['order_number'],
                    'city_name'=>$val['city_name'],
                    'airport'=>$val['airport_name'],
                    'airport_service'=>$string ,
                    'delivery_type'=>$deliver,
                    'order_transfer'=>$ord,
                    'sector'=>$val['sector_name'],
                    'date_of_booking'=>$val['date_created'],
                    'date_of_service'=>$val['order_date'],
                    'date_of_delivery'=>$val['delivery_datetime'],
                    'delivery_date_actual_time'=>$val['delivery_datetime'],
                    'delivery_date_time_expected'=>$val['expected'],
                    'flight_number'=>$val['flight_number'],
                    'pnr_number'=>$val['pnr_number'],
                    'no_of_bags'=>$val['luggage_count'],
                    'payment_mode'=>isset($val['payment_method'])? $val['payment_method'] : $val['payment_mode_excess'],
                    'amount_collected_by_portermeet_time_gate'=>isset($val['excess_bag_amount']) ? $val['excess_bag_amount'] : '0',
                    'gate_meeting_time'=>$val['meet_time_gate'],
                    'service_type'=>getServiceType($val['id_order']),
                    'delivery_service_type'=>$val['delivery_services_type'],
                    'slot'=>$val['time_description'],
                    'porter'=>$val['porter_name'],
                    'porterx'=>$val['porterx_employee'],
                    'assigned_person'=>isset($val['travell_passenger_name']) ? $val['travell_passenger_name'] : $val['customer_name'],
                    'order_status'=>$val['order_status'],
                    'related_order_status'=>$val['related']['order_status'],
                    'amount_collected'=>getAmountCollected($val['id_order']),
                    'total_value'=>$val['amount_paids'],
                    'payment_type'=>$val['payment_type'],
                    'area'=>$val['spot_area'],
                    'pincode'=>$val['spot_pincode'],
                    'landmark'=>$val['spot_landmark'],
                    'address_line_1'=>$val['spot_address_line_1'],
                    'address_line_2'=>$val['spot_address_line_2'],
                    'pickup_person_name'=>$val['pickupPersonName'],
                    'pickup_address_line1'=>$val['pickupPersonAddressLine1'],
                    'pickup_address_line2'=>$val['pickupPersonAddressLine2'],
                    'pickup_area'=>$val['pickupArea'],
                    'pickup_pincode'=>$val['pickupPincode'],
                    'drop_person_name'=>$val['dropPersonName'],
                    'drop_address_line_1'=>$val['dropPersonAddressLine1'],
                    'drop_address_line_2'=>$val['dropPersonAddressLine2'],
                    'drop_area'=>$val['droparea'],
                    'drop_pincode'=>$val['dropPincode'],
                    'order_sms_sent'=>$val['sms_counts']
                );
                array_push($new_array ,$new_array1);

            }
            $final_array =array('Coroporate Name','Transfer Type','Travel Type','Customer Name','Order Number','City Name','Airport','Airport Service','Delivery Type','Order Transfer','Sector', 'Date Of Booking','Date Of Service','Date of Delivery','Delivery Date & Time(Actual)','Delivery Date & Time(Expected)','Flight number','PNR number','Number of bags','Payment Mode','Amount collected by Porter','Gate Meeting Time','Service Type','Delivery Service Type','Slot','Porter','Porterx','Assigned Person','Order Status','Related Order Status','Amount collected','Total Value','Payment Mode','Payment Status','Area','Pincode','Landmark','Address Line1','Address Line2','Address Line2','Pick Up Person Name','Pick Up Address Line 1','Pick Up Address Line 2','Pick Up Area','Pick Up Pincode','Drop Person Name','Drop Address Line 1','Drop Address Line 2','Drop Area','Drop Pincode','Order SMS Sent');
            // Path to the project's root folder    
            // Path to the 'storage/app' folder   
            $filename = 'OrderExport'.time().'.csv';
            $path = base_path().'/storage/app/'.$filename;
            $csv = fopen($path , 'w');
            fputcsv($csv, $final_array);
            foreach ($new_array as $x =>$result){
                fputcsv($csv, $result);
            }
            $close = fclose($csv);
            Log::info('job_success ' . $path);
            $return_array =array(
                'path'=>$filename
            );
            
        }else{
            Log::info('datanotfound');
            
        }

        
    }catch(Exception $e){
        Log::error('jobexporterror ' . $e->getMessage());
    }    

    if(isset($data['0'])){
        
        return $return_array;
    }else{
        
        return $return_array; 
    }
   
   
}

function checkcount($postdata){
    $role_id = $postdata['role_id'];
    $id_employee = $postdata['id_employee'];
    $toDate = $postdata['end_date'];
    $fromDate = $postdata['start_date'];
    $corporate_ids = getCorporateIds($id_employee);
    $corporate_details = getCorporatesAll($corporate_ids);
    $data = usercorporatekioskorderssearch($id_employee,$role_id, $corporate_details,$corporate_ids,$fromDate,$toDate);
    
    return count($data);
}
function checkcountforsupervisor($postdata){
    $fromDate=$postdata['start_date'].'00:00:00';
    $toDate=$postdata['end_date'].'23:59:59';
    $data = Order::with('customer','corporate','cityname','vehical','payment','spot','metadata','airport','slot','confirmation','porterx','related')
    ->where(['deleted_status' => 0])
    ->where('date_created', '>=', $fromDate)
    ->where('date_created', '<=', $toDate)
    ->orderBy('id_order','desc')->get()->toArray();
    return count($data);

}

function checkcountkiosk($postdata){
    $fromDate=$postdata['start_date'].' 00:00:00';
    $toDate=$postdata['end_date'].' 23:59:59';
    $airportArray = array();
    $airport = "";
    $roleid=$postdata['role_id'];
    $id_employee = $postdata['id_employee'];
    $emp_name=getporter($id_employee);
    $kioskName = trim($emp_name);
    $kioskAirportId = EmployeeAirportRegion::select('fk_tbl_airport_of_operation_airport_name_id')->where('fk_tbl_employee_id',$id_employee)->get();
    foreach($kioskAirportId as $value){
        array_push($airportArray,$value['fk_tbl_airport_of_operation_airport_name_id']);
        $airport .= $value['fk_tbl_airport_of_operation_airport_name_id'].',';
    }
   
    if($airportArray){
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(function($query) use ($airport ,$airportArray,$roleid,$kioskName) {
            $query->WhereIn('fk_tbl_airport_of_operation_airport_name_id', $airportArray);
            $query->orwhere(function($query) use ($roleid ,$airportArray,$kioskName) {
                $query->WhereIn('fk_tbl_airport_of_operation_airport_name_id',$airportArray);
                $query->Where('created_by' , $roleid);
                
            });
            $query->orwhere(function($query) use ($kioskName ,$roleid) {
                $query->Where(['created_by_name'=>"'$kioskName'"]);
                $query->Where(['created_by' =>$roleid]);
              
            })
            ->orWhere(['order_transfer'=>"1"]);
           
        })
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        ->orderBy('id_order','desc')->get()->toArray();

       // echo '<pre>'; print_r($data);
    } else {
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        ->where('t.created_by',$roleid)->get()->toArray();
    }
    return count($data);

}
function getcsvkiosk($postdata){

    Log::info('kioskrole',$postdata);
    $return_array =array();
    $final_array =array();
    $fromDate=$postdata['start_date'].' 00:00:00';
    $toDate=$postdata['end_date'].' 23:59:59';
    $airportArray = array();
    $airport = "";
    $roleid=$postdata['role_id'];
    $id_employee = $postdata['id_employee'];
    $emp_name=getporter($id_employee);
    $kioskName = trim($emp_name);
    $kioskAirportId = EmployeeAirportRegion::select('fk_tbl_airport_of_operation_airport_name_id')->where('fk_tbl_employee_id',$id_employee)->get();
    foreach($kioskAirportId as $value){
        array_push($airportArray,$value['fk_tbl_airport_of_operation_airport_name_id']);
        $airport .= $value['fk_tbl_airport_of_operation_airport_name_id'].',';
    }
   
    if($airportArray){
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(function($query) use ($airport ,$airportArray,$roleid,$kioskName) {
            $query->WhereIn('fk_tbl_airport_of_operation_airport_name_id', $airportArray);
            $query->orwhere(function($query) use ($roleid ,$airportArray,$kioskName) {
                $query->WhereIn('fk_tbl_airport_of_operation_airport_name_id',$airportArray);
                $query->Where('created_by' , $roleid);
                
            });
            $query->orwhere(function($query) use ($kioskName ,$roleid) {
                $query->Where(['created_by_name'=>"'$kioskName'"]);
                $query->Where(['created_by' =>$roleid]);
              
            })
            ->orWhere(['order_transfer'=>"1"]);
           
        })
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        ->orderBy('id_order','desc')->get()->toArray();

       // echo '<pre>'; print_r($data);
    } else {
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where(['deleted_status' => 0])
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        ->where('t.created_by',$roleid)->get()->toArray();
    }
    try{
        if(isset($data['0'])){
            Log::info('kioskroledatao');

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
                $data[$x]['customer_name'] =  $result['customer']['name'];
                $data[$x]['corporate_name'] =  $result['corporate']['name'];
                $data[$x]['city_name'] =  $result['cityname']['region_name'];
                $data[$x]['vehical'] =  $result['vehical'];
                $data[$x]['payment_type'] =  $result['payment']['payment_type'];
                $data[$x]['payment_status'] =  $result['payment']['payment_status'];
                $data[$x]['spot_area'] =  $result['spot']['area'];
                $data[$x]['spot_pincode'] =  $result['spot']['pincode'];
                $data[$x]['spot_landmark'] =  $result['spot']['landmark'];
                $data[$x]['spot_address_line_1'] =  $result['spot']['address_line_1'];
                $data[$x]['spot_address_line_2'] =  $result['spot']['address_line_2'];
                $data[$x]['pickupPersonName'] =  $result['metadata']['pickupPersonName'];
                $data[$x]['pickupPersonAddressLine1'] =  $result['metadata']['pickupPersonAddressLine1'];
                $data[$x]['pickupPersonAddressLine2'] =  $result['metadata']['pickupPersonAddressLine2'];
                $data[$x]['pickupArea'] =  $result['metadata']['pickupArea'];
                $data[$x]['pickupPincode'] =  $result['metadata']['pickupPincode'];
                $data[$x]['dropPersonName'] =  $result['metadata']['dropPersonName'];
                $data[$x]['dropPersonAddressLine1'] =  $result['metadata']['dropPersonAddressLine1'];
                $data[$x]['dropPersonAddressLine2'] =  $result['metadata']['dropPersonAddressLine2'];
                $data[$x]['droparea'] =  $result['metadata']['droparea'];
                $data[$x]['dropPincode'] =  $result['metadata']['dropPincode'];
                $data[$x]['airport_name'] =  $result['airport']['airport_name'];
                $data[$x]['id_slots'] =  $result['slot']['id_slots'];
                $data[$x]['slot_name'] =  $result['slot']['slot_name'];
                $data[$x]['slot_start_time'] =  $result['slot']['slot_start_time'];
                $data[$x]['slot_end_time'] =  $result['slot']['slot_end_time'];
                $data[$x]['time_description'] =  $result['slot']['time_description'];
                $data[$x]['delivery_description'] =  $result['slot']['delivery_description'];
                $data[$x]['delivery_time'] =  $result['slot']['delivery_time'];
                $data[$x]['subscription_id'] =  $result['confirmation']['subscription_id'];
                $data[$x]['confirmation_number'] =  $result['confirmation']['confirmation_number'];

            }
            $new_array = array();
            
            foreach($data as $val){
                $terminal="-"; $string ="-"; $delivery_type = "-";$ord="-";
                if($val['corporate_type'] != 2){
                    if($val['delivery_type'] ==1){
                        $deliver ='Local';
                    }else{
                        $deliver='Outstation';
                    }
                }

                if($val['terminal_type'] == 1){
                    $terminal= 'International Travel';
                }else if($val['terminal_type'] == 2){ 
                        $terminal ='Domestic Travel';
                }
                if($val['service_type'] == 1){
                    if($val['airport_service'] == 1){
                        $string = "Airport : DropOff Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Pickup";
                    }
                } else if($val['service_type'] == 2){
                    if($val['airport_service'] == 1){
                        $string = "Airport : Pickup Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Delivery";
                    }
                }
                if($val['order_transfer'] != null && $val['order_transfer'] == 1){
                    $ord ='City Transfer';
                }elseif($val['order_transfer'] != null && $val['order_transfer'] == 2){
                    $ord ='Airport Transfer';
                } 
                        
                $new_array1 = array(
                    'corporate_name'=>$val['corporate']['name'],
                    'transfer_type'=>$val['order_type_str'],
                    'terminal_type'=>$terminal,
                    'customer_name'=>$val['customer_name'],
                    'order_number'=>$val['order_number'],
                    'city_name'=>$val['city_name'],
                    'airport'=>$val['airport_name'],
                    'airport_service'=>$string ,
                    'delivery_type'=>$deliver,
                    'order_transfer'=>$ord,
                    'sector'=>$val['sector_name'],
                    'date_of_booking'=>$val['date_created'],
                    'date_of_service'=>$val['order_date'],
                    'date_of_delivery'=>$val['delivery_datetime'],
                    'delivery_date_actual_time'=>$val['delivery_datetime'],
                    'delivery_date_time_expected'=>$val['expected'],
                    'flight_number'=>$val['flight_number'],
                    'pnr_number'=>$val['pnr_number'],
                    'no_of_bags'=>$val['luggage_count'],
                    'payment_mode'=>isset($val['payment_method'])? $val['payment_method'] : $val['payment_mode_excess'],
                    'amount_collected_by_portermeet_time_gate'=>isset($val['excess_bag_amount']) ? $val['excess_bag_amount'] : '0',
                    'gate_meeting_time'=>$val['meet_time_gate'],
                    'service_type'=>getServiceType($val['id_order']),
                    'delivery_service_type'=>$val['delivery_services_type'],
                    'slot'=>$val['time_description'],
                    'porter'=>$val['porter_name'],
                    'porterx'=>$val['porterx_employee'],
                    'assigned_person'=>isset($val['travell_passenger_name']) ? $val['travell_passenger_name'] : $val['customer_name'],
                    'order_status'=>$val['order_status'],
                    'related_order_status'=>$val['related']['order_status'],
                    'amount_collected'=>getAmountCollected($val['id_order']),
                    'total_value'=>$val['amount_paids'],
                    'payment_type'=>$val['payment_type'],
                    'area'=>$val['spot_area'],
                    'pincode'=>$val['spot_pincode'],
                    'landmark'=>$val['spot_landmark'],
                    'address_line_1'=>$val['spot_address_line_1'],
                    'address_line_2'=>$val['spot_address_line_2'],
                    'pickup_person_name'=>$val['pickupPersonName'],
                    'pickup_address_line1'=>$val['pickupPersonAddressLine1'],
                    'pickup_address_line2'=>$val['pickupPersonAddressLine2'],
                    'pickup_area'=>$val['pickupArea'],
                    'pickup_pincode'=>$val['pickupPincode'],
                    'drop_person_name'=>$val['dropPersonName'],
                    'drop_address_line_1'=>$val['dropPersonAddressLine1'],
                    'drop_address_line_2'=>$val['dropPersonAddressLine2'],
                    'drop_area'=>$val['droparea'],
                    'drop_pincode'=>$val['dropPincode'],
                    'order_sms_sent'=>$val['sms_counts']
                );
                array_push($new_array ,$new_array1);

            }
            $final_array =array('Coroporate Name','Transfer Type','Travel Type','Customer Name','Order Number','City Name','Airport','Airport Service','Delivery Type','Order Transfer','Sector', 'Date Of Booking','Date Of Service','Date of Delivery','Delivery Date & Time(Actual)','Delivery Date & Time(Expected)','Flight number','PNR number','Number of bags','Payment Mode','Amount collected by Porter','Gate Meeting Time','Service Type','Delivery Service Type','Slot','Porter','Porterx','Assigned Person','Order Status','Related Order Status','Amount collected','Total Value','Payment Mode','Payment Status','Area','Pincode','Landmark','Address Line1','Address Line2','Address Line2','Pick Up Person Name','Pick Up Address Line 1','Pick Up Address Line 2','Pick Up Area','Pick Up Pincode','Drop Person Name','Drop Address Line 1','Drop Address Line 2','Drop Area','Drop Pincode','Order SMS Sent');
            // Path to the project's root folder    
            // Path to the 'storage/app' folder   
            $filename = 'OrderExport'.time().'.csv';
            $path = base_path().'/storage/app/'.$filename;
            $csv = fopen($path , 'w');
            fputcsv($csv, $final_array);
            foreach ($new_array as $x =>$result){
                fputcsv($csv, $result);
            }
            $close = fclose($csv);
            Log::info('job_success ' . $path);
            $return_array =array(
                'path'=>$filename
            );
            
        }else{
            Log::info('datanotfound');
            
        }

        
    }catch(Exception $e){
        Log::error('jobexporterror ' . $e->getMessage());
    }    

    if(isset($data['0'])){
        
        return $return_array;
    }else{
        
        return $return_array; 
    }


}
function checkcountcorporate($postdata){

    $copId = $postdata['id_employee'];
    $result = CorporateDetails::select('corporate_detail_id')->where(['employee_id'=>$copId])->first();
    $cId= $result['corporate_detail_id'];
    $data = usercorporateorderssearch($cId,$postdata);
    return count($data);
}

function usercorporateorderssearch($id,$postdata){
    $fromDate=$postdata['start_date'].' 00:00:00';
    $toDate=$postdata['end_date'].' 23:59:59';

    $data = Order::with('customer','vehical','slot','confirmation','porterx','related','airport','payment')
    ->leftjoin('tbl_vehicle_slot_allocation', 'tbl_order.id_order', '=', 'tbl_vehicle_slot_allocation.fk_tbl_vehicle_slot_allocation_id_order')
    ->leftjoin('tbl_employee', function ($join) {
        $join->on('tbl_vehicle_slot_allocation.fk_tbl_vehicle_slot_allocation_id_employee', '=', 'tbl_employee.id_employee');
        
    })
    ->leftjoin('tbl_porterx_allocations', 'tbl_order.id_order', '=', 'tbl_porterx_allocations.tbl_porterx_allocations_id_order')
    ->leftjoin('tbl_employee as e1', function ($join) {
        $join->on('tbl_porterx_allocations.tbl_porterx_allocations_id_employee', '=', 'e1.id_employee');

    })
    //->leftjoin('tbl_employee' , 'tbl_porterx_allocations.tbl_porterx_allocations_id_employee', '=', 'e2`.`id_employee')
    ->where(['deleted_status' => 0])
    ->where('tbl_order.date_created', '>=', $fromDate)
    ->where('tbl_order.date_created', '<=', $toDate)
    ->Where('corporate_id',$id)
    ->orderBy('id_order','desc')->get()->toArray();

    return  $data;
}
function getcsvreportcorporate($postdata){
    $return_array =array();
    $final_array =array();
    $fromDate=$postdata['start_date'].' 00:00:00';
    $toDate=$postdata['end_date'].' 23:59:59';

    $copId = $postdata['id_employee'];
    $result = CorporateDetails::select('corporate_detail_id')->where(['employee_id'=>$copId])->first();
    $cId= $result['corporate_detail_id'];
    $data = usercorporateorderssearch($cId,$postdata);
    try{
        if(isset($data['0'])){
            Log::info('corporateorder0');
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
                $data[$x]['meet_time_gate'] =  $meet_time_gate;
                $data[$x]['delivery_datetime'] =  $delivery_datetime;
                $data[$x]['actual_time'] =  $actual_time;
                $data[$x]['expected'] =  $expected;
                $data[$x]['date_created'] =  $date_created;
                $data[$x]['order_date'] =  $order_date;
                $data[$x]['customer_name'] =  $result['customer']['name'];
                $data[$x]['vehical'] =  $result['vehical'];
                $data[$x]['payment_type'] =  $result['payment']['payment_type'];
                $data[$x]['payment_status'] =  $result['payment']['payment_status'];
                $data[$x]['airport_name'] =  $result['airport']['airport_name'];
                $data[$x]['time_description'] =  $result['slot']['time_description'];
                $data[$x]['delivery_description'] =  $result['slot']['delivery_description'];
                $data[$x]['delivery_time'] =  $result['slot']['delivery_time'];
                $data[$x]['subscription_id'] =  $result['confirmation']['subscription_id'];
                $data[$x]['confirmation_number'] =  $result['confirmation']['confirmation_number'];

            }
            $new_array = array();
            foreach($data as $val){
                $terminal="-"; $string ="-"; $delivery_type = "-";$ord="-";
                if($val['corporate_type'] != 2){
                    if($val['delivery_type'] ==1){
                        $deliver ='Local';
                    }else{
                        $deliver='Outstation';
                    }
                }
                if($val['dservice_type'] == 1){
                    $deliver ='Normal';
                    
                }else if($val['dservice_type'] == 2){

                    $deliver='Express';
                    
                }

                if($val['terminal_type'] == 1){
                    $terminal= 'International Travel';
                }else if($val['terminal_type'] == 2){ 
                        $terminal ='Domestic Travel';
                }
                if($val['service_type'] == 1){
                    if($val['airport_service'] == 1){
                        $string = "Airport : DropOff Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Pickup";
                    }
                } else if($val['service_type'] == 2){
                    if($val['airport_service'] == 1){
                        $string = "Airport : Pickup Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Delivery";
                    }
                }
                if($val['order_transfer'] != null && $val['order_transfer'] == 1){
                    $ord ='City Transfer';
                }elseif($val['order_transfer'] != null && $val['order_transfer'] == 2){
                    $ord ='Airport Transfer';
                } 
                        
                $new_array1 = array(
                    'refrence_number'=>$val['flight_number'],
                    'destination'=>$terminal,
                    'customer_name'=>$val['customer_name'],
                    'date_of_booking'=>$val['date_created'],
                    'date_of_service'=>$val['order_date'],
                    'gate_meeting_time'=>$val['meet_time_gate'],
                    'date_of_delivery'=>$val['delivery_datetime'],
                    'delivery_date_actual_time'=>$val['delivery_datetime'],
                    'delivery_date_time_expected'=>$val['expected'],
                    'airport'=>$val['airport_name'],
                    'no_of_bags'=>$val['luggage_count'],
                    'passive_tags'=>'',
                    'payment_mode'=>isset($val['payment_method'])? $val['payment_method'] : $val['payment_mode_excess'],
                    'amount_collected_by_portermeet_time_gate'=>isset($val['excess_bag_amount']) ? $val['excess_bag_amount'] : '0',
                    'amount_collected'=>getAmountCollected($val['id_order']),
                    'total_value'=>$val['amount_paids'],
                    'service_type'=>getServiceType($val['id_order']),
                    'order_status'=>$val['order_status'],
                    'related_order_status'=>$val['related']['order_status'],
                );
                array_push($new_array ,$new_array1);

            }
            $final_array =array('Reference Number','Customer Name','Destination','Date of Booking','Date of Service','Gate Meet Time','Date of Delivery','Delivery Date & Time(Actual)','Delivery Date & Time(Expected)','Airport','Number of bags','Passive Tags','Payment Mode','Amount collected by Porter','Amount collected','Total Value','Delivery Service Type','Order Status','Related Order Status');
            // Path to the project's root folder    
            // Path to the 'storage/app' folder   
            $filename = 'OrderExport'.time().'.csv';
            $path = base_path().'/storage/app/'.$filename;
            $csv = fopen($path , 'w');
            fputcsv($csv, $final_array);
            foreach ($new_array as $x =>$result){
                fputcsv($csv, $result);
            }
            $close = fclose($csv);
            Log::info('job_success ' . $path);
            $return_array =array(
                'path'=>$filename
            );
        }else{
            Log::info('datanotfound');
            
        }
    }catch(Exception $e){
        Log::error('jobexporterror ' . $e->getMessage());
    } 

}

function getuspersubscription_detail($id_employee){
    $details = DB::table('tbl_super_subscription as supersub')->select('supersub.*')
    ->leftjoin('tbl_employee as emp', function ($join) {
        $join->on('supersub.primary_email', '=', 'emp.email')->orOn('supersub.secondary_email', '=', 'emp.email' );
    })
    ->where('emp.id_employee',$id_employee)
    ->where('emp.fk_tbl_employee_id_employee_role',17)->get()->toArray();
    return $details;
}
function getsuperdetail($subscription_id,$postdata){
    $role_id=$postdata['role_id'];
    $fromDate=$postdata['start_date'].' 00:00:00';
    $toDate=$postdata['end_date'].' 23:59:59';
    $id_employee = $postdata['id_employee'];
    $confNumberArray =array();
    $confNumber = "";
    $confNumberId = DB::table('tbl_subscription_transaction_details')->select('subscription_transaction_id')->where('subscription_id',$subscription_id)->get();
    foreach($confNumberId as $value){
        array_push($confNumberArray,$value->subscription_transaction_id);
        $confNumber .= $value->subscription_transaction_id.',';
    }
    if($confNumberArray){
        
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
       
        ->WhereIn('confirmation_number', $confNumberArray)
        ->where('deleted_status', 0)
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        ->orderBy('id_order','desc')->get()->toArray();
    } else {
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','slot','metadata','airport','confirmation','porterx','related')
        ->where('deleted_status', 0)
        ->where('date_created', '>=', "$fromDate")
        ->where('date_created', '<=', "$toDate")
        ->where('created_by', '=', "$role_id")
        ->orderBy('id_order','desc')->get()->toArray();
    }
    return $data;
}
function getsubscriptionordercount($postdata){
    $role_id=$postdata['role_id'];
    $fromDate=$postdata['start_date'].' 00:00:00';
    $toDate=$postdata['end_date'].' 23:59:59';
    $id_employee = $postdata['id_employee'];
    $confNumberArray =array();
    $confNumber = "";
    $details = getuspersubscription_detail($id_employee);
    $result =  getsuperdetail($details[0]->subscription_id,$postdata);
    
    return count($result);
    
}
function getcsvreportsuper($postdata){
    $id_employee = $postdata['id_employee'];
    $details = getuspersubscription_detail($id_employee);
    $data =  getsuperdetail($details[0]->subscription_id,$postdata);
    try{
        if(isset($data['0'])){
            Log::info('supersubscriber');

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
                $data[$x]['customer_name'] =  $result['customer']['name'];
                $data[$x]['corporate_name'] =  $result['corporate']['name'];
                $data[$x]['city_name'] =  $result['cityname']['region_name'];
                $data[$x]['vehical'] =  $result['vehical'];
                $data[$x]['payment_type'] =  $result['payment']['payment_type'];
                $data[$x]['payment_status'] =  $result['payment']['payment_status'];
                $data[$x]['spot_area'] =  $result['spot']['area'];
                $data[$x]['spot_pincode'] =  $result['spot']['pincode'];
                $data[$x]['spot_landmark'] =  $result['spot']['landmark'];
                $data[$x]['spot_address_line_1'] =  $result['spot']['address_line_1'];
                $data[$x]['spot_address_line_2'] =  $result['spot']['address_line_2'];
                $data[$x]['pickupPersonName'] =  $result['metadata']['pickupPersonName'];
                $data[$x]['pickupPersonAddressLine1'] =  $result['metadata']['pickupPersonAddressLine1'];
                $data[$x]['pickupPersonAddressLine2'] =  $result['metadata']['pickupPersonAddressLine2'];
                $data[$x]['pickupArea'] =  $result['metadata']['pickupArea'];
                $data[$x]['pickupPincode'] =  $result['metadata']['pickupPincode'];
                $data[$x]['dropPersonName'] =  $result['metadata']['dropPersonName'];
                $data[$x]['dropPersonAddressLine1'] =  $result['metadata']['dropPersonAddressLine1'];
                $data[$x]['dropPersonAddressLine2'] =  $result['metadata']['dropPersonAddressLine2'];
                $data[$x]['droparea'] =  $result['metadata']['droparea'];
                $data[$x]['dropPincode'] =  $result['metadata']['dropPincode'];
                $data[$x]['airport_name'] =  $result['airport']['airport_name'];
                $data[$x]['id_slots'] =  $result['slot']['id_slots'];
                $data[$x]['slot_name'] =  $result['slot']['slot_name'];
                $data[$x]['slot_start_time'] =  $result['slot']['slot_start_time'];
                $data[$x]['slot_end_time'] =  $result['slot']['slot_end_time'];
                $data[$x]['time_description'] =  $result['slot']['time_description'];
                $data[$x]['delivery_description'] =  $result['slot']['delivery_description'];
                $data[$x]['delivery_time'] =  $result['slot']['delivery_time'];
                $data[$x]['subscription_id'] =  $result['confirmation']['subscription_id'];
                $data[$x]['confirmation_number'] =  $result['confirmation']['confirmation_number'];

            }
            $new_array = array();
            
            foreach($data as $val){
                $terminal="-"; $string ="-"; $delivery_type = "-";$ord="-";
                if($val['corporate_type'] != 2){
                    if($val['delivery_type'] ==1){
                        $deliver ='Local';
                    }else{
                        $deliver='Outstation';
                    }
                }

                if($val['terminal_type'] == 1){
                    $terminal= 'International Travel';
                }else if($val['terminal_type'] == 2){ 
                        $terminal ='Domestic Travel';
                }
                if($val['service_type'] == 1){
                    if($val['airport_service'] == 1){
                        $string = "Airport : DropOff Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Pickup";
                    }
                } else if($val['service_type'] == 2){
                    if($val['airport_service'] == 1){
                        $string = "Airport : Pickup Point";
                    } else if($val['airport_service'] == 2){
                        $string = "Door Step Delivery";
                    }
                }
                if($val['order_transfer'] != null && $val['order_transfer'] == 1){
                    $ord ='City Transfer';
                }elseif($val['order_transfer'] != null && $val['order_transfer'] == 2){
                    $ord ='Airport Transfer';
                } 
                        
                $new_array1 = array(
                    'corporate_name'=>$val['corporate']['name'],
                    'transfer_type'=>$val['order_type_str'],
                    'terminal_type'=>$terminal,
                    'customer_name'=>$val['customer_name'],
                    'order_number'=>$val['order_number'],
                    'city_name'=>$val['city_name'],
                    'airport'=>$val['airport_name'],
                    'airport_service'=>$string ,
                    'delivery_type'=>$deliver,
                    'order_transfer'=>$ord,
                    'sector'=>$val['sector_name'],
                    'date_of_booking'=>$val['date_created'],
                    'date_of_service'=>$val['order_date'],
                    'date_of_delivery'=>$val['delivery_datetime'],
                    'delivery_date_actual_time'=>$val['delivery_datetime'],
                    'delivery_date_time_expected'=>$val['expected'],
                    'flight_number'=>$val['flight_number'],
                    'pnr_number'=>$val['pnr_number'],
                    'no_of_bags'=>$val['luggage_count'],
                    'payment_mode'=>isset($val['payment_method'])? $val['payment_method'] : $val['payment_mode_excess'],
                    'amount_collected_by_portermeet_time_gate'=>isset($val['excess_bag_amount']) ? $val['excess_bag_amount'] : '0',
                    'gate_meeting_time'=>$val['meet_time_gate'],
                    'service_type'=>getServiceType($val['id_order']),
                    'delivery_service_type'=>$val['delivery_services_type'],
                    'slot'=>$val['time_description'],
                    'porter'=>$val['porter_name'],
                    'porterx'=>$val['porterx_employee'],
                    'assigned_person'=>isset($val['travell_passenger_name']) ? $val['travell_passenger_name'] : $val['customer_name'],
                    'order_status'=>$val['order_status'],
                    'related_order_status'=>$val['related']['order_status'],
                    'amount_collected'=>getAmountCollected($val['id_order']),
                    'total_value'=>$val['amount_paids'],
                    'payment_type'=>$val['payment_type'],
                    'area'=>$val['spot_area'],
                    'pincode'=>$val['spot_pincode'],
                    'landmark'=>$val['spot_landmark'],
                    'address_line_1'=>$val['spot_address_line_1'],
                    'address_line_2'=>$val['spot_address_line_2'],
                    'pickup_person_name'=>$val['pickupPersonName'],
                    'pickup_address_line1'=>$val['pickupPersonAddressLine1'],
                    'pickup_address_line2'=>$val['pickupPersonAddressLine2'],
                    'pickup_area'=>$val['pickupArea'],
                    'pickup_pincode'=>$val['pickupPincode'],
                    'drop_person_name'=>$val['dropPersonName'],
                    'drop_address_line_1'=>$val['dropPersonAddressLine1'],
                    'drop_address_line_2'=>$val['dropPersonAddressLine2'],
                    'drop_area'=>$val['droparea'],
                    'drop_pincode'=>$val['dropPincode'],
                    'order_sms_sent'=>$val['sms_counts']
                );
                array_push($new_array ,$new_array1);

            }
            $final_array =array('Coroporate Name','Transfer Type','Travel Type','Customer Name','Order Number','City Name','Airport','Airport Service','Delivery Type','Order Transfer','Sector', 'Date Of Booking','Date Of Service','Date of Delivery','Delivery Date & Time(Actual)','Delivery Date & Time(Expected)','Flight number','PNR number','Number of bags','Payment Mode','Amount collected by Porter','Gate Meeting Time','Service Type','Delivery Service Type','Slot','Porter','Porterx','Assigned Person','Order Status','Related Order Status','Amount collected','Total Value','Payment Mode','Payment Status','Area','Pincode','Landmark','Address Line1','Address Line2','Address Line2','Pick Up Person Name','Pick Up Address Line 1','Pick Up Address Line 2','Pick Up Area','Pick Up Pincode','Drop Person Name','Drop Address Line 1','Drop Address Line 2','Drop Area','Drop Pincode','Order SMS Sent');
            // Path to the project's root folder    
            // Path to the 'storage/app' folder   
            $filename = 'OrderExport'.time().'.csv';
            $path = base_path().'/storage/app/'.$filename;
            $csv = fopen($path , 'w');
            fputcsv($csv, $final_array);
            foreach ($new_array as $x =>$result){
                fputcsv($csv, $result);
            }
            $close = fclose($csv);
            Log::info('job_success ' . $path);
            $return_array =array(
                'path'=>$filename
            );
            
        }else{
            Log::info('datanotfound');
            
        }

        
    }catch(Exception $e){
        Log::error('jobexporterror ' . $e->getMessage());
    }    

    if(isset($data['0'])){
        
        return $return_array;
    }else{
        
        return $return_array; 
    }

}


