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

    function replaceDashesWithUnderscores($target,$action,$string) {  
        $string = preg_replace($target,$action, $string);
        // 移除开头结尾的下划线和空格（如果有的话）  
        $string = trim($string, '_');
        $string = trim($string, ' ');
        // 返回处理后的字符串  
        return $string;
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
            'statistic' => $statistic,
            'test_qp_num' => $test_qp_num,
            'test_port_num' => $test_port_num,
            'delay' => $delay,
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
            $ssh_client_server_statistic = new SSH2($host_ip_server,$host_ssh_port_server);
            $ssh_client_server_check = new SSH2($host_ip_server,$host_ssh_port_server);
            $verify_server=sshConnVerify($ssh_client_server,$host_login_user_server,$password_server);
            sshConnVerify($ssh_client_server_statistic,$host_login_user_server,$password_server);
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
            $ssh_client_client_statistic = new SSH2($host_ip_client,$host_ssh_port_client);
            $ssh_client_client_check = new SSH2($host_ip_client,$host_ssh_port_client);
            $verify_client=sshConnVerify($ssh_client_client,$host_login_user_client,$password_client);
            sshConnVerify($ssh_client_client_statistic,$host_login_user_client,$password_client);
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
        $card_name_server=$rdmaTest['server_card_name'];
        $rdma_name_server=$rdmaTest['server_ifname'];
        $rdma_ipv4_server=$rdmaTest['server_card_ipv4_addr'];
        $rdma_mac_addr_server=$rdmaTest['server_card_mac_addr'];

        $card_name_client=$rdmaTest['client_card_name'];
        $rdma_name_client=$rdmaTest['client_ifname'];
        $rdma_ipv4_client=$rdmaTest['client_card_ipv4_addr'];
        $rdma_mac_addr_client=$rdmaTest['client_card_mac_addr'];
        $rdma_cm_name=$rdmaTest['rdma_cm']=='0'?"socket":"rdma_cm";
        $rdma_cm_flag=$rdmaTest['rdma_cm']=='0'?"":"-R ";
        $msg_size_name=$rdmaTest['msg_size']=='0'?"all":$rdmaTest['msg_size'];
        $msg_size_flag=$rdmaTest['msg_size']=='0'?"-a":"-s ".$rdmaTest['msg_size'];
        $replace_more_para = $this->replaceDashesWithUnderscores(array("/\s+/","/--/","/-/"),array("_","", ""),$rdmaTest['more_para']);  
        $more_para_name=$rdmaTest['more_para']==''?"no_extra_para":$replace_more_para;
        $replace_more_para_cmd =$this->replaceDashesWithUnderscores(array('/\s+/'),array(" "),$rdmaTest['more_para']); 
        $more_para_flag=$rdmaTest['more_para']==''?" ":" ".$replace_more_para_cmd." ";
        $name_sep=$this->config_vars['FILE_NAME_SEP'];
        $test_path=$this->config_vars['TEST_FILE_PATH'];
        $test_file_name_wo_path=$test_identifier.$name_sep.$test_pair_id.$name_sep.$test_qp_num.$name_sep.
                                $msg_size_name.$name_sep.$rdma_cm_name.$name_sep.$more_para_name.$name_sep.
                                $test_port_num.$name_sep.$source_num.$name_sep.$delay.$name_sep.$this->cmd.$name_sep.
                                $host_name_server.$name_sep.$rdma_name_server.$name_sep.
                                $host_name_client.$name_sep.$rdma_name_client.$name_sep;
        
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
            $test_file_name_wo_path=$test_file_name_wo_path.$direction_name.$name_sep;
        }

        switch ($this->cmd) {
            case 'ib_atomic_bw':
                // #10.10.10.10上运行
                // sudo ib_atomic_bw -A FETCH_AND_ADD -m 4096 -d mlx5_2 -F -q 1
                // #10.10.10.20上运行
                // sudo ib_atomic_bw -A FETCH_AND_ADD -m 4096 -d mlx5_0 -F -q 1 10.10.10.10
                $command_check_server=$this->cmd.' -F -d '.$rdma_name_server.' -q '.$test_qp_num.' -p '.$test_port_num.$more_para_flag.$rdma_cm_flag.'-m 4096 -A FETCH_AND_ADD'.$direction_flag;
                $command_check_client=$this->cmd.' -F -d '.$rdma_name_client.' -q '.$test_qp_num.' -p '.$test_port_num.$more_para_flag.$rdma_cm_flag.'-m 4096 -A FETCH_AND_ADD '.$direction_flag.$rdma_ipv4_server;
                break;
            case 'ib_atomic_lat':
                // #10.10.10.10上运行
                // sudo ib_atomic_lat -F -d mlx5_2 --report_gbits
                // #10.10.10.20上运行
                // sudo ib_atomic_lat -F -d mlx5_0 --report_gbits 10.10.10.10
                $command_check_server=$this->cmd.' -F -d '.$rdma_name_server.' -p '.$test_port_num.$more_para_flag.$rdma_cm_flag.'--report_gbits';
                $command_check_client=$this->cmd.' -F -d '.$rdma_name_client.' -p '.$test_port_num.$more_para_flag.$rdma_cm_flag.'--report_gbits '.$rdma_ipv4_server;
                break;
            case 'raw_ethernet_bw':
                // 1. 测试10.10.10.10至10.10.10.20方向带宽
                // #10.10.10.10上运行
                // sudo raw_ethernet_bw -d mlx5_2 --client -F -B 10:70:fd:31:ea:dc -E 10:70:fd:31:f3:bc --report_gbits -m 9600 -q 10
                // #10.10.10.20上运行
                // sudo raw_ethernet_bw -d mlx5_0 --client -F -B 10:70:fd:31:f3:bc -E 10:70:fd:31:ea:dc --report_gbits -m 9600 -q 10
                $command_check_server=$this->cmd.' -F -m 9600 -d '.$rdma_name_server.' -p '.$test_port_num.$more_para_flag.'--client --report_gbits'.$direction_flag.' -B '.$rdma_mac_addr_server.' -E '.$rdma_mac_addr_client;
                $command_check_client=$this->cmd.' -F -m 9600 -d '.$rdma_name_client.' -p '.$test_port_num.$more_para_flag.'--client --report_gbits'.$direction_flag.' -E '.$rdma_mac_addr_server.' -B '.$rdma_mac_addr_client;
                break;
            case 'raw_ethernet_lat':
                // 1. 测试10.10.10.10至10.10.10.20方向带宽
                // #10.10.10.10上运行
                // sudo raw_ethernet_lat -d mlx5_2 --server -F -B 10:70:fd:31:ea:dc -E 10:70:fd:31:f3:bc --report_gbits -m 9600
                // #10.10.10.20上运行
                // sudo raw_ethernet_lat -d mlx5_0 --client -F -B 10:70:fd:31:f3:bc -E 10:70:fd:31:ea:dc --report_gbits -m 9600
                $command_check_server=$this->cmd.' -F -m 9600 -d '.$rdma_name_server.' -p '.$test_port_num.$more_para_flag.'--server --report_gbits'.$direction_flag.' -B '.$rdma_mac_addr_server.' -E '.$rdma_mac_addr_client;
                $command_check_client=$this->cmd.' -F -m 9600 -d '.$rdma_name_client.' -p '.$test_port_num.$more_para_flag.'--client --report_gbits'.$direction_flag.' -E '.$rdma_mac_addr_server.' -B '.$rdma_mac_addr_client;
                break;
            default:
                $command_check_server=$this->cmd.' '.$msg_size_flag.' -F -d '.$rdma_name_server.$qp_flag.$direction_flag.$more_para_flag.$rdma_cm_flag.'-p '.$test_port_num.' --report_gbits';
                $command_check_client=$this->cmd.' '.$msg_size_flag.' -F -d '.$rdma_name_client.$qp_flag.$direction_flag.$more_para_flag.$rdma_cm_flag.'-p '.$test_port_num.' --report_gbits '.$rdma_ipv4_server;
                break;
        }
        $test_file_name=$test_path."/".$test_file_name_wo_path;
        $command_server=$command_check_server.' 2>&1 >'.$test_file_name.'server.log'.' &';
        // $command_server=$command_check_server.' 2>&1 >'.$test_file_name.'server.log';
        var_dump($command_server);
        $command_client=$command_check_client.' 2>&1 >'.$test_file_name.'client.log'.' &';
        // $command_client=$command_check_client.' 2>&1 >'.$test_file_name.'client.log';
        var_dump($command_client);

        // record.sh enp177s0f0np0 mlx5_4/1 /tmp/ 20240104100441-1-1704333323-5-18515-1-ib_read_lat-SBL_RDMA01-mlx5_1-SBL_RDMA03-mlx5_1-client- current
        if ($statistic == 1) {
            $command_statistic_server='record.sh '.$card_name_server.' '.$rdma_name_server.'/1'.' '.$test_path.' '.$test_file_name_wo_path.'server'.$name_sep.' ';
            $command_statistic_init_server=$command_statistic_server.'init';
            $command_statistic_current_server=$command_statistic_server.'current';
            $command_statistic_clean_server=$command_statistic_server.'clean';
            
            $command_statistic_client='record.sh '.$card_name_client.' '.$rdma_name_client.'/1'.' '.$test_path.' '.$test_file_name_wo_path.'client'.$name_sep.' ';
            $command_statistic_init_client=$command_statistic_client.'init';
            $command_statistic_current_client=$command_statistic_client.'current';
            $command_statistic_clean_client=$command_statistic_client.'clean';

            var_dump($command_statistic_init_server);
            var_dump($command_statistic_init_client);
        }

        $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'2']);
        $this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"2",0);  //开始测试
        $time_start=microtime(true);
        if($rdma_server_state and $serverCheckPass){
            if ($statistic == 1) {
                $ssh_client_server_statistic->exec($command_statistic_init_server); 
            }
            $ssh_client_server->exec($command_server); 
        }
        
        if($rdma_client_state and $clientCheckPass){
            if ($statistic == 1) {
                $ssh_client_client_statistic->exec($command_statistic_init_client);
            }
            $ssh_client_client->exec($command_client);
        }

        $client_test_result_flag = false;
        $server_test_result_flag = false;

        while(1){
            sleep(5);
            $time_end=microtime(true);
            $seconds=$time_end-$time_start;

            if(! $server_test_result_flag && ($rdma_server_state and $serverCheckPass)){
                $pid_check_server=$ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'|wc -l");
                if($pid_check_server=="2"){
                    if($seconds>6){
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"3",$seconds); //成功
                        if ($statistic == 1) {
                            $server_statistic_result=$ssh_client_server_statistic->exec($command_statistic_current_server); 
                            var_dump("----服务端测试成功，统计数据文件合并入perftest结果-----\n");
                            $server_statistic_file=$test_path.'/'.$test_file_name_wo_path.'server'.$name_sep.'stats_info.log';
                            var_dump("----拼接命令-----\n"."cat '".$server_statistic_file."' >> '".$test_file_name."'server.log");
                            $ssh_client_server_check->exec("cat '".$server_statistic_file."' >> '".$test_file_name."'server.log");
                        }
                        $server_test_result_flag = true;
                        var_dump("----result-----");
                        var_dump($ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'"));
                    }else{
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //太短时间也是异常退出
                    };
                    // break;
                }
            }

            if(! $client_test_result_flag && ($rdma_client_state and $clientCheckPass)){
                $pid_check_client=$ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'|wc -l");
                if($pid_check_client=="2" ){
                    if($seconds>6){
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"3",$seconds); //成功
                        if ($statistic == 1) {
                            $client_statistic_result=$ssh_client_client_statistic->exec($command_statistic_current_client);
                            var_dump("----客户端测试成功，统计数据文件合并入perftest结果-----\n");
                            $client_statistic_file=$test_path.'/'.$test_file_name_wo_path.'client'.$name_sep.'stats_info.log';
                            var_dump("----拼接命令-----\n"."cat '".$client_statistic_file."' >> '".$test_file_name."'client.log");
                            $ssh_client_client_check->exec("cat '".$client_statistic_file."' >> '".$test_file_name."'client.log");
                            // var_dump("----客户端测试成功，统计数据文件-----\n".$client_statistic_result);
                        }
                        $client_test_result_flag = true;
                        var_dump("----result-----");
                        var_dump($ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'"));
                    }else{
                        $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //太短时间也是异常退出
                    };
                    // break;
                }
            }

            if($seconds>3560){
                var_dump("----double kill-----");
                if($rdma_server_state and $serverCheckPass){
                    $ssh_client_server_check->exec("pkill -9 -f '".$command_check_server."'");
                    if ($statistic == 1) {
                        $server_clean_result=$ssh_client_server_statistic->exec($command_statistic_clean_server);
                        var_dump("----服务端测试失败，清除统计数据文件-----\n".$server_clean_result);
                    }
                    var_dump($ssh_client_server_check->exec("pkill -9 -f '".$command_check_server."'"));
                    var_dump("----kill result-----");
                    var_dump($ssh_client_server_check->exec("ps -ef|grep '".$command_check_server."'"));
                }
                if($rdma_client_state and $clientCheckPass){
                    $ssh_client_client_check->exec("pkill -9 -f '".$command_check_client."'");
                    if ($statistic == 1) {
                        $client_clean_result=$ssh_client_client_statistic->exec($command_statistic_clean_client);
                        var_dump("----客户端测试失败，清除统计数据文件-----\n".$client_clean_result);
                    }
                    var_dump($ssh_client_client_check->exec("pkill -9 -f '".$command_check_client."'"));
                    var_dump("----kill result-----");
                    var_dump($ssh_client_client_check->exec("ps -ef|grep '".$command_check_client."'"));
                }
                var_dump("----fail result-----");
                $res=$this->updateTestResult($rdma_test_Info,trim($test_identifier),trim($test_pair_id),"4",$seconds); //失败
                break;
            }

            if($server_test_result_flag && $client_test_result_flag){
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
                break;
            }
        }
        $rdma_test_Info->where('test_identifier',$test_identifier)->where('test_pair_id',$test_pair_id)->update(['test_queue_state'=>'3']);

    }

    //任务失败的处理过程回调函数,打印返回错误信息
    public function failed(\Exception $exception)
    {
    }
}
