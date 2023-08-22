<template>
    <el-dialog
        :model-value="dialogVisible"
        :title="props.dialogTitle"
        width="40%"
        @close="handleClose"
    >
    <el-form 
        ref="ruleFormRef"
        status-icon
        :model="ruleForm" 
        :rules="rules"
        label-position="top"
        label-width="100px"
    >
        <el-form-item :label="$t('hostConfig.hostName')" prop="host_name">
            <el-input v-model="ruleForm.host_name" />
        </el-form-item>
        <el-form-item :label="$t('hostConfig.hostIp')" prop="host_ip">
            <el-input v-model="ruleForm.host_ip" />
        </el-form-item>
        <el-form-item :label="$t('hostConfig.hostSSHPort')" prop="host_ssh_port">
            <el-input v-model="ruleForm.host_ssh_port" type="number" />
        </el-form-item>
        <el-form-item :label="$t('hostConfig.hostLoginUser')" prop="host_login_user">
            <el-input v-model="ruleForm.host_login_user" />
        </el-form-item>
        <el-form-item :label="$t('hostConfig.password')" prop="password">
            <el-input v-model="ruleForm.host_login_password" type="password" autocomplete="off" show-password />
        </el-form-item>
        <el-form-item :label="$t('hostConfig.confirmPass')" prop="checkPass">
            <el-input v-model="ruleForm.checkPass" type="password" autocomplete="off" show-password  />
        </el-form-item>
        <el-form-item :label="$t('dialog.state')" v-if="props.dialogTitle==='添加主机'">
            <el-switch v-model="ruleForm.state" />
        </el-form-item>
    </el-form>
        <template #footer>
        <span class="dialog-footer">
            <el-button type="primary" @click="submitForm(ruleFormRef)">
                {{$t('dialog.confirmButton')}}
            </el-button>
            <el-button @click="handleClose">{{$t('dialog.cancelButton')}}</el-button>
        </span>
        </template>
    </el-dialog>
</template>

<script>
    import { ref,reactive } from '@vue/reactivity';
    import { addHost,editHost,updateHostPassword } from '../../../api/hostConfig';
    import { useI18n } from "vue-i18n";
    import { watch } from '@vue/runtime-core';
    import { isInclude } from "../../../utils/filters";

    export default {
        name:'hostDialog',
        emits: ['update:modelValue','triggerGetHostsList'],
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
            const ruleFormRef = ref(null)
            const ruleForm=reactive({
                host_name:'XXX_RDMA01',
                host_ip:'192.168.1.1',
                host_ssh_port:"22",
                host_login_user:'xxx',
                host_login_password:'',
                checkPass:'',
                state: true
            })

            const handleClose=()=>{
                emit('update:modelValue',false)
            }

            const validatePass = (_, value, callback) => {
                        if (value === '') {
                            // callback(new Error(t('hostConfigForm.password')))
                            callback()
                        } else {
                            if (ruleForm.checkPass !== '') {
                            if (!ruleFormRef.value) return
                                ruleFormRef.value.validateField('checkPass', () => null)
                            }
                            callback()
                        }
                        }
            const validatePass2 = (_, value, callback) => {
                    if (value === '') {
                        // callback(new Error(t('dialog.checkPass')))
                        callback()
                    } else if (value !== ruleForm.host_login_password) {
                        callback(new Error(t('dialog.passMismatch')))
                    } else {
                        callback()
                    }
            }

            const rules = reactive({
                host_ip:[{required:true,message:t('hostConfigForm.hostIp'), trigger: 'blur'}],
                host_ssh_port: [{required:true,message:t('hostConfigForm.hostSSHPort'), trigger: 'blur' }],
                host_login_user:[{required:true,message:t('hostConfigForm.hostLoginUser'), trigger: 'blur'}],
                host_login_password: [{ validator: validatePass, trigger: 'blur' }],
                checkPass: [{ validator: validatePass2, trigger: 'blur' }],
            })

            const submitForm = (formEl) => {
                if (formEl) return formEl.validate(async(valid) => {
                    if (valid) {
                        let res
                        let flag=true
                        if (props.dialogTitle===t('dialog.addHostTitle')){
                            res=await addHost(ruleForm)
                        }else{
                            if(isInclude(props.dialogTableValue,ruleForm)){
                                ElMessage({
                                        message: t('response.onlyChangePassword'),
                                        type: 'success',
                                    })
                                res=await updateHostPassword(ruleForm)
                            }else{
                                res=await editHost(ruleForm)
                            }
                        }
                        if(flag){
                            if (res.opCode){
                                ElMessage({
                                    message: t('response.success'),
                                    type: 'success',
                                })
                                emit('triggerGetHostsList')
                                emit('update:modelValue',false)
                            }else{
                                ElMessage({
                                message: t('response.fail'),
                                type: 'error',
                                })
                                }
                            return true
                        }                        
                    }else {
                        ElMessage({
                            message: t('response.invalid'),
                            type: 'error',
                        })
                        return false
                        }
                })
            }
            watch(()=>props.dialogTableValue,()=>{
                Object.assign(ruleForm,props.dialogTableValue)
                ruleForm.state=ruleForm.state==1?true:false
            },{deep:true,immediate:true})

            return {
                handleClose,
                submitForm,
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