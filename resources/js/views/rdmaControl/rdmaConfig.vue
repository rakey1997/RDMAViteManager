<template>
    <el-select v-model="host_name" class="header" placeholder="Select" size="large" @change="initGetRdmaList(false)">
        <el-option
        v-for="item in options"
        :key="item.value"
        :label="item.label"
        :value="item.value"
        />
    </el-select>

    <el-card class="box-card">
        <el-table ref="multipleTableRef" :data="tableData" stripe fit style="width: 100%" size="small" :default-sort="{ prop: 'card_name', order: 'descending' }">
            <el-table-column 
                v-for="(op,index) in rdmaOptions" 
                :key="index" 
                :prop="op.prop" 
                :type="op.type"
                :sortable="op.sortable"
                :label="$t(`rdmaTable.${op.label}`)"
                :width="op.width"
            >
                <template v-slot="{row}" v-if="op.prop==='action'">
                    <el-button-group class="ml-4">
                        <el-button type="primary" icon="DeleteFilled" color="red" size="small" :disabled="flag" @click="DeleteRdma([row.rdma_id])" v-if="CheckDelete(row)"></el-button>
                    </el-button-group>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='update_time'">
                    <div>{{`${row.update_time}`}}</div>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
                background
                layout="->,total"
                :total="total"
            />
    </el-card>
    <hr>
    <cmdExcute :host_name="host_name" >
    </cmdExcute>

</template>

<script>
    import { ref,reactive } from '@vue/reactivity';
    import { rdmaOptions } from "./options";
    import {useStore} from "vuex";
    import { useI18n } from "vue-i18n";
    import { getRdma,delRdma } from "../../api/rdmaConfig";
    import cmdExcute from "../cmdExcute/components/cmdExcute.vue";

    export default {
        name:'rdmaConfig',
        components:{cmdExcute},
        setup(){
            const {t}=useI18n()
            const store=useStore()
            const total=ref()
            const flag=ref(store.getters.role!='admin')

            let hostNameArray = store.getters.hostName.split(',')
            const options=hostNameArray.map(obj => {return {value:obj,label:obj}})
            const host_name = ref(options[0]['value'])

            // const card_name = ref('')
            // let card_options=reactive({})
            // let cardOptions = JSON.parse(store.getters.hostCardList)
            // let cardNameArray = cardOptions[host_name].split(',')
            // card_options=cardNameArray.map(obj => {return {value:obj,label:obj}})
            // card_name = ref(card_options[0])

            const tableData=ref([])
            const DeleteRdma=async(id)=>{
                console.log(id);
                ElMessageBox.confirm(
                    t('dialog.deleteRdmaBody'),
                    t('dialog.deleteTitle'),
                {
                confirmButtonText: t('dialog.confirmButton'),
                cancelButtonText: t('dialog.cancelButton'),
                type: 'warning',
                }
                )
                .then(async () => {
                    await delRdma(id)
                    ElMessage({
                        type: 'success',
                        message: t('dialog.doneDelele'),
                    })
                    initGetRdmaList(true)
                })
                .catch(() => {
                ElMessage({
                    type: 'info',
                    message: t('dialog.cancelDelete'),
                })
                })
            }

            const CheckDelete=(row)=>{
                const deleteAble=['pclr','rxe']
                if(deleteAble.find(item=>row.ifname.includes(item))){
                    return true
                }
            }

            const initGetRdmaList=async(queryflag)=>{
                const res=await getRdma({"host_name":host_name.value,"whole":queryflag})
                tableData.value=res.cards
                total.value=res.total

                if(queryflag){
                    store.dispatch('app/cardInfo',JSON.stringify(res.card_relation))
                    store.dispatch('app/rdmaInfo',JSON.stringify(res.rdma_relation))
                    store.dispatch('app/cardRdmaInfo',JSON.stringify(res.card_rdma_relation))
                }
            }


            initGetRdmaList(false)

            return{
                flag,
                host_name,
                tableData,
                total,
                options,
                rdmaOptions,
                initGetRdmaList,
                DeleteRdma,
                CheckDelete
            }
        }
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
    .el-button {
        margin-right: 0px;
        height: 41px;
    }

</style>