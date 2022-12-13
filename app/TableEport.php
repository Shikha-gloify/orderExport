<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TableEport extends Model 
{

    protected $fillable = ['start_date','end_date','path','status','role_id','id_employee'];

    protected $visible = ['idorderexport','start_date','end_date','status','role_id','id_employee'];  

    protected $table = 'tbl_orderexport';
    protected $primaryKey = 'idorderexport';

   

    

}
