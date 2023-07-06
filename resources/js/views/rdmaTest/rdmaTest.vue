<template>
    <h2 style="text-align: center;width: 90%;">{{$t('rdmaTest.testTitle')}}</h2>
    <el-form :model="form" label-width="100px">
        <el-form-item :label="$t('rdmaTest.testServer')">
            <el-cascader v-model="form.server" :options="menu_options" :props="props" clearable separator="-->"/>
        </el-form-item>
        <el-form-item :label="$t('rdmaTest.testClient')">
            <el-cascader v-model="form.client" :options="menu_options" :props="props" clearable separator="-->" />
        </el-form-item>
        <el-form-item>
            <el-button @click="checkTQ">{{$t('rdmaTest.checkTQ')}}</el-button>
        </el-form-item>
    </el-form>
    <testDialog v-model="dialogVisible" 
            :dialogTableValue="dialogTableValue"
            v-if="dialogVisible"
    >
    </testDialog>
    <el-transfer
        :titles="hostTitles"
        v-model="toBeTestHostValue"
        :data="host"
        @left-check-change="handleSelect"
    >
    <template #left-footer>
        <el-button class="transfer-footer" type="danger" round :disabled="host.length==0" @click="deleteTestHost">{{$t('rdmaTest.delBtn')}}</el-button>
    </template>
    </el-transfer>
    <el-form :model="form" label-width="100px">
        <el-form-item :label="$t('rdmaTest.testCount')">
            <el-input v-model.number="form.count" />
        </el-form-item>
        <el-form-item :label="$t('rdmaTest.qpNum')">
            <el-input v-model.number="form.qpNum" />
        </el-form-item>
        <el-form-item :label="$t('rdmaTest.directions')">
            <el-select v-model="form.directions" placeholder="please select test dierection">
                <el-option label="unidirection" :value=false />
                <el-option label="birections" :value=true />
            </el-select>
        </el-form-item>
        <el-form-item :label="$t('rdmaTest.testQueue')">
            <el-select v-model="form.testQueue" placeholder="please select test queue">
                <el-option label="default" value="default" />
                <el-option label="Queue 1" value="Queue_1" />
                <el-option label="Queue 2" value="Queue_2" />
                <el-option label="Queue 3" value="Queue_3" />
                <el-option label="Queue 4" value="Queue_4" />
                <el-option label="Queue 5" value="Queue_5" />
                <el-option label="Queue 6" value="Queue_6" />
                <el-option label="Queue 7" value="Queue_7" />
                <el-option label="Queue 8" value="Queue_8" />
                <el-option label="Queue 9" value="Queue_9" />
                <el-option label="Queue 10" value="Queue_10" />
                <el-option label="Queue 11" value="Queue_11" />
                <el-option label="Queue 12" value="Queue_12" />
            </el-select>
        </el-form-item>
    </el-form>
    <el-transfer
        :titles="titles"
        v-model="selectedTestItems"
        :data="data"
    />
    <el-form-item class="btn">
        <el-button type="primary" icon="UploadFilled" @click="addTestQueue" :disabled="(!toBeTestHostValue.length||!selectedTestItems.length||flag)" >{{$t('rdmaTest.addTestQueue')}}</el-button>
    </el-form-item>
</template>

<script>
    import { reactive, ref } from '@vue/reactivity'
    import { watch } from '@vue/runtime-core';
    import { getMenu,addTQ,testTQ} from "../../api/rdmaTest";
    import {useStore} from "vuex";
    import { useI18n } from "vue-i18n";
    import testDialog from "./components/testDialog.vue";

    export default {
        name:'rdmaTest',
        components:{testDialog},
        setup(){
            const store=useStore()
            const {t}=useI18n()

            const flag=ref(store.getters.role!='admin')

            const selectedTestItems = ref([])
            const toBeTestHostValue = ref([])
            let testForm={
                "testHosts":[],
                "testItems":[],
                "directions":false,
                "testCount":1,
                "qpNum":10,
                "testQueue":"default",
            }
            let toBeDelHostValue = []
            const sep=','
            const dialogVisible=ref(false)
            const dialogTableValue=ref({})

            const menu_options=reactive([])
            const host = ref(JSON.parse(store.getters.testHostPair))
            let test_info
            const props = {
                expandTrigger: 'hover',
                }
            
            let hostTitles=reactive(["",""])
            let titles=reactive(["",""])

            const form = reactive({
                    test_pair_id:0,
                    directions:false,
                    server: [],
                    client: [],
                    count:1,
                    qpNum:10,
                    testQueue:"default",
                    })

            const data=ref([
                {
                    key:"ib_send_bw",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_read_bw",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_write_bw",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_atomic_bw",
                    label:"",
                    disabled:false
                },
                {
                    key:"raw_ethernet_bw",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_send_lat",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_read_lat",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_write_lat",
                    label:"",
                    disabled:false
                },
                {
                    key:"ib_atomic_lat",
                    label:"",
                    disabled:false
                },
                {
                    key:"raw_ethernet_lat",
                    label:"",
                    disabled:false
                },
            ])

            const handleSelect=(value) => {
                toBeDelHostValue=value
                // console.log(toBeDelHostValue);
            }
            
            const addTestQueue=async ()=>{
                testForm.testHosts=toBeTestHostValue.value
                testForm.testItems=selectedTestItems.value
                testForm.directions=form.directions
                testForm.testCount=form.count
                testForm.qpNum=form.qpNum
                testForm.testQueue=form.testQueue
                const res=await addTQ(testForm)
                if(res.opCode){
                    ElMessage({
                        type: 'success',
                        message: res.result,
                    })
                }else{
                    ElMessage({
                        type: 'error',
                        message: res.result,
                    })
                }
            }

            const checkTQ=async ()=>{
                dialogTableValue.value={
                    opCode:true,
                    msg: t('rdmaTest.inTest'),
                    result:t('rdmaTest.waitResult'),
                }
                form.test_pair_id = Date.parse(new Date())/1000;
                dialogVisible.value=true
                const res=await testTQ(form)
                
                if(res.opCode){
                    const keyValue=form.server[0]+sep+form.server[2]+"------"+form.client[0]+sep+form.client[2]
                    host.value.unshift({key:form.test_pair_id,label:keyValue})
                    // console.log(host.value);
                    store.dispatch('app/testHostPair',JSON.stringify(host.value))
                }
                dialogTableValue.value=res
            }

            watch(
                () => {store.getters.lang},
                () => {
                    hostTitles[0]=t('rdmaTest.toBeSelectHost')
                    hostTitles[1]=t('rdmaTest.selectedHost')
                    titles[0]=t('rdmaTest.toBeSelect')
                    titles[1]=t('rdmaTest.selected')
                    data.value[0].label=t('rdmaTest.sendBWTest')
                    data.value[1].label=t('rdmaTest.readBWTest')
                    data.value[2].label=t('rdmaTest.writeBWTest')
                    data.value[3].label=t('rdmaTest.atomicBWTest')
                    data.value[4].label=t('rdmaTest.ethernetBWTest')
                    data.value[5].label=t('rdmaTest.sendLatTest')
                    data.value[6].label=t('rdmaTest.readLatTest')
                    data.value[7].label=t('rdmaTest.writeLatTest')
                    data.value[8].label=t('rdmaTest.atomicLatTest')
                    data.value[9].label=t('rdmaTest.ethernetLatTest')
                },
                { deep: true,immediate:true }
            );

            const deleteTestHost=async ()=>{
                toBeDelHostValue.forEach(detail => {
                host.value=host.value.filter(item=>item.key!=detail)
                }) 
                store.dispatch('app/testHostPair',JSON.stringify(host.value))
                ElMessage({
                    type: 'success',
                    message: t('dialog.doneDelele'),
                })
            }

            const initGetMenu=async()=>{
                const res=await getMenu()
                if(res.opCode){
                    test_info=res.records
                }
                const hostArr=new Set()
                const cardArr=new Set()
                test_info.forEach(item => {
                const rdma_name=item.rdma_id+sep+item.ifname+sep+item.gid+sep+item.card_ipv4_addr+sep

                if(hostArr.has(item.host_name)){
                    if(cardArr.has(item.host_name+item.card_name)){
                        menu_options.forEach(detail=>{
                            if(detail.label==item.host_name){
                                detail.children.forEach(cardDtail=>{
                                    if(cardDtail.label==item.card_name){
                                        cardDtail.children.push({
                                            label:rdma_name,
                                            value:rdma_name,
                                        })
                                    }
                                })
                            }
                        })
                    }else{
                        menu_options.forEach(detail=>{
                            if(detail.label==item.host_name){
                                detail.children.push({
                                    label:item.card_name,
                                    value:item.card_name,
                                    children:[{
                                        label:rdma_name,
                                        value:rdma_name,
                                    }]
                                })
                            }
                        })
                        cardArr.add(item.host_name+item.card_name)
                    }
                }else{
                        menu_options.push({
                            label:item.host_name,
                            value:item.host_name,
                            children:[{
                                label:item.card_name,
                                value:item.card_name,
                                children:[{
                                    label:rdma_name,
                                    value:rdma_name,
                                }]
                            }]
                        })
                        hostArr.add(item.host_name)
                        cardArr.add(item.host_name+item.card_name)
                    }
                })
            }

            initGetMenu()

            return{
                selectedTestItems,
                toBeTestHostValue,
                data,
                host,
                flag,
                titles,
                hostTitles,
                form,
                menu_options,
                props,
                dialogVisible,
                dialogTableValue,
                addTestQueue,
                deleteTestHost,
                handleSelect,
                checkTQ
            }
        },
    }
</script>

<style lang="scss" scoped>
     /* 穿梭框外框高宽度 */
    :deep(.el-transfer-panel){
        width: 42%;
        height: 500px;
    }

    /* 穿梭框内部展示列表的高宽度 */
    :deep(.el-transfer-panel__list){
        height: 375px;
    }
    /* 穿梭框内部展示列表的高宽度 */
    :deep(.el-transfer-panel__body){
        height: 375px;
    }

    :deep(.el-form-item__content){
    // display:;    
    width:90%;
    justify-content: flex-start; 
    
    :deep(.el-button){
        display:block;
        margin:0 auto
        }
    }

    :deep(.el-input){
        width: 800px;
        }
    // :deep(.el-progress){
    //     width: 500px;
    // }
</style>