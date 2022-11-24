<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderSpotDetails extends Model 
{

    protected $fillable = [];

    protected $visible = ['area','pincode','landmark','address_line_1','address_line_2'];  

    protected $table = 'tbl_order_spot_details';
    protected $primaryKey = 'fk_tbl_order_spot_details_id_order';

    

}
