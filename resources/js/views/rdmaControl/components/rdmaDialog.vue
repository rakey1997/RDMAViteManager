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
                <span>{{ $t('cardDialog.Card_Name') }}: {{ ruleForm.card_name }}</span>
            </div>
        </template>
        <div class="text item">{{ $t('cardDialog.Rdma_Name') }}: {{ ruleForm.ifname }}</div>
        <div class="text item">{{ $t('rdmaTable.Status') }}: {{ ruleForm.rdma_state }}</div>
        <br/>
        <el-form 
            ref="ruleFormRef"
            status-icon
            :model="ruleForm" 
            :rules="rules"
            label-position="top"
            label-width="100px"
        >
            <el-form-item :label="$t('rdmaDialog.Rdma_Dev_Role')" prop="dev_role">
                <el-select v-model="ruleForm.dev_role" :placeholder="$t('rdmaDialog.Rdma_Dev_Role')">
                    <el-option label="server" value="server" />
                    <el-option label="client" value="client" />
                </el-select>
            </el-form-item>
        </el-form>
</el-card>
        <template #footer>
            <span class="dialog-footer">
                <el-button type="primary" @click="modifyMTU(ruleFormRef)">
                    {{$t('dialog.confirmButton')}}
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
            // const hostCardRdmaList=JSON.parse(store.getters.hostRdmaList)

            let usedRdmaName

            const ruleFormRef = ref(null)
            const ruleForm=reactive({
                host_name:'',
                card_name:'',
                card_mtu:'',
                card_mtu_min:0,
                card_mtu_max:0,
                rdma_name:'',
                dev_role:"server",
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
                        } else if (usedRdmaName.includes(value)){
                            callback(new Error(t('cardConfigForm.invalidRdmaName')))
                        }else{
                            callback()
                        }
                    }

            const rules = reactive({
                card_mtu: [{ validator: validateMtu, trigger: 'blur' }],
                rdma_name:[{validator: validateRdmaName, trigger: 'blur'}],
            })

            const modifyMTU=(formEl)=>{
                ruleForm.cmd="modifyMtu"
                excuteCmd('card_mtu',formEl)
            }

            const addRdmaDev=(formEl)=>{
                // ruleForm.cmd="rdma link add "+ruleForm.rdma_name+" type "+ruleForm.driver_type+" netdev "+ruleForm.card_name
                ruleForm.cmd="addRdmaDev"
                console.log(ruleForm);
                excuteCmd('rdma_name',formEl)
            }
            
            const excuteCmd = (field,formEl) => {
                // if (formEl) return formEl.validate(async(valid) => {
                if (formEl) return formEl.validateField(field,async(valid) => {
                    if (valid) {
                        let res
                        if(field=="card_mtu" && isInclude(props.dialogTableValue,ruleForm)){
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
                // usedRdmaName=hostCardRdmaList.find(item => item.host_name === ruleForm.host_name)['group_concat(ifname)'];
			})

            return {
                handleClose,
                modifyMTU,
                addRdmaDev,
                ruleFormRef,
                props,
                ruleForm,
                rules,
            }
        }
    }
</script>

<style>

</style>