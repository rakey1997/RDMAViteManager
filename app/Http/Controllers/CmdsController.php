<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Model\HostModel;
use App\Models\Model\CardModel;

use App\Models\Model\RdmaInfoModel;
use App\Models\Model\RdmaParaModel;

use App\Models\Model\ViewCardModel;
use App\Models\Model\ViewRdmaInfoModel;
use App\Models\Model\ViewRdmaParaModel;

class CmdsController extends Controller
{
    /**
     * Check if the specified rdma driver is loaded
     * 
     * @param object $sshClient - SSH client object
     * @param string $driverType - The rdma driver type
     * @return bool - True if the driver is loaded, false otherwise
     */
    protected function driverLoad($sshClient, $driverType) {
        $command = 'lsmod|grep -w "rdma_'.$driverType.'\s'.'"';
        return $sshClient->exec($command) ? true : false;
    }

        /**
     * Get single RDMA information for a given host and RDMA name
     *
     * @param Object $ssh_client The SSH client object
     * @param string $host_name The name of the host
     * @param string $rdma_name The name of the RDMA
     * @return void
     */
    public function getSingleRDMAInfo($ssh_client, $host_name, $rdma_name){
        $rdma_Info = new RdmaInfoModel();
        $rdma_Para = new RdmaParaModel();
        $view_card_Info = new ViewCardModel();
        $view_rdma_Info = new ViewRdmaInfoModel();
        $view_rdma_Para = new ViewRdmaParaModel();

        $command = 'rdma -jdp link show '.$rdma_name.'/1';
        $result = $ssh_client->exec($command);
        $result_json = json_decode($result, true);

        // Add information
        if ($result_json){
            foreach ($result_json as $rdma_link_detail){
                $card_name = $rdma_link_detail['netdev'];
                $record = $view_card_Info->where('host_name', $host_name)->where('card_name', $card_name)->select("card_id", "card_name")->first();
                if (!$record) {
                    return response()->json(
                        ['message' => 'Card not found.'],
                        404
                    );
                }

                $res = $rdma_Info->create([
                    "card_id" => $record["card_id"], 
                    "ifindex" => $rdma_link_detail['ifindex'], 
                    "ifname" => $rdma_link_detail['ifname'], 
                    "port" => $rdma_link_detail['port'], 
                    "phys_port_name" => $card_name, 
                    "rdma_state" => $rdma_link_detail['state'], 
                    "rdma_physical_state" => $rdma_link_detail['physical_state']
                ])->save();

                if ($res === false){
                    return response()->json(
                        ['message' => 'Record Add occur error.'],
                        404
                    );
                }
            }
        }

        $command = 'rdma -jd dev show ' . $rdma_name;
        $result = $ssh_client->exec($command);
        $result_json = json_decode($result, true);

        // Add information
        if ($result_json){
            foreach ($result_json as $rdma_dev_detail){
                $rdma_name = $rdma_dev_detail['ifname'];
                $record = $view_rdma_Info->where('host_name', $host_name)->where('ifname', $rdma_name)->select("rdma_id", "card_name", "card_ipv4_addr")->first();
                $card_ipv4_addr = $record["card_ipv4_addr"];
                $gid = "";
                if($card_ipv4_addr != ""){
                    $gid = getRDMAGidInfo($ssh_client, $rdma_name, $card_ipv4_addr);
                }

                $res = $rdma_Para->create([
                    "rdma_id" => $record["rdma_id"], 
                    "ifindex" => $rdma_dev_detail['ifindex'], 
                    "node_type" => $rdma_dev_detail['node_type'], 
                    "node_guid" => $rdma_dev_detail['node_guid'], 
                    "sys_image_guid" => $rdma_dev_detail['sys_image_guid'], 
                    "adap_state" => $rdma_dev_detail['adaptive-moderation'], 
                    "caps" => json_encode($rdma_dev_detail['caps']), 
                    "gid" => trim($gid) 
                ])->save();

                if ($res === false){
                    return response()->json(
                        ['message' => 'Record Add occur error.'],
                        404
                    );
                }
            }
        }
    }

    /**
     * Execute SSH command from SSH client
     * 
     * @param Request $request - HTTP request object
     * @return array - JSON response array
     */
    public function sshExcuteFromSSH(Request $request) {
        $jsonArr = array();
        $data = $request->input();
        $hostName = $data['host_name'];
        $hostInfo = new HostModel();
        $cardInfo = new CardModel();
        
        $hostMatch = $hostInfo->where('host_name', $hostName)->first()->toArray();
        if (!empty($hostMatch)) {
            $hostId = $hostMatch['id'];
            $password = $hostMatch['host_login_password'];
            $sshClient = getSSHClient($hostMatch);
            $clientPrompt = is_string($sshClient) ? $sshClient : "OK";
        } else {
            $clientPrompt = "Can not find Host Connection Info!";
        }

        $cmd = $data['cmd'];

        $jsonArr['opCode'] = false;
        $jsonArr['msg'] = 'operation fail';

        if ($clientPrompt == "OK") {
            switch ($cmd) {
                case "modifyMtu":
                    // Modify MTU information
                    $cardName = $data['card_name'];
                    $cardMtu = $data['card_mtu'];
                    $command = 'echo ' . $cardMtu . ' >/sys/class/net/' . $cardName . '/mtu';
                    $sudoCommand = 'echo ' . $password . ' | sudo -S bash -c "' . $command . '"';
                    $sshClient->exec($sudoCommand);
                    $result = getCmdResult($sshClient);
                    $detail = $result ? "excute command success" : "excute command fail";
                    if ($result) {
                        $res = $cardInfo->where('host_id', $hostId)->where('card_name', $cardName)->update([
                            "card_mtu" => $cardMtu,
                        ]);
                        if ($res == 1) {
                            $jsonArr['opCode'] = true;
                            $jsonArr['msg'] = 'update success';
                        }
                    }
                    break;
                case "addRdmaDev":
                    // Add Rdma driver
                    $cardName = $data['card_name'];
                    $driverType = $data['driver_type'];
                    $rdmaName = $data['rdma_name'];

                    $addDriverCmd = 'modprobe rdma_' . $driverType;
                    $sudoAddDriverCmd = 'echo ' . $password . ' | sudo -S bash -c "' . $addDriverCmd . '"';

                    $addDriverDevCmd = 'rdma link add ' . $rdmaName . ' type ' . $driverType . ' netdev ' . $cardName;
                    $sudoAddDriverDevCmd = 'echo ' . $password . ' | sudo -S bash -c "' . $addDriverDevCmd . '"';
                    if ($this->driverLoad($sshClient, $driverType)) {
                        $cmdRes = $sshClient->exec($sudoAddDriverDevCmd);
                    } else {
                        $sshClient->exec($sudoAddDriverCmd);
                        if ($this->driverLoad($sshClient, $driverType)) {
                            $cmdRes = $sshClient->exec($sudoAddDriverDevCmd);
                        } else {
                            return response()->json(
                                ['message' => 'Can not Load rdma_'.$driver_type.',please check driver module']
                            , 404);
                        };
                    }
                    $result = stripos($cmdRes, "error");
                    $detail = ($result === false) ? 'add ' . $rdmaName . ' success' : 'add ' . $rdmaName . ' fail';
                    if (!$result) {
                        $this->getSingleRDMAInfo($sshClient, $hostName, $rdmaName);
                        $jsonArr['opCode'] = true;
                        $jsonArr['msg'] = 'add device success';
                    }
                    break;
                case "nCmd":
                    // Execute normal command
                    $command = $data['query'];
                    $detail = $sshClient->exec($command);
                    $result = $sshClient->exec('echo $?');
                    $jsonArr['opCode'] = true;
                    $jsonArr['cmdState'] = trim($result);
                    $jsonArr['msg'] = ($result == "0") ? 'operation success' : 'operation failed';
                    break;
                case "sCmd":
                    // Execute sudo command
                    $command = $data['query'];
                    $sudoCommand = 'echo ' . $password . ' | sudo -S bash -c "' . $command . '"';
                    $detail = $sshClient->exec($sudoCommand);
                    $result = $sshClient->exec('echo $?');
                    $jsonArr['opCode'] = true;
                    $jsonArr['cmdState'] = trim($result);
                    $jsonArr['msg'] = ($result == "0") ? 'operation success' : 'operation failed';
                    break;
                default:
                    // Other cases
            }
            $jsonArr['result'] = $detail;
        } else {
            $jsonArr['result'] = $clientPrompt;
        }
        
        return $jsonArr;
    }
}
