<template>
    <el-dialog
        :model-value="dialogVisible"
        width="40%"
        @close="handleClose"
    >
      <el-result
            :icon="props.dialogTableValue.opCode?'success':'error'"
            :title="props.dialogTableValue.msg"
            :sub-title="props.dialogTableValue.result"
      >
        <template #extra>
        <el-button type="primary"  @click="handleClose" :disabled="percent!=100">{{$t('dialog.confirmButton')}}</el-button>
        </template>
        </el-result>
        <el-progress :width="90" :height="90" :text-inside="true" :stroke-width="26" :percentage="percent"></el-progress>
    </el-dialog>


</template>

<script>
    import { ref } from '@vue/reactivity';
    import { useI18n } from "vue-i18n";
    import { watch } from '@vue/runtime-core';

    export default {
        name:'testDialog',
        emits: ['update:modelValue'],
        props:{
            dialogVisible:{
                type:Boolean,
                default:false,
            },
            dialogTableValue:{
                type:Object,
                default:()=>{}
            }
        },
        setup(props,{emit}){
            // console.log('props.dialogTableValue:',props.dialogTableValue);
            const {t}=useI18n()
            const percent=ref(50)

            const handleClose=()=>{
                emit('update:modelValue',false)
            }

            watch(()=>props.dialogTableValue,()=>{
                percent.value=props.dialogTableValue.msg==t('rdmaTest.inTest')?60:100
                // console.log('percent',percent);
            },{deep:true,immediate:true})

            return {
                props,
                percent,
                handleClose
            }
        }
    }
</script>

<style>

</style>