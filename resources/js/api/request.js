import axios from "axios";
import { diffTokenTime } from "../utils/auth";
import store from "../store";

const service = axios.create({
    baseURL: process.env.VUE_APP_BASE_API,
    timeout: 60000,
});

service.interceptors.request.use(
    (config) => {
        if (localStorage.getItem("api_token")) {
            if (diffTokenTime()) {
                store.dispatch("app/logout");
                // return Promise.reject(new Error("api_token失效了"));
                return new Promise(function () {});
            }
        }
        config.headers.Authorization = localStorage.getItem("api_token") || "";
        config.headers.userid = localStorage.getItem("userid") || "";
        return config;
    },
    (error) => {
        return Promise.reject(new Error(error));
    }
);

service.interceptors.response.use(
    (response) => {
        const { data, status } = response;
        if (status === 200 && data["opCode"]) {
            return data;
        } else {
            ElMessage.error(data["result"]);
            return data;
            // return Promise.reject(new Error(data["result"]));
        }
    },
    (error) => {
        error.response && ElMessage.error(error.response.data);
        if (error.response.status === 401) {
            store.dispatch("app/logout");
            return new Promise(function () {});
        } else {
            return Promise.reject(new Error(error.response.data));
        }
    }
);

export default service;
