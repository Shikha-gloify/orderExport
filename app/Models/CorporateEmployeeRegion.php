<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateEmployeeRegion extends Model 
{

    protected $fillable = [];

    protected $visible = ['id_employee_region','fk_tbl_employee_id','fk_tbl_region_id'];  

    protected $table = 'tbl_corporate_employee_region';
    protected $primaryKey = 'id_employee_region';

    

}
