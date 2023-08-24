<template>
    <h2 style="text-align: center;width: 90%;">{{$t('rdmaTest.testOpTitle')}}</h2>
    <el-row :gutter="20" class="header">
        <el-col :span="8">
            <el-input 
                clearable 
                v-model="rdmaQueryForm.query" 
                :placeholder="$t('rdmaTest.placeholder')" 
                v-on:keyup.enter="initGetResult(false)"
                >
            </el-input>
        </el-col>
        <el-form-item class="btn">
            <el-button type="primary" icon="UploadFilled" @click="initGetResult(false)" >{{$t('rdmaTest.refreshBtn')}}</el-button>
            <el-button type="primary" icon="DeleteFilled" color="red" @click="batchDelete" :disabled="flag">{{$t('rdmaTest.batchDelete')}}</el-button>
            <el-button type="primary" icon="UploadFilled" @click="startTest" :disabled="(flag)" >{{$t('rdmaTest.startTestBtn')}}</el-button>
            <el-button type="primary" icon="UploadFilled" @click="toExcel" >{{$t('rdmaTest.exportBtn')}}</el-button>
            <el-button type="primary" icon="UploadFilled" @click="openResultUrl" >{{$t('rdmaTest.openResultUrl')}}</el-button>
        </el-form-item>
    </el-row>
    <h2 style="text-align: center;width: 90%;">{{$t('rdmaTest.testStatusTitle')}}</h2>
    <el-card class="box-card">
        <el-table ref="multipleTableRef" :data="tableData" id="out-table" style="width: 100%" max-height="700" size="small">
            <el-table-column 
                v-for="(op,index) in testOptions" 
                :key="index" 
                :prop="op.prop" 
                :type="op.type"
                :fixed="op.fixed"
                :sortable="op.sortable"
                :selectable="selectable"
                :label="$t(`rdmaTestShow.${op.label}`)"
                :width="op.width"
            >
                <template v-slot="{row}" v-if="op.prop==='card_state'">
                    <el-switch v-model="row.card_state" :active-value=1 :inactive-value=0 :disabled="true" :v-if="row.card_state"/>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='test_queue_state'">
                    <div>{{`${row.test_queue_state=='0'?"Wait to put into Test Queue":row.test_queue_state=='1'?"Wait to Start Test":row.test_queue_state=='2'?"Testing":row.test_queue_state=='3'?"Test Finshed":"Test Task Start Failure"}`}}</div>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='bidirection'">
                    <div>{{`${row.bidirection=='2'?"unidirection":"bidirection"}`}}</div>
                </template>
                <template v-slot="{row}" v-else-if="op.prop.includes('flag')">
                    <div>{{`${row[op.prop]=='0'?"no need test":row[op.prop]=='1'?"wait starting":row[op.prop]=='2'?"testing":row[op.prop]=='3'?"success":"fail"}`}}</div>
                </template>
                <template v-slot="{row}" v-else-if="op.prop.includes('costtime')">
                    <div>{{`${row[op.prop]===null?"":row[op.prop].toFixed(4)}`}}</div>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
                v-model:current-page="rdmaQueryForm.pagenum"
                v-model:page-size="rdmaQueryForm.pagesize"
                :page-sizes="[10, 50,100,500,1000,2000,3000,4000]"
                :small=false
                background
                layout="->,prev, pager, next, jumper, sizes, total"
                :total="total"
                @size-change="handleSizeChange"
                @current-change="handleCurrentChange"
            />
    </el-card>
</template>

<script>
    import { reactive, ref } from '@vue/reactivity'
    import { testOptions } from "./options";
    import { getResult,delTQ,excuteTest } from "../../api/rdmaTest";
    import {useStore} from "vuex";
    import { useI18n } from "vue-i18n";
    import { saveAs } from 'file-saver';
    import * as XLSX from 'xlsx';

    export default {
        name:'rdmaTestShow',
        setup(){
            const store=useStore()
            const {t}=useI18n()
            const total=ref()
            const tableData=ref([])
            let id_arr=[]

            const flag=ref(store.getters.role!='admin')
            const rdmaQueryForm=reactive({
                query:'',
                pagenum: 1,
                pagesize: 10,
                flag:false,
            })

            const multipleTableRef=ref(null)

            const initGetResult=async(url_flag)=>{
                rdmaQueryForm.flag=url_flag
                const res=await getResult(rdmaQueryForm)
                if(res.opCode){
                    tableData.value=res.record
                    total.value=res.total
                    url_flag?store.dispatch('app/testUrl',JSON.stringify(res.url)):""
                }
            }

            const handleCurrentChange=(pageNum)=>{
                rdmaQueryForm.pagenum=pageNum
                initGetResult(false)
            }

            const handleSizeChange=(pageSize)=>{
                rdmaQueryForm.pagenum=1
                rdmaQueryForm.pagesize=pageSize
                initGetResult(false)
            }

            const selectable=(row,_)=>{
                return row.test_queue_state=='0'?true:false
            }

            const checkFlag=(res)=>{
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

            const getSelectionRows=()=>{
                const selectRecord=multipleTableRef.value.getSelectionRows()
                id_arr = selectRecord.map(obj => {return [obj.test_identifier,obj.test_pair_id]})
            }

            const openResultUrl=()=>{
                const cleanUrl = store.getters.testUrl.replace(/"/g, '');
                window.open(cleanUrl,'_blank');
            }

            const toExcel=()=>{
                /* 从表生成工作簿对象 */
                var wb = XLSX.utils.table_to_book(document.querySelector("#out-table"));
                /* 获取二进制字符串作为输出 */
                var wbout = XLSX.write(wb, {
                    bookType: "xlsx",
                    bookSST: true,
                    type: "array"
                });
                try {
                    saveAs(
                    //Blob 对象表示一个不可变、原始数据的类文件对象。
                    //Blob 表示的不一定是JavaScript原生格式的数据。
                    //File 接口基于Blob，继承了 blob 的功能并将其扩展使其支持用户系统上的文件。
                    //返回一个新创建的 Blob 对象，其内容由参数中给定的数组串联组成。
                    new Blob([wbout], { type: "application/octet-stream" }),
                    //设置导出文件名称
                    "rdmaTestResult.xlsx"
                    );
                } catch (e) {
                    if (typeof console !== "undefined") console.log(e, wbout);
                }
                return wbout;
            }

            const batchDelete=async ()=>{
                getSelectionRows()
                if (id_arr.length!==0){
                    const res=await delTQ({"id_arr":id_arr})
                    checkFlag(res)
                    initGetResult(false)
                    id_arr=[]
                }else{
                    ElMessage({
                    type: 'info',
                    message: t('dialog.noneSelect'),
                })
                }
            }

            const startTest=async ()=>{
                getSelectionRows()
                if (id_arr.length==0){
                    ElMessageBox.confirm(
                    t('dialog.startAllBody'),
                    t('dialog.startAllTitle'),
                    {
                        confirmButtonText: t('dialog.confirmButton'),
                        cancelButtonText: t('dialog.cancelButton'),
                        type: 'warning',
                    }
                    )
                .then(async () => {
                    const res=await excuteTest({"id_arr":id_arr})
                    checkFlag(res)
                })
                .catch(() => {
                    ElMessage({
                        type: 'info',
                        message: t('dialog.cancelstartAll'),
                    })
                })
                }else{
                    const res=await excuteTest({"id_arr":id_arr})
                    checkFlag(res)
                }
                initGetResult(false)
            }

            initGetResult(true)

            return{
                rdmaQueryForm,
                initGetResult,
                batchDelete,
                multipleTableRef,
                flag,
                total,
                selectable,
                handleCurrentChange,
                handleSizeChange,
                startTest,
                toExcel,
                openResultUrl,
                tableData,
                testOptions
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