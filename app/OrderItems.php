<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model 
{

    protected $fillable = [];

    protected $visible = ['excess_weight','deleted_status'];  

    protected $table = 'tbl_order_items';
    protected $primaryKey = 'id_order_item';

   

    

}
