<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model 
{

    protected $fillable = [];

    protected $visible = ['name'];  

    protected $table = 'tbl_employee';
    protected $primaryKey = 'id_employee';

    

}
