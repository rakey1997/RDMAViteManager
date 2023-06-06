<template>
    <div class="hamburger-container" @click="toogleClick" id="hamburger">
        <el-icon>
            <component
                :is="iconName"
            >
            </component>
        </el-icon>
    </div>
</template>

<script>
    import { computed } from '@vue/runtime-core';
    import { useStore } from 'vuex';

    export default {
        name:'hamburger',
        setup(){
            const store=useStore()
            const iconName=computed(()=>{
                return store.getters.siderType?'Fold':'Expand'
            })
            const toogleClick=()=>{
                store.commit('app/changeSiderType')
            }
            return{
                toogleClick,
                iconName
            }
        }
    }
</script>

<style lang="scss" scoped>
    .hamburger-container{
        margin-right: 16px;
        box-sizing: border-box;
        cursor: pointer;
    }
</style>