<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewRdmaTestModel extends Model
{
    use HasFactory;
    protected $table="view_rdma_test";
    protected $primaryKey="test_pair_id";
}
