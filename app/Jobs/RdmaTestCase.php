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

    public $rdmaTest;
    public $cmd;
    public $jsonArr;
    public $config_vars;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($cmd,$rdmaTest,$config_vars)
    {
        $this->rdmaTest = $rdmaTest;
        $this->cmd = $cmd;
        $this->config_vars=$config_vars;
    }

    /**
     * Updates the test result in the database table
     *
     * @param $rdma_test_Info: The instance of the test info model
     * @param $test_identifier: The test identifier
     * @param $test_pair_id: The test pair id
     * @param $flag: The flag value to update
     * @param $costtime: The cost time value to update
     * @return bool: True if the update was successful, false otherwise
     */
    public function updateTestResult($rdma_test_Info, $test_identifier, $test_pair_id, $flag, $costtime) {
        // An array to store the column names corresponding to each cmd
        $column_names = [
            // cmd => [flag_column, costtime_column]
            
            // ib_send_bw
            "ib_send_bw" => ['rdma_sendbw_flag', 'rdma_sendbw_costtime'],
            
            // ib_read_bw
            "ib_read_bw" => ['rdma_readbw_flag', 'rdma_readbw_costtime'],
            
            // ib_write_bw
            "ib_write_bw" => ['rdma_writebw_flag', 'rdma_writebw_costtime'],
            
            // ib_atomic_bw
            "ib_atomic_bw" => ['rdma_atomicbw_flag', 'rdma_atomicbw_costtime'],
            
            // raw_ethernet_bw
            "raw_ethernet_bw" => ['rdma_ethernetbw_flag', 'rdma_ethernetbw_costtime'],
            
            // ib_send_lat
            "ib_send_lat" => ['rdma_sendlat_flag', 'rdma_sendlat_costtime'],
            
            // ib_read_lat
            "ib_read_lat" => ['rdma_readlat_flag', 'rdma_readlat_costtime'],
            
            // ib_write_lat
            "ib_write_lat" => ['rdma_writelat_flag', 'rdma_writelat_costtime'],
            
            // ib_atomic_lat
            "ib_atomic_lat" => ['rdma_atomiclat_flag', 'rdma_atomiclat_costtime'],
            
            // raw_ethernet_lat
            "raw_ethernet_lat" => ['rdma_ethernetlat_flag', 'rdma_ethernetlat_costtime'],
        ];
        
        // Check if the cmd is valid and update the corresponding columns
        if (isset($column_names[$this->cmd])) {
            // Destructuring the array to get the flag and costtime column names
            [$flag_column, $costtime_column] = $column_names[$this->cmd];
            
            // Updating the columns in the database table
            $res = $rdma_test_Info->where('test_identifier', $test_identifier)
                ->where('test_pair_id', $test_pair_id)
                ->update([$flag_column => $flag, $costtime_column => $costtime]);
            return $res === 1;
        }
        
        // Return false if the cmd is not recognized
        return false;
        }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
        $rdma_test_Info=new RdmaTestModel();
        $rdmaTest=$this->rdmaTest;

        [
            'test_identifier' => $test_identifier,
            'test_pair_id' => $test_pair_id,
            'test_qp_num' => $test_qp_num,
            'test_port_num' => $test_port_num,
            'source_num' => $source_num,
            'server_host_name' => $host_name_server,
            'server_host_ip' => $host_ip_server,
            'server_host_ssh_port' => $host_ssh_port_server,
            'server_host_login_user' => $host_login_user_server,
            'server_host_login_password' => $password_server,
            'client_host_name' => $host_name_client,
            'client_host_ip' => $host_ip_client,
            'client_host_ssh_port' => $host_ssh_port_client,
            'client_host_login_user' => $host_login_user_client,
            'client_host_login_password' => $password_client,
            'rdma_server_state' => $rdma_server_state,
            'rdma_client_state' => $rdma_client_state,
        ] = $rdmaTest;

        $serverCheckPass=false;
        $clientCheckPass=false;

        if($rdma_server_state){
            $ssh_client_server = new SSH2($host_ip_server,$host_ssh_port_server);
            $ssh_client_server_check = new SSH2($host_ip_server,$host_ssh_port_server);
            $verify_server=sshConnVerify($ssh_client_server,$host_login_user_server,$password_server);
            sshConnVerify($ssh_client_server_check,$host_login_user_server,$password_server);
            if(is_string($verify_server)){
                $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'4']);
                var_dump('can not connect to server.');
            }else{
                $serverCheckPass=true;
            }
        }

        if($rdma_client_state){
            $ssh_client_client = new SSH2($host_ip_client,$host_ssh_port_client);
            $ssh_client_client_check = new SSH2($host_ip_client,$host_ssh_port_client);
            $verify_client=sshConnVerify($ssh_client_client,$host_login_user_client,$password_client);
            sshConnVerify($ssh_client_client_check,$host_login_user_client,$password_client);
            if(is_string($verify_client)){
                $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'4']);
                var_dump('can not connect to client.');
            }else{
                $clientCheckPass=true;
            }
        }

        //birection -b
        // server ib_send_bw -a -R -F -d mlx5_2 -R --report_gbits -q 10
        // client ib_send_bw -a -R -F -d mlx5_0 -R --report_gbits -q 10 10.10.10.10
        $rdma_name_server=$rdmaTest['server_ifname'];
        $rdma_ipv4_server=$rdmaTest['server_card_ipv4_addr'];
        $rdma_mac_addr_server=$rdmaTest['server_card_mac_addr'];

        $rdma_name_client=$rdmaTest['client_ifname'];
        $rdma_ipv4_client=$rdmaTest['client_card_ipv4_addr'];
        $rdma_mac_addr_client=$rdmaTest['client_card_mac_addr'];
        $name_sep=$this->config_vars['FILE_NAME_SEP'];

        $test_file_name=$this->config_vars['TEST_FILE_PATH'].$test_identifier.$name_sep.$test_pair_id.$name_sep.$test_qp_num.$name_sep.$test_port_num.$name_sep.$source_num.$name_sep.$this->cmd.$name_sep.$host_name_server.$name_sep.$rdma_name_server.$name_sep.$host_name_client.$name_sep.$rdma_name_client.$name_sep;

        if (stripos($this->cmd,"lat")!==false){
            //测试时延
            $direction_flag=""; 
            $direction_name="";  
            $qp_flag=""; //lat时延测试没有q参数
        }else{
            //测试带宽
            $direction_flag=$rdmaTest['bidirection']==2?"":" -b";   
            $direction_name=$rdmaTest['bidirection']==2?"undirection":"bidirection";  //是否rdma双向测试，2为单向测试，3为双向测试
            $qp_flag=" -q ".$test_qp_num;  //默认采用10
            $test_file_name=$test_file_name.$direction_name.$this->config_vars['FILE_NAME_SEP'];
        }

        switch ($this->cmd) {
            case 'ib_atomic_bw':
                // #10.10.10.10上运行
                // sudo ib_atomic_bw -A FETCH_AND_ADD -m 4096 -d mlx5_2 -F -q 1
                // #10.10.10.20上运行
                // sudo ib_atomic_bw -A FETCH_AND_ADD -m 4096 -d mlx5_0 -F -q 1 10.10.10.10
                $command_check_server=$this->cmd.' -F -d '.$rdma_name_server.' -q '.$test_qp_num.' -p '.$test_port_num.' -m 4096 -A FETCH_AND_ADD'.$direction_flag;
                $command_check_client=$this->cmd.' -F -d '.$rdma_name_client.' -q '.$test_qp_num.' -p '.$test_port_num.' -m 4096 -A FETCH_AND_ADD '.$direction_flag.$rdma_ipv4_server;
                break;
            case 'ib_atomic_lat':
                // #10.10.10.10上运行
                // sudo ib_atomic_lat -F -d mlx5_2 --report_gbits
                // #10.10.10.20上运行
                // sudo ib_atomic_lat -F -d mlx5_0 --report_gbits 10.10.10.10
                $command_check_server=$this->cmd.' -F -d '.$rdma_name_server.' -p '.$test_port_num.' --report_gbits';
                $command_check_client=$this->cmd.' -F -d '.$rdma_name_client.' -p '.$test_port_num.' --report_gbits '.$rdma_ipv4_server;
                break;
            case 'raw_ethernet_bw':
                // 1. 测试10.10.10.10至10.10.10.20方向带宽
                // #10.10.10.10上运行
                // sudo raw_ethernet_bw -d mlx5_2 --client -F -B 10:70:fd:31:ea:dc -E 10:70:fd:31:f3:bc --report_gbits -m 9600 -q 10
                // #10.10.10.20上运行
                // sudo raw_ethernet_bw -d mlx5_0 --client -F -B 10:70:fd:31:f3:bc -E 10:70:fd:31:ea:dc --report_gbits -m 9600 -q 10
                $command_check_server=$this->cmd.' -F -m 9600 -d '.$rdma_name_server.' -p '.$test_port_num.' --client --report_gbits'.$direction_flag.' -B '.$rdma_mac_addr_server.' -E '.$rdma_mac_addr_client;
                $command_check_client=$this->cmd.' -F -m 9600 -d '.$rdma_name_client.' -p '.$test_port_num.' --client --report_gbits'.$direction_flag.' -E '.$rdma_mac_addr_server.' -B '.$rdma_mac_addr_client;
                break;
            case 'raw_ethernet_lat':
                // 1. 测试10.10.10.10至10.10.10.20方向带宽
                // #10.10.10.10上运行
                // sudo raw_ethernet_lat -d mlx5_2 --server -F -B 10:70:fd:31:ea:dc -E 10:70:fd:31:f3:bc --report_gbits -m 9600
                // #10.10.10.20上运行
                // sudo raw_ethernet_lat -d mlx5_0 --client -F -B 10:70:fd:31:f3:bc -E 10:70:fd:31:ea:dc --report_gbits -m 9600
                $command_check_server=$this->cmd.' -F -m 9600 -d '.$rdma_name_server.' -p '.$test_port_num.' --server --report_gbits'.$direction_flag.' -B '.$rdma_mac_addr_server.' -E '.$rdma_mac_addr_client;
                $command_check_client=$this->cmd.' -F -m 9600 -d '.$rdma_name_client.' -p '.$test_port_num.' --client --report_gbits'.$direction_flag.' -E '.$rdma_mac_addr_server.' -B '.$rdma_mac_addr_client;
                break;
            default:
                $command_check_server=$this->cmd.' -a -F -d '.$rdma_name_server.$qp_flag.$direction_flag.' -p '.$test_port_num.' --report_gbits';
                $command_check_client=$this->cmd.' -a -F -d '.$rdma_name_client.$qp_flag.$direction_flag.' -p '.$test_port_num.' --report_gbits '.$rdma_ipv4_server;
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
        if($rdma_server_state and $serverCheckPass){
            $ssh_client_server->exec($command_server); 
        }
        
        if($rdma_client_state and $clientCheckPass){
            $ssh_client_client->exec($command_client);
        }

        $test_result_flag = false;

        while(1){
            sleep(5);
            $time_end=microtime(true);
            $seconds=$time_end-$time_start;

            if($rdma_server_state and $serverCheckPass){
                $pid_check_server=$ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'|wc -l");
                if($pid_check_server=="2"){
                    if($seconds>6){
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"3",$seconds); //成功
                        $test_result_flag = true;
                        var_dump("----result-----");
                        var_dump($ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'"));
                    }else{
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //太短时间也是异常退出
                    };
                    break;
                }
            }

            if($rdma_client_state and $clientCheckPass){
                $pid_check_client=$ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'|wc -l");
                if($pid_check_client=="2" ){
                    if($seconds>6){
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"3",$seconds); //成功
                        $test_result_flag = true;
                        var_dump("----result-----");
                        var_dump($ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'"));
                    }else{
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //太短时间也是异常退出
                    };
                    break;
                }
            }

            if($seconds>3560){
                var_dump("----double kill-----");
                if($rdma_server_state and $serverCheckPass){
                    $ssh_client_server_check->exec("pkill -9 -f '".$command_check_server."'");
                    var_dump($ssh_client_server_check->exec("pkill -9 -f '".$command_check_server."'"));
                    var_dump("----kill result-----");
                    var_dump($ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'"));
                }
                if($rdma_client_state and $clientCheckPass){
                    $ssh_client_client_check->exec("pkill -9 -f '".$command_check_client."'");
                    var_dump($ssh_client_client_check->exec("pkill -9 -f '".$command_check_client."'"));
                    var_dump("----kill result-----");
                    var_dump($ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'"));
                }
                var_dump("----fail result-----");
                $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //失败
                break;
            }
        }
        $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'3']);

        if($test_result_flag){
            if($this->config_vars['LOGSTASH_ENABLE']==="true"){
                var_dump('logstash');
                // sshpass -p "1qaz@WSX" scp /tmp/20230628151550-1-1687936468-ib_atomic_bw-SBL_RDMA03-rxe_0-SBL_RDMA04-rxe_0-undirection-server.log elk@192.168.221.37:/opt/logstash/test_data/server
                $uploadServerCmd="sshpass -p \LOGSTASH_SERVER_PASSWORD scp -o StrictHostKeyChecking=no ".$test_file_name.'server.log '.$this->config_vars['LOGSTASH_SERVER_USER']."@".$this->config_vars['LOGSTASH_SERVER_IP'].":".$this->config_vars['LOGSTASH_SERVER_PATH'];
                $uploadClientCmd="sshpass -p \LOGSTASH_SERVER_PASSWORD scp -o StrictHostKeyChecking=no ".$test_file_name.'client.log '.$this->config_vars['LOGSTASH_SERVER_USER']."@".$this->config_vars['LOGSTASH_SERVER_IP'].":".$this->config_vars['LOGSTASH_CLIENT_PATH'];
                var_dump($uploadServerCmd);
                var_dump($uploadClientCmd);
                if($rdma_server_state and $serverCheckPass){
                    $ssh_client_server_check->exec($uploadServerCmd);
                }
                if($rdma_client_state and $clientCheckPass){
                    $ssh_client_client_check->exec($uploadClientCmd);
                }
            }
        }

    }

    //任务失败的处理过程回调函数,打印返回错误信息
    public function failed(\Exception $exception)
    {
    }
}
