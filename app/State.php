<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class State extends Model 
{

    protected $fillable = [];

    protected $visible = ['extraKilometerPrice'];  

    protected $table = 'tbl_state';
    protected $primaryKey = 'idState';

   

    

}
