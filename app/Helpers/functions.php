<?php

/*
 * Constants
 */
 define('FILE_NAME_SEP', '-');  //文件名之间的分隔符号
 define('TEST_FILE_PATH', '\/tmp\/');  //保存文件目录
 define('LOGSTASH_SERVER_IP', '192.168.236.52');  //采集文件服务器ip
 define('LOGSTASH_SERVER_USER', 'elk');  //采集文件服务器用户名
 define('LOGSTASH_SERVER_PASSWORD', '1qaz@WSX');  //采集文件服务器密码
 define('LOGSTASH_SERVER_PATH', '\/opt\/logstash\/test_data\/server');  //采集服务器服务端日志保存记录
 define('LOGSTASH_CLIENT_PATH', '\/opt\/logstash\/test_data\/client');  //采集服务器客户端日志保存记录

use phpseclib3\Net\SSH2;

use App\Models\Model\CardModel;
use App\Models\Model\RdmaInfoModel;
use App\Models\Model\RdmaParaModel;

use App\Models\Model\ViewCardModel;
use App\Models\Model\ViewRdmaInfoModel;
use App\Models\Model\ViewRdmaParaModel;

/**
 * Get the command execution result.
 *
 * @param  SSH\Client  $ssh_client
 * @return bool
 */
function getCmdResult($ssh_client){
    $result = $ssh_client->exec('echo $?'); // Execute command to check result

    // Check if the result is equal to 0
    // In many Unix-like systems, a return value of 0 indicates success
    $isSuccess = $result == "0";

    return $isSuccess; // Return the result indicating success or failure
}

/**
* Verify SSH connection using provided credentials.
*
* @param  SSH2  $ssh_client
* @param  string  $hostLoginUser
* @param  string  $host_login_password
* @return bool|string
*/
function sshConnVerify($ssh_client, $hostLoginUser, $host_login_password) {
    try {
        // Attempt SSH login with provided username and password
        if ($ssh_client->login($hostLoginUser, $host_login_password)) {
            return true; // Login successful, return true
        }
        return "Incorrect password, please try again"; // Login failed, return password error message
    } catch (\phpseclib3\Exception\UnableToConnectException $e) {
        return $e->getMessage(); // Connection failed, return connection error message
    }
}

/**
 * Get SSH client object for the provided data.
 *
 * @param  array  $data
 * @return SSH2|string
 */
function getSSHClient($data){
    extract($data);
    // Create SSH client object
    $ssh_client = new SSH2($host_ip, $host_ssh_port);

    // Verify SSH connection
    $verify = sshConnVerify($ssh_client,$host_login_user, $host_login_password);

    if (is_string($verify)){
        return $verify; // Verification failed, return error message
    }

    return $ssh_client; // Verification successful, return SSH client object
}

    /**
     * Get card information.
     *
     * @param  object  $ssh_client  The SSH client object.
     * @param  int  $host_id  The ID of the host.
     * @return void
     */
    function getCardInfo($ssh_client, $host_id){
        $cardInfo = new CardModel();
        $command = 'ip -j -d address show';
        $result = $ssh_client->exec($command);
        $resultJson = json_decode($result, true);

        // If the number of cards in the database is greater than the actual number of cards queried, delete the inconsistencies
        $cardMatch = $cardInfo->where('host_id', $host_id)->pluck('card_name')->toArray();
        $cardDelList = array_diff($cardMatch, array_column($resultJson, 'ifname'));

        if (!empty($cardDelList)) {
            $cardInfo->where('host_id', $host_id)->whereIn('card_name', $cardDelList)->delete();
        }

        foreach ($resultJson as $resultDetail) {
            if (!isset($resultDetail['ifname'])) {
                continue;
            }
            
            $card_name = $resultDetail['ifname'];
            $card_ipv4_addr = "";
            $card_pci_addr = isset($resultDetail['parentdev']) ? $resultDetail['parentdev'] : "Without PCI Address";
            $card_mtu = $resultDetail['mtu'];
            $card_mtu_min = isset($resultDetail['min_mtu']) ? $resultDetail['min_mtu'] : "Without MTU Limit";
            $card_mtu_max = isset($resultDetail['max_mtu']) ? $resultDetail['max_mtu'] : "Without MTU Limit";
            $phys_port_name = isset($resultDetail['phys_port_name']) ? $resultDetail['phys_port_name'] : "False";
            $card_state = $resultDetail['operstate'] == "UP" ? 1 : 0;
            $card_mac_addr = $resultDetail['address'];

            foreach ($resultDetail['addr_info'] as $addrInfo) {
                if ($addrInfo['family'] == "inet") {
                    $card_ipv4_addr = $addrInfo['local'];
                    break;
                }
            }

            $existingCard = $cardInfo->where('host_id', $host_id)
                                    ->where('card_name', $card_name)
                                    ->first();
            // If not in the database, add a new record
            if (!$existingCard) {
                $cardInfo->create([
                    "host_id" => $host_id,
                    "card_name" => $card_name,
                    "card_ipv4_addr" => $card_ipv4_addr,
                    "card_mac_addr" => $card_mac_addr,
                    'card_pci_addr' => $card_pci_addr,
                    'card_mtu' => $card_mtu,
                    'card_mtu_min' => $card_mtu_min,
                    'card_mtu_max' => $card_mtu_max,
                    'phys_port_name' => $phys_port_name,
                    'card_state' => $card_state,
                ])->save();
            } // If already in the original database, update the record
            else {
                $existingCard->update([
                    "host_id" => $host_id,
                    "card_name" => $card_name,
                    "card_ipv4_addr" => $card_ipv4_addr,
                    "card_mac_addr" => $card_mac_addr,
                    'card_pci_addr' => $card_pci_addr,
                    'card_mtu' => $card_mtu,
                    'card_mtu_min' => $card_mtu_min,
                    'card_mtu_max' => $card_mtu_max,
                    'phys_port_name' => $phys_port_name,
                    'card_state' => $card_state,
                ]);
            }
        }
    }

    /**
     * Get the GID (Global ID) information for the given RDMA (Remote Direct Memory Access) name and card IPv4 address.
     *
     * @param $ssh_client The SSH client for executing commands on the remote server.
     * @param $rdma_name The name of the RDMA.
     * @param $card_ipv4_addr The IPv4 address of the card.
     * @return string The GID.
     */
    function getRDMAGidInfo($ssh_client, $rdma_name, $card_ipv4_addr){
        $command = 'ibv_devinfo -d ' . $rdma_name . ' -v | grep -E "' . $card_ipv4_addr . ', RoCE v2" | grep -woE "[[:digit:]]"';
        $gid = $ssh_client->exec($command);
        $result = getCmdResult($ssh_client);
        $gid = ($result) ? $gid : '';
        return $gid;
    }

    /**
     * Get the RDMA (Remote Direct Memory Access) information for the given SSH client and host name.
     *
     * @param $ssh_client The SSH client for executing commands on the remote server.
     * @param $host_name The host name of the server.
     * @return JSON The RDMA information in JSON format.
     */
    function getRDMAInfo($ssh_client, $host_name){
        $rdma_Info = new RdmaInfoModel();
        $rdma_Para = new RdmaParaModel();
        $view_card_Info = new ViewCardModel();
        $view_rdma_Info = new ViewRdmaInfoModel();
        $view_rdma_Para = new ViewRdmaParaModel();

        $command = 'rdma -jd link';
        $result = $ssh_client->exec($command);
        $result_json = json_decode($result, true);

        // Delete existing RDMA information
        $rdma_match = $view_rdma_Para->where('host_name', $host_name)->get()->pluck('rdma_id')->toArray();
        if (!empty($rdma_match)) {
            $res = $rdma_Info->whereIn('rdma_id', $rdma_match)->delete();
            if ($res < 1) {
                return response()->json(['message' => 'Failed to delete existing RDMA records.'], 404);
            }
        }

        // Add RDMA information
        if (!empty($result_json)) {
            foreach ($result_json as $rdma_link_detail) {
                $card_name = $rdma_link_detail['netdev'];
                $record = $view_card_Info->where('host_name', $host_name)
                    ->where('card_name', $card_name)
                    ->select("card_id", "card_name")
                    ->first();

                $res = $rdma_Info->create([
                    "card_id" => $record->card_id,
                    "ifindex" => $rdma_link_detail['ifindex'],
                    "ifname" => $rdma_link_detail['ifname'],
                    "port" => $rdma_link_detail['port'],
                    "phys_port_name" => $card_name,
                    "rdma_state" => $rdma_link_detail['state'],
                    "rdma_physical_state" => $rdma_link_detail['physical_state'],
                ]);

                if ($res === false) {
                    return response()->json(['message' => 'Failed to create RDMA records.'], 404);
                }
            }
        }

        $command = 'rdma -jd dev';
        $result = $ssh_client->exec($command);
        $result_json = json_decode($result, true);

        // Add RDMA parameter information
        if (!empty($result_json)) {
            foreach ($result_json as $rdma_dev_detail) {
                $rdma_name = $rdma_dev_detail['ifname'];
                $record = $view_rdma_Info->where('host_name', $host_name)
                    ->where('ifname', $rdma_name)
                    ->select("rdma_id", "card_name", "card_ipv4_addr")
                    ->first();

                $card_ipv4_addr = $record->card_ipv4_addr;
                $gid = ($card_ipv4_addr != "") ? getRDMAGidInfo($ssh_client, $rdma_name, $card_ipv4_addr) : '';

                $res = $rdma_Para->create([
                    "rdma_id" => $record->rdma_id,
                    "ifindex" => $rdma_dev_detail['ifindex'],
                    "node_type" => $rdma_dev_detail['node_type'],
                    "node_guid" => $rdma_dev_detail['node_guid'],
                    "sys_image_guid" => $rdma_dev_detail['sys_image_guid'],
                    "adap_state" => $rdma_dev_detail['adaptive-moderation'],
                    "caps" => json_encode($rdma_dev_detail['caps']),
                    "gid" => trim($gid),
                ]);

                if ($res === false) {
                    return response()->json(['message' => 'Failed to create RDMA parameter records.'], 404);
                }
            }
        }
    }
