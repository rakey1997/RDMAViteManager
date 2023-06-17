<template>
    <el-card class="box-card">
        <el-row :gutter="20" class="header">
            <el-col :span="7">
                <el-input 
                    clearable 
                    v-model="queryForm.query" 
                    :placeholder="$t('table.placeholder')" 
                    v-on:keyup.enter="initGetHostsList"
                    >
                </el-input>
            </el-col>
            <el-button type="primary" icon="Search" @click="initGetHostsList">{{$t('table.search')}}</el-button>
            <el-button type="primary" @click="toggleSelection()">{{$t('table.clearSel')}}</el-button>
            <el-button type="primary" @click="handleAddRecord()" :disabled="flag">{{$t('hostTable.addhost')}}</el-button>
            <el-button type="primary" icon="DeleteFilled" color="red" @click="batchDelete" :disabled="flag">{{$t('table.batchDelete')}}</el-button>
        </el-row>
        <el-table ref="multipleTableRef" :data="tableData" stripe fit style="width: 100%" size="small" :default-sort="{ prop: 'card_name', order: 'descending' }">
            <el-table-column 
                v-for="(op,index) in hostOptions" 
                :key="index" 
                :prop="op.prop" 
                :type="op.type"
                :sortable="op.sortable"
                :label="$t(`hostTable.${op.label}`)"
                :width="op.width"
                :index="indexMethod"
            >
                <template v-slot="{row}" v-if="op.prop==='state'">
                    <el-switch v-model="row.state" :active-value=1 :inactive-value=0 @change="changeState(row)" :disabled="flag"/>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='action'">
                    <el-button type="primary" size="small" icon="Edit" @click="handleAddRecord(row)" :disabled="flag"></el-button>
                    <el-button type="danger" size="small" icon="Delete" @click="deleteHost([row.id])" :disabled="flag"></el-button>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='update_time'">
                    <div>{{`${row.update_time.substr(0, row.update_time.lastIndexOf('.'))}`}}</div>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
                v-model:current-page="queryForm.pagenum"
                v-model:page-size="queryForm.pagesize"
                :page-sizes="[10,20,100]"
                :small=false
                background
                layout="->,prev, pager, next, jumper, sizes, total"
                :total="total"
                @size-change="handleSizeChange"
                @current-change="handleCurrentChange"
            />
    </el-card>
    <hostDialog v-model="dialogVisible" 
            :dialogTitle="dialogTitle" 
            :dialogTableValue="dialogTableValue"
            v-if="dialogVisible"
            @triggerGetHostsList="initGetHostsList"
    >
    </hostDialog>
</template>

<script>
    import { reactive,ref } from '@vue/reactivity'
    import { getHost,changeHostState,delHost } from "../../api/hostConfig";
    import { hostOptions } from "./options";
    import { useI18n } from "vue-i18n";
    import hostDialog from "./components/hostDialog.vue";
    import { isNull } from "../../utils/filters";
    import {useStore} from "vuex";

    export default {
        name:'hostConfig',
        components:{hostDialog},
        setup(){
            const {t}=useI18n()
            const store=useStore()
            const queryForm=reactive({
                query:'',
                pagenum: 1,
                pagesize: 10
            })

            const flag=ref(store.getters.role!='admin')

            const tableData=ref([])
            const total=ref()
            const multipleTableRef=ref(null)

            const dialogVisible=ref(false)
            const dialogTitle=ref('')
            const dialogTableValue=ref({})

            const indexMethod=(index)=>{
                return (queryForm.pagenum-1) * queryForm.pagesize+index+1
            }

            const initGetHostsList=async ()=>{
                const res=await getHost(queryForm)
                tableData.value=res.hosts
                total.value=res.total
                //存放服务器信息
                let hostNameStr=''
                res.hosts.forEach(element => {
                    if(element["state"]===1){
                        hostNameStr+=element["host_name"]+','
                    }
                });
                store.dispatch('app/hostInfo',hostNameStr.substring(0, hostNameStr.length - 1))
                store.dispatch('app/cardInfo',JSON.stringify(res.card_relation))
                store.dispatch('app/rdmaInfo',JSON.stringify(res.rdma_relation))
                store.dispatch('app/cardRdmaInfo',JSON.stringify(res.card_rdma_relation))
            }

            const handleCurrentChange=(pageNum)=>{
                queryForm.pagenum=pageNum
                initGetHostsList()
            }

            const handleSizeChange=(pageSize)=>{
                queryForm.pagenum=1
                queryForm.pagesize=pageSize
                initGetHostsList()
            }

            const changeState=async (info)=>{
                const res=await changeHostState(info.id,info.state)
                if (res.opCode){
                    ElMessage({
                        message: t('response.success'),
                        type: 'success',
                    })
                }
                initGetHostsList()
            }

            const handleAddRecord=(row)=>{
                if (isNull(row)){
                    dialogTitle.value=t('dialog.addHostTitle')
                    dialogTableValue.value={}
                }else{
                    dialogTitle.value=t('dialog.editHostTitle')
                    dialogTableValue.value=JSON.parse(JSON.stringify(row))
                }
                dialogVisible.value=true
            }

            const deleteHost=(id)=>{
                ElMessageBox.confirm(
                    t('dialog.deleteBody'),
                    t('dialog.deleteTitle'),
                {
                confirmButtonText: t('dialog.confirmButton'),
                cancelButtonText: t('dialog.cancelButton'),
                type: 'warning',
                }
                )
                .then(async () => {
                    await delHost(id)
                    ElMessage({
                        type: 'success',
                        message: t('dialog.doneDelele'),
                    })
                    initGetHostsList()
                })
                .catch(() => {
                ElMessage({
                    type: 'info',
                    message: t('dialog.cancelDelete'),
                })
                })
            }

            const toggleSelection = (rows) => {
                if (rows) {
                    rows.forEach((row) => {
                    multipleTableRef.value.toggleRowSelection(row, undefined)
                    })
                } else {
                    multipleTableRef.value.clearSelection()
                }
            }

            const batchDelete=()=>{
                const selectRecord=multipleTableRef.value.getSelectionRows()
                let id_arr = selectRecord.map(obj => {return obj.id})
                if (id_arr.length!==0){
                    deleteHost(id_arr)
                }else{
                    ElMessage({
                    type: 'info',
                    message: t('dialog.noneSelect'),
                })
                }
            }

            initGetHostsList()

            return{
                flag,
                queryForm,
                tableData,
                multipleTableRef,
                indexMethod,
                initGetHostsList,
                handleSizeChange,
                toggleSelection,
                handleCurrentChange,
                changeState,
                handleAddRecord,
                deleteHost,
                batchDelete,
                hostOptions,
                total,
                dialogVisible,
                dialogTitle,
                dialogTableValue
            }
        },
    }
</script>

<style lang="scss" scoped>
    .header{
        padding-bottom: 16px;
        box-sizing: border-box;
    }
    :deep(.el-input__suffix){
        align-items: center;
    }
    :deep(.el-pagination){
        padding-top: 16px;
        box-sizing: border-box;
        text-align: right;
    }
</style>