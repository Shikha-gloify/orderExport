<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model 
{

    protected $fillable = [];

    protected $visible = ['id_slots','slot_name','slot_start_time','slot_end_time','delivery_description','delivery_time','slot_type','time_description'];  

    protected $table = 'tbl_slots';
    protected $primaryKey = 'id_slots';

   

    

}
