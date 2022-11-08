<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model 
{

    protected $fillable = [];

    protected $table = 'tbl_order_history';
    protected $primaryKey = 'id_order_history';

    

}
