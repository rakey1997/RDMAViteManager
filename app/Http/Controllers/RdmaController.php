<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

use App\Models\Model\HostModel;
use App\Models\Model\RdmaInfoModel;

use App\Models\Model\ViewRdmaParaModel;

class RdmaController extends Controller
{
    /**
     * Return the RDMA information of a host
     *
     * @param Request $request The request object
     *
     * @return array The JSON response with the RDMA information
     */
    protected function returnRDMA(Request $request){
        // Create an empty JSON array
        $jsonArr = [];
        $cardRelation=[];
        $rdmaRelation=[];
        $cardRdmaRelation=[];

        // Get the host name and whether to return all data from the request parameters
        $host_name = $request->input('host_name');
        $whole = $request->input('whole');

        // Instantiate the ViewRdmaParaModel model for database operations
        $viewRdmaModel = new ViewRdmaParaModel();

        // Fetch the total count of rdma cards for the host
        $total=$viewRdmaModel->where('host_name',$host_name)->count();
        // Return RDMA data for the specified host
        $record=$viewRdmaModel->select('host_id','host_name','card_id','card_name','card_ipv4_addr','card_mac_addr','card_pci_addr','rdma_id','ifname','port','rdma_state','rdma_physical_state','node_type','node_guid','gid','sys_image_guid','adap_state','caps','update_time')
                            ->orderBy('rdma_state','asc')
                            ->where('host_name',$host_name)
                            ->get()
                            ->toArray(); 

        // Check the value of whole
        if ($whole == "true") {
            // Return all RDMA data
            // $cardRelation=$viewRdmaModel->where('host_state',1)->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(card_name)'))->groupBy('host_name')->get()->toArray(); 
            $rdmaRelation=$viewRdmaModel->where('host_state',1)->orderBy('update_time','desc')->select('host_name',DB::raw('group_concat(ifname)'))->groupBy('host_name')->get()->toArray(); 
            $cardRdmaRelation=$viewRdmaModel->where('host_state',1)->orderBy('update_time','desc')->select('host_name','card_name',DB::raw('group_concat(ifname)'))->groupBy('host_name','card_name')->get()->toArray(); 
        } 

        // Check if record is empty
        if (!empty($record)) {
            // Store the RDMA information and the total count in the JSON array
            $jsonArr['rdma'] = $record;
            $jsonArr['total'] = $total;
        } else {
            // Set the rdma and total fields as empty array and 0 respectively
            $jsonArr['rdma'] = [];
            $jsonArr['total'] = 0;
        }

        // Store the card relation information in the 'cardRelation' field of JSON array
        // $jsonArr['card_relation']=$cardRelation;
        $jsonArr['rdma_relation']=$rdmaRelation;
        $jsonArr['card_rdma_relation']=$cardRdmaRelation;
        // Set opCode as true
        $jsonArr['opCode'] = true;

        // Return the JSON array
        return $jsonArr;
    }

     /**
     * Delete a specific RDMA card
     *
     * @param int $id The ID of the RDMA card to be deleted
     * @return array The result of the deletion
     */
    protected function deleteRdma($id){
        $jsonArr = [];
        $host_Info = new HostModel();
        $rdma_Info = new RdmaInfoModel();
        $rdma_Para = new ViewRdmaParaModel();
        $jsonArr['opCode'] = false;

        // Find the RDMA card record
        $rdma_para_match = $rdma_Para->where('rdma_id', $id)->select("host_id", "ifname")->first();

        if (empty($rdma_para_match)) {
            // RDMA card record not found
            $jsonArr['result'] = "cannot find rdma card record";
            return $jsonArr;
        }

        $rdma_name = $rdma_para_match->ifname;
        $host_id = $rdma_para_match->host_id;
        
        // Find the host record with the specified RDMA card
        $host_match = $host_Info->find($host_id);

        if (empty($host_match)) {
            // Host record not found with this RDMA card
            $jsonArr['result'] = "cannot find host record with this rdma card";
            return $jsonArr;
        }

        $password = $host_match->host_login_password;

        $delDriverCmd = 'rdma link del '.$rdma_name;
        $sudoDelDriverCmd = 'echo '.$password.' | sudo -S bash -c "'.$delDriverCmd.'"';

        $ssh_client = getSSHClient($host_match->toArray());
        $client_prompt = is_string($ssh_client) ? $ssh_client : "OK";
        $jsonArr['result'] = $client_prompt;

        if ($client_prompt == "OK") {
            $ssh_client->exec($sudoDelDriverCmd);
            $result = getCmdResult($ssh_client);
            if ($result) {
                // Delete the RDMA card record
                $res = $rdma_Info->destroy($id);
                if ($res >= 1) {
                    $jsonArr['opCode'] = true;
                    $jsonArr['msg'] = 'delete success';
                    $detail = 'del '.$rdma_name.' success';
                } else {
                    $jsonArr['msg'] = 'delete fail';
                    $detail = 'del '.$rdma_name.' fail';
                }
            }
            $jsonArr['result'] = $detail;
        } 

        return $jsonArr;
    }
}