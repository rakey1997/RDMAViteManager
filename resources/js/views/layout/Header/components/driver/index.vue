<template>
    <div id="guide" @click.prevent.stop="handleGuide">
        <el-icon><Guide /></el-icon>
    </div>
</template>

<script>
    import Driver from 'driver.js';
    import 'driver.js/dist/driver.min.css';
    import { onMounted } from '@vue/runtime-core';
    import { steps } from "./steps";
    import {watchLang} from '../../../../../i18n/watchlang'

    // import i18n from '@/i18n'
    import { useI18n } from 'vue-i18n'

    export default {
        name:'driver',
        setup(){
            let driver
            // const t=i18n.global.t
            const {t}=useI18n()
            onMounted(()=>{
                initDriver()
            })

            const initDriver=()=>{
                driver=new Driver({
                    animate: false,                    // Whether to animate or not
                    opacity: 0.75,                    // Background opacity (0 means only popovers and without overlay)
                    padding: 6,                      // Distance of element from around the edges
                    allowClose: true,                 // Whether the click on overlay should close or not
                    overlayClickNext: false,          // Whether the click on overlay should move next
                    doneBtnText: t('driver.done'),              // Text on the final button
                    closeBtnText: t('driver.close'),            // Text on the close button for this step
                    stageBackground: '#ffffff',       // Background color for the staged behind highlighted element
                    nextBtnText: t('driver.next'),              // Next button text for this step
                    prevBtnText: t('driver.prev'),          // Previous button text for this step
                })
            }

            watchLang(initDriver)

            const handleGuide=()=>{
                    driver.defineSteps(steps(t))
                    driver.start()
                }

            return {
                handleGuide
            }
        }
    }
</script>

<style>

</style>