<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Model\HostModel;
use App\Models\Model\RdmaTestRelationModel;
use App\Models\Model\RdmaTestModel;
use App\Models\Model\ConfigModel;

use App\Models\Model\ViewRdmaParaModel;
use App\Models\Model\ViewRdmaTestModel;

use App\Jobs\RdmaTestCase;

class TestsController extends Controller
{
    /**
     * Return test menu
     *
     * @param \Illuminate\Http\Request $request The request object
     * @return array The test menu records
     */
    protected function returnTestMenu(Request $request){
        $jsonArr = [];

        $rdma_Info = new ViewRdmaParaModel();
        $relations = $rdma_Info->where('host_state', 1)
            ->where('card_ipv4_addr', "<>", "")
            ->orderBy('host_name', 'asc')
            ->orderBy('card_name', 'asc')
            ->select('rdma_id', 'host_name', 'card_name', 'ifname', 'card_ipv4_addr', 'gid')
            ->get()
            ->toArray();

        $jsonArr['opCode'] = true;
        $jsonArr['records'] = $relations;
        $jsonArr['msg'] = 'query success';

        return $jsonArr;
    }

    /**
     * Perform a testTQ operation.
     *
     * @param Request $request The request object.
     * @return array The result of the test operation.
     */
    protected function testTQ(Request $request){
        // Initialize the result array
        $jsonArr = [];

        // Create instances of the HostModel and RdmaTestRelationModel
        $hostInfo = new HostModel();
        $rdmaTestRelation = new RdmaTestRelationModel();

        // Set the initial value of opCode to false
        $jsonArr['opCode'] = false;

        // Retrieve the input data from the request
        $data = $request->input();
        $serverInfo = $data['server'];
        $serverState = $data['serverState'];
        $rdmaInfoServer = explode(',', $serverInfo[2]);
        $clientInfo = $data['client'];
        $clientState = $data['clientState'];
        $rdmaInfoClient = explode(',', $clientInfo[2]);
        $serverPrompt = "Can not find server info, please check ...";
        $clientPrompt = "Can not find client info, please check ...";

        if($serverState and $clientState){
            // Find the server host info
            $hostMatchServer = $hostInfo->where('host_name', $serverInfo[0])->first()->toArray();

            if ($hostMatchServer) {
                // Get SSH client for the server
                $sshClientServer = getSSHClient($hostMatchServer);
                $serverPrompt = is_string($sshClientServer) ? $sshClientServer : "OK";
            }

            // Find the client host info
            $hostMatchClient = $hostInfo->where('host_name', $clientInfo[0])->first()->toArray();
            if ($hostMatchClient) {
                // Get SSH client for the client
                $sshClientClient = getSSHClient($hostMatchClient);
                $clientPrompt = is_string($sshClientClient) ? $sshClientClient : "OK";
            }

            if ($serverPrompt === "OK" && $clientPrompt === "OK") {
                // server ibv_rc_pingpong -d mlx5_0 -g 3 
                // client ibv_rc_pingpong -d pclr_0 -g 1 10.10.10.10
                // Construct the server and client commands
                $commandServer = 'ibv_rc_pingpong -d ' . $rdmaInfoServer[1] . ' -n 3 -g ' . $rdmaInfoServer[2];
                $commandClient = 'ibv_rc_pingpong -d ' . $rdmaInfoClient[1] . ' -n 3 -g ' . $rdmaInfoClient[2] . ' ' . $rdmaInfoServer[3];

                // Execute the server command
                $sshClientServer->enablePTY();
                $sshClientServer->exec($commandServer);
                $sshClientServer->setTimeout(10);

                // Execute the client command
                $sshClientClient->enablePTY();
                $sshClientClient->exec($commandClient);
                $sshClientClient->setTimeout(10);

                if ($sshClientServer->isTimeout() || $sshClientClient->isTimeout()) {
                    // If the commands have timed out, send a cancel signal
                    $sshClientServer->write("\x03");
                    $sshClientClient->write("\x03");
                } else {
                    // Read the command result
                    $cmdRes = $sshClientClient->read();
                    $resultValid = stripos($cmdRes, "3 iters in");
                    if ($resultValid) {
                        // Create a relation in the database if the result is valid
                        $relationCreated = $rdmaTestRelation->create([
                            "test_pair_id" => $data['test_pair_id'],
                            "rdma_id_server" => $rdmaInfoServer[0],
                            "rdma_server_state" =>$serverState,
                            "rdma_id_client" => $rdmaInfoClient[0],
                            "rdma_client_state" => $clientState,
                        ])->save();
                        if ($relationCreated) {
                            $jsonArr['opCode'] = true;
                            $jsonArr['msg'] = 'Test Pair Connect Success';
                        }
                    } else {
                        $jsonArr['msg'] = 'Test Pair Connect fail, please check';
                    }
                    $jsonArr['result'] = $cmdRes;
                }
            } else {
                $jsonArr['result'] = 'Server: ' . $serverPrompt . ';;' . 'Client: ' . $clientPrompt;
            }
        }else{
            $relationCreated = $rdmaTestRelation->create([
                "test_pair_id" => $data['test_pair_id'],
                "rdma_id_server" => $rdmaInfoServer[0],
                "rdma_server_state" =>$serverState,
                "rdma_id_client" => $rdmaInfoClient[0],
                "rdma_client_state" => $clientState,
            ])->save();
            $jsonArr['opCode'] = true;
            $jsonArr['msg'] = 'Test Pair Create Directly';
            $jsonArr['result'] = 'Only Create record and no need test';
        }
        
        // Return the result array
        return $jsonArr;
    }

    /**
     * Add Test Pair
     * Add test pair information to the database.
     * This function receives a JSON request containing information about the test pair,
     * such as test items, test hosts, test count, qp number, directions, and test queue.
     * It saves the test information in the rdma_test_Info table.
     * The function returns a JSON response indicating the success or failure of the operation.
     *
     * @param  Request  $request  The JSON request containing the test pair information
     * @return array              JSON response indicating the status and result of the operation
     */
    protected function addTQ(Request $request){
        // Initialize response JSON array
        $jsonArr = [
            'opCode' => false,
            'result' => 'Add Test Pair Fail',
            'msg' => 'add test pair info fail'
        ];

        // Create instances of RdmaTestModel and RdmaTestRelationModel
        $rdma_test_Info = new RdmaTestModel();
        $rdma_test_relation = new RdmaTestRelationModel();

        // Get input data from request
        $data = $request->input();
        $testItems = $data['testItems'];
        $testHosts = $data['testHosts'];
        $statistic = $data['statistic'];
        $testCount = $data['testCount'];
        $qpNum = $data['qpNum'];
        $conPort = $data['conPort'];
        $directions = $data['directions'];
        $rdma_cm = $data['rdma_cm'];
        $msg_size = $data['msgSize'];
        $more_para = $data['more_para'];
        $delay = $data['delay'];
        $testQueue = $data['testQueue'];
        $sourceNum = $data['sourceNum'];

        // Set flags based on selected test items
        $rdma_sendbw_flag = in_array('ib_send_bw', $testItems) ? 1 : 0;
        $rdma_readbw_flag = in_array('ib_read_bw', $testItems) ? 1 : 0;
        $rdma_writebw_flag = in_array('ib_write_bw', $testItems) ? 1 : 0;
        $rdma_atomicbw_flag = in_array('ib_atomic_bw', $testItems) ? 1 : 0;
        $rdma_ethernetbw_flag = in_array('raw_ethernet_bw', $testItems) ? 1 : 0;

        $rdma_sendlat_flag = in_array('ib_send_lat', $testItems) ? 1 : 0;
        $rdma_readlat_flag = in_array('ib_read_lat', $testItems) ? 1 : 0;
        $rdma_writelat_flag = in_array('ib_write_lat', $testItems) ? 1 : 0;
        $rdma_atomiclat_flag = in_array('ib_atomic_lat', $testItems) ? 1 : 0;
        $rdma_ethernetlat_flag = in_array('raw_ethernet_lat', $testItems) ? 1 : 0;

        $flag = 1;

        // Iterate through testHosts array
        foreach ($testHosts as $test_pair_id) {
            // Retrieve test pair information from rdma_test_relation table
            $test_relation = $rdma_test_relation->where('test_pair_id', $test_pair_id)
                ->select('rdma_id_server', 'rdma_id_client')->get()->toArray();

            // Iterate through testCount
            for ($no = 1; $no <= $testCount; $no++) {
                // Generate test_identifier
                $test_identifier = date('YmdHis', time()) . '-' . $no;

                // Create and save test information in rdma_test_Info table
                $res = $rdma_test_Info->create([
                    "test_identifier" => $test_identifier,
                    "test_pair_id" => $test_pair_id,
                    "test_count_no" => $no,
                    "test_qp_num" => $qpNum,
                    "test_port_num" => $conPort,
                    "bidirection" => $directions ? 3 : 2,
                    "statistic" => $statistic ? 1 : 0,
                    "rdma_cm" => $rdma_cm ? 1 : 0,
                    "msg_size" => $msg_size,
                    "more_para" => $more_para,
                    "delay" => $delay,
                    "rdma_id_server" => $test_relation[0]['rdma_id_server'],
                    "rdma_id_client" => $test_relation[0]['rdma_id_client'],
                    "test_queue" => $testQueue,
                    "source_num" => $sourceNum,
                    "test_queue_state" => "0",
                    "rdma_sendbw_flag" => $rdma_sendbw_flag,
                    "rdma_readbw_flag" => $rdma_readbw_flag,
                    "rdma_writebw_flag" => $rdma_writebw_flag,
                    "rdma_atomicbw_flag" => $rdma_atomicbw_flag,
                    "rdma_ethernetbw_flag" => $rdma_ethernetbw_flag,
                    "rdma_sendlat_flag" => $rdma_sendlat_flag,
                    "rdma_readlat_flag" => $rdma_readlat_flag,
                    "rdma_writelat_flag" => $rdma_writelat_flag,
                    "rdma_atomiclat_flag" => $rdma_atomiclat_flag,
                    "rdma_ethernetlat_flag" => $rdma_ethernetlat_flag,
                ])->save();

                // Update flag based on successful save operation
                if ($res == 1) {
                    $flag++;
                }
                $flag--;
            }
        }

        // Update response JSON array based on flag
        if ($flag == 1) {
            $jsonArr['opCode'] = true;
            $jsonArr['result'] = 'Add Test Pair Success';
            $jsonArr['msg'] = 'add test pair info success';
        }

        return $jsonArr;
    }

    /**
     * Delete Test Queue
     * Delete test queue records from the database.
     * This function receives a JSON request containing the test identifiers and test pair IDs to be deleted.
     * It first checks if the test pair records exist in the rdma_test_Info table.
     * If the records are found, it deletes them from the table.
     * The function returns a JSON response indicating the success or failure of the operation.
     *
     * @param  Request  $request  The JSON request containing the test identifiers and test pair IDs
     * @return array              JSON response indicating the status and result of the operation
     */
    protected function deleteTQ(Request $request){
        // Initialize response JSON array and TQ_list array
        $jsonArr = [];
        $TQ_list = [];
        $rdma_test_Info = new RdmaTestModel();

        // Get input data from request
        $data = $request->input();
        $TQ_Info_List = $data['id_arr'];

        // Initialize flag
        $flag = false;

        // Iterate through TQ_Info_List array
        foreach ($TQ_Info_List as $TQ_Info) {
            // Check if test identifier and test pair ID exist in rdma_test_Info table
            $TQ_match = $rdma_test_Info->where('test_identifier', $TQ_Info[0])
                ->where('test_pair_id', $TQ_Info[1])->get()->toArray();

            // If no match found, return error response
            if (empty($TQ_match)) {
                return response()->json(
                    ['message' => "Test_identifier {$TQ_Info[0]} test_pair_id {$TQ_Info[1]} Test Pair Record not found."],
                    404
                );
                $flag = true;
            } else {
                // Add matched IDs to TQ_list array
                foreach ($TQ_match as $TQ_each) {
                    $TQ_list[] = $TQ_each['id'];
                }
            }
        }

        // If flag is true, return error response
        if ($flag) {
            return response()->json(
                ['message' => 'please check record..'],
                404
            );
        } else {
            // Delete test records from rdma_test_Info table
            $res = $rdma_test_Info->destroy($TQ_list);

            // Update response JSON array based on delete result
            if ($res >= 1) {
                $jsonArr['opCode'] = true;
                $jsonArr['result'] = 'delete success';
            } else {
                $jsonArr['opCode'] = false;
                $jsonArr['result'] = 'delete fail';
            }

            return $jsonArr;
        }
    }

    /**
     * Unpack and execute the RDMA tests.
     *
     * @param array $rdmaTestList The list of RDMA tests.
     * 
     * @return int The total number of tests started.
     */
    private function unpackAndExecute($rdmaTestList,$config_vars){
        $startFlag = 0;
        $needTestFlag = "1";
        $tests = [
            'rdma_sendbw_flag' => 'ib_send_bw',
            'rdma_readbw_flag' => 'ib_read_bw',
            'rdma_writebw_flag' => 'ib_write_bw',
            'rdma_atomicbw_flag' => 'ib_atomic_bw',
            'rdma_ethernetbw_flag' => 'raw_ethernet_bw',
            'rdma_sendlat_flag' => 'ib_send_lat',
            'rdma_readlat_flag' => 'ib_read_lat',
            'rdma_writelat_flag' => 'ib_write_lat',
            'rdma_atomiclat_flag' => 'ib_atomic_lat',
            'rdma_ethernetlat_flag' => 'raw_ethernet_lat',
        ];

        foreach ($rdmaTestList as $rdmaTest) {
            $testQueue = $rdmaTest['test_queue'];

            foreach ($tests as $flag => $cmd) {
                if ($rdmaTest[$flag] == $needTestFlag) {
                    ++$startFlag;
                    RdmaTestCase::dispatch($cmd, $rdmaTest,$config_vars)->onQueue($testQueue);
                }
            }
        }

        return $startFlag;
    }

    /**
     * Execute the RDMA test based on the request.
     *
     * @param Request $request The HTTP request.
     * 
     * @return array The JSON response.
     */
    protected function executeTest(Request $request){
        $jsonArr = array();
        $rdmaTestInfo = new RdmaTestModel();
        $viewRdmaTestInfo = new ViewRdmaTestModel();
        $data = $request->input();

        $tqInfoList = $data['id_arr'];
        $total = 0;
        $startFlag = 0;
        $config_vars=getConfigPara();
        
        // No specific test IDs provided, execute all tests in test_queue_state 0
        if (empty($tqInfoList)) {
            $rdmaTestList = $viewRdmaTestInfo->where('test_queue_state', '0')->get()->toArray();
            $rdmaTestInfo->where('test_queue_state', '0')->update(['test_queue_state' => '1']);
            $total = $this->unpackAndExecute($rdmaTestList,$config_vars);
        } else {
            // Execute tests with specific test IDs
            foreach ($tqInfoList as $tqInfo) {
                $rdmaTestList = $viewRdmaTestInfo->where('test_identifier', $tqInfo[0])
                    ->where('test_pair_id', $tqInfo[1])
                    ->where('test_queue_state', '0')->get()->toArray();
                $startFlag = $this->unpackAndExecute($rdmaTestList,$config_vars);
                $total += $startFlag;

                $rdmaTestInfo->where('test_identifier', $tqInfo[0])
                    ->where('test_pair_id', $tqInfo[1])->update(['test_queue_state' => '1']);
            }
        }

        $jsonArr['opCode'] = true;
        $jsonArr['result'] = $total . ' Test Items Add to Test Queue Successfully';
        return $jsonArr;
    }

    /**
     * Return test result
     *
     * @param Request $request
     * @return array
     */
    protected function returnTestResult(Request $request){
        $jsonArr = []; // Initialize an empty array for the JSON response
        $rdmaTestInfo = new ViewRdmaTestModel(); // Create a new instance of ViewRdmaTestModel

        $flag = $request->input('flag'); // Get the test url query flag from the request
        $queryItem = $request->input('query'); // Get the query parameter from the request
        $pagenum = $request->input('pagenum'); // Get the pagenum parameter from the request
        $pageSize = $request->input('pagesize'); // Get the pagesize parameter from the request

        $skipNum = ($pagenum - 1) * $pageSize; // Calculate the number of records to skip

        if($flag){
            $config_Info = new ConfigModel();
            $test_url=$config_Info->where('key', 'KIBANA_URL')->first();
            $jsonArr['url'] = $test_url->value; // Add test url to the JSON response
        }

        $query = $rdmaTestInfo
            ->when($queryItem, function ($query, $queryItem) {
                // Perform a dynamic query based on a query parameter
                $query->where(function ($subQuery) use ($queryItem) {
                    $subQuery->where('test_identifier', 'like', '%' . $queryItem . '%')
                        ->orWhere('test_pair_id', 'like', '%' . $queryItem . '%')
                        ->orWhere('server_host_name', 'like', '%' . $queryItem . '%')
                        ->orWhere('client_host_name', 'like', '%' . $queryItem . '%');
                });
            });

        $total = $query->count(); // Get the total number of records
        $records = $query->select([
            // Select specific columns from the query
            'test_identifier', 'test_pair_id', 'test_count_no', 'test_queue', 'test_queue_state',
            'bidirection','statistic', 'test_qp_num', 'test_port_num','rdma_cm','msg_size','more_para','delay','source_num',
            'rdma_server_state','server_host_name', 'server_card_name', 'server_card_ipv4_addr','server_card_mac_addr', 'server_ifname', 'server_gid', 
            'rdma_client_state','client_host_name', 'client_card_name', 'client_card_ipv4_addr', 'client_card_mac_addr', 'client_ifname', 'client_gid', 
            'rdma_sendbw_flag', 'rdma_sendbw_costtime', 'rdma_readbw_flag', 'rdma_readbw_costtime', 'rdma_writebw_flag', 'rdma_writebw_costtime', 
            'rdma_atomicbw_flag', 'rdma_atomicbw_costtime', 'rdma_ethernetbw_flag', 'rdma_ethernetbw_costtime', 
            'rdma_sendlat_flag', 'rdma_sendlat_costtime', 'rdma_readlat_flag', 'rdma_readlat_costtime', 'rdma_writelat_flag', 'rdma_writelat_costtime', 
            'rdma_atomiclat_flag', 'rdma_atomiclat_costtime', 'rdma_ethernetlat_flag', 'rdma_ethernetlat_costtime', 'update_time'
        ])
            ->orderBy('update_time', 'desc')
            ->skip($skipNum)
            ->take($pageSize)
            ->get()
            ->toArray();

        $jsonArr['pagenum'] = $pagenum; // Add pagenum to the JSON response
        $jsonArr['pagesize'] = $pageSize; // Add pagesize to the JSON response
        $jsonArr['total'] = $total; // Add total number of records to the JSON response
        $jsonArr['record'] = empty($records) ? [] : $records; // Add records to the JSON response (empty array if no records found)
        $jsonArr['opCode'] = true; // Add operation code to the JSON response

        return $jsonArr; // Return the JSON response
    }
}
