export default {
    route: {
        Dashboard: "首页",
        hostConfig: "服务器配置管理",
        cardConfig: "网卡配置管理",
        rdmaConfig: "RDMA配置管理",
        hostCmdExcute: "命令控制中心",
        rdmaTest: "RDMA测试",
        rdmaTestShow: "RDMA测试操作",
        userConfig: "用户设置",
    },
    login: {
        title: "RDMA配置和测试管理系统",
        btnTitle: "登录",
        warnUserNull: "用户名不能为空",
        warnUserLen: "用户名长度需要3-12位之间",
        warnPasswordNull: "密码不能为空",
        warnPasswordLen: "密码长度需要8-16位之间",
    },
    hostConfig: {
        hostName: "服务器名称",
        hostIp: "服务器IP",
        hostSSHPort: "服务器SSH端口号",
        hostLoginUser: "服务器SSH登录用户",
        password: "服务器SSH登录密码",
        confirmPass: "请重新输入服务器SSH登录密码",
    },
    hostConfigForm: {
        hostIp: "请输入服务器IP地址",
        hostSSHPort: "请输入有效的服务器SSH端口号",
        hostLoginUser: "请输入登录用户名",
        password: "请输入登录密码",
        confirmPass: "请重新输入登录密码",
    },
    menus: {
        configManager: "配置管理",
        hostConfig: "服务器配置管理",
        cardConfig: "网卡配置管理",
        rdmaConfig: "RDMA配置管理",
        hostCmdExcute: "命令控制中心",
        rdmaTest: "RDMA测试",
        rdmaTestMgr: "RDMA测试配置",
        rdmaTestShow: "RDMA测试操作",
        userConfig: "用户设置",
    },
    driver: {
        done: "完成",
        close: "关闭",
        next: "下一个",
        prev: "上一个",
        guideTitle: "导航栏说明按钮",
        guideDesc: "说明各个导航按钮作用",
        hamburgerTitle: "菜单栏缩放按钮",
        hamburgerDesc: "菜单栏的缩放功能",
        screenfulTitle: "全屏按钮",
        screenfulDesc: "控制网页的全屏显示",
        langTitle: "语言切换按钮",
        langDesc: "切换网页显示语言",
        logout: "注销",
    },
    table: {
        placeholder: "需要查找的内容",
        search: "搜索",
        adduser: "添加用户",
        batchDelete: "批量删除",
        clearSel: "清除选择",
        Selection: "【多选】",
        No: "序号",
        Name: "用户名",
        Email: "邮箱",
        Role: "角色",
        Create_time: "创建时间",
        Status: "状态",
        Action: "操作",
    },
    hostTable: {
        Selection: "【多选】",
        No: "序号",
        addhost: "添加主机",
        Host_Name: "主机名称",
        Host_IP: "主机IP地址",
        Host_SSH_Port: "主机SSH端口",
        Login_User: "登录用户名",
        Update_time: "上次刷新时间",
        Status: "状态",
        Action: "操作",
    },
    cardTable: {
        Selection: "【多选】",
        No: "序号",
        Host_Name: "主机名称",
        Card_Name: "网卡名称",
        Card_IPV4_Addr: "网卡IPV4地址",
        Card_Mac_Addr: "网卡MAC地址",
        Card_Pci_Addr: "网卡PCI地址",
        Card_Mtu: "网卡MTU信息",
        Card_Mtu_Min: "网卡支持最小MTU信息",
        Card_Mtu_Max: "网卡支持最大MTU信息",
        Card_Phys_Port: "网卡物理端口信息",
        Status: "UP状态",
        Update_time: "上次刷新时间",
        Action: "操作",
    },
    cardDialog: {
        editCardTitle: "修改网卡配置",
        Card_Name: "网卡名称",
        usedRdmaName: "服务器已绑定的RDMA驱动设备",
        usedRdmaType: "本网卡已绑定的RDMA驱动设备",
        Card_Mtu: "网卡MTU信息",
        Rdma_Name: "RDMA驱动设备名称",
        Rdma_Driver_Type: "网卡绑定RDMA驱动类型",
    },
    cardConfigForm: {
        exceedMTU: "超出最大MTU限制",
        invalidMtu: "小于最小MTU限制",
        rdma_name: "请输入RDMA驱动设备名",
        invalidRdmaName: "RDMA驱动设备名重复,请重新输入",
        RepeatRdmaDriverType: "同类型RDMA驱动已绑定该网卡,请重新选择",
    },
    rdmaTable: {
        Selection: "【多选】",
        No: "序号",
        Host_Name: "主机名称",
        Card_Name: "网卡名称",
        Card_IPV4_Addr: "网卡IPV4地址",
        Card_Mac_Addr: "网卡MAC地址",
        Card_Pci_Addr: "网卡PCI地址",
        RDMA_Name: "RDMA名称",
        RDMA_Physical_State: "RDMA连接状态",
        Node_Guid: "RDMA GUID",
        Link_Gid: "Link GID",
        Adap_State: "RDMA自适应调整状态",
        // Caps: "RDMA能力",
        Status: "RDMA工作状态",
        Update_time: "上次刷新时间",
        Action: "操作",
    },
    rdmaDialog: {
        editRDMATitle: "RDMA设备配置项",
        Rdma_Dev_Role: "RDMA设备的测试角色",
        Card_Name: "网卡名称",
        Card_Mtu: "网卡MTU信息",
        Rdma_Name: "RDMA驱动设备名称",
        Rdma_Driver_Type: "网卡绑定RDMA驱动类型",
    },
    response: {
        success: "操作成功",
        fail: "操作失败",
        invalid: "不满足要求",
        unchanged: "数据未变化",
        onlyChangePassword: "其他信息未变化，本次只更新登录密码",
        MtuNoChange: "MTU没有变化",
    },
    dialog: {
        addUserTitle: "添加用户",
        editUserTitle: "编辑用户",
        addHostTitle: "添加主机",
        editHostTitle: "编辑主机",
        passTitle: "密码",
        confirmPassTitle: "重复密码",
        username: "请输入用户名",
        password: "请输入密码",
        checkPass: "请重新输入密码",
        passMismatch: "两次输入不一致！",
        email: "请输入email邮箱",
        emailFormat: "输入的email邮箱格式不正确",
        role: "请输入角色",
        state: "是否启用",
        deleteTitle: "警告",
        deleteBody: "将会永久删除记录,继续?",
        deleteRdmaBody: "将会卸载RDMA驱动设备,继续?",
        confirmButton: "确认",
        cancelButton: "取消",
        doneDelele: "完成删除",
        failDelele: "删除失败",
        cancelDelete: "取消删除",
        noneSelect: "未选择记录，请选择",
        modifyMTU: "修改MTU数值",
        addRdmaDev: "绑定RDMA驱动设备",
        startAllTitle: "提示",
        startAllBody: "将会启动所有待测试任务进入队列,启动后将无法取消，继续?",
        cancelstartAll: "取消启动所有测试任务",
    },
    cmdExcute: {
        placeholder: "需要执行的命令",
        sendBtn: "发送命令",
        normalCmd: "普通命令",
        sudoCmd: "sudo命令",
        cmdStatusDesc: "命令执行状态",
        cmdSuccess: "正常",
        cmdError: "不正常",
        cmdResult: "命令执行结果",
        blankCmd: "请输入需要执行的命令",
        forbidCmd: "不支持危险操作命令",
        invalidCmd: "输入命令非法",
        noright: "没有执行权限",
    },
    rdmaTest: {
        testTitle: "RDMA测试配置",
        testOpTitle: "RDMA测试操作",
        testStatusTitle: "RDMA测试状态",
        directions: "测试方向",
        birections: "双向",
        unidirection: "单向",
        inTest: "测试中",
        waitResult: "请稍后",
        checkTQ: "生成待测试主机组",
        testServer: "测试服务端",
        testClient: "测试客户端",
        delBtn: "删除待测试主机组",
        addTestQueue: "加入待测试数据库",
        delTestQueue: "从测试数据库删除",
        startTestBtn: "开始测试",
        exportBtn: "下载测试状态数据",
        openResultUrl: "RDMA测试结果浏览",
        toBeSelectHost: "待选择测试主机组",
        selectedHost: "选中测试主机组",
        toBeSelect: "待选择测试项",
        selected: "选中测试项",
        sendBWTest: "ib_send_bw带宽测试",
        readBWTest: "ib_read_bw带宽测试",
        writeBWTest: "ib_write_bw带宽测试",
        atomicBWTest: "ib_atomic_bw带宽测试",
        ethernetBWTest: "raw_ethernet_bw带宽测试",
        sendLatTest: "ib_send_lat时延测试",
        readLatTest: "ib_read_lat时延测试",
        writeLatTest: "ib_write_lat时延测试",
        atomicLatTest: "ib_atomic_lat时延测试",
        ethernetLatTest: "raw_ethernet_lat时延测试",
        refreshBtn: "刷新测试结果",
        batchDelete: "批量删除未放入测试队列的任务",
        testCount: "测试次数",
        qpNum: "测试QP参数",
        conPort: "测试端口参数",
        testQueue: "测试队列",
        placeholder:
            "要查找的日期，如20230608或0608等;或测试组ID;或服务/客户端主机名",
    },
    rdmaTestShow: {
        No: "序号",
        Test_Identifier: "测试项标识",
        Test_Pair_ID: "测试组ID",
        Test_Count_No: "测试组重复测试序号",
        Test_Queue: "测试队列组",
        Test_Queue_State: "测试队列组状态",
        Bidirection: "是否双端测试",
        test_qp_num: "测试QP参数",
        test_port_num: "测试端口参数",
        Server_Host_Name: "服务端主机名称",
        Server_Card_Name: "服务端网卡名称",
        Server_Card_IPV4_Addr: "服务端网卡IPV4地址",
        Server_Card_Mac_Addr: "服务端网卡MAC地址",
        Server_RDMA_Name: "服务端RDMA名称",
        Server_Node_GID: "服务端RDMA GUID",
        Client_Host_Name: "客户端主机名称",
        Client_Card_Name: "客户端网卡名称",
        Client_Card_IPV4_Addr: "客户端网卡IPV4地址",
        Client_Card_Mac_Addr: "客户端网卡MAC地址",
        Client_RDMA_Name: "客户端RDMA名称",
        Client_Node_GID: "客户端RDMA GUID",
        rdma_sendbw_flag: "ib_send_bw测试状态",
        rdma_sendbw_costtime: "ib_send_bw测试耗时",
        rdma_readbw_flag: "ib_read_bw测试状态",
        rdma_readbw_costtime: "ib_read_bw测试耗时",
        rdma_writebw_flag: "ib_write_bw测试状态",
        rdma_writebw_costtime: "ib_write_bw测试耗时",
        rdma_atomicbw_flag: "ib_atomic_bw测试状态",
        rdma_atomicbw_costtime: "ib_atomic_bw测试耗时",
        rdma_ethernetbw_flag: "raw_ethernet_bw测试状态",
        rdma_ethernetbw_costtime: "raw_ethernet_bw测试耗时",
        rdma_sendlat_flag: "ib_send_lat测试状态",
        rdma_sendlat_costtime: "ib_send_lat测试耗时",
        rdma_readlat_flag: "ib_read_lat测试状态",
        rdma_readlat_costtime: "ib_read_lat测试耗时",
        rdma_writelat_flag: "ib_write_lat测试状态",
        rdma_writelat_costtime: "ib_write_lat测试耗时",
        rdma_atomiclat_flag: "ib_atomic_lat测试状态",
        rdma_atomiclat_costtime: "ib_atomic_lat测试耗时",
        rdma_ethernetlat_flag: "raw_ethernet_lat测试状态",
        rdma_ethernetlat_costtime: "raw_ethernet_lat测试耗时",
        Update_time: "刷新时间",
    },
};
