<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Model\RdmaTestModel;
use phpseclib3\Net\SSH2;

class RdmaTestCase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // public $connection;
    public $rdmaTest,$cmd;
    public $jsonArr;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($cmd,$rdmaTest)
    {
        // $this->connection = $connection;
        $this->rdmaTest = $rdmaTest;
        $this->cmd = $cmd;
    }

    public function sshConnVerify($ssh_client,$hostLoginUser,$password){
        if (!$ssh_client->login($hostLoginUser, $password)) {
            return false;
        }
        return true;
    }

    public function updateTestResult($rdma_test_Info,$test_identifier,$test_pair_id,$flag,$costtime){
        $res=0;
        switch($this->cmd){
            case "ib_send_bw":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_sendbw_flag'=>$flag,'rdma_sendbw_costtime'=>$costtime]);
                break;
            case "ib_read_bw":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_readbw_flag'=>$flag,'rdma_readbw_costtime'=>$costtime]);
                break;
            case "ib_write_bw":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_writebw_flag'=>$flag,'rdma_writebw_costtime'=>$costtime]);
                break;
            case "ib_atomic_bw":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_atomicbw_flag'=>$flag,'rdma_atomicbw_costtime'=>$costtime]);
                break;
            case "raw_ethernet_bw":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_ethernetbw_flag'=>$flag,'rdma_ethernetbw_costtime'=>$costtime]);
                break;
            case "ib_send_lat":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_sendlat_flag'=>$flag,'rdma_sendlat_costtime'=>$costtime]);
                break;
            case "ib_read_lat":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_readlat_flag'=>$flag,'rdma_readlat_costtime'=>$costtime]);
                break;
            case "ib_write_lat":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_writelat_flag'=>$flag,'rdma_writelat_costtime'=>$costtime]);
                break;
            case "ib_atomic_lat":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_atomiclat_flag'=>$flag,'rdma_atomiclat_costtime'=>$costtime]);
                break;
            case "raw_ethernet_lat":
                $res=$rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['rdma_ethernetlat_flag'=>$flag,'rdma_ethernetlat_costtime'=>$costtime]);
                break;
            default:
                break;
        };

        return $res==1?true:false;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rdma_test_Info=new RdmaTestModel();
        $rdmaTest=$this->rdmaTest;

        $test_identifier=$rdmaTest['test_identifier'];
        $test_pair_id=$rdmaTest['test_pair_id'];
        $test_qp_num=$rdmaTest['test_qp_num'];
        $sep="-";
        $path="\/tmp\/";  //保存文件目录
        $collect_server_ip="192.168.221.37";  //采集文件服务器
        $collect_server_path="\/opt\/logstash\/test_data\/server"; //服务端日志保存记录
        $collect_client_path="\/opt\/logstash\/test_data\/client"; //服务端日志保存记录

        $host_name_server=$rdmaTest['server_host_name'];
        $host_ip_server=$rdmaTest['server_host_ip'];
        $host_ssh_port_server=$rdmaTest['server_host_ssh_port'];
        $host_login_user_server=$rdmaTest['server_host_login_user'];
        $password_server=$rdmaTest['server_host_login_password'];
        $ssh_client_server = new SSH2($host_ip_server,$host_ssh_port_server);
        $ssh_client_server_check = new SSH2($host_ip_server,$host_ssh_port_server);

        $host_name_client=$rdmaTest['client_host_name'];
        $host_ip_client=$rdmaTest['client_host_ip'];
        $host_ssh_port_client=$rdmaTest['client_host_ssh_port'];
        $host_login_user_client=$rdmaTest['client_host_login_user'];
        $password_client=$rdmaTest['client_host_login_password'];
        $ssh_client_client = new SSH2($host_ip_client,$host_ssh_port_client);
        $ssh_client_client_check = new SSH2($host_ip_client,$host_ssh_port_client);

        $verify_server=$this->sshConnVerify($ssh_client_server,$host_login_user_server,$password_server);
        $verify_client=$this->sshConnVerify($ssh_client_client,$host_login_user_client,$password_client);

        $this->sshConnVerify($ssh_client_server_check,$host_login_user_server,$password_server);
        $this->sshConnVerify($ssh_client_client_check,$host_login_user_client,$password_client);

        // $jsonArr['opCode']=false;
        // $jsonArr['msg']='test finished';

        if(!$verify_server){
            $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'4']);
            throw new Exception('can not connect to server.');
        }elseif(!$verify_client){
            $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'4']);
            throw new Exception('can not connect to client.');
        }else{
            //birection -b
            // server ib_send_bw -a -R -F -d mlx5_2 -R --report_gbits -q 10
            // client ib_send_bw -a -R -F -d mlx5_0 -R --report_gbits -q 10 10.10.10.10
            $rdma_name_server=$rdmaTest['server_ifname'];
            $rdma_ipv4_server=$rdmaTest['server_card_ipv4_addr'];
            $rdma_mac_addr_server=$rdmaTest['server_card_mac_addr'];

            $rdma_name_client=$rdmaTest['client_ifname'];
            $rdma_ipv4_client=$rdmaTest['client_card_ipv4_addr'];
            $rdma_mac_addr_client=$rdmaTest['client_card_mac_addr'];

            if (stripos($this->cmd,"lat")!==false){
                //测试时延
                $direction_flag=""; 
                $direction_name="";  
                $qp_flag=""; //lat时延测试没有q参数
                $test_file_name=$path.$test_identifier.$sep.$test_pair_id.$sep.$test_qp_num.$sep.$this->cmd.$sep.$host_name_server.$sep.$rdma_name_server.$sep.$host_name_client.$sep.$rdma_name_client.$sep;
            }else{
                //测试带宽
                $direction_flag=$rdmaTest['bidirection']==2?"":" -b";   
                $direction_name=$rdmaTest['bidirection']==2?"undirection":"bidirection";  //是否rdma双向测试，2为单向测试，3为双向测试
                $qp_flag=" -q ".$test_qp_num;  //默认采用10
                $test_file_name=$path.$test_identifier.$sep.$test_pair_id.$sep.$test_qp_num.$sep.$this->cmd.$sep.$host_name_server.$sep.$rdma_name_server.$sep.$host_name_client.$sep.$rdma_name_client.$sep.$direction_name.$sep;
            }

            switch ($this->cmd) {
                case 'ib_atomic_bw':
                    // #10.10.10.10上运行
                    // sudo ib_atomic_bw -A FETCH_AND_ADD -m 4096 -d mlx5_2 -F -q 1
                    // #10.10.10.20上运行
                    // sudo ib_atomic_bw -A FETCH_AND_ADD -m 4096 -d mlx5_0 -F -q 1 10.10.10.10
                    $command_check_server=$this->cmd.' -F -d '.$rdma_name_server.' -q '.$test_qp_num.' -m 4096 -A FETCH_AND_ADD'.$direction_flag;
                    $command_check_client=$this->cmd.' -F -d '.$rdma_name_client.' -q '.$test_qp_num.' -m 4096 -A FETCH_AND_ADD '.$direction_flag.$rdma_ipv4_server;
                    break;
                case 'ib_atomic_lat':
                    // #10.10.10.10上运行
                    // sudo ib_atomic_lat -F -d mlx5_2 --report_gbits
                    // #10.10.10.20上运行
                    // sudo ib_atomic_lat -F -d mlx5_0 --report_gbits 10.10.10.10
                    $command_check_server=$this->cmd.' -F -d '.$rdma_name_server.' --report_gbits';
                    $command_check_client=$this->cmd.' -F -d '.$rdma_name_client.' --report_gbits '.$rdma_ipv4_server;
                    break;
                case 'raw_ethernet_bw':
                    // 1. 测试10.10.10.10至10.10.10.20方向带宽
                    // #10.10.10.10上运行
                    // sudo raw_ethernet_bw -d mlx5_2 --client -F -B 10:70:fd:31:ea:dc -E 10:70:fd:31:f3:bc --report_gbits -m 9600 -q 10
                    // #10.10.10.20上运行
                    // sudo raw_ethernet_bw -d mlx5_0 --client -F -B 10:70:fd:31:f3:bc -E 10:70:fd:31:ea:dc --report_gbits -m 9600 -q 10
                    $command_check_server=$this->cmd.' -F -m 9600 -d '.$rdma_name_server.' --client --report_gbits'.$direction_flag.' -B '.$rdma_mac_addr_server.' -E '.$rdma_mac_addr_client;
                    $command_check_client=$this->cmd.' -F -m 9600 -d '.$rdma_name_client.' --client --report_gbits'.$direction_flag.' -E '.$rdma_mac_addr_server.' -B '.$rdma_mac_addr_client;
                    break;
                case 'raw_ethernet_lat':
                    // 1. 测试10.10.10.10至10.10.10.20方向带宽
                    // #10.10.10.10上运行
                    // sudo raw_ethernet_lat -d mlx5_2 --server -F -B 10:70:fd:31:ea:dc -E 10:70:fd:31:f3:bc --report_gbits -m 9600
                    // #10.10.10.20上运行
                    // sudo raw_ethernet_lat -d mlx5_0 --client -F -B 10:70:fd:31:f3:bc -E 10:70:fd:31:ea:dc --report_gbits -m 9600
                    $command_check_server=$this->cmd.' -F -m 9600 -d '.$rdma_name_server.' --server --report_gbits'.$direction_flag.' -B '.$rdma_mac_addr_server.' -E '.$rdma_mac_addr_client;
                    $command_check_client=$this->cmd.' -F -m 9600 -d '.$rdma_name_client.' --client --report_gbits'.$direction_flag.' -E '.$rdma_mac_addr_server.' -B '.$rdma_mac_addr_client;
                    break;
                default:
                    $command_check_server=$this->cmd.' -a -F -d '.$rdma_name_server.$qp_flag.$direction_flag.' --report_gbits';
                    $command_check_client=$this->cmd.' -a -F -d '.$rdma_name_client.$qp_flag.$direction_flag.' --report_gbits '.$rdma_ipv4_server;
                    break;
            }
            $command_server=$command_check_server.' 2>&1 >'.$test_file_name.'server.log'.' &';
            // $command_server=$command_check_server.' 2>&1 >'.$test_file_name.'server.log';
            var_dump($command_server);
            $command_client=$command_check_client.' 2>&1 >'.$test_file_name.'client.log'.' &';
            // $command_client=$command_check_client.' 2>&1 >'.$test_file_name.'client.log';
            var_dump($command_client);

            $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'2']);
            $this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"2",0);  //开始测试
            $time_start=microtime(true);
            $ssh_client_server->exec($command_server);            
            $ssh_client_client->exec($command_client);

            $test_result_flag = false;

            while(1){
                sleep(5);
                $time_end=microtime(true);
                $seconds=$time_end-$time_start;
                // var_dump($seconds);

                $pid_check_server=$ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'|wc -l");
                $pid_check_client=$ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'|wc -l");

                if($seconds>1780){
                    $ssh_client_server_check->exec("pkill -9 -f '".$command_check_server."'");
                    $ssh_client_client_check->exec("pkill -9 -f '".$command_check_client."'");
                    var_dump($ssh_client_server_check->exec("pkill -9 -f '".$command_check_server."'"));
                    var_dump("----double kill-----");
                    var_dump($ssh_client_client_check->exec("pkill -9 -f '".$command_check_client."'"));
                    var_dump("----kill result-----");
                    $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //失败
                    var_dump($ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'"));
                    var_dump("----fail result-----");
                    var_dump($ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'"));


                    // $jsonArr['opCode']=$res;
                    // $jsonArr['msg']='test fail';
                    // throw new Exception('test fail, please check result');
                    // var_dump('test timeout, break');
                    break;
                }else if($pid_check_server=="2" && $pid_check_client=="2" ){
                    if($seconds>6){
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"3",$seconds); //成功
                        // $jsonArr['opCode']=$res;
                        // $jsonArr['msg']='test success';
                        $test_result_flag = true;
                        var_dump($ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'"));
                        var_dump("----result-----");
                        var_dump($ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'"));
                    }else{
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //太短时间也是异常退出
                    };
                    break;
                }else {
                    // var_dump('in testing');
                }
            }
            $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'3']);
            if($test_result_flag){
                $collect_server_ip="192.168.236.52";  //采集文件服务器
                $collect_server_path="\/opt\/logstash\/test_data\/server"; //服务端日志保存记录
                $collect_client_path="\/opt\/logstash\/test_data\/client"; //服务端日志保存记录
    
                // sshpass -p "1qaz@WSX" scp /tmp/20230628151550-1-1687936468-ib_atomic_bw-SBL_RDMA03-rxe_0-SBL_RDMA04-rxe_0-undirection-server.log elk@192.168.221.37:/opt/logstash/test_data/server
                $uploadServerCmd="sshpass -p '1qaz@WSX' scp -o StrictHostKeyChecking=no ".$test_file_name.'server.log '."elk@".$collect_server_ip.":".$collect_server_path;
                $uploadClientCmd="sshpass -p '1qaz@WSX' scp -o StrictHostKeyChecking=no ".$test_file_name.'client.log '."elk@".$collect_server_ip.":".$collect_client_path;
                
                $ssh_client_server_check->exec($uploadServerCmd);
                $ssh_client_client_check->exec($uploadClientCmd);
            }
        }
    }

    //任务失败的处理过程回调函数,打印返回错误信息
    public function failed(\Exception $exception)
    {
        $rdma_test_Info=new RdmaTestModel();
        // $test_identifier=$this->rdmaTest['test_identifier'];
        // $test_pair_id=$this->rdmaTest['test_pair_id'];
        // $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'4']);
        //php artisan queue:work --once --tries=3
        // $jsonArr['opCode']=false;
        // $jsonArr['msg']=$exception->getMessage();
    }
}
