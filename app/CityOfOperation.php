<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CityOfOperation extends Model 
{

    protected $fillable = [];
    protected $visible = ['region_name'];  

    protected $table = 'tbl_city_of_operation';
    protected $primaryKey = 'id';
    

    
}
