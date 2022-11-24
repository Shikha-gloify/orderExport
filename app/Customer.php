<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model 
{

    protected $fillable = [];

    protected $visible = ['name'];  

    protected $table = 'tbl_customer';
    protected $primaryKey = 'id_customer';

   

}
