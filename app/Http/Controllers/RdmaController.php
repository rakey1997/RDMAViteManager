<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Model\ViewDNSModel;
use App\Models\Model\ViewPageModel;
use App\Models\Model\HostModel;
use App\Models\Model\CardModel;
use App\Models\Model\RdmaInfoModel;
use App\Models\Model\RdmaParaModel;
use App\Models\Model\ViewCardModel;
use App\Models\Model\ViewRdmaInfoModel;
use App\Models\Model\ViewRdmaParaModel;
use App\Models\Model\RdmaTestModel;
use App\Models\Model\ViewRdmaTestModel;
use phpseclib3\Net\SSH2;
use App\Jobs\RdmaTestCase;
// use Illuminate\Support\Facades\Artisan;


class RdmaController extends Controller
{
    protected function returnHost(Request $request){
        $jsonArr=array();
        $hostName=$request->input('query');
        $pagenum=$request->input('pagenum');
        $pagesize=$request->input('pagesize');

        $skipNum=($pagenum-1)*$pagesize;

        $host_Info=new HostModel();
        $rdma_Para=new ViewRdmaParaModel();

        if($hostName==''){
            $total=$host_Info->count();
            $record=$host_Info->select('id','host_name','host_ip','host_ssh_port','host_login_user','state','update_time')->orderBy('update_time','desc')->skip($skipNum)->take($pagesize)->get()->toArray(); 
        }else{
            $total=$host_Info->where('host_name','like', '%'.$hostName.'%')->count();
            $record=$host_Info->select('id','host_name','host_ip','host_ssh_port','host_login_user','state','update_time')->orderBy('update_time','desc')->where('host_name','like', '%'.$hostName.'%')->skip($skipNum)->take($pagesize)->get()->toArray(); 
        }
        $card_relation=$rdma_Para->where('host_state',1)->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(card_name)'))->groupBy('host_name')->get()->toArray(); 
        $rdma_relation=$rdma_Para->where('host_state',1)->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(ifname)'))->groupBy('host_name')->get()->toArray(); 
        $card_rdma_relation=$rdma_Para->where('host_state',1)->orderBy('update_time','desc')->select('host_name','card_name',DB::raw('group_concat(ifname)'))->groupBy('host_name','card_name')->get()->toArray(); 

        $jsonArr['pagenum']=$pagenum;
        $jsonArr['pagesize']=$pagesize;
        $jsonArr['total']=$total;
        if (!empty($record)){
            $jsonArr['hosts']=$record;
            $jsonArr['card_relation']=$card_relation;
            $jsonArr['rdma_relation']=$rdma_relation;
            $jsonArr['card_rdma_relation']=$card_rdma_relation;
            $jsonArr['total']=$total;
        }else {
            $jsonArr['hosts']=[];
            $jsonArr['card_relation']=[];
            $jsonArr['rdma_relation']=[];
            $jsonArr['total']=0;
        }
        $jsonArr['opCode']=true;
        return $jsonArr;
    }

    protected function addHost(Request $request){
        $jsonArr=array();
        $host_Info=new HostModel();
        $data=$request->input();
        $host_match=$host_Info->where('host_name',$data['host_name'])->get();
        $host_ip=$data['host_ip'];
        $host_ssh_port=$data['host_ssh_port'];
        $host_login_user=$data['host_login_user'];
        $password=$data['password'];
        $ssh_client = new SSH2($host_ip,$host_ssh_port);

        if (count($host_match)===0){
            $verify=$this->sshConnVerify($ssh_client,$host_ip,$host_ssh_port,$host_login_user,$password);
            if(!$verify){
                return response()->json(
                    ['message' => 'Can not connect to server, please check connection or password.']
                , 404);
            }else{
                $newRecord=$host_Info->create([
                    "host_name"=>$data['host_name'], 
                    "host_ip"=>$data['host_ip'],
                    "host_ssh_port"=>$data['host_ssh_port'],
                    'host_login_user'=>$data['host_login_user'],
                    'host_login_password' => $data['password'],
                    'state' => $data['state'],
                ]);
                $res=$newRecord->save();
                if ($res==1){
                    $jsonArr['opCode']=true;
                    $jsonArr['msg']='add record success';
                }else{
                    $jsonArr['opCode']=false;
                    $jsonArr['msg']='add record fail';
                }
                $this->getCardInfo($ssh_client,$newRecord->id);
                $this->getRDMAInfo($ssh_client,$data['host_name']);
                return $jsonArr;
            }
        }else{
            return response()->json(
                ['message' => 'Host already exsist!']
            , 404);
        }
    }

    protected function editHost($hostID,Request $request){
        $jsonArr=array();
        $host_Info=new HostModel();
        $data=$request->input();
        $host_match=$host_Info->where('id',$hostID)->get()->toArray();
        $host_ip=$data['host_ip'];
        $host_ssh_port=$data['host_ssh_port'];
        $host_login_user=$data['host_login_user'];
        $password=$data['password'];
        $ssh_client = new SSH2($host_ip,$host_ssh_port);

        $verify=$this->sshConnVerify($ssh_client,$host_ip,$host_ssh_port,$host_login_user,$password);
        if (!empty($host_match)){
            if(!$verify){
                return response()->json(
                    ['message' => 'Can not connect to server, please check connection or password.']
                , 404);
            }else{
                $res=$host_Info->where('id',$hostID)->update([
                    "host_name"=>$data['host_name'], 
                    "host_ip"=>$host_ip,
                    "host_ssh_port"=>$host_ssh_port,
                    'host_login_user'=>$host_login_user,
                    'host_login_password' => $password,
                    // 'state' => $data['state'],
                ]);
                if ($res==1){
                    $jsonArr['opCode']=true;
                    $jsonArr['msg']='update success';
                }else{
                    $jsonArr['opCode']=false;
                    $jsonArr['msg']='update fail';
                }

                $this->getCardInfo($ssh_client,$hostID);
                return $jsonArr;
            }
        }else{
            return response()->json(
                ['message' => 'Record not found.']
            , 404);
        }
    }

    protected function updateHostPass($hostID,Request $request){
        $jsonArr=array();
        $host_Info=new HostModel();
        $data=$request->input();
        $host_match=$host_Info->where('id',$hostID)->get();

        $host_ip=$data['host_ip'];
        $host_ssh_port=$data['host_ssh_port'];
        $host_login_user=$data['host_login_user'];
        $password=$data['password'];
        $ssh_client = new SSH2($host_ip,$host_ssh_port);

        $verify=$this->sshConnVerify($ssh_client,$host_ip,$host_ssh_port,$host_login_user,$password);

        if (!empty($host_match)){
            if(!$verify){
                return response()->json(
                    ['message' => 'Can not connect to server, please check connection or password.']
                , 404);
            }else{
                $res=$host_Info->where('id',$hostID)->update([
                    'host_login_password' => $data['password'],
                ]);
                if ($res==1){
                    $jsonArr['opCode']=true;
                    $jsonArr['msg']='update success';
                }else{
                    $jsonArr['opCode']=false;
                    $jsonArr['msg']='update fail';
                }
                $this->getCardInfo($ssh_client,$hostID);
                return $jsonArr;
            }
        }else{
            return response()->json(
                ['message' => 'Record not found.']
            , 404);
        }
    }

    protected function updateHostState($hostID,$state){
        $jsonArr=array();
        $host_Info=new HostModel();
        $card_Info=new CardModel();
        $host_match=$host_Info->where('id',$hostID)->get()->toArray()[0];

        $host_ip=$host_match['host_ip'];
        $host_name=$host_match['host_name'];
        $host_ssh_port=$host_match['host_ssh_port'];
        $host_login_user=$host_match['host_login_user'];
        $password=$host_match['host_login_password'];

        $jsonArr['opCode']=false;
        $jsonArr['msg']='update fail';

        if (!empty($host_match)){
            if($state==0){
                $card_res=$card_Info->where('host_id',$hostID)->delete();
                $res=$host_Info->where('id',$hostID)->update(['state'=>$state]);
                $jsonArr['opCode']=true;
                $jsonArr['msg']='update success';
            }else{
                $ssh_client = new SSH2($host_ip,$host_ssh_port);
                $verify=$this->sshConnVerify($ssh_client,$host_ip,$host_ssh_port,$host_login_user,$password);
                if ($verify){
                    $this->getCardInfo($ssh_client,$hostID);
                    $this->getRDMAInfo($ssh_client,$host_name);
                    $res=$host_Info->where('id',$hostID)->update(['state'=>$state]);
                    if ($res==1){
                        $jsonArr['opCode']=true;
                        $jsonArr['msg']='update success';
                    }
                }
            }
        }else{
            return response()->json(
                ['message' => 'Record not found.']
            , 404);
        }
        return $jsonArr;
    }

    protected function returnCard(Request $request){
        $jsonArr=array();
        $cardRelation=array();
        $host_name=$request->input('host_name');
        $whole=$request->input('whole');

        $card_Info=new ViewCardModel();
        $total=$card_Info->where('host_name',$host_name)->count();

        if($whole=="true"){
            $cardRelation=$card_Info->where('card_ipv4_addr',"<>","")->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(card_name)'))->groupBy('host_name')->get()->toArray();
        }
        $record=$card_Info->select('card_id','host_name','card_name','card_ipv4_addr','card_mac_addr','card_pci_addr','card_mtu','card_mtu_min','card_mtu_max','phys_port_name','card_state','update_time')->orderBy('create_time','asc')->where('host_name',$host_name)->get()->toArray(); 
        if (!empty($record)){
            $jsonArr['cards']=$record;
            $jsonArr['total']=$total;
        }else {
            $jsonArr['cards']=[];
            $jsonArr['total']=0;
        }
        $jsonArr['cardRelation']=$cardRelation;
        $jsonArr['opCode']=true;
        return $jsonArr;
    }

    protected function returnRDMA(Request $request){
        $jsonArr=array();
        $host_name=$request->input('host_name');
        $whole=$request->input('whole');
        $card_relation=[];
        $rdma_relation=[];
        $card_rdma_relation=[];

        $rdma_Info=new ViewRdmaParaModel();
        $total=$rdma_Info->where('host_name',$host_name)->count();
        $record=$rdma_Info->select('host_id','host_name','card_id','card_name','card_ipv4_addr','card_mac_addr','card_pci_addr','rdma_id','ifname','port','rdma_state','rdma_physical_state','node_type','node_guid','gid','sys_image_guid','adap_state','caps','update_time')->orderBy('rdma_state','asc')->where('host_name',$host_name)->get()->toArray(); 
        if($whole=="true"){
            $card_relation=$rdma_Info->where('host_state',1)->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(card_name)'))->groupBy('host_name')->get()->toArray(); 
            $rdma_relation=$rdma_Info->where('host_state',1)->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(ifname)'))->groupBy('host_name')->get()->toArray(); 
            $card_rdma_relation=$rdma_Info->where('host_state',1)->orderBy('update_time','desc')->select('host_name','card_name',DB::raw('group_concat(ifname)'))->groupBy('host_name','card_name')->get()->toArray(); 
        }
        if (!empty($record)){
            $jsonArr['cards']=$record;
            $jsonArr['total']=$total;

        }else {
            $jsonArr['cards']=[];
            $jsonArr['total']=0;
            $jsonArr['card_relation']=[];
            $jsonArr['rdma_relation']=[];
            $jsonArr['card_rdma_relation']=[];
        }
        $jsonArr['card_relation']=$card_relation;
        $jsonArr['rdma_relation']=$rdma_relation;
        $jsonArr['card_rdma_relation']=$card_rdma_relation;
        $jsonArr['opCode']=true;
        return $jsonArr;
    }

    public function getCardInfo($ssh_client,$host_id){
        $card_Info=new CardModel();
        $command='ip -br addr |grep -v "lo"|cut -d " " -f 1';
        $result=$ssh_client->exec($command);
        $cardArray=explode("\n",rtrim($result));
        $cardArray_num=count($cardArray);
        
        $card_match=$card_Info->where('host_id',$host_id)->select("card_name")->get()->map(function ($value) {return $value['card_name'];})->toArray();
        $card_match_num=count($card_match);
        // 数据库中的网卡大于实际查询得到的网卡数，则删除不一致的
        if ($card_match_num>=$cardArray_num && $cardArray_num!=0){
            $card_del_list=array_diff(array_values($card_match),$cardArray);
            foreach ($card_del_list as $card){
                $res=$card_Info->where('host_id',$host_id)->where('card_name',$card)->delete();
                if ($res!=1){
                    return response()->json(
                        ['message' => 'Record Clear occur error.']
                    , 404);
                }
            }
        }
        foreach ($cardArray as $card){
            $command='ip -j -d address show dev '.$card;
            // var_dump($card);
            $result=$ssh_client->exec($command);
            $result_json=json_decode($result,$assoc=true);
            if($result_json){
                foreach ($result_json as $result_detail){
                    if(isset($result_detail['ifname'])){
                        $card_ipv4_addr="";
                        if(isset($result_detail['phys_port_name'])){
                            $phys_port_name=$result_detail['phys_port_name'];
                        }else{
                            $phys_port_name="False";
                        }
                        if(isset($result_detail['parentdev'])){
                            $card_pci_addr=$result_detail['parentdev'];
                        }else{
                            $card_pci_addr="Without PCI Address";
                        }
                        if(isset($result_detail['min_mtu'])){
                            $card_mtu_min=$result_detail['min_mtu'];
                            $card_mtu_max=$result_detail['max_mtu'];
                        }else{
                            $card_mtu_min="Without MTU Limit";
                            $card_mtu_max="Without MTU Limit";
                        }
                        if($result_detail['addr_info']){
                            foreach($result_detail['addr_info'] as $addr_info){
                                if($addr_info['family']=="inet"){
                                    $card_ipv4_addr=$addr_info['local'];
                                }
                            }
                        }
                        //没有在数据库中，增加值
                        if($card_match_num==0 || !in_array($card,$card_match)){
                            $res=$card_Info->create([
                                "host_id"=>$host_id, 
                                "card_name"=>$card, 
                                "card_ipv4_addr"=>$card_ipv4_addr,
                                "card_mac_addr"=>$result_detail['address'],
                                'card_pci_addr'=>$card_pci_addr,
                                'card_mtu' => $result_detail['mtu'],
                                'card_mtu_min' => $card_mtu_min,
                                'card_mtu_max' => $card_mtu_max,
                                'phys_port_name' => $phys_port_name,
                                'card_state' => $result_detail['operstate']=="UP"?1:0,
                            ])->save();
                            if ($res!=1){
                                return response()->json(
                                    ['message' => 'Record Add occur error.']
                                , 404);
                            }
                        //在原先的数据库中，更新值
                        }else{
                            $res=$card_Info->where('host_id',$host_id)->where('card_name',$card)->update([
                                "host_id"=>$host_id, 
                                "card_name"=>$card, 
                                "card_ipv4_addr"=>$card_ipv4_addr,
                                "card_mac_addr"=>$result_detail['address'],
                                'card_pci_addr'=>$card_pci_addr,
                                'card_mtu' => $result_detail['mtu'],
                                'card_mtu_min' => $card_mtu_min,
                                'card_mtu_max' => $card_mtu_max,
                                'phys_port_name' => $phys_port_name,
                                'card_state' => $result_detail['operstate']=="UP"?1:0,
                            ]);
                            if ($res!=1){
                                return response()->json(
                                    ['message' => 'Record Update occur error.']
                                , 404);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getRDMAGidInfo($ssh_client,$rdma_name,$card_ipv4_addr){
        $command='ibv_devinfo -d '.$rdma_name.' -v |grep -E "'.$card_ipv4_addr.', RoCE v2"|grep -woE "[[:digit:]]"';
        $gid=$ssh_client->exec($command);
        $result=$this->getCmdResult($ssh_client);
        if(!$result){
            $gid='';
        }
        return $gid;
    }

    public function getRDMAInfo($ssh_client,$host_name){
        $rdma_Info=new RdmaInfoModel();
        $rdma_Para=new RdmaParaModel();
        $view_card_Info=new ViewCardModel();
        $view_rdma_Info=new ViewRdmaInfoModel();
        $view_rdma_Para=new ViewRdmaParaModel();

        $command='rdma -jd link';
        $result=$ssh_client->exec($command);
        $result_json=json_decode($result,$assoc=true);

        // 单机上的网卡绑定的rdma信息
        $rdma_match=$view_rdma_Para->where('host_name',$host_name)->select("rdma_id")->get()->map(function ($value) {return $value['rdma_id'];})->toArray();
        // 先清空
        if ($rdma_match){
            $res=$rdma_Info->destroy($rdma_match);
            if ($res>=1){
                $jsonArr['msg']='delete success';
            }else{
                $jsonArr['msg']='delete fail';
            }
        }

        // 添加信息
        if($result_json){
            foreach ($result_json as $rdma_link_detail){
                $card_name=$rdma_link_detail['netdev'];
                $record=$view_card_Info->where('host_name',$host_name)->where('card_name',$card_name)->select("card_id","card_name")->get()->toArray();
                // var_dump($record);
                //没有在数据库中，增加值
                $res=$rdma_Info->create([
                    "card_id"=>$record[0]["card_id"], 
                    "ifindex"=>$rdma_link_detail['ifindex'], 
                    "ifname"=>$rdma_link_detail['ifname'], 
                    "port"=>$rdma_link_detail['port'], 
                    "phys_port_name"=>$card_name, 
                    "rdma_state"=>$rdma_link_detail['state'], 
                    "rdma_physical_state"=>$rdma_link_detail['physical_state'], 
                ])->save();
                if ($res!=1){
                    return response()->json(
                        ['message' => 'Record Add occur error.']
                    , 404);
                }
            }
        }

        $command='rdma -jd dev';
        $result=$ssh_client->exec($command);
        $result_json=json_decode($result,$assoc=true);

        // 添加信息
        if($result_json){
            foreach ($result_json as $rdma_dev_detail){
                $rdma_name=$rdma_dev_detail['ifname'];
                $record=$view_rdma_Info->where('host_name',$host_name)->where('ifname',$rdma_name)->select("rdma_id","card_name","card_ipv4_addr")->get()->toArray();
                $card_ipv4_addr=$record[0]["card_ipv4_addr"];
                $gid="";
                if($card_ipv4_addr!=""){
                    $gid=$this->getRDMAGidInfo($ssh_client,$rdma_name,$card_ipv4_addr);
                }
                //没有在数据库中，增加值
                $res=$rdma_Para->create([
                    "rdma_id"=>$record[0]["rdma_id"], 
                    "ifindex"=>$rdma_dev_detail['ifindex'], 
                    "node_type"=>$rdma_dev_detail['node_type'], 
                    "node_guid"=>$rdma_dev_detail['node_guid'], 
                    "sys_image_guid"=>$rdma_dev_detail['sys_image_guid'], 
                    "adap_state"=>$rdma_dev_detail['adaptive-moderation'], 
                    "caps"=>json_encode($rdma_dev_detail['caps']), 
                    "gid"=>trim($gid), 
                ])->save();
                if ($res!=1){
                    return response()->json(
                        ['message' => 'Record Add occur error.']
                    , 404);
                }
            }
        }
    }

    public function getSingleRDMAInfo($ssh_client,$host_name,$rdma_name){
        $rdma_Info=new RdmaInfoModel();
        $rdma_Para=new RdmaParaModel();
        $view_card_Info=new ViewCardModel();
        $view_rdma_Info=new ViewRdmaInfoModel();
        $view_rdma_Para=new ViewRdmaParaModel();

        $command='rdma -jdp link show '.$rdma_name.'/1';
        $result=$ssh_client->exec($command);
        $result_json=json_decode($result,$assoc=true);

        // 添加信息
        if($result_json){
            foreach ($result_json as $rdma_link_detail){
                $card_name=$rdma_link_detail['netdev'];
                $record=$view_card_Info->where('host_name',$host_name)->where('card_name',$card_name)->select("card_id","card_name")->get()->toArray();
                // var_dump($record);
                //没有在数据库中，增加值
                $res=$rdma_Info->create([
                    "card_id"=>$record[0]["card_id"], 
                    "ifindex"=>$rdma_link_detail['ifindex'], 
                    "ifname"=>$rdma_link_detail['ifname'], 
                    "port"=>$rdma_link_detail['port'], 
                    "phys_port_name"=>$card_name, 
                    "rdma_state"=>$rdma_link_detail['state'], 
                    "rdma_physical_state"=>$rdma_link_detail['physical_state'], 
                ])->save();
                if ($res!=1){
                    return response()->json(
                        ['message' => 'Record Add occur error.']
                    , 404);
                }
            }
        }

        $command='rdma -jd dev show '.$rdma_name;
        $result=$ssh_client->exec($command);
        $result_json=json_decode($result,$assoc=true);

        // 添加信息
        if($result_json){
            foreach ($result_json as $rdma_dev_detail){
                $rdma_name=$rdma_dev_detail['ifname'];
                $record=$view_rdma_Info->where('host_name',$host_name)->where('ifname',$rdma_name)->select("rdma_id","card_name","card_ipv4_addr")->get()->toArray();
                $card_ipv4_addr=$record[0]["card_ipv4_addr"];
                $gid="";
                if($card_ipv4_addr!=""){
                    $gid=$this->getRDMAGidInfo($ssh_client,$rdma_name,$card_ipv4_addr);
                }
                //没有在数据库中，增加值
                $res=$rdma_Para->create([
                    "rdma_id"=>$record[0]["rdma_id"], 
                    "ifindex"=>$rdma_dev_detail['ifindex'], 
                    "node_type"=>$rdma_dev_detail['node_type'], 
                    "node_guid"=>$rdma_dev_detail['node_guid'], 
                    "sys_image_guid"=>$rdma_dev_detail['sys_image_guid'], 
                    "adap_state"=>$rdma_dev_detail['adaptive-moderation'], 
                    "caps"=>json_encode($rdma_dev_detail['caps']), 
                    "gid"=>trim($gid), 
                ])->save();
                if ($res!=1){
                    return response()->json(
                        ['message' => 'Record Add occur error.']
                    , 404);
                }
            }
        }
    }

    public function sshConnVerify($ssh_client,$hostIp,$hostSSHPort,$hostLoginUser,$password){
        if (!$ssh_client->login($hostLoginUser, $password)) {
            return false;
        }
        return true;
    }

    protected function driverLoad($ssh_client,$driver_type){
        $command='lsmod|grep -w "rdma_'.$driver_type.'\s'.'"';
        $find_result=$ssh_client->exec($command);
        if($find_result){
            return True;
        }else{
            return False;
        }
    }

    protected function getCmdResult($ssh_client){
        $result=$ssh_client->exec('echo $?')=="0"?True:False;
        return $result;
    }

    public function sshExcuteFromSSH(Request $request){
        $jsonArr=array();
        $data=$request->input();
        $host_name=$data['host_name'];
        $host_Info=new HostModel();
        $card_Info=new CardModel();

        $host_match=$host_Info->where('host_name',$host_name)->get()->toArray()[0];
        if (count($host_match)!==0){
            $host_id=$host_match['id'];
            $host_ip=$host_match['host_ip'];
            $host_ssh_port=$host_match['host_ssh_port'];
            $host_login_user=$host_match['host_login_user'];
            $password=$host_match['host_login_password'];

            $ssh_client = new SSH2($host_ip,$host_ssh_port);

            $verify=$this->sshConnVerify($ssh_client,$host_ip,$host_ssh_port,$host_login_user,$password);
            if(!$verify){
                return response()->json(
                    ['message' => 'Can not connect to Host!']
                , 404);
            }
        }else{
            return response()->json(
                ['message' => 'Can not find Host Connection Info!']
            , 404);
        }

        $cmd=$data['cmd'];

        $jsonArr['opCode']=false;
        $jsonArr['msg']='operation fail';

        switch($cmd){
            case "modifyMtu":
                #修改MTU信息
                // $command='ip -4 -br addr |grep -v "lo"|cut -d " " -f 1|xargs -i ip -j -d address show dev {$1}';
                $card_name=$data['card_name'];
                $card_mtu=$data['card_mtu'];
                $command='echo '.$card_mtu.' >/sys/class/net/'.$card_name.'/mtu';
                $sudoCommand='echo '.$password.' | sudo -S bash -c "'.$command.'"';
                // var_dump($sudoCommand);
                $ssh_client->exec($sudoCommand);
                $result=$this->getCmdResult($ssh_client);
                $detail=$result?"excute command success":"excute command fail";
                if($result){
                    $res=$card_Info->where('host_id',$host_id)->where('card_name',$card_name)->update([
                        "card_mtu"=>$card_mtu, 
                    ]);
                    if ($res==1){
                        $jsonArr['opCode']=true;
                        $jsonArr['msg']='update success';
                    }
                }
                break;
            case "addRdmaDev":
                #增加Rdma驱动
                $card_name=$data['card_name'];
                $driver_type=$data['driver_type'];
                $rdma_name=$data['rdma_name'];

                $addDriverCmd='modprobe rdma_'.$driver_type;
                $sudoAddDriverCmd='echo '.$password.' | sudo -S bash -c "'.$addDriverCmd.'"';

                $addDriverDevCmd='rdma link add '.$rdma_name.' type '.$driver_type.' netdev '.$card_name;
                $sudoAddDriverDevCmd='echo '.$password.' | sudo -S bash -c "'.$addDriverDevCmd.'"';
                if($this->driverLoad($ssh_client,$driver_type)){
                    $cmd_res=$ssh_client->exec($sudoAddDriverDevCmd);
                }else{
                    $ssh_client->exec($sudoAddDriverCmd);
                    if($this->driverLoad($ssh_client,$driver_type)){
                        $cmd_res=$ssh_client->exec($sudoAddDriverDevCmd);
                    }else{
                        return response()->json(
                            ['message' => 'Can not Load rdma_'.$driver_type.',please check driver module']
                        , 404);
                    };
                }
                // $result=$this->getCmdResult($ssh_client);
                $result=stripos($cmd_res,"error");
                $detail=$result===false?'add '.$rdma_name.' success':'add '.$rdma_name.' fail';
                if(!$result){
                    $this->getSingleRDMAInfo($ssh_client,$host_name,$rdma_name);
                    $jsonArr['opCode']=true;
                    $jsonArr['msg']='add device success';
                }
                break;
            case "nCmd":
                $command=$data['query'];
                $detail=$ssh_client->exec($command);
                $result=$ssh_client->exec('echo $?');
                $jsonArr['opCode']=true;
                $jsonArr['cmdState']=trim($result);
                if($result=="0"){
                    $jsonArr['msg']='operation success';
                }else{
                    $jsonArr['msg']='operation failed';
                }
                break;
            case "sCmd":
                $command=$data['query'];
                $sudoCommand='echo '.$password.' | sudo -S bash -c "'.$command.'"';
                $detail=$ssh_client->exec($sudoCommand);
                $result=$ssh_client->exec('echo $?');
                $jsonArr['opCode']=true;
                $jsonArr['cmdState']=trim($result);
                if($result=="0"){
                    $jsonArr['msg']='operation success';
                }else{
                    $jsonArr['msg']='operation failed';
                }
                break;
            default:
                
        }

        $jsonArr['result']=$detail;

        return $jsonArr;
    }
        
    protected function deleteHost($ids){
        $jsonArr=array();
        $host_Info=new HostModel();
        $flag=false;
        $hostIDs=explode(',', $ids);
        $host_name_list=array();
        foreach ($hostIDs as $hostID){
            $host_match=$host_Info->where('id',$hostID)->select("host_name")->get();
            array_push($host_name_list,$host_match[0]->host_name);
            if (empty($host_match)){
                return response()->json(
                    ['message' => $hostID.'Host Record not found.']
                , 404);
                $flag=true;
            }
        }
        if ($flag){
            return response()->json(
                ['message' => 'please check record..']
            , 404);
        }else{
            $res=$host_Info->destroy($hostIDs);
            if ($res>=1){
                $jsonArr['opCode']=true;
                $jsonArr['msg']='delete success';
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['msg']='delete fail';
            }
            return $jsonArr;
        }
    }

    protected function deleteRdma($id){
        $jsonArr=array();
        $host_Info=new HostModel();
        $rdma_Info=new RdmaInfoModel();
        $rdma_Para=new ViewRdmaParaModel();

        $rdma_para_match=$rdma_Para->where('rdma_id',$id)->select("host_id","ifname")->get()->toArray();
        $rdma_name=$rdma_para_match[0]['ifname'];

        $host_match=$host_Info->where('id',$rdma_para_match[0]['host_id'])->get()->toArray();
        $host_ip=$host_match[0]['host_ip'];
        $host_ssh_port=$host_match[0]['host_ssh_port'];
        $host_login_user=$host_match[0]['host_login_user'];
        $password=$host_match[0]['host_login_password'];
        $ssh_client = new SSH2($host_ip,$host_ssh_port);
        $detail="'del '.$rdma_name.'fail'";

        $delDriverCmd='rdma link del '.$rdma_name;
        $sudoDelDriverCmd='echo '.$password.' | sudo -S bash -c "'.$delDriverCmd.'"';

        $verify=$this->sshConnVerify($ssh_client,$host_ip,$host_ssh_port,$host_login_user,$password);
        if(!$verify){
            return response()->json(
                ['message' => 'Can not connect to server, please check connection or password.']
            , 404);
        }else{
            $ssh_client->exec($sudoDelDriverCmd);
            $result=$this->getCmdResult($ssh_client);
            $detail=$result?'del '.$rdma_name.'success':'del '.$rdma_name.'fail';
            if($result){
                $res=$rdma_Info->destroy($id);
                if ($res>=1){
                    $jsonArr['opCode']=true;
                    $jsonArr['msg']='delete success';
                }else{
                    $jsonArr['opCode']=false;
                    $jsonArr['msg']='delete fail';
                }
                return $jsonArr;
            }
        }
        $jsonArr['result']=$detail;
    }

    protected function returnTestMenu(Request $request){
        $jsonArr=array();
        $rdma_Info=new ViewRdmaParaModel();
        $relations=$rdma_Info->where('host_state',1)->where('card_ipv4_addr',"<>","")->orderBy('host_name','asc')->orderBy('card_name','asc')->select('rdma_id','host_name','card_name','ifname','card_ipv4_addr','gid')->get()->toArray(); 

        $jsonArr['opCode']=true;
        $jsonArr['records']=$relations;
        $jsonArr['msg']='query success';

        return $jsonArr;
    }

    protected function addTQ(Request $request){
        $jsonArr=array();
        $host_Info=new HostModel();
        $rdma_test_Info=new RdmaTestModel();

        $data=$request->input();
        $server_info=$data['server'];
        $rdma_info_server=explode(',',$server_info[2]);

        $host_match_server=$host_Info->where('host_name',$server_info[0])->get();
        if (count($host_match_server)!=0){
            $host_ip_server=$host_match_server[0]['host_ip'];
            $host_ssh_port_server=$host_match_server[0]['host_ssh_port'];
            $host_login_user_server=$host_match_server[0]['host_login_user'];
            $password_server=$host_match_server[0]['host_login_password'];
            $ssh_client_server = new SSH2($host_ip_server,$host_ssh_port_server);
        }else{
            return response()->json(
                ['message' => 'Can not find server info, please check ...']
            , 404);
        }

        $client_info=$data['client'];
        $rdma_info_client=explode(',',$client_info[2]);
        $host_match_client=$host_Info->where('host_name',$client_info[0])->get();
        if (count($host_match_server)!=0){
            $host_ip_client=$host_match_client[0]['host_ip'];
            $host_ssh_port_client=$host_match_client[0]['host_ssh_port'];
            $host_login_user_client=$host_match_client[0]['host_login_user'];
            $password_client=$host_match_client[0]['host_login_password'];
            $ssh_client_client = new SSH2($host_ip_client,$host_ssh_port_client);
        }else{
            return response()->json(
                ['message' => 'Can not find server info, please check ...']
            , 404);
        }

        $verify_server=$this->sshConnVerify($ssh_client_server,$host_ip_server,$host_ssh_port_server,$host_login_user_server,$password_server);
        $verify_client=$this->sshConnVerify($ssh_client_client,$host_ip_client,$host_ssh_port_client,$host_login_user_client,$password_client);


        if(!$verify_server){
            return response()->json(
                ['message' => 'Can not connect to server, please check connection or password.']
            , 404);
        }elseif(!$verify_client){
            return response()->json(
                ['message' => 'Can not connect to client, please check connection or password.']
            , 404);
        }else{
            // server ibv_rc_pingpong -d mlx5_0 -g 3 
            // client ibv_rc_pingpong -d pclr_0 -g 1 10.10.10.10
            $command_server='ibv_rc_pingpong -d '.$rdma_info_server[1].' -n 3 -g '.$rdma_info_server[2];
            $command_client='ibv_rc_pingpong -d '.$rdma_info_client[1].' -n 3 -g '.$rdma_info_client[2].' '.$rdma_info_server[3];

            
            $ssh_client_server->enablePTY();
            $ssh_client_server->exec($command_server);
            $ssh_client_server->setTimeout(10);
            
            $ssh_client_client->enablePTY();
            $ssh_client_client->exec($command_client);
            $ssh_client_client->setTimeout(10);
            
            if($ssh_client_server->isTimeout() || $ssh_client_client->isTimeout()){
                $ssh_client_server->write("\x03");
                $ssh_client_client->write("\x03");
            }else{
                $cmd_res=$ssh_client_client->read();
                $result_valid=stripos($cmd_res,"3 iters in");
                if($result_valid!==false) {
                    $res=$rdma_test_Info->create([
                        "bidirection"=>$data['birections']?3:2, 
                        "test_pair_id"=>$data['test_pair_id'], 
                        "rdma_id_server"=>$rdma_info_server[0],
                        "rdma_id_client"=>$rdma_info_client[0],
                    ])->save();
                    if ($res==1){
                        $jsonArr['opCode']=true;
                        $jsonArr['msg']='add test pair info success';
                    }else{
                        $jsonArr['opCode']=false;
                        $jsonArr['msg']='add test pair info fail';
                    }
                }else{
                    $jsonArr['opCode']=false;
                    $jsonArr['msg']='Test Pair Connect fail, please check';
                }
                $jsonArr['result']=$cmd_res;
                return $jsonArr;
            }
        }
    }

    protected function deleteTQ($ids){
        $jsonArr=array();
        $rdma_test_Info=new RdmaTestModel();
        $flag=false;
        $TQ_Ids=explode(',', $ids);
        $TQ_list=array();

        foreach ($TQ_Ids as $TQ_Id){
            $TQ_match=$rdma_test_Info->where('test_pair_id',$TQ_Id)->get()->toArray();
            if (empty($TQ_match)){
                return response()->json(
                    ['message' => 'No '.$TQ_Id.' Test Pair Record not found.']
                , 404);
                $flag=true;
            }else{
                array_push($TQ_list,$TQ_match[0]['id']);
            }
        }
        if ($flag){
            return response()->json(
                ['message' => 'please check record..']
            , 404);
        }else{
            $res=$rdma_test_Info->destroy($TQ_list);
            if ($res>=1){
                $jsonArr['opCode']=true;
                $jsonArr['msg']='delete success';
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['msg']='delete fail';
            }
            return $jsonArr;
        }
    }

    protected function excuteTest(Request $request){
        $jsonArr=array();
        $rdma_test_Info=new ViewRdmaTestModel();

        $data=$request->input();
        $TQ_info=$data['testHosts'];
        $TQ_Items=$data['testItems'];

        // var_dump($TQ_info);
        // var_dump($TQ_Items);
        // foreach ($TQ_info as $TQ_id){
        //     $rdmaTest=$rdma_test_Info->where('test_pair_id',$TQ_id)->get();
        //     // $direction_flag=$rdmaTest[0]['bidirection']==2?" ":" -b ";  //是否rdma双向测试，2为单向测试，3为双向测试
        //     $direction_flag=$rdmaTest[0]['bidirection']==2?" -b ":" ";  //是否rdma双向测试，2为单向测试，3为双向测试

        //     $test_file_name="/tmp/333_";
        //     $test_pair_id=$rdmaTest[0]['test_pair_id'];
        //     $sep="_";

        //     $host_name_server=$rdmaTest[0]['server_host_name'];
        //     $host_ip_server=$rdmaTest[0]['server_host_ip'];
        //     $host_ssh_port_server=$rdmaTest[0]['server_host_ssh_port'];
        //     $host_login_user_server=$rdmaTest[0]['server_host_login_user'];
        //     $password_server=$rdmaTest[0]['server_host_login_password'];
        //     $ssh_client_server = new SSH2($host_ip_server,$host_ssh_port_server);

        //     $host_name_client=$rdmaTest[0]['client_host_name'];
        //     $host_ip_client=$rdmaTest[0]['client_host_ip'];
        //     $host_ssh_port_client=$rdmaTest[0]['client_host_ssh_port'];
        //     $host_login_user_client=$rdmaTest[0]['client_host_login_user'];
        //     $password_client=$rdmaTest[0]['client_host_login_password'];
        //     $ssh_client_client = new SSH2($host_ip_client,$host_ssh_port_client);

        //     $rdma_name_server=$rdmaTest[0]['server_ifname'];
        //     $rdma_ipv4_server=$rdmaTest[0]['server_card_ipv4_addr'];
        //     $rdma_gid_server=$rdmaTest[0]['server_gid'];

        //     $rdma_name_client=$rdmaTest[0]['client_ifname'];
        //     $rdma_ipv4_client=$rdmaTest[0]['client_card_ipv4_addr'];
        //     $rdma_gid_client=$rdmaTest[0]['client_gid'];

        //     $verify_server=$this->sshConnVerify($ssh_client_server,$host_ip_server,$host_ssh_port_server,$host_login_user_server,$password_server);
        //     $verify_client=$this->sshConnVerify($ssh_client_client,$host_ip_client,$host_ssh_port_client,$host_login_user_client,$password_client);

        //     // $command_server="ibv_rc_pingpong -d pclr_0 -g 1 >".$test_file_name."server";
        //     $command_server='ib_send_bw -a -R -F -d '.$rdma_name_server.' --report_gbits -q 10'.$direction_flag." >".$test_file_name."server"." &";
        //     // $command_server="ibv_rc_pingpong -d pclr_0 -g 1 &";
        //     // $command_client="ibv_rc_pingpong -d pclr_0 -g 1 10.10.10.10 >".$test_file_name."client";
        //     $command_client='ib_send_bw -a -R -F -d '.$rdma_name_client.' --report_gbits -q 10'.$direction_flag.$rdma_ipv4_server." >".$test_file_name."client";
        //     // $command_client="ibv_rc_pingpong -d pclr_0 -g 1 10.10.10.10";
            
        //     $command_check="ps -ef|grep 'ib_send_bw'|wc -l";
        //     // $command_check="jobs -l";
        //     var_dump($command_server);
        //     var_dump($command_client);
        //     // jobs -p
        //     $ssh_client_server->disablePTY();
        //     $ssh_client_server->exec($command_server);
        //     // $ssh_client_server->setTimeout(140);
        //     $a=$ssh_client_server->exec($command_check);
        //     $b="first";
        //     $ssh_client_client->disablePTY();
        //     $b=$ssh_client_client->exec($command_client,function($str) {
        //         // if (strpos($str, 'iter') !== false) {
        //         //     $b= "find the iter word";
        //         //     var_dump('bb:'.$str);
        //         //     return $b;
        //         if (stripos($str, 'Mpps') !== false) {
        //             $b= "find the iter word";
        //             var_dump('bb:'.$str);
        //             return $b;
        //         }else{
        //             $b= "didn;t find the iter word";
        //             var_dump("not come int");
        //             return $b;
        //         }
        //     });
        //     // $ssh_client_client->setTimeout(140);
        //     // while(1){
        //     //     sleep(4);

        //     //     // $job_finished_client=$ssh_client_client->exec($command_check);
    
        //     //     // $ssh_client_server->exec($command_check);
        //     //     // $ssh_client_client->exec($command_check);
    
        //         var_dump("a:".$a);
        //         var_dump("getExitStatus_server:".$ssh_client_server->getExitStatus());
        //         var_dump("b:".$b);
        //         var_dump("getExitStatus_client:".$ssh_client_client->getExitStatus());
        //     //     // var_dump("job_finished_server:".$job_finished_server);
        //     //     // var_dump("job_finished_client:".$job_finished_client);

        //     //     if($a ||$b){
        //     //         var_dump("a:".$a);
        //     //         var_dump("b:".$b);
        //     //         var_dump("job_finished_server_last:".$ssh_client_server->getExitStatus());
        //     //         // var_dump("job_finished_client_last:".$job_finished_client);
        //     //         break;
        //     //     }
        //     // }

        //     // var_dump("job_finished_server1:".$ssh_client_server->read());
        //     // var_dump("job_finished_client1:".$job_finished_client->read());
        // }
        foreach ($TQ_info as $TQ_id){
            foreach ($TQ_Items as $TQ_Item){
                $rdmaTest=$rdma_test_Info->where('test_pair_id',$TQ_id)->get();
                // var_dump($rdmaTest);
                RdmaTestCase::dispatch($TQ_Item,$rdmaTest)->onQueue($TQ_id); 
                // Artisan::queue('RdmaTestCase', [
                //     '--queue' => $TQ_id
                // ])->onConnection('database')->onQueue($TQ_id)->dispatch($rdmaTest); 
            }
        }
    }

    protected function returnTestResult(Request $request){
        $jsonArr=array();
        $rdma_test_Info=new ViewRdmaTestModel();

        $data=$request->input();
        $TQ_info=$data['testHosts'];
        $TQ_Items=$data['testItems'];

        // var_dump($TQ_info);

        $record=$rdma_test_Info->where('test_pair_id',$TQ_info)->get()->toArray();
        // foreach ($TQ_info as $TQ_id){
                
        // }

        $total=$rdma_test_Info->where('test_pair_id',$TQ_info)->count();
        if (!empty($record)){
            $jsonArr['record']=$record;
            $jsonArr['total']=$total;

        }else {
            $jsonArr['record']=[];
            $jsonArr['total']=0;
        }
        $jsonArr['opCode']=true;
        return $jsonArr;
    }
}