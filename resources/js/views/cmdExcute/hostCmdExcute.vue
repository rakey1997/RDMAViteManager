<template>
    <el-select v-model="host_name" class="header" placeholder="Select" size="large">
        <el-option
        v-for="item in options"
        :key="item.value"
        :label="item.label"
        :value="item.value"
        />
    </el-select>
    <hr>
    <cmdExcute :host_name="host_name" >
    </cmdExcute>
</template>
  
  <script>
    import { ref } from '@vue/runtime-core';
    import { useI18n } from "vue-i18n";
    import {useStore} from "vuex";
    import cmdExcute from "./components/cmdExcute.vue";

    export default {
        name:'HostCmdExcute',
        components:{cmdExcute},
        setup() {
            const {t}=useI18n()
            const store=useStore()
            let hostNameArray = store.getters.hostName.split(',')
            const options=hostNameArray.map(obj => {return {value:obj,label:obj}})
            const host_name = ref(options[0]['value'])

            return{
                host_name,
                options
            }
        }
    }
  </script>

<style lang="scss" scoped>
    .header{
        padding-bottom: 16px;
        box-sizing: border-box;
        margin: auto;
    }
   
</style>
