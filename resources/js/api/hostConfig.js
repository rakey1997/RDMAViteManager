import request from "./request";

export const getHost = (params) => {
    return request({
        url: "/host",
        params,
    });
};

export const changeHostState = (uid, type) => {
    return request({
        url: `/host/${uid}/state/${type}`,
        method: "put",
    });
};

export const addHost = (data) => {
    return request({
        url: "/hosts",
        method: "post",
        data,
    });
};

export const editHost = (data) => {
    return request({
        url: `/editHost/${data.id}`,
        method: "put",
        data,
    });
};

export const updateHostPassword = (data) => {
    return request({
        url: `/updatePass/${data.id}`,
        method: "put",
        data,
    });
};

export const delHost = (ids) => {
    return request({
        url: `/host/${ids}`,
        method: "delete",
    });
};
