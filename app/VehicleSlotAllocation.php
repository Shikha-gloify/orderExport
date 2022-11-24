<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VehicleSlotAllocation extends Model 
{

    protected $fillable = [];

    protected $visible = ['fk_tbl_vehicle_slot_allocation_id_order','fk_tbl_vehicle_slot_allocation_id_employee'];  

    protected $table = 'tbl_vehicle_slot_allocation';
    protected $primaryKey = 'id_vehicle_slot_allocation';

    

}
