<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AirportName extends Model 
{

    protected $fillable = [];

    protected $visible = ['airport_name'];  

    protected $table = 'tbl_airport_of_operation';
    protected $primaryKey = 'airport_name_id';

    

}
