<template>
    <h2 style="text-align: center;width: 90%;">{{$t('rdmaTest.testResultTitle')}}</h2>
    
    <el-form-item class="btn">
        <el-button type="primary" icon="UploadFilled" @click="initGetResult" :disabled="flag" >{{$t('rdmaTest.refreshBtn')}}</el-button>
    </el-form-item>
    <el-card class="box-card">
        <el-table ref="multipleTableRef" :data="tableData" style="width: 100%" max-height="1000" size="small" :default-sort="{ prop: 'card_name', order: 'descending' }">
            <el-table-column 
                v-for="(op,index) in testOptions" 
                :key="index" 
                :prop="op.prop" 
                :type="op.type"
                :sortable="op.sortable"
                :label="$t(`rdmaTestShow.${op.label}`)"
                :width="op.width"
            >
                <template v-slot="{row}" v-if="op.prop==='card_state'">
                    <el-switch v-model="row.card_state" :active-value=1 :inactive-value=0 :disabled="true" :v-if="row.card_state"/>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='bidirection'">
                    <div>{{`${row.bidirection=='2'?"unidirection":"bidirection"}`}}</div>
                </template>
                <template v-slot="{row}" v-else-if="op.prop.includes('flag')">
                    <div>{{`${row[op.prop]=='0'?"not test":row[op.prop]=='1'?"not finish":row[op.prop]=='2'?"success":"fail"}`}}</div>
                </template>
                <template v-slot="{row}" v-else-if="op.prop.includes('costtime')">
                    <div>{{`${row[op.prop]===null?"":row[op.prop].toFixed(4)}`}}</div>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
                background
                layout="->,total"
                :total="total"
            />
    </el-card>
</template>

<script>
    import { reactive, ref } from '@vue/reactivity'
    import { testOptions } from "./options";
    import { getResult } from "../../api/rdmaTest";
    import {useStore} from "vuex";
    import { useI18n } from "vue-i18n";

    export default {
        name:'rdmaTestShow',
        setup(){
            const store=useStore()
            const {t}=useI18n()
            const total=ref()
            const tableData=ref([])

            const flag=ref(store.getters.role!='admin')
            const testForm = JSON.parse(store.getters.testForm)
            const initGetResult=async()=>{
                const res=await getResult(testForm)
                if(res.opCode){
                    tableData.value=res.record
                    total.value=res.total
                    console.log(res.record);
                    console.log('tableData:',tableData.value);
                }
            }

            initGetResult()

            return{
                initGetResult,
                flag,
                total,
                tableData,
                testOptions
            }
        },
    }
</script>

<style lang="scss" scoped>
     /* 穿梭框外框高宽度 */
    :deep(.el-transfer-panel){
        width: 42%;
        height: 400px;
    }

    /* 穿梭框内部展示列表的高宽度 */
    :deep(.el-transfer-panel__list){
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