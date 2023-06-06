<template>
    <el-container class="app-wrapper">
        <el-aside :width="asideWidth" class="sidebar-container">
            <Menu />
        </el-aside>
      <el-container 
        class="container"
        :class="{hidderContainer:!$store.getters.siderType}"
        >
        <el-header>
            <DNSHeader />
        </el-header>
        <el-main>
            <router-view/>
        </el-main>
      </el-container>
    </el-container>
</template>

<script>
    import Menu from "./Menu/index.vue";
    import DNSHeader from "./Header/index.vue";
    import { computed } from '@vue/runtime-core';
    import variables from '../../../css/styles/global.module.scss';
    import { useStore } from 'vuex';

    export default {
        name:'layout',
        components:{Menu,DNSHeader},
        setup(){
            const store=useStore()
            const asideWidth=computed(()=>{
                return store.getters.siderType ? variables.sideBarWidth : variables.hideSideBarWidth
            })
            return{
                asideWidth,
            }
        }
    }
</script>

<style lang="scss" scoped>
    .app-container{
        position: relative;
        width:100%;
        height: 100%;
    }
    .container{
        width: calc(100% - $sideBarWidth);
        height: 100%;

        position: fixed;
        top:0;
        right: 0;
        z-index: 0;
        transition: all 0.28s;

        &.hidderContainer{
            width: calc(100% - $hideSideBarWidth);
        }
    }

    :deep(.el-header){
        padding: 0;
    }
</style>