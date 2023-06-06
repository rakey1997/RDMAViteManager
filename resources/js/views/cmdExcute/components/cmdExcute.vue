<template>
    <el-form 
        ref="queryFormRef"
        status-icon
        :model="queryForm" 
        :rules="rules"
        label-position="top"
        label-width="100px"
        @submit.prevent
    >
        <el-radio-group v-model="queryForm.cmd">
            <el-radio label="nCmd">{{$t('cmdExcute.normalCmd')}}</el-radio>
            <el-radio label="sCmd">{{$t('cmdExcute.sudoCmd')}}</el-radio>
        </el-radio-group>

        <el-row :gutter="10" class="header">
            <el-col :span="10">
                <el-form-item prop="query">
                    <el-input 
                        clearable 
                        v-model="queryForm.query" 
                        :placeholder="$t('cmdExcute.placeholder')" 
                        @keyup.enter="sendCmdtoHost(queryFormRef)"
                        >
                    </el-input>
                </el-form-item>
            </el-col>
            <el-button type="primary" icon="Promotion" :disabled="flag" @click="sendCmdtoHost(queryFormRef)">{{$t('cmdExcute.sendBtn')}}</el-button>
        </el-row>

    </el-form>
    <el-descriptions :column="1">
        <el-descriptions-item :label="$t('cmdExcute.cmdStatusDesc')">
            <el-tag type="success" v-if="systemLogform.status === '0'">{{$t('cmdExcute.cmdSuccess')}}</el-tag>
            <el-tag type="warning" v-else>{{$t('cmdExcute.cmdError')}}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item :label="$t('cmdExcute.cmdResult')">
          <json-viewer :value="systemLogform.jsonResult" :expand-depth=100 copyable boxed sort expanded></json-viewer>
        </el-descriptions-item>
    </el-descriptions>
  </template>
  
  <script>
    import { ref,reactive } from '@vue/runtime-core';
    import { useI18n } from "vue-i18n";
    import {useStore} from "vuex";
    import { excuteCmdFromSSH } from '../../../api/cardConfig';

    export default {
        name:'cmdExcute',
        props:{
            host_name:{
                type:String,
                default:'',
            },
        },
        setup(props) {
            const {t}=useI18n()
            const store=useStore()
            const flag=ref(store.getters.role!='admin')
            const queryForm=reactive({
                host_name:'',
                query:'',
                cmd: 'nCmd',
            })

            const queryFormRef = ref(null)

            const systemLogform=reactive({
                  jsonResult: {},
                  status: '0',
              })

            const validateQuery = (_, value, callback) => {
                    const forbidKey=['rm',':{:|:&};:','>/dev','/dev/null','-O-|sh','^foo^bar','dd']
                    let checkIsForbid=false
                    forbidKey.forEach(x=>{if(value.includes(x))checkIsForbid=true})
                    if (value=="") {
                        callback(new Error(t('cmdExcute.blankCmd')))
                    } else if (checkIsForbid){
                        callback(new Error(t('cmdExcute.forbidCmd')))
                    }else{
                        callback()
                    }
                }

            const rules = reactive({
                query: [{validator: validateQuery, trigger: 'blur' }],
            })

            const sendCmdtoHost=(formEl)=>{
                if (formEl) return formEl.validateField(['query'],async(valid) => {
                    if (valid) {
                            queryForm.host_name=props.host_name
                            console.log('host_name:',props.host_name);
                            console.log('queryForm:',queryForm);
                            const res=await excuteCmdFromSSH(queryForm)
                            if (res.opCode){
                                ElMessage({
                                    message: t('response.success'),
                                    type: 'success',
                                })
                            }else{
                                ElMessage({
                                message: t('response.fail'),
                                type: 'error',
                                })
                            }
                            systemLogform.status=res.cmdState
                            systemLogform.jsonResult={"result":res.result}
                            console.log('systemLogform:',systemLogform);
                        }else{
                            ElMessage({
                                    message: t('cmdExcute.invalidCmd'),
                                    type: 'error',
                                })
                        }
                    })
                }

            return{
                flag,
                props,
                systemLogform,
                queryForm,
                queryFormRef,
                rules,
                sendCmdtoHost
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
</style>
