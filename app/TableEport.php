<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TableEport extends Model 
{

    protected $fillable = ['start_date','end_date','path','status'];

    protected $visible = ['idorderexport','start_date','end_date','status'];  

    protected $table = 'tbl_orderexport';
    protected $primaryKey = 'idorderexport';

   

    

}
