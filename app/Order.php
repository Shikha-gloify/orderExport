<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model 
{

    protected $fillable = [
        'id_order'
    ];

    protected $table = 'tbl_order';
    protected $primaryKey = 'id_order';
    

    public function cityname()
    {
        return $this->belongsTo('\App\CityOfOperation','city_id','id');
    }
    public function customer()
    {
        //return $this->belongsTo(Customer::class, ['id_customer' => 'fk_tbl_order_id_customer']);

        return $this->belongsTo('\App\Customer', 'fk_tbl_order_id_customer', 'id_customer');
        
    }
    public function corporate()
    {
        //return $this->belongsTo(Customer::class, ['id_customer' => 'fk_tbl_order_id_customer']);

        return $this->belongsTo('\App\CorporateDetails', 'corporate_id', 'corporate_detail_id');
        
    }
    public function vehical()
    {
        return $this->belongsTo('\App\VehicleSlotAllocation', 'id_order', 'fk_tbl_vehicle_slot_allocation_id_order');
    }
   
    public function payment()
    {

        return $this->belongsTo('\App\OrderPaymentDetails', 'id_order', 'id_order');
        
    }
    public function spot()
    {

        return $this->belongsTo('\App\OrderSpotDetails', 'id_order', 'fk_tbl_order_spot_details_id_order');
        
    }

    public function metadata()
    {

        return $this->belongsTo('\App\orderMetaDetailsRelation', 'id_order', 'orderId');
        
    }
    public function airport()
    {

        return $this->belongsTo('\App\AirportName', 'fk_tbl_airport_of_operation_airport_name_id', 'airport_name_id');
        
    }
    public function slot()
    {
        return $this->belongsTo('\App\Slot', 'fk_tbl_order_id_slot','id_slots');
    }

    public function supersubscriber()
    {
        return $this->belongsTo('\App\SuperSubscriber', 'confirmation_number','subscription_id');
    }

    public function confirmation()
    {
        return $this->belongsTo('\App\Confirmation', 'confirmation_number','subscription_transaction_id');
    }
    public function porterx()
    {
        return $this->belongsTo('\App\PorterxAllocations', 'id_order', 'tbl_porterx_allocations_id_order');
    }
    public function employee()
    {
        return $this->belongsTo('\App\Models\Employee', 'tbl_vehicle_slot_allocation.fk_tbl_vehicle_slot_allocation_id_employee', 'id_employee');
    }
    public function employeeporter()
    {
        return $this->belongsTo('\App\Models\Employee', 'tbl_porterx_allocations.tbl_porterx_allocations_id_employee', 'id_employee');
    }
    
    
    public function related()
    {
        return $this->hasOne('\App\Order','related_order_id','id_order');
    }
    
    
}
