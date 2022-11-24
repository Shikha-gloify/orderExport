<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class orderMetaDetailsRelation extends Model 
{

    protected $fillable = [];

    protected $visible = ['pickupPersonName','pickupPersonAddressLine1','pickupPersonAddressLine2','pickupArea','pickupPincode','dropPersonName','dropPersonAddressLine1','dropPersonAddressLine2','droparea','dropPincode'];  

    protected $table = 'tbl_order_meta_details';
    protected $primaryKey = 'idOrderMeta';

    

}
