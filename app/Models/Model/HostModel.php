<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostModel extends Model
{
    use HasFactory;
    protected $table="hostinfo_tbl";
    protected $primaryKey="id";

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['host_name','host_ip','host_ssh_port','host_login_user','host_login_password','state'];
}
