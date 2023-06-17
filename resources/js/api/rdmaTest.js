import request from "./request";

export const getMenu = (params) => {
    return request({
        url: "/menu",
        params,
    });
};

export const testTQ = (data) => {
    return request({
        url: "/testTQ",
        method: "post",
        data,
    });
};
export const addTQ = (data) => {
    return request({
        url: "/addTQ",
        method: "post",
        data,
    });
};

export const delTQ = (data) => {
    return request({
        url: "/delTQ",
        method: "delete",
        data,
    });
};

export const excuteTest = (data) => {
    return request({
        url: "/excuteTest",
        method: "post",
        data,
    });
};

export const getResult = (data) => {
    return request({
        url: "/result",
        method: "post",
        data,
    });
};
