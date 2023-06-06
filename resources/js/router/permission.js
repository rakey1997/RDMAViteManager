import router from "./index";
import store from "../store";
import { diffTokenTime } from "../utils/auth";

const whiteList = ["/login"];

router.beforeEach((to, from, next) => {
    if (store.getters.api_token) {
        if (to.path === "/login") {
            next("/");
        } else {
            if (localStorage.getItem("api_token")) {
                if (diffTokenTime()) {
                    store.dispatch("app/logout");
                    return Promise.reject(new Error("token失效了"));
                } else {
                    next();
                }
            }
        }
    } else {
        if (whiteList.includes(to.path)) {
            next();
        } else {
            next("/login");
        }
    }
});
