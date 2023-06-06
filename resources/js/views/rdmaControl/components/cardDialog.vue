<template>
    <el-dialog
        :model-value="dialogVisible"
        :title="props.dialogTitle"
        width="40%"
        @close="handleClose"
    >
        <el-card class="box-card">
        <template #header>
            <div class="card-header">
                <span>{{ $t('cardDialog.Card_Name') }}: {{ ruleForm.card_name }}</span><br>
                <span>{{ $t('cardDialog.usedRdmaName') }}: {{ usedRdmaName }}</span><br>
                <span>{{ $t('cardDialog.usedRdmaType') }}: {{ usedRdmaType }}</span>
            </div>
        </template>

    <el-form 
        ref="ruleFormRef"
        status-icon
        :model="ruleForm" 
        :rules="rules"
        label-position="top"
        label-width="100px"
    >
        <el-form-item :label="$t('cardDialog.Card_Mtu')+'('+ruleForm.card_mtu_min+','+ruleForm.card_mtu_max+')'" prop="card_mtu">
            <el-input v-model="ruleForm.card_mtu" type="number"/>
        </el-form-item>
        <el-form-item :label="$t('cardDialog.Rdma_Name')" prop="rdma_name">
            <el-input v-model="ruleForm.rdma_name"/>
        </el-form-item>
        <el-form-item :label="$t('cardDialog.Rdma_Driver_Type')" prop="driver_type">
            <el-select v-model="ruleForm.driver_type" placeholder="请选择需要使用的RDMA驱动类型">
                <el-option label="pclr" value="pclr" />
                <el-option label="rxe" value="rxe" />
            </el-select>
        </el-form-item>
    </el-form>
</el-card>
        <template #footer>
            <span class="dialog-footer">
                <el-button type="primary" @click="modifyMTU(ruleFormRef)">
                    {{$t('dialog.modifyMTU')}}
                </el-button>
                <el-button type="primary" @click="addRdmaDev(ruleFormRef)">
                    {{$t('dialog.addRdmaDev')}}
                </el-button>
                <el-button @click="handleClose">{{$t('dialog.cancelButton')}}</el-button>
            </span>
        </template>
    </el-dialog>

    
</template>

<script>
    import { ref,reactive } from '@vue/reactivity';
    import { excuteCmdFromSSH } from '../../../api/cardConfig';
    import { useI18n } from "vue-i18n";
    import { onMounted, watch } from '@vue/runtime-core';
    import { isInclude } from "../../../utils/filters";
    import {useStore} from "vuex";

    export default {
        name:'hostDialog',
        emits: ['update:modelValue','triggerGetCardsList'],
        props:{
            dialogTitle:{
                type:String,
                default:'',
            },
            dialogTableValue:{
                type:Object,
                default:()=>{}
            }
        },
        setup(props,{ emit }){
            const {t}=useI18n()
            const store=useStore()
            const hostCardRdmaList=JSON.parse(store.getters.hostRdmaList)
            const cardRdmaList=JSON.parse(store.getters.cardRdmaList)
            const usedRdmaName=ref([])
            const usedRdmaType=ref([])
            
            const ruleFormRef = ref(null)
            const ruleForm=reactive({
                host_name:'',
                card_name:'',
                card_mtu:'',
                card_mtu_min:0,
                card_mtu_max:0,
                rdma_name:'',
                driver_type:"pclr",
                cmdType:""
            })

            const handleClose=()=>{
                emit('update:modelValue',false)
            }

            const validateMtu = (_, value, callback) => {
                        if (parseInt(value) > parseInt(ruleForm.card_mtu_max)) {
                            callback(new Error(t('cardConfigForm.exceedMTU')))
                        } else if (parseInt(value) < parseInt(ruleForm.card_mtu_min)){
                            callback(new Error(t('cardConfigForm.invalidMtu')))
                        }else{
                            callback()
                        }
                    }

            const validateRdmaName = (_, value, callback) => {
                        if (value=="") {
                            callback(new Error(t('cardConfigForm.rdma_name')))
                        } else if (usedRdmaName.value.includes(value)){
                            callback(new Error(t('cardConfigForm.invalidRdmaName')))
                        }else{
                            callback()
                        }
                    }

            const validateRdmaType = (_, value, callback) => {
                        if (usedRdmaType.value.includes(value)){
                            callback(new Error(t('cardConfigForm.RepeatRdmaDriverType')))
                        } else{
                            callback()
                        }
                    }

            const rules = reactive({
                card_mtu: [{validator: validateMtu, trigger: 'blur' }],
                rdma_name:[{validator: validateRdmaName, trigger: 'blur'}],
                driver_type:[{validator: validateRdmaType, trigger: 'blur'}],
            })

            const modifyMTU=(formEl)=>{
                ruleForm.cmd="modifyMtu"
                excuteCmd(['card_mtu'],formEl)
            }

            const addRdmaDev=(formEl)=>{
                ruleForm.cmd="addRdmaDev"
                excuteCmd(['rdma_name','driver_type'],formEl)
            }
            
            const excuteCmd = (field,formEl) => {
                // if (formEl) return formEl.validate(async(valid) => {
                if (formEl) return formEl.validateField(field,async(valid) => {
                    if (valid) {
                        let res
                        if(field[0]=="card_mtu" && isInclude(props.dialogTableValue,ruleForm)){
                            ElMessage({
                                    message: t('response.MtuNoChange'),
                                    type: 'success',
                                })
                        }else{
                            console.log(ruleForm);
                            res=await excuteCmdFromSSH(ruleForm)
                        }
                        if (res.opCode){
                            ElMessage({
                                message: t('response.success'),
                                type: 'success',
                            })
                            emit('triggerGetCardsList')
                            emit('update:modelValue',false)
                        }else{
                            ElMessage({
                            message: t('response.fail'),
                            type: 'fail',
                            })
                        }
                    }
                })
            }

            watch(()=>props.dialogTableValue,()=>{
                Object.assign(ruleForm,props.dialogTableValue)
                ruleForm.state=ruleForm.state==1?true:false
            },{deep:true,immediate:true})

            onMounted(()=>{
                const usedCard=hostCardRdmaList.find(item => item.host_name === ruleForm.host_name);
                if(usedCard!=undefined){
                    usedRdmaName.value=usedCard['group_concat(ifname)']
                }else{
                    usedRdmaName.value=[]
                }
                const usedRdma=cardRdmaList.find(item => (item.host_name === ruleForm.host_name && item.card_name=== ruleForm.card_name))
                if(usedRdma!=undefined){
                    usedRdmaType.value=usedRdma['group_concat(ifname)'];
                }else{
                    usedRdmaType.value=[]
                } 
			})
            
            return {
                handleClose,
                modifyMTU,
                addRdmaDev,
                ruleFormRef,
                usedRdmaName,
                usedRdmaType,
                props,
                ruleForm,
                rules,
            }
        }
    }
</script>

<style>

</style>