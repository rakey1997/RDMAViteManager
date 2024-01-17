<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RdmaTestRelationModel extends Model
{
    use HasFactory;

    protected $table="rdmatestrelation_tbl";
    protected $primaryKey="id";

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['test_pair_id','rdma_id_server','rdma_server_state','rdma_id_client','rdma_client_state'];
}
