<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RdmaParaModel extends Model
{
    use HasFactory;

    protected $table="rdmapara_tbl";
    protected $primaryKey="id";

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['rdma_id','ifindex','node_type','node_guid','sys_image_guid','adap_state','caps','gid'];
}
