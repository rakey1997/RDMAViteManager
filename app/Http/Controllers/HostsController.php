<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CardsController;

use App\Models\Model\HostModel;
use App\Models\Model\CardModel;
use App\Models\Model\ConfigModel;
use App\Models\Model\ViewRdmaParaModel;

class HostsController extends Controller
{
     /**
     * Return Host information.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function returnHost(Request $request){
        $hostName = $request->input('query');
        $pagenum = $request->input('pagenum');
        $pagesize = $request->input('pagesize');
    
        $skipNum = ($pagenum - 1) * $pagesize;
    
        $host_Info = new HostModel();
        $config_Info = new ConfigModel();
        $rdma_Para = new ViewRdmaParaModel();

        $test_url=$config_Info->where('key', 'KIBANA_URL')->first();

        $jsonArr = [
            'opCode' => true,
            'hosts' => [],
            'hosts_list' => [],
            'rdma_relation' => [],
            'card_rdma_relation' => [],
            'url' => $test_url->value,
            'total' => 0,
        ];
        
        if ($hostName == ''){
            // If hostName is empty, retrieve all records
            $total = $host_Info->count();
            $record = $host_Info->select('id', 'host_name', 'host_ip', 'host_ssh_port', 'host_login_user', 'state', 'update_time')
                ->orderBy('update_time', 'desc')
                ->skip($skipNum)
                ->take($pagesize)
                ->get()
                ->toArray();
        } else {
            // If hostName is provided, retrieve records matching the host name
            $total = $host_Info->where('host_name', 'like', '%'.$hostName.'%')->count();
            $record = $host_Info->where('host_name', 'like', '%'.$hostName.'%')
                ->select('id', 'host_name', 'host_ip', 'host_ssh_port', 'host_login_user', 'state', 'update_time')
                ->orderBy('update_time', 'desc')
                ->skip($skipNum)
                ->take($pagesize)
                ->get()
                ->toArray();
        }
    
        // Retrieve related card and RDMA information
        // $card_relation = $rdma_Para->where('host_state', 1)
        //     ->orderBy('update_time', 'desc')
        //     ->select('host_name', DB::raw('group_concat(card_name)'))
        //     ->groupBy('host_name')
        //     ->get()
        //     ->toArray();
        $hosts_list=$rdma_Para->where('host_state', 1)
                              ->select('host_name')
                              ->distinct()
                              ->orderBy('update_time', 'desc')
                              ->get()
                              ->toArray();
        $rdma_relation = $rdma_Para->where('host_state', 1)
            ->orderBy('update_time', 'desc')
            ->select('host_name', DB::raw('group_concat(ifname)'))
            ->groupBy('host_name')
            ->get()
            ->toArray();
        $card_rdma_relation = $rdma_Para->where('host_state', 1)
            ->orderBy('update_time', 'desc')
            ->select('host_name', 'card_name', DB::raw('group_concat(ifname)'))
            ->groupBy('host_name', 'card_name')
            ->get()
            ->toArray();

        // Prepare the JSON response array
        if (!empty($record)){
            $jsonArr = [
                'opCode' => true,
                'hosts' => $record,
                'hosts_list' => $hosts_list,
                'rdma_relation' => $rdma_relation,
                'card_rdma_relation' => $card_rdma_relation,
                'url' => $test_url->value,
                'total' => $total,
            ];
        } 

        return $jsonArr;
    }

    /**
     * Add a new host to the database.
     *
     * @param  Request  $request
     * @return array
    */
    protected function addHost(Request $request){
        $jsonArr = array();
        $host_Info = new HostModel();
        $data = $request->input();

        $host_match = $host_Info->where('host_name', $data['host_name'])->get();
        $jsonArr['opCode'] = false;
        $client_prompt = "";

        // Check if same hostname already exists
        if (count($host_match) === 0) {
            $ssh_client = getSSHClient($data);
            $client_prompt = is_string($ssh_client) ? $ssh_client : "OK";

            // Check if SSH connection is successful
            if ($client_prompt == "OK") {
                // Create a new record
                $newRecord = $host_Info->create([
                    "host_name" => $data['host_name'],
                    "host_ip" => $data['host_ip'],
                    "host_ssh_port" => $data['host_ssh_port'],
                    'host_login_user' => $data['host_login_user'],
                    'host_login_password' => $data['host_login_password'],
                    'state' => $data['state'],
                ]);
                $res = $newRecord->save();
                if ($res == 1) {
                    $jsonArr['opCode'] = true;
                    $jsonArr['msg'] = 'add record success';
                } else {
                    $jsonArr['msg'] = 'add record fail';
                }
                // Get Card information
                getCardInfo($ssh_client, $newRecord->id);
                // Get RDMA information
                getRDMAInfo($ssh_client, $data['host_name']);
            }
        } else {
            $client_prompt = "Host already exists!";
        }
        $jsonArr['result'] = $client_prompt;
        return $jsonArr;
    }

    /**
     * Update host information in the database.
     *
     * @param  int  $hostID
     * @param  Request  $request
     * @return array
     */
    protected function updateHostAndPass($hostID, Request $request) {
        $jsonArr = array();
        $host_Info = new HostModel();
        $data = $request->input();
        $host_match = $host_Info->find($hostID);
        $jsonArr = [
            'opCode' => false,
            'result' => '',
            'msg' => 'Update fail'
        ];

        // Check if record exists
        if (!empty($host_match)) {
            // Update the record
            $updateData = [
                "host_name" => $data['host_name'],
                "host_ip" => $data['host_ip'],
                "host_ssh_port" => $data['host_ssh_port'],
                'host_login_user' => $data['host_login_user'],
            ];
            // Update host password if provided
            if($data['host_login_password']!=""){
                $updateData['host_login_password'] = $data['host_login_password'];
            }else{
                $data['host_login_password'] =$host_match['host_login_password'];
            }
            $ssh_client = getSSHClient($data);
            $client_prompt = is_string($ssh_client) ? $ssh_client : "OK";

            // Check if SSH connection is successful
            if ($client_prompt == "OK") {
                $res = $host_Info->where('id', $hostID)->update($updateData);

                if ($res == 1) {
                    $jsonArr['opCode'] = true;
                    $jsonArr['msg'] = 'Update success';
                } 
                // Get Card information
                // getCardInfo($ssh_client, $hostID);
            }
        } else {
            $client_prompt = "Record not found.";
        }
        $jsonArr['result'] = $client_prompt;
        return $jsonArr;
    }

    /**
     * Update the state of a host
     *
     * @param int $hostID The ID of the host
     * @param int $state The state to be updated (0 for offline, non-zero for online)
     *
     * @return array The JSON response with the update result
     */
    protected function updateHostState($hostID, $state){
        // Create an empty JSON array
        $jsonArr = [];
        $client_prompt = "Host already exists!";

        // Instantiate the HostModel and CardModel models for database operations
        $hostModel = new HostModel();
        $cardModel = new CardModel();

        // Fetch the host information based on the hostID
        $host_match = $hostModel->where('id',$hostID)->get()->toArray()[0];

        // Get the host name from host_match
        $host_name = '';
        if (!empty($host_match)) {
            $host_name = $host_match['host_name'];
        }

        // Set initial JSON array values
        $jsonArr['opCode'] = false;
        $jsonArr['msg'] = 'update fail';

        // Check if host_match is empty
        if (!empty($host_match)) {
            // If state is 0, set the host's state as 0 (offline)
            if ($state == 0) {
                // Delete all card information of the host
            $cardModel->where('host_id',$hostID)->delete();
                // Update host state as 0
            $hostModel->where('id',$hostID)->update(['state'=>$state]);
                // Set opCode and msg as true and 'update success' respectively
                $jsonArr['opCode'] = true;
                $jsonArr['msg'] = 'update success';
            }
            // If state is non-zero, set the host's state as non-zero (online)
            else {
                // Get the SSH client result by invoking getSSHClient() function
                $ssh_client = getSSHClient($host_match);
                
                // Check the type of ssh_client
                if (is_string($ssh_client)) {
                    // Connection failed, store the connection failure prompt
                    $client_prompt = $ssh_client;
                } else {
                    // Connection successful, get card info by invoking getCardInfo() function
                    getCardInfo($ssh_client, $hostID);
                    
                    // Get RDMA info by invoking getRDMAInfo() function
                    getRDMAInfo($ssh_client, $host_name);
                    
                    // Update host state as non-zero
                    $hostModel->where('id',$hostID)->update(['state'=>$state]);
                    // Set opCode and msg as true and 'update success' respectively
                    $jsonArr['opCode'] = true;
                    $jsonArr['msg'] = 'update success';
                }
            }
        }
        // If host_match is empty, set the client_prompt as "Record not found."
        else {
            $client_prompt = "Record not found.";
        }

        // Store client_prompt in the 'result' field of JSON array
        $jsonArr['result'] = $client_prompt;

        // Return the JSON array
        return $jsonArr;
    }

        /**
     * Delete multiple hosts
     *
     * @param string $ids The IDs of the hosts to be deleted, separated by commas
     * @return array The result of the deletion
     */
    protected function deleteHost($ids){
        $jsonArr = [];

        $host_Info = new HostModel();
        $hostIDs = explode(',', $ids);
        $host_name_list = [];

        foreach ($hostIDs as $hostID) {
            // Find the host record
            $host_match = $host_Info->where('id', $hostID)->select("host_name")->first();

            if (empty($host_match)) {
                // Host record not found
                return response()->json(
                    ['message' => $hostID.' Host Record not found.'],
                    404
                );
            }

            $host_name_list[] = $host_match->host_name;
        }

        // Delete the host records
        $res = $host_Info->destroy($hostIDs);

        if ($res >= 1) {
            $jsonArr['opCode'] = true;
            $jsonArr['msg'] = 'delete success';
            $jsonArr['records'] = $host_name_list;
        } else {
            $jsonArr['opCode'] = false;
            $jsonArr['msg'] = 'delete fail';
            $jsonArr['records'] = [];
        }

        return $jsonArr;
    }
}
