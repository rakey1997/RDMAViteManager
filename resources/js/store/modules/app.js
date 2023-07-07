import { login as loginApi } from "../../api/login";
import router from "../../router";
import { setTokenTime } from "../../utils/auth";
import { setCookie, getCookie, delCookie } from "../../api/cookie";

export default {
    namespaced: true,
    state: () => ({
        userid: localStorage.getItem("userid") || "",
        role: localStorage.getItem("role") || "",
        api_token: localStorage.getItem("api_token") || "",
        siderType: true,
        lang: localStorage.getItem("lang") || "zh",
        hostname: localStorage.getItem("hostname") || "",
        cardname: localStorage.getItem("cardname") || "",
        rdmaname: localStorage.getItem("rdmaname") || "",
        cardRdmaName: localStorage.getItem("cardRdmaName") || "",
        testHostPair: localStorage.getItem("testHostPair") || [],
    }),
    mutations: {
        setUserID(state, userid) {
            state.userid = userid;
            localStorage.setItem("userid", userid);
        },
        setRole(state, role) {
            state.role = role;
            localStorage.setItem("role", role);
        },
        setToken(state, api_token) {
            state.api_token = api_token;
            localStorage.setItem("api_token", api_token);
        },
        changeSiderType(state) {
            state.siderType = !state.siderType;
        },
        changeLang(state, lang) {
            state.lang = lang;
        },
        setHost(state, hostname) {
            state.hostname = hostname;
            localStorage.setItem("hostname", hostname);
        },
        setCardName(state, cardname) {
            state.cardname = cardname;
            localStorage.setItem("cardname", cardname);
        },
        setRdmaName(state, rdmaname) {
            state.rdmaname = rdmaname;
            localStorage.setItem("rdmaname", rdmaname);
        },
        setCardRdmaName(state, cardRdmaName) {
            state.cardRdmaName = cardRdmaName;
            localStorage.setItem("cardRdmaName", cardRdmaName);
        },
        setTestHostPair(state, testHostPair) {
            state.testHostPair = testHostPair;
            localStorage.setItem("testHostPair", testHostPair);
        },
    },
    actions: {
        login({ commit }, loginForm) {
            return new Promise((resolve, reject) => {
                loginApi(loginForm)
                    .then((res) => {
                        if (res.opCode) {
                            commit("setUserID", res.userid);
                            commit("setRole", res.role);
                            commit("setToken", res.api_token);
                            setTokenTime();
                            router.replace("/");
                            ElMessage({
                                message: "登录成功",
                                type: "success",
                            });
                            resolve();
                        }
                    })
                    .catch((err) => {
                        reject(err);
                    });
            });
        },
        logout({ commit }) {
            commit("setUserID", "");
            commit("setRole", "");
            commit("setToken", "");
            commit("setHost", "");
            commit("setCardName", "");
            commit("setRdmaName", "");
            commit("setCardRdmaName", "");
            // commit("setTestHostPair", "");
            delCookie("laravel_session");
            localStorage.clear();
            router.replace("/login");
        },
        hostInfo({ commit }, hostNameStr) {
            commit("setHost", hostNameStr);
        },
        cardInfo({ commit }, cardNameStr) {
            commit("setCardName", cardNameStr);
        },
        rdmaInfo({ commit }, rdmaNameStr) {
            commit("setRdmaName", rdmaNameStr);
        },
        cardRdmaInfo({ commit }, cardRdmaNameStr) {
            commit("setCardRdmaName", cardRdmaNameStr);
        },
        testHostPair({ commit }, testHostPair) {
            commit("setTestHostPair", testHostPair);
        },
    },
};
