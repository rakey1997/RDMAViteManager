<template>
    <div @click="handleFullScreen" id="screenful">
        <el-icon>
            <component
                :is="iconName?'ZoomOut':'ZoomIn'"
            >
            </component>
        </el-icon>
    </div>
</template>

<script>
    import screenfull from 'screenfull';
    import { ref } from '@vue/reactivity';
    import { onBeforeUnmount, onMounted } from '@vue/runtime-core';
    export default {
        name:'screenful',
        setup(){
            const iconName=ref(screenfull.isFullscreen)

            const handleFullScreen=()=>{
                if (screenfull.isEnabled) {
                    screenfull.toggle();
                }
            }

            const changeIcon=()=>{
                iconName.value=screenfull.isFullscreen
            }

            onMounted(()=>{
                screenfull.on('change',changeIcon)
            })

            onBeforeUnmount(()=>{
                screenfull.off('change')
            })

            return {
                handleFullScreen,
                iconName
            }
        }
    }
</script>

<style>

</style>