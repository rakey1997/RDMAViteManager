export default {
    route: {
        Dashboard: "Home page",
        hostConfig: "Server Config Management",
        cardConfig: "Card Config Management",
        rdmaConfig: "RDMA Config Management",
        hostCmdExcute: "Command Control Center",
        rdmaTest: "RDMA Test",
        rdmaTestShow: "RDMA Test Operation",
        userConfig: "User Settings",
    },
    login: {
        title: "RDMA Configuration Management System",
        btnTitle: "Login",
        warnUserNull: "User name cannot be empty",
        warnUserLen: "The username length needs to be between 3 and 12 digits",
        warnPasswordNull: "Password cannot be empty",
        warnPasswordLen: "Password length needs to be between 8 and 16 bits",
    },
    hostConfig: {
        hostName: "Server Name",
        hostIp: "Server IP",
        hostSSHPort: "Server SSH port number",
        hostLoginUser: "Server SSH login user",
        password: "Server SSH login password",
        confirmPass: "Please re-enter the server SSH login password",
    },
    hostConfigForm: {
        hostIp: "Please enter the server IP addr",
        hostSSHPort: "Please enter a valid server SSH port number",
        hostLoginUser: "Please enter the login username",
        password: "Please enter the login password",
        confirmPass: "Please re-enter the login password",
    },
    menus: {
        configManager: "Config Management",
        hostConfig: "Server Config Management",
        cardConfig: "Card Config Management",
        rdmaConfig: "RDMA Config Management",
        hostCmdExcute: "Command Control Center",
        rdmaTest: "RDMA Test",
        rdmaTestMgr: "RDMA Test Config",
        rdmaTestShow: "RDMA Test Operation",
        userConfig: "User Settings",
    },
    driver: {
        done: "Done",
        close: "Close",
        next: "Next",
        prev: "Previous",
        guideTitle: "Navigation Bar Description Button",
        guideDesc: "Explain Navigation Button",
        hamburgerTitle: "Zoom Button",
        hamburgerDesc: "Zoom In/Out",
        screenfulTitle: "Full Screen button",
        screenfulDesc: "Full Screen",
        langTitle: "Language Switch Button",
        langDesc: "Switch Language",
        logout: "Logout",
    },
    table: {
        placeholder: "What needs to be searched for",
        search: "Search",
        adduser: "Add user",
        batchDelete: "Batch delete",
        clearSel: "Clear Selection",
        Selection: "【Multi】",
        No: "No",
        Name: "User Name",
        Email: "Email",
        Role: "Role",
        Create_time: "Creation time",
        Status: "Status",
        Action: "Action",
    },
    hostTable: {
        Selection: "【Multi】",
        No: "No",
        addhost: "Add host",
        Host_Name: "Hostname",
        Host_IP: "Host IP Addr",
        Host_SSH_Port: "Host SSH port",
        Login_User: "Login username",
        Update_time: "Last refresh time",
        Status: "Status",
        Action: "Action",
    },
    cardTable: {
        Selection: "【Multi】",
        No: "No",
        Host_Name: "Hostname",
        Card_Name: "Card Name",
        Card_IPV4_Addr: "Card IPV4 addr",
        Card_Mac_Addr: "Card MAC addr",
        Card_Pci_Addr: "Card PCI addr",
        Card_Mtu: "Card MTU",
        Card_Mtu_Min: "Card Min MTU",
        Card_Mtu_Max: "Card Max MTU",
        Card_Phys_Port: "Card physical port",
        Status: "Status",
        Update_time: "Last refresh time",
        Action: "Action",
    },
    cardDialog: {
        editCardTitle: "Modifying Card Configuration",
        Card_Name: "Card Name",
        usedRdmaName: "RDMA Device bound to server",
        usedRdmaType: "RDMA Device already bound to this Card",
        Card_Mtu: "Card MTU",
        Rdma_Name: "RDMA Device Name",
        Rdma_Driver_Type: "Card binding RDMA driver type",
    },
    cardConfigForm: {
        exceedMTU: "Exceeded maximum MTU limit",
        invalidMtu: "Less than the minimum MTU limit",
        rdma_name: "Please enter the RDMA Device name",
        invalidRdmaName: "RDMA Device name is duplicate, please re-enter",
        RepeatRdmaDriverType:
            "The same type of RDMA driver is already bound to this Card, please reselect",
    },
    rdmaTable: {
        Selection: "【Multi】",
        No: "No",
        Host_Name: "Hostname",
        Card_Name: "Card Name",
        Card_IPV4_Addr: "Card IPV4 addr",
        Card_Mac_Addr: "Card MAC addr",
        Card_Pci_Addr: "Card PCI addr",
        RDMA_Name: "RDMA Name",
        RDMA_Physical_State: "RDMA Connection State",
        Node_Guid: "RDMA Guid",
        Link_Gid: "Link GID",
        Adap_State: "RDMA adaptive adjustment state",
        //Caps: "RDMA Capabilities",
        Status: "RDMA working status",
        Update_Time: "Last refresh time",
        Action: "Action",
    },
    rdmaDialog: {
        editRDMATitle: "RDMA Device Configuration Item",
        Rdma_Dev_Role: "The testing role of RDMA devices",
        Card_Name: "Card Name",
        Card_Mtu: "Card MTU",
        Rdma_Name: "RDMA Driver Name",
        Rdma_Driver_Type: "Card binding RDMA driver type",
    },
    response: {
        success: "Operation successful",
        fail: "Operation failed",
        invalid: "Not meeting the requirements",
        unchanged: "The data has not changed",
        onlyChangePassword:
            "Other information has not changed, only the login password will be updated this time",
        MtuNoChange: "MTU has not changed",
    },
    dialog: {
        addUserTitle: "Add User",
        editUserTitle: "Edit User",
        addHostTitle: "Add Host",
        editHostTitle: "Edit Host",
        passTitle: "Password",
        confirmPassTitle: "Duplicate Password",
        username: "Please enter a username",
        password: "Please enter the password",
        checkPass: "Please re-enter the password",
        passMismatch: "The two inputs are inconsistent!",
        email: "Please enter email addr",
        emailFormat: "The email format entered is incorrect",
        role: "Please enter a role",
        state: "Enable",
        deleteTitle: "Warning",
        deleteBody: "The record will be permanently deleted, Continue?",
        deleteRdmaBody: "RDMA device will be uninstalled, Continue?",
        confirmButton: "Confirm",
        cancelButton: "Cancel",
        doneDelele: "Done deleting",
        cancelDelete: "Cancel deletion",
        noneSelect: "No record selected, please select",
        modifyMTU: "Modify MTU value",
        addRdmaDev: "Bind RDMA device",
        startAllTitle: "Hint",
        startAllBody:
            "All tasks to be tested will be started and queued. Once started, they cannot be cancelled. Continue?",
        cancelstartAll: "Cancel starting all test tasks",
    },
    cmdExcute: {
        placeholder: "Command to be executed",
        sendBtn: "Send command",
        normalCmd: "Normal Command",
        sudoCmd: "sudo Command",
        cmdStatusDesc: "Command execution status",
        cmdSuccess: "Normal",
        cmdError: "Abnormal",
        cmdResult: "Command execution result",
        blankCmd: "Please enter the command that needs to be executed",
        forbidCmd: "Dangerous operation commands are not supported",
        invalidCmd: "The input command is illegal",
    },
    rdmaTest: {
        testTitle: "RDMA Test Configuration",
        testOpTitle: "RDMA Test Operations",
        testStatusTitle: "RDMA Test Status",
        directions: "direction",
        birections: "bidirectional",
        unidirection: "unidirectional",
        inTest: "Testing",
        waitResult: "Please wait a moment",
        checkTQ: "Generate to be testing Host Group",
        testServer: "Test Server",
        testClient: "Test Client",
        delBtn: "Delete the waiting-testing Host Group",
        addTestQueue: "Add to test database",
        delTestQueue: "Delete from test database",
        startTestBtn: "Start testing",
        exportBtn: "Download test status data",
        openResultUrl: "RDMA test results browsing",
        toBeSelectHost: "Test host group to be Selected",
        selectedHost: "Test host group Selected",
        toBeSelect: "Test items to be selected",
        selected: "Test items Selected",
        sendBWTest: "ib_send_bw bandwidth test",
        readBWTest: "ibunread_bw bandwidth test",
        writeBWTest: "ib_write_bw bandwidth test",
        atomicBWTest: "ib_atomic_bw bandwidth test",
        ethernetBWTest: "raw_ethernet_bw bandwidth test",
        sendLatTest: "ib_send_lat latency test",
        readLatTest: "ib_read_lat latency test",
        writeLatTest: "ib_write_lat latency test",
        atomicLatTest: "ib_atomic_lat latency test",
        ethernetLatTest: "raw_ethernet_lat latency test",
        refreshBtn: "Refresh Test Progress",
        batchDelete: "Batch delete tasks not in test queue",
        testCount: "Test Times",
        testQueue: "Test Queue",
        placeholder:
            "The date of the test record that needs to be searched, such as 20230608 or 0608",
    },
    rdmaTestShow: {
        No: "No",
        Test_Identifier: "Test Identifier",
        Test_Pair_ID: "Test Group ID",
        Test_Count_No: "Test group repeated SN",
        Test_Queue: "Test queue group",
        Test_Queue_State: "Test Queue State",
        Bidirection: "Direction",
        Server_Host_Name: "Server Host Name",
        Server_Card_Name: "Server Card Name",
        Server_Card_IPV4_Addr: "Server Card IPV4 addr",
        Server_Card_Mac_Addr: "MAC addr of the server Card",
        Server_RDMA_Name: "Server RDMA Name",
        Server_Node_GID: "Server RDMA Guid",
        Client_Host_Name: "Client Host Name",
        Client_Card_Name: "Client Card Name",
        Client_Card_IPV4_Addr: "Client Card IPV4 addr",
        Client_Card_Mac_Addr: "Client Card MAC addr",
        Client_RDMA_Name: "Client RDMA Name",
        Client_Node_GID: "Client RDMA Guid",
        rdma_sendbw_flag: "ib_send_bw test status",
        rdma_sendbw_costtime: "ib_send_bw cost time",
        rdma_readbw_flag: "ib_read_bw test status",
        rdma_readbw_costtime: "ib_read_bw cost time",
        rdma_writebw_flag: "ib_write_bw test status",
        rdma_writebw_costtime: "ib_write_bw cost time",
        rdma_atomicbw_flag: "ib_atomic_bw test status",
        rdma_atomicbw_costtime: "ib_atomic_bw cost time",
        rdma_ethernetbw_flag: "raw_ethernet_bw test status",
        rdma_ethernetbw_costtime: "raw_ethernet_bw cost time",
        rdma_sendlat_flag: "ib_send_lat cost time",
        rdma_sendlat_costtime: "ib_send_lat cost time",
        rdma_readlat_flag: "ib_read_lat cost time",
        rdma_readlat_costtime: "ib_read_lat cost time",
        rdma_writelat_flag: "ib_write_lat test status",
        rdma_writelat_costtime: "ib_write_lat cost time",
        rdma_atomiclat_flag: "ib_atomic_lat test status",
        rdma_atomiclat_costtime: "ib_atomic_lat cost time",
        rdma_ethernetlat_flag: "raw_ethernet_lat test status",
        rdma_ethernetlat_costtime: "raw_ethernet_lat cost time",
        Update_time: "Refresh time",
    },
};
