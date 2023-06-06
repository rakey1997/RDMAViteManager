<template>
    <el-card class="box-card">
        <el-row :gutter="20" class="header">
            <el-col :span="7">
                <el-input 
                    clearable 
                    v-model="queryForm.query" 
                    :placeholder="$t('table.placeholder')" 
                    >
                </el-input>
            </el-col>
            <el-button type="primary" icon="Search" @click="initGetUsersList">{{$t('table.search')}}</el-button>
            <el-button type="primary" @click="toggleSelection()">{{$t('table.clearSel')}}</el-button>
            <el-button type="primary" @click="handleAddRecord()" :disabled="flag">{{$t('table.adduser')}}</el-button>
            <el-button type="primary" icon="DeleteFilled" color="red" @click="batchDelete" :disabled="flag">{{$t('table.batchDelete')}}</el-button>
        </el-row>
        <el-table ref="multipleTableRef" :data="tableData" stripe fit style="width: 100%">
            <el-table-column 
                v-for="(op,index) in options" 
                :key="index" 
                :prop="op.prop" 
                :type="op.type"
                :label="$t(`table.${op.label}`)"
                :width="op.width"
                :index="indexMethod"
            >
                <template v-slot="{row}" v-if="op.prop==='state'">
                    <el-switch v-model="row.state" :active-value=1 :inactive-value=0 @change="changeState(row)" :disabled="flag"/>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='action'">
                    <el-button type="primary" size="small" icon="Edit" @click="handleAddRecord(row)" :disabled="flag"></el-button>
                    <el-button type="danger" size="small" icon="Delete" @click="delUser([row.id])" :disabled="flag"></el-button>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='create_time'">
                    <div>{{`${row.create_time.substr(0, row.create_time.lastIndexOf('.'))}`}}</div>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
                v-model:current-page="queryForm.pagenum"
                v-model:page-size="queryForm.pagesize"
                :page-sizes="[10, 50,100]"
                :small=false
                background
                layout="->,prev, pager, next, jumper, sizes, total"
                :total="total"
                @size-change="handleSizeChange"
                @current-change="handleCurrentChange"
            />
    </el-card>
    <Dialog v-model="dialogVisible" 
            :dialogTitle="dialogTitle" 
            :dialogTableValue="dialogTableValue"
            v-if="dialogVisible"
            @triggerGetUsersList="initGetUsersList"
    >
    </Dialog>
</template>

<script>
    import { reactive,ref } from '@vue/reactivity'
    import { getUsers,changeUserState,deleteUser } from "../../api/users";
    import { options } from "./options";
    import { useI18n } from "vue-i18n";
    import Dialog from "./components/dialog.vue";
    import { isNull } from "../../utils/filters";
    import {useStore} from "vuex";

    export default {
        name:'manageConfig',
        components:{Dialog},
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

            const initGetUsersList=async ()=>{
                const res=await getUsers(queryForm)
                tableData.value=res.users
                total.value=res.total
            }

            const handleCurrentChange=(pageNum)=>{
                queryForm.pagenum=pageNum
                initGetUsersList()
            }

            const handleSizeChange=(pageSize)=>{
                queryForm.pagenum=1
                queryForm.pagesize=pageSize
                initGetUsersList()
            }

            const changeState=async (info)=>{
                const res=await changeUserState(info.id,info.state)
                if (res.opCode){
                    ElMessage({
                        message: t('response.success'),
                        type: 'success',
                    })
                }
            }

            const handleAddRecord=(row)=>{
                if (isNull(row)){
                    dialogTitle.value=t('dialog.addUserTitle')
                    dialogTableValue.value={}
                }else{
                    dialogTitle.value=t('dialog.editUserTitle')
                    dialogTableValue.value=JSON.parse(JSON.stringify(row))
                }
                dialogVisible.value=true
            }

            const delUser=(id)=>{
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
                    await deleteUser(id)
                    ElMessage({
                        type: 'success',
                        message: t('dialog.doneDelele'),
                    })
                    initGetUsersList()
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
                    delUser(id_arr)
                }else{
                    ElMessage({
                    type: 'info',
                    message: t('dialog.noneSelect'),
                })
                }
            }

            initGetUsersList()

            return{
                flag,
                queryForm,
                tableData,
                multipleTableRef,
                indexMethod,
                initGetUsersList,
                handleSizeChange,
                toggleSelection,
                handleCurrentChange,
                changeState,
                handleAddRecord,
                delUser,
                batchDelete,
                options,
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