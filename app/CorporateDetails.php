<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorporateDetails extends Model 
{

    protected $fillable = [];

    protected $visible = ['name'];  

    protected $table = 'tbl_corporate_details';
    protected $primaryKey = 'id_customer';

   

}
