<template>
    <el-breadcrumb separator-icon="ArrowRight">
        <el-breadcrumb-item v-for="(item,index) in breadcrumbList"
        :key="index"
        >
        <span class="no-redirect" v-if="index===breadcrumbList.length-1">{{$t(`route.${item.name}`)}}</span>
        <span class="redirect" v-else @click="handleRedirect(item.path)">{{$t(`route.${item.name}`)}}</span>
        </el-breadcrumb-item>
    </el-breadcrumb>
</template>

<script>
    import { ref, watch } from '@vue/runtime-core';
    import { useRoute, useRouter } from "vue-router";
    
    export default {
        name:'breadcrumb',
        setup(){
            const route=useRoute()
            const router=useRouter()

            const breadcrumbList=ref([])
            const initBreadcrumbList=()=>{
                breadcrumbList.value=route.matched
            }

            const handleRedirect=(path)=>{
                router.push(path)
            }

            watch(route,()=>{
                initBreadcrumbList()
            },{deep:true,immediate:true})
            return{
                breadcrumbList,
                handleRedirect
            }
        }
    }
</script>

<style lang="scss" scoped>
    .no-redirect{
        color:#978abe;
        cursor: text;
    }
    .redirect{
        color: #666;
        font-weight: 600;
        cursor: pointer;
        &:hover {
            color:menuBg;
        }
    }
</style>