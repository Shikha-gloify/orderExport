<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Oath_clients extends Model 
{

    protected $fillable = [];

    protected $visible = ['client_id','client_secret','employee_id'];  

    protected $table = 'oauth_clients';
    protected $primaryKey = 'client_id';

   

    

}
