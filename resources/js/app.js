// import ElementPlus from "element-plus";
import * as ElementPlusIconsVue from "@element-plus/icons-vue";
import "../css/styles/index.scss";

import JsonViewer from "vue3-json-viewer";
import "vue3-json-viewer/dist/index.css";
import "element-plus/dist/index.css";
import "element-plus/theme-chalk/base.css";

import i18n from "./i18n/index";
import store from "./store";
import router from "./router";
import "./router/permission";

import App from "./App.vue";
import { createApp } from "vue";

const app = createApp(App)
    .use(store)
    .use(JsonViewer)
    .use(i18n)
    // .use(ElementPlus)
    .use(router);

for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
    app.component(key, component);
}

app.mount("#app");
