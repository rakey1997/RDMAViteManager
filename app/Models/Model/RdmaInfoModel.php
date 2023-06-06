<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RdmaInfoModel extends Model
{
    use HasFactory;
    protected $table="rdmainfo_tbl";
    protected $primaryKey="id";

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['card_id','ifindex','ifname','port','phys_port_name','rdma_state','rdma_physical_state'];
}
