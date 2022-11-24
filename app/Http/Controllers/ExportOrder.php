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
        Log::info('hitting exportjob' .'csv');
        dispatch(new Exportjob($postdata->all()));

        return $rturn_array =array('status'=>'200','msg' => "Import has been Started");
    }
    
    public function getcsvreporta(Request $postdata){
       
        $final_array =array();
        $fromDate=$postdata['start_date'].'00:00:00';
        $toDate=$postdata['end_date'].'23:59:59';
        /*$data = Order::where(['deleted_status' => 0])
        ->where('order_date', '>=', $fromDate)
        ->where('order_date', '<=', $toDate)
        ->orderBy('id_order','desc')->get()->toArray();*/
        $data = Order::with('customer','corporate','cityname','vehical','payment','spot','metadata','airport','slot','confirmation','porterx','related')
        ->where(['deleted_status' => 0])
        ->where('order_date', '>=', $fromDate)
        ->where('order_date', '<=', $toDate)
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
        $final_array =array('Coroporate Name','Transfer Type','Terminal Type','Customer Name','Order Number','City Name','Airport','Airport Service','Delivery Type','Order Transfer','Sector', 'Date Of Booking','Date Of Service','Date of Delivery','Delivery Date & Time(Actual)','Delivery Date & Time(Expected)','Flight number','PNR number','Number of bags','Payment Mode','Amount collected by Porter','Gate Meeting Time','Service Type','Delivery Service Type','Slot','Porter','Porterx','Assigned Person','Order Status','Related Order Status','Amount collected','Total Value','Payment Mode','Payment Status','Area','Pincode','Landmark','Address Line1','Address Line2','Address Line2','Pick Up Person Name','Pick Up Address Line 1','Pick Up Address Line 2','Pick Up Area','Pick Up Pincode','Drop Person Name','Drop Address Line 1','Drop Address Line 2','Drop Area','Drop Pincode','Order SMS Sent');


        // Path to the project's root folder    
        // Path to the 'storage/app' folder   
       
        $filename = base_path().'/storage/app/OrderExport'.time().'.csv'; 
        
        $path = $filename;
        $csv = fopen($path , 'w');
        fputcsv($csv, $final_array);
        foreach ($new_array as $x =>$result){
            fputcsv($csv, $result);
        }

        $close = fclose($csv);

        if($close) {

            // Trigger Event to check if file generated completely.
            // Trigger Email after fclose with link $filename.
        }

        
        


        return response()->json($path, 201);
        
        
    }
    public function downloadcsvfile($ext_id){
        //return response()->download($path);
        $filename =$ext_id.'.csv';
        
        $path = storage_path().'/'.'app'.'/'.$filename;
        if (file_exists($path)) {
            return Response::download($path);
        }

    }
    
}