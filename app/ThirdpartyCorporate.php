<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ThirdpartyCorporate extends Model 
{

    protected $fillable = [];

    protected $visible = ['thirdparty_corporate_id'];  

    protected $table = 'tbl_thirdparty_corporate';
    protected $primaryKey = 'thirdparty_corporate_id';

   

    

}
