<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Models\Model\ViewCardModel;

class CardsController extends Controller
{
    /**
     * Return the card information of a host
     *
     * @param Request $request The request object
     *
     * @return array The JSON response with the card information
     */
    protected function returnCard(Request $request){
        // Create an empty JSON array
        $jsonArr = [];
        $cardRelation=[];
        // Get the host name and whether to return all data from the request parameters
        $host_name = $request->input('host_name');
        $whole = $request->input('whole');

        // Instantiate the ViewCardModel model for database operations
        $viewCardModel = new ViewCardModel();

        // Fetch the total count of cards for the host
        $total=$viewCardModel->where('host_name',$host_name)->count();

        // Check the value of whole
        if ($whole == "true") {
            // Return card relationship
            $cardRelation=$viewCardModel->where('card_ipv4_addr',"<>","")
                                        ->orderBy('update_time','desc')
                                        ->select('host_name',DB::raw('group_concat(card_name)'))
                                        ->groupBy('host_name')
                                        ->get()
                                        ->toArray();
        } 
        $record=$viewCardModel->select('card_id','host_name','card_name','card_ipv4_addr','card_mac_addr','card_pci_addr','card_mtu','card_mtu_min','card_mtu_max','phys_port_name','card_state','update_time')
                        ->orderBy('create_time','asc')
                        ->where('host_name',$host_name)
                        ->get()
                        ->toArray(); 

        // Check if record is empty
        if (!empty($record)) {
            // Store the card information and the total count in the JSON array
            $jsonArr['cards'] = $record;
            $jsonArr['total'] = $total;
        } else {
            // Set the cards and total fields as empty array and 0 respectively
            $jsonArr['cards'] = [];
            $jsonArr['total'] = 0;
        }

        // Store the card relation information in the 'cardRelation' field of JSON array
        $jsonArr['cardRelation'] = $cardRelation;
        // Set opCode as true
        $jsonArr['opCode'] = true;

        // Return the JSON array
        return $jsonArr;
    }

}
