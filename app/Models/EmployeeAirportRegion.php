<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAirportRegion extends Model 
{

    protected $fillable = [];

    protected $visible = ['employee_airport_region_id','fk_tbl_employee_id','fk_tbl_airport_of_operation_airport_name_id'];  

    protected $table = 'tbl_employee_airport_region';
    protected $primaryKey = 'employee_airport_region_id';

    

}
