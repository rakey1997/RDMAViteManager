import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import { ElementPlusResolver } from "unplugin-vue-components/resolvers";
import { createStyleImportPlugin, VantResolve } from "vite-plugin-style-import";

export default defineConfig({
    define: {
        "process.env": {},
    },
    plugins: [
        laravel({
            input: [
                // "./resources/css/styles/index.scss",
                // "element-plus/dist/index.css",
                // "element-plus/theme-chalk/base.css",
                "resources/js/app.js",
                // "vue3-json-viewer/dist/index.css",
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    // Vue 插件将重写资产 URLs，当被引用
                    // 在单文件组件中，指向 Laravel Web 服务
                    // 设置它为 `null` 允许 Laravel 插件
                    // 去替代重写资产 URLs 指向到 Vite 服务
                    base: null,

                    //  Vue 插件将解析绝对 URLs
                    // 并把它们看做磁盘上的绝对路径。
                    // 设置它为 `false` 将保留绝对 URLs
                    // 以便它们可以按照预期直接引用公共资产。
                    includeAbsolute: false,
                },
            },
        }),
        AutoImport({
            resolvers: [ElementPlusResolver()],
        }),
        Components({
            resolvers: [ElementPlusResolver()],
        }),
        createStyleImportPlugin({
            resolves: [VantResolve()],
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                additionalData:
                    '@import "./resources/css/styles/variables.scss";',
                javascriptEnabled: true,
            },
        },
    },
    build: {
        rollupOptions: {
            output: {
                assetFileNames: "css/[name].[hash].css",
                chunkFileNames: "js/[name].[hash].js",
                entryFileNames: "js/[name].[hash].js",
            },
        },
    },
});
