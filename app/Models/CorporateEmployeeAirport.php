<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateEmployeeAirport extends Model 
{

    protected $fillable = [];

    protected $visible = ['id_employee_airport','fk_tbl_employee_id','fk_tbl_airport_id'];  

    protected $table = 'tbl_corporate_employee_airport';
    protected $primaryKey = 'id_employee_airport';

    

}
