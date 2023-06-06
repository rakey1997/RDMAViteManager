import request from "./request";

export const getMenu = (params) => {
    return request({
        url: "/menu",
        params,
    });
};

export const addTQ = (data) => {
    return request({
        url: "/addTQ",
        method: "post",
        data,
    });
};

export const delTQ = (ids) => {
    return request({
        url: `/TQ/${ids}`,
        method: "delete",
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
