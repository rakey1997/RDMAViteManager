<?php

namespace App\Models\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardModel extends Model
{
    use HasFactory;
    protected $table="cardinfo_tbl";
    protected $primaryKey="id";

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['host_id','card_name','card_ipv4_addr','card_mac_addr','card_pci_addr','card_mtu','card_mtu_min','card_mtu_max','phys_port_name','card_state'];
}
