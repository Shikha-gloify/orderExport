<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateUser extends Model 
{

    protected $fillable = [];

    protected $visible = ['id_corporate_user','fk_tbl_employee_id','corporate_id','status'];  

    protected $table = 'tbl_corporate_user';
    protected $primaryKey = 'airport_name_id';

    

}
