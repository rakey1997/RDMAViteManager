<template>
    <el-dropdown @command="handleCommand" id="lang">
        <el-icon><Location /></el-icon>
    <template #dropdown>
      <el-dropdown-menu>
        <el-dropdown-item command="zh" :disabled="currentLanguage==='zh'">中文</el-dropdown-item>
        <el-dropdown-item command="en" :disabled="currentLanguage==='en'">English</el-dropdown-item>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
</template>

<script>
    import { computed } from '@vue/runtime-core'
    import { useI18n } from 'vue-i18n'
    import { useStore } from 'vuex'
    
    export default {
        name:'lang',
        setup(){
            const t=useI18n()
            const store=useStore()

            const currentLanguage=computed(()=>{
                return t.locale.value
            })

            const handleCommand=(val)=>{
                t.locale.value=val
                store.commit('app/changeLang',val)
                localStorage.setItem('lang',val)
            }

            return{
                currentLanguage,
                handleCommand
            }
        }
    }
</script>

<style>

</style>