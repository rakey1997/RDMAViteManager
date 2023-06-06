<template>
    <el-dropdown>
        <span class="el-dropdown-link">
            <el-avatar :size="40" :src="circleUrl" />
        </span>
        <template #dropdown>
        <el-dropdown-menu>
            <el-dropdown-item @click="logout">{{$t('driver.logout')}}</el-dropdown-item>
        </el-dropdown-menu>
        </template>
    </el-dropdown>
</template>

<script>
    import { ref } from '@vue/reactivity'
    import { useStore } from 'vuex'
    export default {
        name:'avatar',
        setup(){
            const circleUrl =ref('logo.png')
            
            const store=useStore()
            const logout=()=>{
                store.dispatch('app/logout')
            }
            return{
                circleUrl,
                logout
            }
        }
    }
</script>

<style lang="scss" scoped>
    :deep(.el-dropdown-menu__item){
        white-space: nowrap;
    }
</style>