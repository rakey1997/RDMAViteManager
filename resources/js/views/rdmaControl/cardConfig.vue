<template>
    <el-select v-model="host_name" class="header" placeholder="Select" size="large" @change="initGetCardsList()">
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
                v-for="(op,index) in cardOptions" 
                :key="index" 
                :prop="op.prop" 
                :type="op.type"
                :sortable="op.sortable"
                :label="$t(`cardTable.${op.label}`)"
                :width="op.width"
            >
                <template v-slot="{row}" v-if="op.prop==='card_state'">
                    <el-switch v-model="row.card_state" :active-value=1 :inactive-value=0 :disabled="true" :v-if="row.card_state"/>
                </template>
                <template v-slot="{row}" v-else-if="op.prop==='action'">
                    <el-button type="primary" size="small" icon="Edit" @click="handleChange(row)" :disabled="flag"></el-button>
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
    <cardDialog v-model="dialogVisible" 
            :dialogTitle="dialogTitle" 
            :dialogTableValue="dialogTableValue"
            v-if="dialogVisible"
            @triggerGetCardsList="initGetCardsList"
    >
    </cardDialog>
</template>

<script>
    import { ref,reactive } from '@vue/reactivity';
    import { cardOptions } from "./options";
    import {useStore} from "vuex";
    import { useI18n } from "vue-i18n";
    import { getCard } from "../../api/cardConfig";
    import cardDialog from "./components/cardDialog.vue";
    import cmdExcute from "../cmdExcute/components/cmdExcute.vue";
    

    export default {
        name:'cardConfig',
        components:{cardDialog,cmdExcute},
        setup(){
            const {t}=useI18n()
            const store=useStore()
            const total=ref()
            const dialogVisible=ref(false)
            const dialogTitle=ref('')
            const dialogTableValue=ref({})
            const flag=ref(store.getters.role!='admin')
           
            let hostNameArray = JSON.parse(store.getters.hostName)
            const options=hostNameArray.map(obj => {return {value:obj,label:obj}})
            const host_name = ref(options[0]['value'])

            const tableData=ref([])
            const handleChange=(row)=>{
                dialogTitle.value=t('cardDialog.editCardTitle')
                dialogTableValue.value=JSON.parse(JSON.stringify(row))
                dialogVisible.value=true
            }

            const initGetCardsList=async ()=>{
                const res=await getCard({"host_name":host_name.value,"whole":flag.value})
                tableData.value=res.cards
                total.value=res.total

                //存放服务器信息
                // if(relationflag){
                //     let cardRelation={}
                //     res.cardRelation.forEach((item,_) => {
                //         cardRelation[item["host_name"]] = item["group_concat(card_name)"];
                //     })
                //     store.dispatch('app/cardInfo',JSON.stringify(cardRelation))
                // }
            }

            initGetCardsList()

            return{
                flag,
                host_name,
                tableData,
                total,
                options,
                cardOptions,
                dialogTitle,
                dialogTableValue,
                dialogVisible,
                initGetCardsList,
                handleChange
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
        margin-right: 100px;
        height: 41px;
    }
</style>