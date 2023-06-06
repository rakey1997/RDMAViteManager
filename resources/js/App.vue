<template>
    <router-view />
</template>

<script>
  import { useI18n } from "vue-i18n";
  import {useStore} from "vuex";
  import { watch } from '@vue/runtime-core';

  export default {
    name: 'app',
    setup(){
      const { t } = useI18n();
      const store=useStore()

      watch(
                () => store.getters.lang,
                () => document.title = t("login.title"),
                { deep: true,immediate:true }
            );
    }
  };
</script>