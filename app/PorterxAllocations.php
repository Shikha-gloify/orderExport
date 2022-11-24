<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PorterxAllocations extends Model 
{

    protected $fillable = [];

    protected $visible = ['tbl_porterx_allocations_id','tbl_porterx_allocations_id_employee','tbl_porterx_allocations_id_order'];  

    protected $table = 'tbl_porterx_allocations';
    protected $primaryKey = 'tbl_porterx_allocations_id';

    

}
