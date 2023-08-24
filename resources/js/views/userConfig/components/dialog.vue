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
        <el-form-item :label="$t('table.Name')" prop="username">
            <el-input v-model="ruleForm.username" />
        </el-form-item>
        <el-form-item :label="$t('dialog.passTitle')" prop="password" v-if="props.dialogTitle===$t('dialog.addUserTitle')">
            <el-input v-model="ruleForm.password" type="password" autocomplete="off" />
        </el-form-item>
        <el-form-item :label="$t('dialog.confirmPassTitle')" prop="checkPass" v-if="props.dialogTitle===$t('dialog.addUserTitle')">
            <el-input v-model="ruleForm.checkPass" type="password" autocomplete="off" />
        </el-form-item>
        <el-form-item :label="$t('table.Email')" prop="email">
            <el-input v-model="ruleForm.email" />
        </el-form-item>
        <el-form-item :label="$t('table.Role')" prop="role">
            <el-input v-model="ruleForm.role" />
        </el-form-item>
        <el-form-item :label="$t('dialog.state')">
            <el-switch v-model="ruleForm.state" />
        </el-form-item>
    </el-form>
        <template #footer>
        <span class="dialog-footer">
            <el-button type="primary" @click="submitForm(ruleFormRef)">
            {{ $t('dialog.confirmButton') }}
            </el-button>
            <el-button @click="handleClose">{{ $t('dialog.cancelButton') }}</el-button>
        </span>
        </template>
    </el-dialog>
</template>

<script>
    import { ref,reactive } from '@vue/reactivity';
    import { addUser,editUser } from "../../../../js/api/users";
    import { useI18n } from "vue-i18n";
    import { watch } from '@vue/runtime-core';
    import { isInclude } from "../../../utils/filters";

    export default {
        name:'userDialog',
        emits: ['update:modelValue','triggerGetUsersList'],
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
                username:'',
                password:'',
                checkPass:'',
                email:'',
                role:'',
                state: true
            })

            const handleClose=()=>{
                emit('update:modelValue',false)
            }

            const validatePass = (_, value, callback) => {
                        if (value === '') {
                            callback(new Error(t('dialog.password')))
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
                        callback(new Error(t('dialog.checkPass')))
                    } else if (value !== ruleForm.password) {
                        callback(new Error(t('dialog.passMismatch')))
                    } else {
                        callback()
                    }
            }
            const rules = reactive({
                username:[{required:true,message:t('dialog.username'), trigger: 'blur'}],
                password: [{ validator: validatePass, trigger: 'blur' }],
                checkPass: [{ validator: validatePass2, trigger: 'blur' }],
                email:[
                    {required:true,message:t('dialog.email'), trigger: 'blur'},
                    {
                        type: 'email',
                        message: t('dialog.emailFormat'),
                        trigger: ['blur', 'change'],
                    },
                    ],
                role:[{required:true,message:t('dialog.role'), trigger: 'blur'}],
                })

            const submitForm = (formEl) => {
                if (formEl) return formEl.validate(async(valid) => {
                    if (valid) {
                        let res
                        let flag=true
                        if (props.dialogTitle===t('dialog.addUserTitle')){
                            res=await addUser(ruleForm)
                        }else{
                            if(isInclude(props.dialogTableValue,ruleForm)){
                                ElMessage({
                                        message: t('response.unchanged'),
                                        type: 'fail',
                                    })
                                flag=false
                            }else{
                                res=await editUser(ruleForm)
                            }
                        }
                        if(flag){
                            if (res.opCode){
                                ElMessage({
                                    message: t('response.success'),
                                    type: 'success',
                                })
                                emit('triggerGetUsersList')
                                emit('update:modelValue',false)
                            }else{
                                ElMessage({
                                message: t('response.fail'),
                                type: 'fail',
                                })
                                }
                            return true
                        }                        
                    }else {
                        ElMessage({
                            message: t('response.invalid'),
                            type: 'fail',
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