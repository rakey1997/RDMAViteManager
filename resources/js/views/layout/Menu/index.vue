<template>
    <el-menu
        active-text-color="#ffd04b"
        background-color="#545c64"
        class="el-menu-vertical-demo"
        text-color="#fff"
        :default-openeds="['1']"
        :collapse="!$store.getters.siderType"
        router
    >
    <el-sub-menu index="1">
        <template #title>
        <el-icon><Edit /></el-icon>
        <span>{{$t('menus.configManager')}}</span>
        </template>
        <el-menu-item index="hostConfig">
            <el-icon><Menu /></el-icon>
            {{$t('menus.hostConfig')}}
        </el-menu-item>
        <el-menu-item index="cardConfig">
            <el-icon><Menu /></el-icon>
            {{$t('menus.cardConfig')}}
        </el-menu-item>
        <el-menu-item index="rdmaConfig">
            <el-icon><Menu /></el-icon>
            {{$t('menus.rdmaConfig')}}
        </el-menu-item>
    </el-sub-menu>
    <el-menu-item index="hostCmdExcute">
        <el-icon><Edit /></el-icon>
        <span>{{$t('menus.hostCmdExcute')}}</span>
    </el-menu-item>
    <el-sub-menu index="2">
        <template #title>
        <el-icon><Promotion /></el-icon>
        <span>{{$t('menus.rdmaTest')}}</span>
        </template>
        <el-menu-item index="rdmaTest">
        <el-icon><Promotion /></el-icon>
        <span>{{$t('menus.rdmaTestMgr')}}</span>
        </el-menu-item>
        <el-menu-item index="rdmaTestShow">
        <el-icon><PieChart /></el-icon>
        <span>{{$t('menus.rdmaTestShow')}}</span>
        </el-menu-item>
    </el-sub-menu>

    <el-menu-item index="userConfig">
        <el-icon><setting /></el-icon>
        <span>{{$t('menus.userConfig')}}</span>
    </el-menu-item>
    </el-menu>
</template>

<script>
    export default{
        name:'RDMAMenu',
        setup(){

        }
    }    
</script>

<style>

</style>