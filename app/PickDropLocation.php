<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PickDropLocation extends Model 
{

    protected $fillable = [];

    protected $visible = ['sector'];  

    protected $table = 'tbl_pick_drop_location';
    protected $primaryKey = 'id_pick_drop_location';

    

}
