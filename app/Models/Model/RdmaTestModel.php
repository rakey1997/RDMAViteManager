<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RdmaTestModel extends Model
{
    use HasFactory;

    protected $table="rdmatest_tbl";
    protected $primaryKey="id";

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['test_identifier','test_pair_id','test_count_no','test_queue','test_queue_state','rdma_id_server','rdma_id_client',
    'bidirection','statistic','test_qp_num','test_port_num','rdma_cm','msg_size','more_para','delay','source_num',
    'rdma_sendbw_flag','rdma_sendbw_costtime','rdma_readbw_flag','rdma_readbw_costtime','rdma_writebw_flag','rdma_writebw_costtime',
    'rdma_atomicbw_flag','rdma_atomicbw_costtime','rdma_ethernetbw_flag','rdma_ethernetbw_costtime',
    'rdma_sendlat_flag','rdma_sendlat_costtime','rdma_readlat_flag','rdma_readlat_costtime','rdma_writelat_flag','rdma_writelat_costtime',
    'rdma_atomiclat_flag','rdma_atomiclat_costtime','rdma_ethernetlat_flag','rdma_ethernetlat_costtime'];
}
