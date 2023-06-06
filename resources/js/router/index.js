import { createRouter, createWebHashHistory } from "vue-router";

const routes = [
    {
        name: "Login",
        path: "/login",
        component: () => import("../views/login/index.vue"),
    },
    {
        name: "Dashboard",
        path: "/",
        component: () => import("../views/layout/index.vue"),
        redirect: "/hostConfig",
        children: [
            {
                path: "hostConfig",
                name: "hostConfig",
                component: () => import("../views/rdmaControl/hostConfig.vue"),
            },
            {
                path: "cardConfig",
                name: "cardConfig",
                component: () => import("../views/rdmaControl/cardConfig.vue"),
            },
            {
                path: "rdmaConfig",
                name: "rdmaConfig",
                component: () => import("../views/rdmaControl/rdmaConfig.vue"),
            },
            {
                path: "hostCmdExcute",
                name: "hostCmdExcute",
                component: () => import("../views/cmdExcute/hostCmdExcute.vue"),
            },
            {
                path: "rdmaTest",
                name: "rdmaTest",
                component: () => import("../views/rdmaTest/rdmaTest.vue"),
            },
            {
                path: "rdmaTestShow",
                name: "rdmaTestShow",
                component: () => import("../views/rdmaTest/rdmaTestShow.vue"),
            },
            {
                path: "userConfig",
                name: "userConfig",
                component: () => import("../views/userConfig/userConfig.vue"),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

export default router;
